<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class QuoteItemService
{
    public function __construct(private QuoteItemRepository $repository)
    {
    }

    /**
     * @param QuoteItem $model
     * @param array $array
     * @param string $quote_id
     * @param PR $pr
     * @param QIAR $qiar
     * @param QIAS $qias
     * @param UR $uR
     * @param TRR $trr
     * @param Translator $translator
     */
    public function addQuoteItem(QuoteItem $model, array $array, string $quote_id, PR $pr, QIAR $qiar, QIAS $qias, UR $uR, TRR $trr, Translator $translator): void
    {
        // This function is used in product/save_product_lookup_item_quote when adding a quote using the modal
        $tax_rate_id = ((isset($array['tax_rate_id'])) ? (int)$array['tax_rate_id'] : '');
        $model->setTax_rate_id((int)$tax_rate_id);
        $product_id = (int)($array['product_id'] ?? '');
        $model->setProduct_id($product_id);
        $model->setQuote_id((int)$quote_id);
        $product = $pr->repoProductquery((string)$array['product_id']);
        $name = '';
        if ($product) {
            if (isset($array['product_id']) && $pr->repoCount((string)$product_id) > 0) {
                $name = $product->getProduct_name();
            }
            null !== $name ? $model->setName($name) : $model->setName('');
            // If the user has changed the description on the form => override default product description
            $description = (isset($array['description']) ?
                                      (string)$array['description'] :
                                      $product->getProduct_description());

            null !== $description ? $model->setDescription($description) : $model->setDescription($translator->translate('not.available')) ;
        }
        isset($array['quantity']) ? $model->setQuantity((float)$array['quantity']) : '';
        isset($array['price']) ? $model->setPrice((float)$array['price']) : '';
        isset($array['discount_amount']) ? $model->setDiscount_amount((float)$array['discount_amount']) : '';
        isset($array['order']) ? $model->setOrder((int)$array['order']) : '';
        // Product_unit is a string which we get from unit's name field using the unit_id
        $unit = $uR->repoUnitquery((string)$array['product_unit_id']);
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int)$array['product_unit_id']);
        // Users are required to enter a tax rate even if it is zero percent.
        $tax_rate_percentage = $this->taxrate_percentage((int)$tax_rate_id, $trr);
        if ($product_id) {
            $this->repository->save($model);
            if (isset($array['quantity'], $array['price'], $array['discount_amount'])     && null !== $tax_rate_percentage) {
                $this->saveQuoteItemAmount((int)$model->getId(), (float)$array['quantity'], (float)$array['price'], (float)$array['discount_amount'], $tax_rate_percentage, $qiar, $qias);
            }
        }
    }

    /**
     * @param QuoteItem $model
     * @param array $array
     * @param string $quote_id
     * @param PR $pr
     * @param UR $uR
     * @param Translator $translator
     * @return int
     */
    public function saveQuoteItem(QuoteItem $model, array $array, string $quote_id, PR $pr, UR $uR, Translator $translator): int
    {
        // This function is used in quoteitem/edit when editing an item on the quote view
        // see https://github.com/cycle/orm/issues/348
        isset($array['tax_rate_id']) ? $model->setTaxRate($model->getTaxRate()?->getTaxRateId() == (int)$array['tax_rate_id'] ? $model->getTaxRate() : null) : '';
        $tax_rate_id = ((isset($array['tax_rate_id'])) ? (int)$array['tax_rate_id'] : '');
        $model->setTax_rate_id((int)$tax_rate_id);
        isset($array['product_id']) ? $model->setProduct($model->getProduct()?->getProduct_id() == (int)$array['product_id'] ? $model->getProduct() : null) : '';
        $product_id = (isset($array['product_id']) ? (int)$array['product_id'] : '');
        $model->setProduct_id((int)$product_id);
        $model->setQuote($model->getQuote()?->getId() == $quote_id ? $model->getQuote() : null);
        $model->setQuote_id((int)$quote_id);
        $product = $pr->repoProductquery((string)$array['product_id']);
        if (null !== $product) {
            if (isset($array['product_id'])) {
                $name = ($pr->repoCount((string)$product_id) > 0 ? $product->getProduct_name() : '');
                $model->setName($name ?? '');
                // If the user has changed the description on the form => override default product description
                $description = ((isset($array['description'])) ?
                                          (string)$array['description'] :
                                          ($product->getProduct_description() ?? $translator->translate('not.available')));
                $model->setDescription($description);
            }
        }
        isset($array['quantity']) ? $model->setQuantity((float)$array['quantity']) : '';
        isset($array['price']) ? $model->setPrice((float)$array['price']) : '';
        isset($array['discount_amount']) ? $model->setDiscount_amount((float)$array['discount_amount']) : $model->setDiscount_amount(0.00);
        isset($array['order']) ? $model->setOrder((int)$array['order']) : '';
        // Product_unit is a string which we get from unit's name field using the unit_id
        $unit = $uR->repoUnitquery((string)$array['product_unit_id']);
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int)$array['product_unit_id']);
        if (isset($array['product_id'])) {
            $this->repository->save($model);
        }
        // pass the tax_rate_id so that we can save the quote item amount
        return (int)$tax_rate_id;
    }

    /**
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string)$id);
        if ($taxrate) {
            return $taxrate->getTaxRatePercent();
        }
        return null;
    }

    /**
     * @param int $quote_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float|null $tax_rate_percentage
     * @param QIAR $qiar
     * @param QIAS $qias
     */
    public function saveQuoteItemAmount(int $quote_item_id, float $quantity, float $price, float $discount, float|null $tax_rate_percentage, QIAR $qiar, QIAS $qias): void
    {
        $qias_array = [];
        $qias_array['quote_item_id'] = $quote_item_id;
        $sub_total = $quantity * $price;
        if (null !== $tax_rate_percentage) {
            $tax_total = ($sub_total * ($tax_rate_percentage / 100.00));
        } else {
            $tax_total = 0.00;
        }
        $discount_total = $quantity * $discount;
        $qias_array['discount'] = $discount_total;
        $qias_array['subtotal'] = $sub_total;
        $qias_array['taxtotal'] = $tax_total;
        $qias_array['total'] = $sub_total - $discount_total + $tax_total;
        if ($qiar->repoCount((string)$quote_item_id) === 0) {
            $qias->saveQuoteItemAmountNoForm(new QuoteItemAmount(), $qias_array);
        } else {
            $quote_item_amount = $qiar->repoQuoteItemAmountquery($quote_item_id);
            if ($quote_item_amount) {
                $qias->saveQuoteItemAmountNoForm($quote_item_amount, $qias_array);
            }
        }
    }

    /**
     * @param array|QuoteItem|null $model
     */
    public function deleteQuoteItem(array|QuoteItem|null $model): void
    {
        $this->repository->delete($model);
    }
}
