<?php

declare(strict_types=1);

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;

final readonly class InvAmountService
{
    public function __construct(
        private IAR $repository,
        private IR $iR,
    ) {
    }

    /**
     * @param InvAmount $model
     * @param string $inv_id
     */
    public function initializeInvAmount(
        InvAmount $model,
        string $inv_id
    ): void {
        $this->persist($model, ['inv_id' => $inv_id]);
        $inv_id ? $model->setInvId((int) $inv_id) : '';
        $model->setSign(1);
        $model->setItemSubtotal(0.00);
        $model->setItemTaxTotal(0.00);
        $model->setPackhandleshipTotal(0.00);
        $model->setPackhandleshipTax(0.00);
        $model->setTaxTotal(0.00);
        $model->setTotal(0.00);
        $model->setPaid(0.00);
        $model->setBalance(0.00);
        $this->repository->save($model);
    }

    /**
     * @param InvAmount $model
     * @param int $basis_inv_id
     * @param string $new_inv_id
     */
    public function initializeCreditInvAmount(
        InvAmount $model,
        int $basis_inv_id,
        string $new_inv_id
    ): void {
        $this->persist($model, ['inv_id' => $new_inv_id]);
        $basis_invoice = $this->repository->repoInvquery(
            $basis_inv_id);
        $new_inv_id ? $model->setInvId((int) $new_inv_id) : '';
        $model->setSign(1);
        null !== $basis_invoice ? $model->setItemSubtotal(($basis_invoice->getItemSubtotal() ?: 0.00) * -1.00) : '';
        null !== $basis_invoice ? $model->setItemTaxTotal(($basis_invoice->getItemTaxTotal() ?: 0.00) * -1.00) : '';
        null !== $basis_invoice ? $model->setPackhandleshipTotal(($basis_invoice->getPackhandleshipTotal() ?: 0.00) * -1.00) : '';
        null !== $basis_invoice ? $model->setPackhandleshipTax(($basis_invoice->getPackhandleshipTax() ?: 0.00) * -1.00) : '';
        null !== $basis_invoice ? $model->setTaxTotal(($basis_invoice->getTaxTotal() ?? 0.00) * -1.00) : '';
        null !== $basis_invoice ? $model->setTotal(($basis_invoice->getTotal() ?? 0.00) * -1.00) : '';
        $model->setPaid(0.00);
        null !== $basis_invoice ? $model->setBalance(($basis_invoice->getBalance() ?? 0.00) * -1.00) : '';
        $this->repository->save($model);
    }

    /**
     * @param InvAmount $model
     * @param int $basis_inv_id
     * @param string $new_inv_id
     */
    public function initializeCopyInvAmount(
        InvAmount $model,
        int $basis_inv_id,
        string $new_inv_id
    ): void {
        $this->persist($model, ['inv_id' => $new_inv_id]);
        $basis_invoice = $this->repository->repoInvquery(
            $basis_inv_id);
        $new_inv_id ? $model->setInvId((int) $new_inv_id) : '';
        $model->setSign(1);
        /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference */
        $model->setItemSubtotal($basis_invoice->getItemSubtotal());
        $model->setItemTaxTotal($basis_invoice->getItemTaxTotal() ?: 0.00);
        $model->setPackhandleshipTotal($basis_invoice->getPackhandleshipTotal() ?: 0.00);
        $model->setPackhandleshipTax($basis_invoice->getPackhandleshipTax() ?: 0.00);
        $model->setTaxTotal($basis_invoice->getTaxTotal() ?? 0.00);
        $model->setTotal($basis_invoice->getTotal() ?? 0.00);
        $model->setPaid(0.00);
        $model->setBalance($basis_invoice->getTotal() ?? 0.00);
        $this->repository->save($model);
    }

    /**
     * @param InvAmount $model
     * @param array $array
     */
    public function saveInvAmount(
        InvAmount $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['inv_id']) ?
            $model->setInvId((int) $array['inv_id']) : '';
        $model->setSign(1);
        isset($array['item_subtotal']) ?
            $model->setItemSubtotal(
                (float) $array['item_subtotal']) : '';
        isset($array['item_tax_total']) ?
            $model->setItemTaxTotal(
                (float) $array['item_tax_total']) : '';
        isset($array['packhandleship_total']) ?
            $model->setPackhandleshipTotal(
                (float) $array['packhandleship_total']) : '';
        isset($array['packhandleship_tax']) ?
            $model->setPackhandleshipTax(
                (float) $array['packhandleship_tax']) : '';
        isset($array['tax_total']) ?
            $model->setTaxTotal((float) $array['tax_total']) : '';
        isset($array['total']) ?
            $model->setTotal((float) $array['total']) : '';
        isset($array['paid']) ?
            $model->setPaid((float) $array['paid']) : '';
        isset($array['balance']) ?
            $model->setBalance((float) $array['balance']) : '';
        $this->repository->save($model);
    }

    private function persist(
        InvAmount $model,
        array $array
    ): void {
        $inv = 'inv_id';
        if (isset($array[$inv])) {
            $invEntity = $this->iR->repoInvUnLoadedquery(
                (string) $array[$inv]);
            if ($invEntity) {
                $model->setInv($invEntity);
            }
        }
    }

    /**
     * @param InvAmount $model
     * @param array $array
     */
    public function saveInvAmountViaCalculations(InvAmount $model, array $array): void
    {
        $model->setInvId((int) $array['inv_id']);
        $model->setItemSubtotal((float) $array['item_subtotal']);
        $model->setItemTaxTotal((float) $array['item_taxtotal']);
        $model->setPackhandleshipTotal((float) $array['packhandleship_total']);
        $model->setPackhandleshipTax((float) $array['packhandleship_tax']);
        $model->setTaxTotal((float) $array['tax_total']);
        $model->setTotal((float) $array['total']);
        $model->setPaid((float) $array['paid']);
        $model->setBalance((float) $array['balance']);
        $this->repository->save($model);
    }

    /**
     * Update the Invoice Amounts when an inv item allowance or charge is added to an invoice item.
     * Also update the Invoice totals using Numberhelper calculate inv_taxes function
     * Related logic: see InvItemAllowanceChargeController functions add and edit
     * @param int $inv_id
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @param NumberHelper $numberHelper
     */
    public function updateInvAmount(int $inv_id, IAR $iaR, IIAR $iiaR, ITRR $itrR, NumberHelper $numberHelper): void
    {
        $model = $this->repository->repoInvquery($inv_id);
        if (null !== $model) {
            $inv = $model->getInv();
            if (null !== $inv) {
                /**
                 * Related logic: see Entity\Inv #[HasMany(target: InvItem::class)] private ArrayCollection $items;
                 * @var
                 */
                $items = $inv->getItems();
                $subtotal = 0.00;
                $packHandleShipTotal = 0.00;
                $packHandleShipTax = 0.00;
                $taxTotal = 0.00;
                /**
                 * @var InvItem $item
                 */
                foreach ($items as $item) {
                    $invItemId = $item->getId();
                    if (null !== $invItemId) {
                        $invItemAmount = $iiaR->repoInvItemAmountquery((string) $invItemId);
                        if ($invItemAmount) {
                            $subtotal += $invItemAmount->getSubtotal() ?? 0.00;
                            $taxTotal += $invItemAmount->getTaxTotal() ?? 0.00;
                        }
                    }
                }

                $model->setSign(1);
                $model->setItemSubtotal($subtotal);
                $model->setItemTaxTotal($taxTotal);
                $model->setPackhandleshipTotal($packHandleShipTotal);
                $model->setPackhandleshipTax($packHandleShipTax);
                $additionalTaxTotal = $numberHelper->calculateInvTaxes((string) $inv_id, $itrR, $iaR);
                $model->setTaxTotal($additionalTaxTotal);
                $model->setTotal($subtotal + $taxTotal + $additionalTaxTotal);
                $this->repository->save($model);
            }
        }
    }

    /**
     * @param InvAmount $model
     */
    public function deleteInvAmount(InvAmount $model): void
    {
        $this->repository->delete($model);
    }
}
