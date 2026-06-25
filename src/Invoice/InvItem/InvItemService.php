<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Task\Task;
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

    public function addInvItemProduct(
        InvItem $model,
        array $array,
        string $inv_id,
        IiAddProductDeps $deps,
    ): ?int {
        $pr = $deps->pR;
        $trr = $deps->trR;
        $iias = $deps->iias;
        $iiar = $deps->iiaR;
        $s = $deps->sR;
        $unR = $deps->uR;
        $invEntity = $this->iR->repoInvUnLoadedquery((int) $inv_id);
        if ($invEntity) {
            $model->setInv($invEntity);
        }
        if (isset($array['tax_rate_id'])) {
            $model->setTaxRate($this->trR->repoTaxRatequery((int) $array['tax_rate_id']));
        }
        if (isset($array['product_id'])) {
            $productEntity = $this->pR->repoProductquery((int) $array['product_id']);
            if ($productEntity) {
                $model->setProduct($productEntity);
            }
        }
        if (isset($array['task_id'])) {
            $taskEntity = $this->taskR->repoTaskquery((int) $array['task_id']);
            if ($taskEntity) {
                $model->setTask($taskEntity);
            }
        }
        $tax_rate_id = (int) ($array['tax_rate_id'] ?? 0);
        $model->setTaxRateId($tax_rate_id);
        $model->setInvId((int) $inv_id);
        $model->setSoItemId((int) ($array['so_item_id'] ?? 0));
        $this->applyPeppolIds($model, $array);
        $product_id = (int) ($array['product_id'] ?? 0);
        $model->setProductId($product_id);
        $product = $pr->repoProductquery($product_id);
        if (null !== $product) {
            $this->applyInvItemProductNameDesc($model, $array, $product_id, $product, $pr);
        }
        $this->applyOptionalInvItemFields($model, $array);
        $model->setDate(new \DateTimeImmutable('now'));
        $this->applyInvItemUnit($model, $array, $unR);
        $tax_rate_percentage = $this->taxratePercentage($tax_rate_id, $trr);
        $model->setBelongsToVatInvoice((int) ($s->getSetting('enable_vat_registration') ?: '0'));
        if ($product_id > 0) {
            $this->repository->save($model);
            if (isset($array['quantity'], $array['price'], $array['discount_amount'])
                && null !== $tax_rate_percentage) {
                $this->saveInvItemAmount(
                    $model->reqId(),
                    (float) $array['quantity'],
                    (float) $array['price'],
                    (float) $array['discount_amount'],
                    $tax_rate_percentage,
                    $iias,
                    $iiar
                );
            }
        }
        return $model->reqId();
    }

    public function accumulativeChargeTotal(int $iiId, ACIIR $aciiR): float
    {
        $copyAcs = $aciiR->repoInvItemquery($iiId);
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
        $copyAcs = $aciiR->repoInvItemquery($iiId);
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
     * Related logic: see InvController function invToInvInvItems
     * @param string $copyInvId
     * @param int $originalId
     * @param int $newId
     * @param ACIIR $aciiR
     */
    public function addInvItemAllowanceCharges(string $copyInvId,
        int $originalId, int $newId, ACIIR $aciiR): void
    {
        $originalACs = $aciiR->repoInvItemquery($originalId);
        /**
         * @var InvItemAllowanceCharge $originalAC
         */
        foreach ($originalACs as $originalAC) {
            $iiac = new InvItemAllowanceCharge();
            $iiac->setAllowanceChargeId(
                (int) $originalAC->getAllowanceCharge()?->reqId());
            $iiac->setInvId((int) $copyInvId);
            $iiac->setInvItemId($newId);
            $iiac->setAmount((float) $originalAC->getAmount());
            $iiac->setVatOrTax((float) $originalAC->getVatOrTax());
            $aciiR->save($iiac);
        }
    }

    /**
     * Related logic: see QuoteController function quoteToInvoiceQuoteItems
     * @param string $copyInvId
     * @param int $originalId
     * @param int $newId
     * @param ACQIR $acqiR
     * @param ACIIR $aciiR
     */
    public function addInvItemAllowanceChargesFromQuote(string $copyInvId,
        int $originalId, int $newId, ACQIR $acqiR, ACIIR $aciiR): void
    {
        // Get all allowance charges associated with quote_item i.e. $originalId
        $originalACs = $acqiR->repoQuoteItemquery($originalId);
        /**
         * @var QuoteItemAllowanceCharge $originalAC
         */
        foreach ($originalACs as $originalAC) {
            $iiac = new InvItemAllowanceCharge();
            $iiac->setAllowanceChargeId(
                (int) $originalAC->getAllowanceCharge()?->reqId());
            $iiac->setInvId((int) $copyInvId);
            $iiac->setInvItemId($newId);
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
     * @param UNR $unR
     * @return int
     */
    public function saveInvItemProduct(InvItem $model, array $array,
                                string $inv_id, PR $pr, UNR $unR): int
    {
        $tax_rate_id = (int) ($array['tax_rate_id'] ?? 0);
        $model->setTaxRateId($tax_rate_id);
        $model->setInvId((int) $inv_id);
        $model->setSoItemId((int) ($array['so_item_id'] ?? 0));
        $this->applyPeppolIds($model, $array);
        $product_id = (int) ($array['product_id'] ?? 0);
        $model->setProductId($product_id);
        $product = $pr->repoProductquery($product_id);
        if (null !== $product) {
            $this->applyInvItemProductNameDesc($model, $array, $product_id, $product, $pr);
        }
        $this->applyOptionalInvItemFields($model, $array);
        $model->setDate(new \DateTimeImmutable('now'));
        $this->applyInvItemUnit($model, $array, $unR);
        if ($product_id > 0) {
            $this->repository->save($model);
        }
        return $tax_rate_id;
    }

    /**
     * Related logic: see InvController function invToInvItems
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param taskR $taskR
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return int|null
     */
    public function addInvItemTask(InvItem $model, array $array, string $inv_id,
                    taskR $taskR, TRR $trr, IIAS $iias, IIAR $iiar): ?int
    {
        // This function is used in task/selection_inv when adding a new task
        // from the modal. Related logic https://github.com/cycle/orm/issues/348
        $tax_rate_id = ((isset($array['tax_rate_id'])) ?
            (int) $array['tax_rate_id'] : '');
        $model->setTaxRateId((int) $tax_rate_id);
        $task_id = ((isset($array['task_id'])) ? (int) $array['task_id'] : '');
        // Product id and task id are mutually exclusive
        $model->setTaskId((int) $task_id);

        $model->setInvId((int) $inv_id);

        /** @var Task $task */
        $task = $taskR->repoTaskquery((int) $array['task_id']);
        $model->setName($task->getName() ?? '');

        // If the user has changed the description on the form => override
        // default task description
        if (isset($array['description'])) {
            $description = (string) $array['description'];
        } else {
            $description = $task->getDescription();
        }
        $model->setDescription($description ?: '');
        $note = ((isset($array['note'])) ? (string) $array['note'] : '');
        $model->setNote($note ?: '');

        $model->setQuantity((float) $array['quantity'] ?: 1.00);
        $model->setProductUnit('');
        $model->setPrice((float) $array['price'] ?: 0.00);
        $model->setDiscountAmount((float) $array['discount_amount'] ?: 0.00);
        $model->setOrder((int) $array['order'] ?: 0);

        $datetimeimmutable = new \DateTimeImmutable('now');
        $model->setDate($datetimeimmutable);
        $tax_rate_percentage =
                            $this->taxratePercentage((int) $tax_rate_id, $trr);
        if ($task_id > 0) {
            $this->repository->save($model);
            if (isset($array['quantity'], $array['price'],
                    $array['discount_amount'])
                        && null !== $tax_rate_percentage) {
                $this->saveInvItemAmount($model->reqId(),
                        (float) $array['quantity'],
                        (float) $array['price'],
                        (float) $array['discount_amount'],
                        $tax_rate_percentage,
                        $iias,
                        $iiar);
            }
        }
        return $model->reqId();
    }

    /**
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param taskR $taskR
     * @return int
     */
    public function saveInvItemTask(InvItem $model, array $array,
                                    string $inv_id, taskR $taskR): int
    {
        if (isset($array['tax_rate_id'])) {
            $currentTaxRate = $model->getTaxRate();
            $model->setTaxRate(
                $currentTaxRate?->reqId() == (int) $array['tax_rate_id'] ? $currentTaxRate : null
            );
        }
        $tax_rate_id = (int) ($array['tax_rate_id'] ?? 0);
        $model->setTaxRateId($tax_rate_id);
        if (isset($array['task_id'])) {
            $currentTask = $model->getTask();
            $model->setTask(
                $currentTask?->reqId() == (int) $array['task_id'] ? $currentTask : null
            );
        }
        $model->setTaskId((int) ($array['task_id'] ?? 0));
        $model->setInvId((int) $inv_id);
        /** @var Task $task */
        $task = $taskR->repoTaskquery((int) ($array['task_id'] ?? 0));
        if (isset($array['name'])) {
            $model->setName($task->getName() ?? '');
        }
        $description = isset($array['description'])
            ? (string) $array['description']
            : $task->getDescription();
        $model->setDescription($description ?: '');
        $model->setNote(isset($array['note']) ? (string) $array['note'] : '');
        $model->setQuantity((float) $array['quantity'] ?: 1.00);
        $model->setPrice((float) $array['price'] ?: 0.00);
        $model->setDiscountAmount((float) $array['discount_amount'] ?: 0.00);
        $model->setOrder((int) $array['order'] ?: 0);
        $model->setProductUnit('');
        $model->setDate(new \DateTimeImmutable('now'));
        if ((int) ($array['task_id'] ?? 0) > 0) {
            $this->repository->save($model);
        }
        return $tax_rate_id;
    }

    private function applyPeppolIds(InvItem $model, array $array): void
    {
        if (isset($array['peppol_po_itemid'])) {
            $model->setPeppolPoItemid((string) $array['peppol_po_itemid']);
        }
        if (isset($array['peppol_po_lineid'])) {
            $model->setPeppolPoLineid((string) $array['peppol_po_lineid']);
        }
    }

    private function applyInvItemProductNameDesc(
        InvItem $model, array $array, int $product_id,
        Product $product, PR $pr
    ): void {
        $name = (isset($array['product_id']) && $pr->repoCount($product_id) > 0)
            ? $product->getProductName()
            : '';
        $model->setName($name ?? '');
        $productDescription = $product->getProductDescription();
        if (null !== $productDescription) {
            $model->setDescription(isset($array['description'])
                ? (string) $array['description']
                : $productDescription);
        }
    }

    private function applyOptionalInvItemFields(InvItem $model, array $array): void
    {
        if (isset($array['note'])) { $model->setNote((string) $array['note']); }
        if (isset($array['quantity'])) { $model->setQuantity((float) $array['quantity']); }
        if (isset($array['price'])) { $model->setPrice((float) $array['price']); }
        if (isset($array['discount_amount'])) { $model->setDiscountAmount((float) $array['discount_amount']); }
        if (isset($array['order'])) { $model->setOrder((int) $array['order']); }
    }

    private function applyInvItemUnit(InvItem $model, array $array, UNR $unR): void
    {
        $unit = $unR->repoUnitquery((int) $array['product_unit_id']);
        if ($unit) {
            $model->setProductUnit($unit->getUnitName());
        }
        $model->setProductUnitId((int) $array['product_unit_id']);
    }

    private function applyProductItemBlock(
        InvItem $model,
        array $array,
        int $product_id,
        Product $product,
        PR $pr,
        Translator $translator,
    ): void {
        $model->setProductId($product_id);
        $name = null;
        if (isset($array['product_id']) && $pr->repoCount($product_id) > 0) {
            $name = $product->getProductName();
        }
        null !== $name ? $model->setName($name) : $model->setName('');
        $description = isset($array['description'])
            ? (string) $array['description']
            : $product->getProductDescription();
        $model->setDescription($description ?? $translator->translate('not.available'));
    }

    private function applyTaskItemBlock(
        InvItem $model,
        array $array,
        int $task_id,
        Task $task,
        taskR $taskR,
        Translator $translator,
    ): void {
        $model->setTaskId($task_id);
        $name = null;
        if (isset($array['task_id']) && $taskR->repoCount($task_id) > 0) {
            $name = $task->getName();
        }
        null !== $name ? $model->setName($name) : $model->setName('');
        $description = isset($array['description'])
            ? (string) $array['description']
            : $task->getDescription();
        if (strlen($description) > 0) {
            $model->setDescription($description);
        } else {
            $model->setDescription($translator->translate('not.available'));
        }
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
     * @param UNR $uR
     * @param Translator $translator
     */
    public function addInvItemProductTask(InvItem $model, array $array,
            string $inv_id, PR $pr, taskR $taskR,
            UNR $uR, Translator $translator): InvItem
    {
        $tax_rate_id = isset($array['tax_rate_id']) ? (int) $array['tax_rate_id'] : 0;
        $model->setTaxRateId($tax_rate_id);
        $product_id = (int) ($array['product_id'] ?? 0);
        $task_id = (int) ($array['task_id'] ?? 0);
        $model->setInvId((int) $inv_id);
        $model->setSoItemId(isset($array['so_item_id']) ? (int) $array['so_item_id'] : 0);
        $this->applyPeppolIds($model, $array);
        $product = $pr->repoProductquery($product_id);
        if ($product) {
            $this->applyProductItemBlock($model, $array, $product_id, $product, $pr, $translator);
        }
        $task = $taskR->repoTaskquery($task_id);
        if ($task) {
            $this->applyTaskItemBlock($model, $array, $task_id, $task, $taskR, $translator);
        }
        $model->setQuantity(isset($array['quantity']) ? (float) $array['quantity'] : 0.0);
        $model->setPrice(isset($array['price']) ? (float) $array['price'] : 0.00);
        $model->setDiscountAmount(isset($array['discount_amount']) ? (float) $array['discount_amount'] : 0.00);
        $model->setOrder(isset($array['order']) ? (int) $array['order'] : 0);
        $unit = $uR->repoUnitquery((int) $array['product_unit_id']);
        if ($unit) {
            $model->setProductUnit($unit->getUnitName());
        }
        $model->setProductUnitId((int) $array['product_unit_id']);
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
     * Related logic: see Used in InvController function invToInvItems
     * @param int $inv_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $tax_rate_percentage
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return InvItemAmount|null
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function saveInvItemAmount(
        int $inv_item_id,
        float $quantity,
        float $price,
        float $discount,
        float $tax_rate_percentage,
        IIAS $iias,
        IIAR $iiar
    ): InvItemAmount|null {
        $iias_array = [];
        $iias_array['inv_item_id'] = $inv_item_id;
        $sub_total = $quantity * $price;
        // Total cash settlement discount negotiated on this item
        $discount_total = ($quantity * $discount);
        // Fetch all allowance/charges for this item
        $all_charges = 0.00;
        $all_allowances = 0.00;
        $aciis = $this->aciiR->repoInvItemquery($inv_item_id);
        /** @var \App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge $acii */
        foreach ($aciis as $acii) {
            if ($acii->getAllowanceCharge()?->getIdentifier() == '1') {
                $all_charges += (float) $acii->getAmount();
            } else {
                $all_allowances += (float) $acii->getAmount();
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
        $inv_item_amount = $iiar->repoInvItemAmountquery($inv_item_id);
        if ($iiar->repoCount($inv_item_id) === 0) {
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
    public function taxratePercentage(int $id, TRR $trr): ?float
    {
        $taxrate = $trr->repoTaxRatequery($id);
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
     */
    public function initializeCreditInvItems(int $basis_inv_id,
           string $new_inv_id, InvItemRepository $iiR, IIAR $iiaR): void
    {
        // Get the basis invoice's items and balance with a negative quantity
        $items = $iiR->repoInvquery($basis_inv_id);
        /** @var InvItem $item */
        foreach ($items as $item) {
            $new_item = new InvItem();
            $new_item->setInvId((int) $new_inv_id);
            $new_item->setTaxRateId($item->reqTaxRateId());
            null !== $item->getProductId() ?
                    $new_item->setProductId((int) $item->getProductId())
                        : $new_item->setTaskId((int) $item->getTaskId());
            $new_item->setName($item->getName() ?? '');
            $new_item->setDescription($item->getDescription() ?? '');
            $new_item->setNote($item->getNote() ?? '');
            $new_item->setQuantity(($item->getQuantity() ?? 1.00) * -1.00);
            $new_item->setPrice($item->getPrice() ?? 0.00);
            $new_item->setDiscountAmount($item->getDiscountAmount() ?? 0.00);
            $new_item->setOrder(0);
            // Even if an invoice is balanced with a credit invoice it will
            // remain recurring ... unless stopped.
            // Is_recurring will be either stored as 0 or 1 in mysql. Cannot be
            // null.
            $new_item->setIsRecurring($item->getIsRecurring() ?? false);
            $new_item->setProductUnit($item->getProductUnit() ?? '');
            $new_item->setProductUnitId((int) $item->getProductUnitId());
            $new_item->setDate($item->getDateAdded());
            $iiR->save($new_item);

            // Create an item amount for this item; reversing the items amounts
            // to negative
            $basis_item_amount =
                        $iiaR->repoInvItemAmountquery($item->reqId());
            if ($basis_item_amount) {
                $new_item_amount = new InvItemAmount();
                $new_item_amount->setInvItemId($new_item->reqId());
                $new_item_amount->setSubtotal(
                            ($basis_item_amount->getSubtotal() ?? 0.00) * -1.00);
                $new_item_amount->setTaxTotal(
                            ($basis_item_amount->getTaxTotal() ?? 0.00) * -1.00);
                $new_item_amount->setDiscount(
                            ($basis_item_amount->getDiscount() ?? 0.00) * -1.00);
                $new_item_amount->setTotal(
                            ($basis_item_amount->getTotal() ?? 0.00) * -1.00);
                $iiaR->save($new_item_amount);
            }
        }
    }
}
