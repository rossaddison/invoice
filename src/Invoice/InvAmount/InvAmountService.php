<?php

declare(strict_types=1);

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use Doctrine\Common\Collections\ArrayCollection;

final class InvAmountService
{
    private InvAmountRepository $repository;

    public function __construct(InvAmountRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     *
     * @param InvAmount $model
     * @param string $inv_id
     * @return void
     */
    public function initializeInvAmount(InvAmount $model, string $inv_id): void
    {
        $inv_id ? $model->setInv_id((int)$inv_id) : '';
        $model->setSign(1);
        $model->setItem_subtotal(0.00);
        $model->setItem_tax_total(0.00);
        $model->setTax_total(0.00);
        $model->setTotal(0.00);
        $model->setPaid(0.00);
        $model->setBalance(0.00);
        $this->repository->save($model);
    }

    /**
     * @param InvAmount $model
     * @param int $basis_inv_id
     * @param string $new_inv_id
     * @return void
     */
    public function initializeCreditInvAmount(InvAmount $model, int $basis_inv_id, string $new_inv_id): void
    {
        $basis_invoice = $this->repository->repoInvquery($basis_inv_id);
        $new_inv_id ? $model->setInv_id((int)$new_inv_id) : '';
        $model->setSign(1);
        null !== $basis_invoice ? $model->setItem_subtotal(($basis_invoice->getItem_subtotal() ?: 0.00) * -1) : '';
        null !== $basis_invoice ? $model->setItem_tax_total(($basis_invoice->getItem_tax_total() ?: 0.00) * -1) : '';
        null !== $basis_invoice ? $model->setTax_total(($basis_invoice->getTax_total() ?? 0.00) * -1) : '';
        null !== $basis_invoice ? $model->setTotal(($basis_invoice->getTotal() ?? 0.00) * -1) : '';
        $model->setPaid(0.00);
        null !== $basis_invoice ? $model->setBalance(($basis_invoice->getBalance() ?? 0.00) * -1) : '';
        $this->repository->save($model);
    }

    /**
     *
     * @param InvAmount $model
     * @param int $basis_inv_id
     * @param string $new_inv_id
     * @return void
     */
    public function initializeCopyInvAmount(InvAmount $model, int $basis_inv_id, string $new_inv_id): void
    {
        $basis_invoice = $this->repository->repoInvquery($basis_inv_id);
        $new_inv_id ? $model->setInv_id((int)$new_inv_id) : '';
        $model->setSign(1);
        /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference */
        $model->setItem_subtotal($basis_invoice->getItem_subtotal());
        $model->setItem_tax_total($basis_invoice->getItem_tax_total() ?: 0.00);
        $model->setTax_total($basis_invoice->getTax_total() ?? 0.00);
        $model->setTotal($basis_invoice->getTotal() ?? 0.00);
        $model->setPaid(0.00);
        $model->setBalance($basis_invoice->getTotal() ?? 0.00);
        $this->repository->save($model);
    }

    /**
     * @param InvAmount $model
     * @param array $array
     * @param InvAmountForm $form
     * @return void
     */
    public function saveInvAmount(InvAmount $model, array $array): void
    {
        isset($array['inv_id']) ? $model->setInv_id((int)$array['inv_id']) : '';
        $model->setSign(1);
        isset($array['item_subtotal']) ? $model->setItem_subtotal((float)$array['item_subtotal']) : '';
        isset($array['item_tax_total']) ? $model->setItem_tax_total((float)$array['item_tax_total']) : '';
        isset($array['tax_total']) ? $model->setTax_total((float)$array['tax_total']) : '';
        isset($array['total']) ? $model->setTotal((float)$array['total']) : '';
        isset($array['paid']) ? $model->setPaid((float)$array['paid']) : '';
        isset($array['balance']) ? $model->setBalance((float)$array['balance']) : '';
        $this->repository->save($model);
    }

    /**
     *
     * @param InvAmount $model
     * @param array $array
     * @return void
     */
    public function saveInvAmountViaCalculations(InvAmount $model, array  $array): void
    {
        $model->setInv_id((int)$array['inv_id']);
        $model->setItem_subtotal((float)$array['item_subtotal']);
        $model->setItem_tax_total((float)$array['item_taxtotal']);
        $model->setTax_total((float)$array['tax_total']);
        $model->setTotal((float)$array['total']);
        $model->setPaid((float)$array['paid']);
        $model->setBalance((float)$array['balance']);
        $this->repository->save($model);
    }


    /**
     * Update the Invoice Amounts when an inv item allowance or charge is added to an invoice item.
     * Also update the Invoice totals using Numberhelper calculate inv_taxes function
     * @see InvItemAllowanceChargeController functions add and edit
     * @param int $inv_id
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @param NumberHelper $numberHelper
     * @return void
     */
    public function updateInvAmount(int $inv_id, IAR $iaR, IIAR $iiaR, ITRR $itrR, NumberHelper $numberHelper): void
    {
        $model = $this->repository->repoInvquery($inv_id);
        if (null !== $model) {
            $inv = $model->getInv();
            if (null !== $inv) {
                /**
                 * @see Entity\Inv #[HasMany(target: InvItem::class)] private ArrayCollection $items;
                 * @var
                 */
                $items = $inv->getItems();
                $subtotal = 0.00;
                $taxTotal = 0.00;
                $discount = 0.00;
                $charge = 0.00;
                $allowance = 0.00;
                /**
                 * @var InvItem $item
                 */
                foreach ($items as $item) {
                    $invItemId = $item->getId();
                    if (null !== $invItemId) {
                        $invItemAmount = $iiaR->repoInvItemAmountquery((string)$invItemId);
                        if ($invItemAmount) {
                            $subtotal += $invItemAmount->getSubtotal() ?? 0.00;
                            $taxTotal += $invItemAmount->getTax_total() ?? 0.00;
                            $discount += $invItemAmount->getDiscount() ?? 0.00;
                            $charge += $invItemAmount->getCharge() ?? 0.00;
                            $allowance += $invItemAmount->getAllowance() ?? 0.00;
                        }
                    }
                }
                $model->setSign(1);
                $model->setItem_subtotal($subtotal);
                $model->setItem_tax_total($taxTotal);
                $additionalTaxTotal = $numberHelper->calculate_inv_taxes((string)$inv_id, $itrR, $iaR);
                $model->setTax_total($additionalTaxTotal);
                $model->setTotal($subtotal + $taxTotal + $additionalTaxTotal);
                $this->repository->save($model);
            }
        }
    }

    /**
     *
     * @param InvAmount $model
     * @return void
     */
    public function deleteInvAmount(InvAmount $model): void
    {
        $this->repository->delete($model);
    }
}
