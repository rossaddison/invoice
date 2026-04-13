<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\
{
    Inv, InvAllowanceCharge, InvItem, InvCustom, InvTaxRate 
};
use App\Invoice\{
    InvItemAmount\InvItemAmountService as IIAS,
    InvAllowanceCharge\InvAllowanceChargeForm, InvCustom\InvCustomForm,
    InvItem\InvItemForm, InvTaxRate\InvTaxRateForm,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR, Inv\InvForm,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvCustom\InvCustomRepository as ICR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    Product\ProductRepository as PR,
    Task\TaskRepository as TASKR,
    TaxRate\TaxRateRepository as TRR,
    Unit\UnitRepository as UNR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\User\UserRepository as UR;
use Yiisoft\{
    FormModel\FormHydrator, Json\Json, Security\Random 
};
use Psr\{Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait MultipleCopy
{
    public function multiplecopy(
        Request $request,
        FormHydrator $formHydrator,
        ACIIR $aciiR,
        GR $gR,
        IIAS $iiaS,
        PR $pR,
        TASKR $taskR,
        ICR $icR,
        IAR $iaR,
        IIAR $iiaR,
        IIR $iiR,
        IR $iR,
        ITRR $itrR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
        UNR $unR,
    ): Response {
        $data = $request->getQueryParams();
        /**
         * Purpose: Provide a list of ids from inv/index checkbox column
         * as an array
         * @var array $data['keylist']
         */
        $keyList = $data['keylist'] ?? [];
        if (!empty($keyList)) {
            /**
             * @var string $value
             */
            foreach ($keyList as $value) {
                $invId = $value;
                $original = $iR->repoInvUnloadedquery($invId);
                if ($original) {
                    $invoice_body = [
                        'client_id' => $original->getClientId(),
                        'group_id' => $original->getGroupId(),
                        'so_id' => $original->getSoId(),
                        'quote_id' => $original->getQuoteId(),
                        // user_id below
                        'status_id' =>
                        $this->sR->getSetting('mark_invoices_sent_copy') ===
                            '1' ? 2 : 1,
                        'is_read_only' => $this->sR->getSetting(
                            'mark_invoices_sent_copy') === '1',
                        'password' => '',
                        // date_supplied and date_tax_point will change as soon
                        // as goods are supplied and a supplied/service date
                        // is recorded
                        'date_supplied' => new \DateTimeImmutable('now'),
                        'date_tax_point' => new \DateTimeImmutable('now'),
                        'time_created' =>
                            (new \DateTimeImmutable('now'))->format('H:i:s'),
                        // the company will be registered for their own personal
                        // peppol stand-in-code
                        'stand_in_code' => $this->sR->getSetting('stand_in_code'),
                        // if draft invoices must get invoice numbers
                        'number' => $this->sR->getSetting(
                            'generate_invoice_number_for_draft') === '1' ?
                                (string) $gR->generateNumber(
                                    (int) $original->getGroupId(), true) : '',
                        'discount_amount' =>
                            (float) $original->getDiscountAmount(),
                        'terms' => $original->getTerms(),
                        'note' => $original->getNote(),
                        'document_description' =>
                            $original->getDocumentDescription(),
                        'url_key' => Random::string(32),
                        'payment_method' => $original->getPaymentMethod(),
                        // a copied invoice will not have a credit note
                        'creditinvoice_parent_id' => null,
                        'delivery_id' => $original->getDeliveryId(),
                        'delivery_location_id' =>
                            $original->getDeliveryLocationId(),
                        'postal_address_id' => $original->getPostalAddressId(),
                        'contract_id' => $original->getContractId(),
                    ];
                    $copy = new Inv();
                    $form = new InvForm($copy);
                    if ($formHydrator->populateAndValidate($form, $invoice_body)) {
                        /**
                         * @var string $invoice_body['client_id']
                         */
                        $client_id = $invoice_body['client_id'];
                        $user = $this->activeUser($client_id, $uR, $ucR, $uiR);
                        if (null !== $user) {
                            $copied = $this->inv_service->copyInv(
                                $user, $copy, $invoice_body, $this->sR);
                            /**
                             * Note: Reset the immutable date_created
                             * outside the inv_service
                             */
                            $copied->setDateCreated(
                                (string) $data['modal_created_date']);
                            $copied_id = $copied->getId();
                            $iR->save($copied);
                            // Transfer each inv_item to inv_item and the
                            // corresponding inv_item_amount to inv_item_amount
                            // for each item

                            if (null !== $copied_id) {
                                $this->invToInvInvItems($invId, $copied_id,
                                    $iiaR, $iiaS, $pR, $taskR, $iiR, $trR,
                                    $aciiR, $formHydrator, $unR);
                                $this->invToInvInvTaxRates($invId,
                                    $copied_id, $itrR, $formHydrator);
                                $this->invToInvInvCustom($invId, $copied_id,
                                    $icR, $formHydrator);
                                $this->invToInvInvAmount((int) $invId,
                                    (int) $copied_id, $iaR);
                                $iR->save($copy);
                            }
                        }
                    }
                } // original
            } // foreach $keyList
            return $this->factory->createResponse(Json::encode(['success' => 1]));
        } // !empty($keyList)
        return $this->factory->createResponse(Json::encode(['success' => 0]));
    }
    
    private function invToInvInvAllowanceCharges(string $inv_id,
        string $copy_id, ACIR $aciR, FormHydrator $formHydrator): void
    {
        $inv_allowance_charges = $aciR->repoACIquery($inv_id);
        /**
         * @var InvAllowanceCharge $inv_allowance_charge
         */
        foreach ($inv_allowance_charges as $inv_allowance_charge) {
            $copy_inv_allowance_charge = [
                'inv_id' => $copy_id,
                'allowance_charge_id' =>
                    $inv_allowance_charge->getAllowanceChargeId(),
                'amount' => $inv_allowance_charge->getAmount(),
                'vat_or_tax' => $inv_allowance_charge->getVatOrTax(),
            ];
            $invAllowanceCharge = new InvAllowanceCharge();
            $form = new InvAllowanceChargeForm($invAllowanceCharge,
                (int) $copy_id);
            if ($formHydrator->populateAndValidate($form,
                    $copy_inv_allowance_charge)) {
                $this->inv_allowance_charge_service->saveInvAllowanceCharge(
                     $invAllowanceCharge, $copy_inv_allowance_charge);
            }
        }
    }

    private function invToInvInvAmount(int $invId, int $copiedId, IAR $iaR):
        void
    {
        $original = $iaR->repoInvquery($invId);
        if (null !== $original) {
            $array = [];
            $array['inv_id'] = $original->getInvId();
            $array['item_subtotal'] = $original->getItemSubtotal();
            $array['item_taxtotal'] = $original->getItemTaxTotal();
            $array['packhandleship_tax'] = $original->getPackhandleshipTax();
            $array['packhandleship_total'] = $original->getPackhandleshipTax();
            $array['tax_total'] = $original->getTaxTotal();
            $array['total'] = $original->getTotal();
            $array['paid'] = 0;
            $array['balance'] = $original->getBalance();
            $copied = $iaR->repoInvquery($copiedId);
            null !== $copied ?
                $this->inv_amount_service->saveInvAmountViaCalculations(
                        $copied, $array) : '';
        }
    }

    /**
     * Related logic: see Data fed from
     *  inv.js->$(document).on('click', '#inv_to_inv_confirm', function () {
     */
    public function invToInvConfirm(
        Request $request,
        FormHydrator $formHydrator,
        ACIIR $aciiR,
        ACIR $aciR,
        GR $gR,
        IIAS $iiaS,
        PR $pR,
        TASKR $taskR,
        IAR $iaR,
        ICR $icR,
        IIAR $iiaR,
        IIR $iiR,
        IR $iR,
        ITRR $itrR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
        UNR $unR,
    ): Response {
        $data_inv_js = $request->getQueryParams();
        $inv_id = (string) $data_inv_js['inv_id'];
        $original = $iR->repoInvUnloadedquery($inv_id);
        if ($original) {
            $group_id = $original->getGroupId();
            $ajax_body = [
                'quote_id' => null,
                'client_id' => $data_inv_js['client_id'],
                'group_id' => $group_id,
                'status_id' =>
                    $this->sR->getSetting('mark_invoices_sent_copy') === '1' ?
                        2 : 1,
                'number' => $gR->generateNumber((int) $group_id),
                'creditinvoice_parent_id' => null,
                'discount_amount' => (float) $original->getDiscountAmount(),
                'url_key' => '',
                'password' => '',
                'payment_method' => 6,
                'terms' => '',
            ];
            $copy = new Inv();
            $form = new InvForm($copy);
            if ($formHydrator->populateAndValidate($form, $ajax_body)) {
                /**
                 * @var string $ajax_body['client_id']
                 */
                $client_id = $ajax_body['client_id'];
                $user = $this->activeUser($client_id, $uR, $ucR, $uiR);
                if (null !== $user) {
                    $this->inv_service->saveInv(
                        $user, $copy, $ajax_body, $this->sR, $gR);
                    // Transfer each inv_item to inv_item and the corresponding
                    // inv_item_amount to inv_item_amount for each item
                    $copy_id = $copy->getId();
                    if (null !== $copy_id) {
                        $this->invToInvInvItems($inv_id, $copy_id, $iiaR,
                            $iiaS, $pR, $taskR, $iiR, $trR, $aciiR,
                                $formHydrator, $unR);
                        $this->invToInvInvTaxRates($inv_id, $copy_id, $itrR,
                            $formHydrator);
                        $this->invToInvInvCustom($inv_id, $copy_id, $icR,
                            $formHydrator);
                        $this->invToInvInvAllowanceCharges($inv_id, $copy_id,
                            $aciR, $formHydrator);
                        $this->invToInvInvAmount((int) $inv_id,
                            (int) $copy_id, $iaR);
                        $iR->save($copy);
                        $parameters = ['success' => 1,
                            'new_invoice_id' => $copy_id];
                        //return response to inv.js to redirect to newly
                        //created invoice
                        $this->flashMessage('info', $this->translator->translate(
                            'draft.guest'));
                        return $this->factory->createResponse(
                            Json::encode($parameters));
                    }
                }
            }
            $parameters = [
                'success' => 0,
            ];
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
        // if original
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $copy_id
     */
    private function invToInvInvCustom(string $inv_id, string $copy_id,
        ICR $icR, FormHydrator $formHydrator): void
    {
        $inv_customs = $icR->repoFields($inv_id);
        /**
         * @var InvCustom $inv_custom
         */
        foreach ($inv_customs as $inv_custom) {
            $copyCustom = [
                'inv_id' => $copy_id,
                'custom_field_id' => $inv_custom->getCustomFieldId(),
                'value' => $inv_custom->getValue(),
            ];
            $invCustom = new InvCustom();
            $form = new InvCustomForm($invCustom);
            if ($formHydrator->populateAndValidate($form, $copyCustom)) {
                $this->inv_custom_service->saveInvCustom(
                    $invCustom, $copyCustom);
            }
        }
    }

    /**
     * This procedure is used solely for making identical copies of invoices
     */
    private function invToInvInvItems(
        string $inv_id,
        string $copy_id,
        IIAR $iiaR,
        IIAS $iiaS,
        PR $pR,
        TASKR $taskR,
        IIR $iiR,
        TRR $trR,
        ACIIR $aciiR,
        FormHydrator $formHydrator,
        UNR $unR,
    ): void {
        // Get all items that belong to the original invoice
        $items = $iiR->repoInvItemIdquery($inv_id);
        /**
         * @var InvItem $inv_item
         */
        foreach ($items as $inv_item) {
            $copy_item = [
                // Follow sequence of InvItem construct
                //id
                'date added' => new \DateTimeImmutable(),
                'task_id' => $inv_item->getTaskId(),
                'name' => $inv_item->getName(),
                'description' => $inv_item->getDescription(),
                /**
                 * Related logic: see quantity
                 *   #[GreaterThan(0.00)]. See InvItemForm
                 */
                'quantity' => $inv_item->getQuantity(),
                'price' => $inv_item->getPrice(),
                'discount_amount' => $inv_item->getDiscountAmount(),
                'order' => $inv_item->getOrder(),
                'is_recurring' => $inv_item->getIsRecurring(),
                /**
                 * Related logic: see Not required since will conflict with
                 * task which does not require a product_unit i.e.
                 * service/product
                 */
                'product_unit' => $inv_item->getProductUnit(),
                'inv_id' => $copy_id,
                'so_item_id' => $inv_item->getSoItemId(),
                /**
                 * Related logic: see tax_rate_id #[Required]. See InvItemForm
                 */
                'tax_rate_id' => $inv_item->getTaxRateId(),
                'product_id' => $inv_item->getProductId(),
                'product_unit_id' => $inv_item->getProductUnitId(),
                // recurring date
                'date' => $inv_item->getDate(),
                'belongs_to_vat_invoice' =>
                    $inv_item->getBelongsToVatInvoice(),
                'delivery_id' => $inv_item->getDeliveryId(),
                'note' => $inv_item->getNote(),
            ];
            $originalInvItemId = $inv_item->getId();
            if (null !== $originalInvItemId) {
                // Create an equivalent invoice item for the invoice item
                $invItem = new InvItem();
                $form = new InvItemForm($invItem, (int) $inv_id);
                if ($formHydrator->populateAndValidate($form, $copy_item)) {
                    $productId = (int) $inv_item->getProductId();
                    if ($productId > 0) {
                        $newInvItemId =
                            $this->inv_item_service->addInvItemProduct(
                                $invItem, $copy_item, $copy_id, $pR, $trR, $iiaS,
                                    $iiaR, $this->sR, $unR);
                        if (null !== $newInvItemId) {
                            $this->inv_item_service->addInvItemAllowanceCharges(
                                $copy_id, $originalInvItemId, $newInvItemId,
                                    $aciiR);
                            if (($invItem->getQuantity() >=  0.00)
                                && ($invItem->getPrice() >= 0.00)
                                && ($invItem->getDiscountAmount() >= 0.00)
                                && ($inv_item->getTaxRate()?->getTaxRatePercent()
                                    >= 0.00)) {
                                $this->inv_item_service->saveInvItemAmount(
                                    $newInvItemId,
                                    $invItem->getQuantity() ?? 0.00,
                                    $invItem->getPrice() ?? 0.00,
                                    $invItem->getDiscountAmount() ?? 0.00,
                                    $inv_item->getTaxRate()?->getTaxRatePercent()
                                        ?? 0.00,
                                    $iiaS,
                                    $iiaR
                                );
                            }
                        }
                    }
                    $taskId = (int) $inv_item->getTaskId();
                    if ($taskId > 0) {
                        $newInvItemId = $this->inv_item_service->addInvItemTask(
                            $invItem, $copy_item, $copy_id, $taskR, $trR, $iiaS,
                                $iiaR);
                        if (null !== $newInvItemId) {
                            $this->inv_item_service->addInvItemAllowanceCharges(
                            $copy_id, $originalInvItemId, $newInvItemId, $aciiR);
                            if (($invItem->getQuantity() >=  0.00)
                                && ($invItem->getPrice() >= 0.00)
                                && ($invItem->getDiscountAmount() >= 0.00)
                                && ($inv_item->getTaxRate()?->getTaxRatePercent()
                                    >= 0.00)) {
                                $this->inv_item_service->saveInvItemAmount(
                                    $newInvItemId,
                                    $invItem->getQuantity() ?? 0.00,
                                    $invItem->getPrice() ?? 0.00,
                                    $invItem->getDiscountAmount() ?? 0.00,
                                    $inv_item->getTaxRate()?->getTaxRatePercent()
                                        ?? 0.00,
                                    $iiaS,
                                    $iiaR
                                );
                            }
                        }
                    }
                } else {
                    if (!empty(
                        $form->getValidationResult()
                             ->getErrorMessagesIndexedByProperty())) {
                        $this->flashMessage('danger',
                            'You have validation errors on '
                                . (string) $inv_item->getId());
                    }
                }
            } // null!==originalItemId
        } // foreach
    }

    /**
     * @param string $copy_id
     */
    private function invToInvInvTaxRates(string $inv_id, string $copy_id,
        ITRR $itrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the invoice
        $inv_tax_rates = $itrR->repoInvquery($inv_id);
        /**
         * @var InvTaxRate $inv_tax_rate
         */
        foreach ($inv_tax_rates as $inv_tax_rate) {
            $copy_tax_rate = [
                'inv_id' => $copy_id,
                'tax_rate_id' => $inv_tax_rate->getTaxRateId(),
                'include_item_tax' => $inv_tax_rate->getIncludeItemTax(),
                'amount' => $inv_tax_rate->getInvTaxRateAmount(),
            ];
            $invTaxRate = new InvTaxRate();
            $form = new InvTaxRateForm($invTaxRate);
            if ($formHydrator->populateAndValidate($form, $copy_tax_rate)) {
                $this->inv_tax_rate_service->saveInvTaxRate(
                    $invTaxRate, $copy_tax_rate);
            }
        }
    }
}
