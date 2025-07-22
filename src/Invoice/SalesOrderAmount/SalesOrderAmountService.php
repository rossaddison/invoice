<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAmount;

use App\Invoice\Entity\SalesOrderAmount as SoAmount;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SOAR;

final readonly class SalesOrderAmountService
{
    public function __construct(private SOAR $repository)
    {
    }

    public function initializeSalesOrderAmount(SoAmount $model, int $so_id): void
    {
        $model->setSo_id($so_id);
        $model->setItem_subtotal(0.00);
        $model->setItem_tax_total(0.00);
        $model->setTax_total(0.00);
        $model->setTotal(0.00);
        $this->repository->save($model);
    }

    /**
     * Used in quote/quote_to_so_quote_amount.
     */
    public function initializeCopyQuoteAmount(SoAmount $model, QAR $qaR, SOAR $soaR, string $basis_quote_id, ?string $new_so_id): void
    {
        $basis_quote = $qaR->repoQuotequery($basis_quote_id);
        if ($basis_quote) {
            $model->setSo_id((int) $new_so_id);
            $model->setItem_subtotal($basis_quote->getItem_subtotal() ?? 0.00);
            $model->setItem_tax_total($basis_quote->getItem_tax_total() ?? 0.00);
            $model->setTax_total($basis_quote->getTax_total() ?? 0.00);
            $model->setTotal($basis_quote->getTotal() ?? 0.00);
            $soaR->save($model);
        }
    }

    public function saveSalesOrderAmountViaCalculations(SoAmount $model, array $array): void
    {
        /*
         * @var int $array['so_id']
         * @var float $array['item_subtotal']
         * @var float $array['item_taxtotal']
         * @var float $array['tax_total']
         * @var float $array['total']
         */
        $model->setSo_id($array['so_id']);
        $model->setItem_subtotal($array['item_subtotal']);
        $model->setItem_tax_total($array['item_taxtotal']);
        $model->setTax_total($array['tax_total']);
        $model->setTotal($array['total']);
        $this->repository->save($model);
    }

    public function deleteSalesOrderAmount(?SoAmount $model): void
    {
        $this->repository->delete($model);
    }
}
