<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Infrastructure\Persistence\{
    CustomField\CustomField,
    Inv\Inv,
    InvAllowanceCharge\InvAllowanceCharge,
    InvCustom\InvCustom,
    InvItem\InvItem,
    InvItemAllowanceCharge\InvItemAllowanceCharge,
    InvTaxRate\InvTaxRate,
    SalesOrder\SalesOrder,
    SalesOrderAllowanceCharge\SalesOrderAllowanceCharge,
    SalesOrderCustom\SalesOrderCustom,
    SalesOrderItem\SalesOrderItem,
    SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge,
    SalesOrderTaxRate\SalesOrderTaxRate,
};
use App\Invoice\{
    InvAllowanceCharge\InvAllowanceChargeForm,
    InvAllowanceCharge\InvAllowanceChargeService,
    InvCustom\InvCustomForm,
    InvCustom\InvCustomService,
    InvItem\InvItemForm,
    InvItem\InvItemService,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvTaxRate\InvTaxRateForm,
    InvTaxRate\InvTaxRateService,
    SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR,
};
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Translator\TranslatorInterface;

final readonly class SalesOrderToInvoiceConverter
{
    public function __construct(
        private InvItemService $invItemService,
        private InvTaxRateService $invTaxRateService,
        private InvCustomService $invCustomService,
        private InvAllowanceChargeService $invAllowanceChargeService,
        private TranslatorInterface $translator,
    ) {}

    public function soToInvoiceSoItems(
        int $so_id,
        int $new_inv_id,
        FormHydrator $formHydrator,
        SoToInvoiceDependencies $d,
    ): void {
        $items = $d->soiR->repoSalesOrderItemIdquery($so_id);
        /** @var SalesOrderItem $so_item */
        foreach ($items as $so_item) {
            $origSoItemId = $so_item->reqId();
            $newInvItem = new InvItem();
            $inv_item = [
                'inv_id' => $new_inv_id,
                'so_item_id' => $origSoItemId,
                'tax_rate_id' => $so_item->getTaxRateId(),
                'product_id' => $so_item->getProduct()?->reqId(),
                'task_id' => $so_item->getTask()?->reqId(),
                'product_unit' => $so_item->getProductUnit(),
                'product_unit_id' => $so_item->getProductUnitId(),
                'peppol_po_itemid' => $so_item->getPeppolPoItemid(),
                'peppol_po_lineid' => $so_item->getPeppolPoLineid(),
                'name' => $so_item->getName(),
                'description' => $so_item->getDescription(),
                'quantity' => $so_item->getQuantity(),
                'price' => $so_item->getPrice(),
                'discount_amount' => $so_item->getDiscountAmount(),
                'order' => $so_item->getOrder(),
                'is_recurring' => 0,
                'date' => '',
            ];
            $form = new InvItemForm();
            if ($formHydrator->populateAndValidate($form, $inv_item)) {
                $savedInvItem = $this->invItemService->addInvItemProductTask(
                    $newInvItem, $inv_item, (string) $new_inv_id,
                    $d->pR, $d->taskR, $d->unR, $this->translator);
                $this->copySoItemAllowanceChargesToInv(
                        $origSoItemId, $d->acsoiR, $new_inv_id,
                        $savedInvItem, $d->aciiR);
                $tax_rate_percentage = $this->invItemService->taxratePercentage(
                        (int) $inv_item['tax_rate_id'], $d->trR);
                if (isset($inv_item['quantity'], $inv_item['price'],
                    $inv_item['discount_amount'])
                    && null !== $tax_rate_percentage
                ) {
                    $this->invItemService->saveInvItemAmount(
                        $savedInvItem->reqId(),
                        $inv_item['quantity'],
                        $inv_item['price'],
                        $inv_item['discount_amount'],
                        $tax_rate_percentage,
                        $d->iiaS,
                        $d->iiaR
                    );
                }
            }
        }
    }

    public function soToInvoiceSoTaxRates(
        int $so_id,
        int $inv_id,
        SoToInvoiceDependencies $d,
        FormHydrator $formHydrator,
    ): void {
        $so_tax_rates = $d->sotrR->repoSalesOrderquery($so_id);
        /** @var SalesOrderTaxRate $so_tax_rate */
        foreach ($so_tax_rates as $so_tax_rate) {
            $inv_tax_rate = [
                'inv_id' => $inv_id,
                'tax_rate_id' => $so_tax_rate->reqTaxRateId(),
                'include_item_tax' => $so_tax_rate->getIncludeItemTax(),
                'inv_tax_rate_amount' =>
                    $so_tax_rate->getSalesOrderTaxRateAmount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm();
            if ($formHydrator->populateAndValidate($form, $inv_tax_rate)) {
                $this->invTaxRateService->saveInvTaxRate($entity, $inv_tax_rate);
            }
        }
    }

    public function soToInvoiceSoCustom(
        int $so_id,
        int $inv_id,
        SoToInvoiceDependencies $d,
        FormHydrator $formHydrator,
    ): void {
        $so_customs = $d->socR->repoFields($so_id);
        /** @var SalesOrderCustom $so_custom */
        foreach ($so_customs as $so_custom) {
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $d->cfR->repoCustomFieldquery(
                $so_custom->reqCustomFieldId());
            if ($d->cfR->repoTableAndLabelCountquery('inv_custom',
                (string) $existing_custom_field->getLabel()) !== 0) {
                $custom_field = new CustomField();
                $custom_field->setTable('inv_custom');
                $custom_field->setLabel(
                    (string) $existing_custom_field->getLabel());
                $custom_field->setType(
                    $existing_custom_field->getType());
                $custom_field->setLocation(
                    (int) $existing_custom_field->getLocation());
                $custom_field->setOrder(
                    (int) $existing_custom_field->getOrder());
                $d->cfR->save($custom_field);
                $inv_custom = [
                    'inv_id' => $inv_id,
                    'custom_field_id' => $custom_field->reqId(),
                    'value' => $so_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm();
                if ($formHydrator->populateAndValidate($form, $inv_custom)) {
                    $this->invCustomService->saveInvCustom($entity, $inv_custom);
                }
            }
        }
    }

    public function soToInvoiceSoAmount(
        SalesOrder $so,
        Inv $inv,
        SoToInvoiceDependencies $d,
    ): void {
        $soA = $so->getSalesOrderAmount();
        $iA = $inv->getInvAmount();
        $iA->setInvId($inv->reqId());
        $iA->setItemSubtotal($soA->getItemSubtotal() ?? 0.00);
        $iA->setItemTaxTotal($soA->getItemTaxTotal() ?? 0.00);
        $iA->setPackhandleshipTotal($soA->getPackhandleshipTotal() ?: 0.00);
        $iA->setPackhandleshipTax($soA->getPackhandleshipTax() ?: 0.00);
        $iA->setTaxTotal($soA->getTaxTotal() ?? 0.00);
        $iA->setTotal($soA->getTotal() ?? 0.00);
        $d->iR->save($inv);
    }

    public function soToInvoiceSoAllowanceCharges(
        int $so_id,
        int $new_inv_id,
        SoToInvoiceDependencies $d,
        FormHydrator $formHydrator,
    ): void {
        $so_allowance_charges = $d->acsoR->repoACSOquery($so_id);
        /** @var SalesOrderAllowanceCharge $so_allowance_charge */
        foreach ($so_allowance_charges as $so_allowance_charge) {
            $new_inv_ac = [
                'inv_id' => $new_inv_id,
                'allowance_charge_id' =>
                    $so_allowance_charge->getAllowanceChargeId(),
                'amount' => $so_allowance_charge->getAmount(),
            ];
            $invAllowanceCharge = new InvAllowanceCharge();
            $form = InvAllowanceChargeForm::show($invAllowanceCharge, $new_inv_id);
            if ($formHydrator->populateAndValidate($form, $new_inv_ac)) {
                $this->invAllowanceChargeService->saveInvAllowanceCharge(
                    $invAllowanceCharge, $new_inv_ac
                );
            }
        }
    }

    private function copySoItemAllowanceChargesToInv(
        int $origSoItemId, ACSOIR $acsoiR, int $new_inv_id,
            InvItem $newInvItem, ACIIR $aciiR): void {

        $all = $acsoiR->repoSalesOrderItemquery($origSoItemId);
        /**
         * @var SalesOrderItemAllowanceCharge $salesOrderItemAllowanceCharge
         */
        foreach ($all as $salesOrderItemAllowanceCharge) {
            $acInvItem = new InvItemAllowanceCharge();
            $acInvItem->setInv($newInvItem->getInv());
            $acInvItem->setInvItem($newInvItem);
            $acInvItem->setAllowanceCharge(
                            $salesOrderItemAllowanceCharge->getAllowanceCharge());

            // Also set FK IDs for consistency
            $acInvItem->setInvId($new_inv_id);
            $acInvItem->setInvItemId($newInvItem->reqId());
            $acInvItem->setAllowanceChargeId(
            (int) $salesOrderItemAllowanceCharge->getAllowanceCharge()?->reqId()
            );

            // Set other properties
            $acInvItem->setAmount((float)
                $salesOrderItemAllowanceCharge->getAmount());
            $acInvItem->setVatOrTax((float)
                $salesOrderItemAllowanceCharge->getVatOrTax() ?: 0.00);
            $aciiR->save($acInvItem);
        }
    }
}
