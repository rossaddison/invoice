<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Entity\QuoteItemAllowanceCharge;
use App\Invoice\Entity\Task;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository
    as ACQIR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository
    as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as taskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class InvItemService
{
    public function __construct(
        private ACIIR $aciiR,    
        private InvItemRepository $repository,
        private IR $iR,
        private TRR $trR,
        private PR $pR,
        private taskR $taskR,
    ) {
    }

    private function persist(
        InvItem $model,
        array $array
    ): InvItem {
        $inv = 'inv_id';
        if (isset($array[$inv])) {
            $invEntity = $this->iR->repoInvUnLoadedquery(
                (string) $array[$inv]);
            if ($invEntity) {
                $model->setInv($invEntity);
            }
        }
        $tax_rate = 'tax_rate_id';
        if (isset($array[$tax_rate])) {
            $model->setTaxRate(
                $this->trR->repoTaxRatequery(
                    (string) $array[$tax_rate]));
        }
        $product = 'product_id';
        if (isset($array[$product])) {
            $productEntity = $this->pR->repoProductquery(
                (string) $array[$product]);
            if ($productEntity) {
                $model->setProduct($productEntity);
            }
        }
        $task = 'task_id';
        if (isset($array[$task])) {
            $taskEntity = $this->taskR->repoTaskquery(
                (string) $array[$task]);
            if ($taskEntity) {
                $model->setTask($taskEntity);
            }
        }
        return $model;
    }

    /**
     * Related logic: see InvController function inv_to_inv_items
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param PR $pr
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @param UNR $unR
     * @return int|null
     */
    public function addInvItem_product(
        InvItem $model,
        array $array,
        string $inv_id,
        PR $pr,
        TRR $trr,
        IIAS $iias,
        IIAR $iiar,
        SR $s,
        UNR $unR
    ): ?int {
        $this->persist($model, array_merge(
            $array,
            ['inv_id' => $inv_id]
        ));
        // This function is used in product/save_product_lookup_item_product
        // when adding a product using the modal
        $tax_rate_id =
            ((isset($array['tax_rate_id'])) ? (int) $array['tax_rate_id'] : '');
        // The form is required to have a tax value even if it is a zero rate
        $model->setTax_rate_id((int) $tax_rate_id);
        $model->setInv_id((int) $inv_id);
        $so_item_id =
            ((isset($array['so_item_id'])) ? (int) $array['so_item_id'] : '');
        $model->setSo_item_id((int) $so_item_id);
        $product_id =
            ((isset($array['product_id'])) ? (int) $array['product_id'] : '');
        $model->setProduct_id((int) $product_id);
        $product = $pr->repoProductquery((string) $product_id);

        if (null !== $product) {
            $name = (((isset($array['product_id']))
                    && ($pr->repoCount((string) $product_id) > 0)) ?
                    $product->getProduct_name() : '');
            $model->setName($name ?? '');

            $productDescription = $product->getProduct_description();
            if (null !== $productDescription) {
                isset($array['description']) ? 
                    $model->setDescription(
                        (string) $array['description']) : 
                    $model->setDescription($productDescription);
            }
        }

        isset($array['note']) ? 
            $model->setNote((string) $array['note']) : '';
        isset($array['quantity']) ? 
            $model->setQuantity((float) $array['quantity']) : '';
        isset($array['price']) ? 
            $model->setPrice((float) $array['price']) : '';
        isset($array['discount_amount']) ? 
            $model->setDiscount_amount(
                (float) $array['discount_amount']) : '';
        isset($array['order']) ? $model->setOrder((int) $array['order']) : '';

        $model->setDate(new \DateTimeImmutable('now'));

        // Product_unit is a string which we get from unit's name field using
        // the unit_id
        $unit = $unR->repoUnitquery((string) $array['product_unit_id']);
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int) $array['product_unit_id']);
        $tax_rate_percentage =
            $this->taxrate_percentage((int) $tax_rate_id, $trr);
        // Users are required to enter a tax rate even if it is zero percent.

        $model->setBelongs_to_vat_invoice(
            (int) ($s->getSetting('enable_vat_registration') ?: '0'));
        if ($product_id > 0) {
            $this->repository->save($model);
            // Peppol Allowances / charges can only be added
            // on an existing product => zero for allowances
            // or charges
            if (isset($array['quantity'], 
                      $array['price'], 
                      $array['discount_amount']) 
                && null !== $tax_rate_percentage) {
                $this->saveInvItemAmount(
                    (int) $model->getId(), 
                    (float) $array['quantity'], 
                    (float) $array['price'], 
                    (float) $array['discount_amount'], 
                    $tax_rate_percentage, 
                    $iias, 
                    $iiar, 
                    $s);
            }
        }
        return $model->getId();
    }

    public function accumulativeChargeTotal(int $iiId, ACIIR $aciiR): float
    {
        $copyAcs = $aciiR->repoInvItemquery((string) $iiId);
        $accumulativeChargeTotal = 0.00;
        /**
         * If identifier is 1 it is a charge
         * @var InvItemAllowanceCharge $copyAc
         */
        foreach ($copyAcs as $copyAc) {
            // If the parent allowancecharge is a charge, add to total to
            // appear in InvItemAmount charge total
            if ($copyAc->getAllowanceCharge()?->getIdentifier() == 1) {
                $accumulativeChargeTotal += (float) $copyAc->getAmount();
            }
        }
        return $accumulativeChargeTotal;
    }

    public function accumulativeAllowanceTotal(int $iiId, ACIIR $aciiR): float
    {
        $copyAcs = $aciiR->repoInvItemquery((string) $iiId);
        $accumulativeAllowanceTotal = 0.00;
        /**
         * If identifier is 0 it is an allowance
         * @var InvItemAllowanceCharge $copyAc
         */
        foreach ($copyAcs as $copyAc) {
            // If the parent allowancecharge is an allowance, add to total to
            // appear in InvItemAmount allowance total
            if ($copyAc->getAllowanceCharge()?->getIdentifier() == 0) {
                $accumulativeAllowanceTotal += (float) $copyAc->getAmount();
            }
        }
        return $accumulativeAllowanceTotal;
    }

    /**
     * Related logic: see InvController function inv_to_inv_inv_items
     * @param string $copyInvId
     * @param int $originalId
     * @param int $newId
     * @param ACIIR $aciiR
     */
    public function addInvItem_allowance_charges(string $copyInvId,
        int $originalId, int $newId, ACIIR $aciiR): void
    {
        $originalACs = $aciiR->repoInvItemquery((string) $originalId);
        /**
         * @var InvItemAllowanceCharge $originalAC
         */
        foreach ($originalACs as $originalAC) {
            $iiac = new InvItemAllowanceCharge();
            $iiac->setAllowance_charge_id(
                (int) $originalAC->getAllowanceCharge()?->getId());
            $iiac->setInv_id((int) $copyInvId);
            $iiac->setInv_item_id($newId);
            $iiac->setAmount((float) $originalAC->getAmount());
            $iiac->setVatOrTax((float) $originalAC->getVatOrTax());
            $aciiR->save($iiac);
        }
    }
    
    /**
     * Related logic: see QuoteController function quote_to_invoice_quote_items
     * @param string $copyInvId
     * @param int $originalId
     * @param int $newId
     * @param ACQIR $acqiR
     * @param ACIIR $aciiR
     */
    public function addInvItem_allowance_charges_from_quote(string $copyInvId,
        int $originalId, int $newId, ACQIR $acqiR, ACIIR $aciiR): void
    {
        // Get all allowance charges associated with quote_item i.e. $originalId
        $originalACs = $acqiR->repoQuoteItemquery((string) $originalId);
        /**
         * @var QuoteItemAllowanceCharge $originalAC
         */
        foreach ($originalACs as $originalAC) {
            $iiac = new InvItemAllowanceCharge();
            $iiac->setAllowance_charge_id(
                (int) $originalAC->getAllowanceCharge()?->getId());
            $iiac->setInv_id((int) $copyInvId);
            $iiac->setInv_item_id($newId);
            $iiac->setAmount((float) $originalAC->getAmount());
            $iiac->setVatOrTax((float) $originalAC->getVatOrTax());
            $aciiR->save($iiac);
        }
    }

    /**
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param PR $pr
     * @param SR $s
     * @param UNR $unR
     * @return int
     */
    public function saveInvItem_product(InvItem $model, array $array,
                                string $inv_id, PR $pr, SR $s, UNR $unR): int
    {
        // This function is used in product/save_product_lookup_item_product
        // when adding a product using the modal
        $tax_rate_id = ((isset($array['tax_rate_id'])) ?
                (int) $array['tax_rate_id'] : '');
        // The form is required to have a tax value even if it is a zero rate
        $model->setTax_rate_id((int) $tax_rate_id);
        $model->setInv_id((int) $inv_id);
        $so_item_id = ((isset($array['so_item_id'])) ?
                (int) $array['so_item_id'] : '');
        $model->setSo_item_id((int) $so_item_id);
        $product_id = ((isset($array['product_id'])) ?
                (int) $array['product_id'] : '');
        $model->setProduct_id((int) $product_id);
        $product = $pr->repoProductquery((string) $product_id);

        if (null !== $product) {
            $name = (((isset($array['product_id']))
                    && ($pr->repoCount((string) $product_id) > 0)) ?
                    $product->getProduct_name() : '');
            $model->setName($name ?? '');

            $productDescription = $product->getProduct_description();
            if (null !== $productDescription) {
                isset($array['description']) ?
                $model->setDescription((string) $array['description']) :
                    $model->setDescription($productDescription);
            }
        }
        isset($array['note']) ? $model->setNote((string) $array['note']) : '';

        isset($array['quantity']) ?
            $model->setQuantity((float) $array['quantity']) : '';

        isset($array['price']) ? $model->setPrice((float) $array['price']) : '';
        isset($array['discount_amount']) ?
            $model->setDiscount_amount((float) $array['discount_amount']) : '';
        isset($array['order']) ? $model->setOrder((int) $array['order']) : '';

        $model->setDate(new \DateTimeImmutable('now'));

        // Product_unit is a string which we get from unit's name field using
        // the unit_id
        $unit = $unR->repoUnitquery((string) $array['product_unit_id']);
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int) $array['product_unit_id']);
        $product_id > 0 ? $this->repository->save($model) : '';
        return (int) $tax_rate_id;
    }

    /**
     * Related logic: see InvController function inv_to_inv_items
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param taskR $taskR
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @return int|null
     */
    public function addInvItem_task(InvItem $model, array $array, string $inv_id,
                    taskR $taskR, TRR $trr, IIAS $iias, IIAR $iiar, SR $s): ?int
    {
        // This function is used in task/selection_inv when adding a new task
        // from the modal. Related logic https://github.com/cycle/orm/issues/348
        $tax_rate_id = ((isset($array['tax_rate_id'])) ?
            (int) $array['tax_rate_id'] : '');
        $model->setTax_rate_id((int) $tax_rate_id);
        $task_id = ((isset($array['task_id'])) ? (int) $array['task_id'] : '');
        // Product id and task id are mutually exclusive
        $model->setTask_id((int) $task_id);

        $model->setInv_id((int) $inv_id);

        /** @var Task $task */
        $task = $taskR->repoTaskquery((string) $array['task_id']);
        $model->setName($task->getName() ?? '');

        // If the user has changed the description on the form => override
        // default task description
        $description = '';
        if (isset($array['description'])) {
            $description = (string) $array['description'];
        } else {
            $description = $task->getDescription();
        }
        $model->setDescription($description ?: '');
        $note = ((isset($array['note'])) ? (string) $array['note'] : '');
        $model->setNote($note ?: '');

        $model->setQuantity((float) $array['quantity'] ?: 1.00);
        $model->setProduct_unit('');
        $model->setPrice((float) $array['price'] ?: 0.00);
        $model->setDiscount_amount((float) $array['discount_amount'] ?: 0.00);
        $model->setOrder((int) $array['order'] ?: 0);

        $datetimeimmutable = new \DateTimeImmutable('now');
        $model->setDate($datetimeimmutable);
        $tax_rate_percentage =
                            $this->taxrate_percentage((int) $tax_rate_id, $trr);
        if ($task_id > 0) {
            $this->repository->save($model);
            if (isset($array['quantity'], $array['price'],
                    $array['discount_amount'])
                        && null !== $tax_rate_percentage) {
                $this->saveInvItemAmount((int) $model->getId(),
                        (float) $array['quantity'],
                        (float) $array['price'],
                        (float) $array['discount_amount'],
                        $tax_rate_percentage,
                        $iias,
                        $iiar,
                        $s);
            }
        }
        return $model->getId();
    }

    /**
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param taskR $taskR
     * @param SR $s
     * @return int
     */
    public function saveInvItem_task(InvItem $model, array $array,
                                    string $inv_id, taskR $taskR, SR $s): int
    {
        // This function is used in invitem/edit_task when editing an item on
        // the inv view. Related logic: https://github.com/cycle/orm/issues/348
        isset($array['tax_rate_id']) ?
            $model->setTaxRate($model->getTaxRate()?->getTaxRateId() ==
                (int) $array['tax_rate_id'] ? $model->getTaxRate() : null) : '';
        $tax_rate_id = ((isset($array['tax_rate_id'])) ?
            (int) $array['tax_rate_id'] : '');
        $model->setTax_rate_id((int) $tax_rate_id);

        isset($array['task_id']) ?
            $model->setTask($model->getTask()?->getId() ==
                (int) $array['task_id'] ? $model->getTask() : null) : '';
        $task_id = ((isset($array['task_id']))
                ? (int) $array['task_id'] : '');
        // Product id and task id are mutually exclusive
        $model->setTask_id((int) $task_id);

        $model->setInv_id((int) $inv_id);

        /** @var Task $task */
        $task = $taskR->repoTaskquery((string) $array['task_id']);
        if (isset($array['name'])) {
            $model->setName($task->getName() ?? '');
        }

        // If the user has changed the description on the form => override
        //  default task description
        $description = '';
        if (isset($array['description'])) {
            $description = (string) $array['description'];
        } else {
            $description = $task->getDescription();
        }
        $model->setDescription($description ?: '');
        $note = ((isset($array['note'])) ? (string) $array['note'] : '');
        $model->setNote($note ?: '');
        $model->setQuantity((float) $array['quantity'] ?: 1);
        $model->setProduct_unit('');
        $model->setPrice((float) $array['price'] ?: 0.00);
        $model->setDiscount_amount((float) $array['discount_amount'] ?: 0.00);
        $model->setOrder((int) $array['order'] ?: 0);

        $datetimeimmutable = new \DateTimeImmutable('now');
        $model->setDate($datetimeimmutable);
        if ($task_id > 0) {
            $this->repository->save($model);
        }
        return (int) $tax_rate_id;
    }
    
    /**
     * Used in salesorder/so_to_invoice_so_items subfunction in
     * salesorder/so_to_invoice
     * Functional: 05/01/2026:
     *          Emulates the SalesOrderItemService function addSoItemProductTask
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param PR $pr
     * @param taskR $taskR
     * @param IIAR $iiar
     * @param IIAS $iias
     * @param UNR $unR
     * @param TRR $trr
     * @param Translator $translator
     * @param SR $sR
     */
    public function addInvItemProductTask(InvItem $model, array $array,
            string $inv_id, PR $pr, taskR $taskR, IIAR $iiar, IIAS $iias,
            UNR $uR, TRR $trr, Translator $translator, SR $sR): InvItem
    {
        $tax_rate_id = ((isset($array['tax_rate_id'])) ?
            (int) $array['tax_rate_id'] : '');
        $model->setTax_rate_id((int) $tax_rate_id); 
        $product_id = (int) ($array['product_id'] ?? null);
        $task_id = (int) ($array['task_id'] ?? null);
        $model->setInv_id((int) $inv_id);
        $so_item_id = ((isset($array['so_item_id'])) ?
            (int) $array['so_item_id'] : '');
        $model->setSo_item_id((int)$so_item_id);
        $product = $pr->repoProductquery((string) $array['product_id']);
        $name = '';
        if ($product) {
            $model->setProduct_id($product_id);
            if (isset($array['product_id'])
                    && $pr->repoCount((string) $product_id) > 0) {
                $name = $product->getProduct_name();
            }
            null !== $name ? $model->setName($name) : $model->setName('');
            // If the user has changed the description on the form =>
            //  override default product description
            $description = ((isset($array['description']))
                                   ? (string) $array['description']
                                   : $product->getProduct_description());
            null !== $description ? 
                $model->setDescription($description) : 
                $model->setDescription($translator->translate('not.available')) ;
        }
        $task = $taskR->repoTaskquery((string) $array['task_id']);
        if ($task) {
            $model->setTask_id($task_id);
            if (isset($array['task_id'])
                    && $taskR->repoCount((string) $task_id) > 0) {
                $name = $task->getName();
            }
            null !== $name ? $model->setName($name) : $model->setName('');
            // If the user has changed the description on the form => override
            // default product description
            $description = (isset($array['description'])
                                      ? (string) $array['description']
                                      : $task->getDescription());

            strlen($description) > 0 ? $model->setDescription($description) :
                $model->setDescription($translator->translate('not.available'));
        }
        isset($array['quantity']) ?
            $model->setQuantity((float) $array['quantity']) :
                $model->setQuantity(0);
        isset($array['price']) ?
            $model->setPrice((float) $array['price']) :
                $model->setPrice(0.00);
        isset($array['discount_amount']) ?
            $model->setDiscount_amount((float) $array['discount_amount']) :
                $model->setDiscount_amount(0.00);
        isset($array['order']) ?
            $model->setOrder((int) $array['order']) :
                $model->setOrder(0) ;
        // Product_unit is a string which we get from unit's name field using
        //  the unit_id
        $unit = $uR->repoUnitquery((string) $array['product_unit_id']);
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int) $array['product_unit_id']);
        $this->repository->save($model);
        return $model;
    }

    /**
     * Used solely for building up an invoice line item which is an identical
     * copy of the copied invoice
     * The subtotal which is normally just quantity x price has to be adjusted
     * for one or more inv item allowance or charges if using peppol
     *
     * Any adjustments to this function should be reflected also in the
     * similar InvItemController function saveInvItemAmount which is used when
     * the user adjusts e.g. a product item
     *
     * Related logic: see Used in InvController function inv_to_inv_items
     * @param int $inv_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $charge
     * @param float $allowance
     * @param float $tax_rate_percentage
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @return InvItemAmount|null
     */
    public function saveInvItemAmount(
        int $inv_item_id,
        float $quantity,
        float $price,
        float $discount,
        float $tax_rate_percentage,
        IIAS $iias,
        IIAR $iiar,
        SR $s,
    ): InvItemAmount|null {
        $iias_array = [];
        $iias_array['inv_item_id'] = $inv_item_id;
        $sub_total = $quantity * $price;
        // Total cash settlement discount negotiated on this item
        $discount_total = ($quantity * $discount);
        // Fetch all allowance/charges for this item
        $all_charges = 0.00;
        $all_charges_vat_or_tax = 0.00;
        $all_allowances = 0.00;
        $all_allowances_vat_or_tax = 0.00;
        $aciis = $this->aciiR->repoInvItemquery((string)$inv_item_id);
        /** @var \App\Invoice\Entity\InvItemAllowanceCharge $acii */
        foreach ($aciis as $acii) {
            if ($acii->getAllowanceCharge()?->getIdentifier() == '1') {
                $all_charges += (float) $acii->getAmount();
                $all_charges_vat_or_tax += (float) $acii->getVatOrTax();
            } else {
                $all_allowances += (float) $acii->getAmount();
                $all_allowances_vat_or_tax += (float) $acii->getVatOrTax();
            }
        }
        $ipInvAc = $sub_total + $all_charges - $all_allowances;
        if ($tax_rate_percentage >= 0.00) {
            $tax_total =
                // Cash Settlement discounts must be removed before tax worked    
                ($ipInvAc - $discount_total) * ($tax_rate_percentage / 100.00);
        } else {
            $tax_total = 0.00;
        }
        $iias_array['charge'] = $all_charges;
        $iias_array['allowance'] = $all_allowances;
        $iias_array['discount'] = $discount_total;
        $iias_array['subtotal'] = $ipInvAc;
        $iias_array['taxtotal'] = $tax_total;
        $iias_array['total'] = $ipInvAc - $discount_total + $tax_total;
        // retrieve the existing InvItemAmount record
        $inv_item_amount = $iiar->repoInvItemAmountquery((string) $inv_item_id);
        if ($iiar->repoCount((string) $inv_item_id) === 0) {
            $iias->saveInvItemAmountNoForm(new InvItemAmount(), $iias_array);
        } else {
            if ($inv_item_amount) {
                $iias->saveInvItemAmountNoForm($inv_item_amount, $iias_array);
            }
        }
        return $inv_item_amount;
    }

    /**
     * @param InvItem $model
     */
    public function deleteInvItem(InvItem $model): void
    {
        $this->repository->delete($model);
    }

    /**
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(int $id, TRR $trr): ?float
    {
        $taxrate = $trr->repoTaxRatequery((string) $id);
        if ($taxrate) {
            return $taxrate->getTaxRatePercent();
        }
        return null;
    }

    /**
     * @param int $basis_inv_id
     * @param string $new_inv_id
     * @param InvItemRepository $iiR
     * @param IIAR $iiaR
     * @param SR $sR
     */
    public function initializeCreditInvItems(int $basis_inv_id,
           string $new_inv_id, InvItemRepository $iiR, IIAR $iiaR, SR $sR): void
    {
        // Get the basis invoice's items and balance with a negative quantity
        $items = $iiR->repoInvquery((string) $basis_inv_id);
        /** @var InvItem $item */
        foreach ($items as $item) {
            $new_item = new InvItem();
            $new_item->setInv_id((int) $new_inv_id);
            $new_item->setTax_rate_id((int) $item->getTax_rate_id());
            null !== $item->getProduct_id() ?
                    $new_item->setProduct_id((int) $item->getProduct_id())
                        : $new_item->setTask_id((int) $item->getTask_id());
            $new_item->setName($item->getName() ?? '');
            $new_item->setDescription($item->getDescription() ?? '');
            $new_item->setNote($item->getNote() ?? '');
            $new_item->setQuantity(($item->getQuantity() ?? 1.00) * -1.00);
            $new_item->setPrice($item->getPrice() ?? 0.00);
            $new_item->setDiscount_amount($item->getDiscount_amount() ?? 0.00);
            $new_item->setOrder(0);
            // Even if an invoice is balanced with a credit invoice it will
            // remain recurring ... unless stopped.
            // Is_recurring will be either stored as 0 or 1 in mysql. Cannot be
            // null.
            $new_item->setIs_recurring($item->getIs_recurring() ?? false);
            $new_item->setProduct_unit($item->getProduct_unit() ?? '');
            $new_item->setProduct_unit_id((int) $item->getProduct_unit_id());
            $new_item->setDate($item->getDate_added());
            $iiR->save($new_item);

            // Create an item amount for this item; reversing the items amounts
            // to negative
            $basis_item_amount =
                        $iiaR->repoInvItemAmountquery((string) $item->getId());
            if ($basis_item_amount) {
                $new_item_amount = new InvItemAmount();
                $new_item_amount->setInv_item_id((int) $new_item->getId());
                $new_item_amount->setSubtotal(
                            ($basis_item_amount->getSubtotal() ?? 0.00) * -1.00);
                $new_item_amount->setTax_total(
                            ($basis_item_amount->getTax_total() ?? 0.00) * -1.00);
                $new_item_amount->setDiscount(
                            ($basis_item_amount->getDiscount() ?? 0.00) * -1.00);
                $new_item_amount->setTotal(
                            ($basis_item_amount->getTotal() ?? 0.00) * -1.00);
                $iiaR->save($new_item_amount);
            }
        }
    }
}
