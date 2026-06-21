<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\
{
    Inv\Inv, InvAllowanceCharge\InvAllowanceCharge,
    InvItem\InvItem, InvCustom\InvCustom, InvTaxRate\InvTaxRate
};

use App\Invoice\{
    InvAllowanceCharge\InvAllowanceChargeForm, InvCustom\InvCustomForm,
    InvItem\IiAddProductDeps, InvItem\InvItemForm, InvTaxRate\InvTaxRateForm,
    Inv\InvCopyDeps,
    Inv\InvForm
};

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
        InvCopyDeps $d,
    ): Response {
        $data = $request->getQueryParams();
        /**
         * Purpose: Provide a list of ids from inv/index checkbox column
         * as an array
         * @var array $data['keylist']
         */
        $keyList = $data['keylist'] ?? [];
        if (empty($keyList)) {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }

        // Accept client_ids[] (multiselect modal); fall back to each invoice's own client
        /** @var int[] $selectedClientIds */
        $selectedClientIds = array_values(
            array_filter(array_map('intval', (array)($data['client_ids'] ?? [])))
        );

        $anySuccess = false;

        /**
         * @var string $value
         */
        foreach ($keyList as $value) {
            $invId    = (int) $value;
            $original = $d->iR->repoInvUnloadedquery($invId);
            if (!$original) {
                continue;
            }

            // Collect product IDs from this invoice once (for ProductClient sync)
            $productIds = [];
            /** @var InvItem $item */
            foreach ($d->iiR->repoInvItemIdquery($invId) as $item) {
                $pid = $item->getProductId();
                if ($pid !== null && $pid > 0) {
                    $productIds[] = $pid;
                }
            }

            // Use selected clients, or fall back to the invoice's own client
            $targets = !empty($selectedClientIds)
                ? $selectedClientIds
                : [$original->reqClientId()];

            foreach ($targets as $targetClientId) {
                $invoice_body = [
                    'client_id' => $targetClientId,
                    'group_id' => $original->reqGroupId(),
                    'so_id' => $original->getSoId(),
                    'quote_id' => $original->getQuoteId(),
                    'status_id' =>
                    $this->sR->getSetting('mark_invoices_sent_copy') ===
                        '1' ? 2 : 1,
                    'is_read_only' => $this->sR->getSetting(
                        'mark_invoices_sent_copy') === '1',
                    'password' => '',
                    'date_supplied' => new \DateTimeImmutable('now'),
                    'date_tax_point' => new \DateTimeImmutable('now'),
                    'time_created' =>
                        (new \DateTimeImmutable('now'))->format('H:i:s'),
                    'stand_in_code' => $this->sR->getSetting('stand_in_code'),
                    'number' => $this->sR->getSetting(
                        'generate_invoice_number_for_draft') === '1' ?
                            (string) $d->gR->generateNumber(
                                $original->reqGroupId(), true) : '',
                    'discount_amount' =>
                        (float) $original->getDiscountAmount(),
                    'terms' => $original->getTerms(),
                    'note' => $original->getNote(),
                    'document_description' =>
                        $original->getDocumentDescription(),
                    'url_key' => Random::string(32),
                    'payment_method' => $original->getPaymentMethod(),
                    'creditinvoice_parent_id' => null,
                    'delivery_id' => $original->getDeliveryId(),
                    'delivery_location_id' =>
                        $original->getDeliveryLocationId(),
                    'postal_address_id' => $original->getPostalAddressId(),
                    'contract_id' => $original->getContractId(),
                ];
                $copy = new Inv();
                $form = new InvForm();
                if (!$formHydrator->populateAndValidate($form, $invoice_body)) {
                    continue;
                }
                $user = $this->activeUser($targetClientId, $d->uR, $d->ucR, $d->uiR);
                if (null === $user) {
                    continue;
                }
                $this->inv_service->withTransaction(
                    function () use (
                        $user, $copy, $invoice_body, $data, $invId,
                        $d, $formHydrator, $productIds, $targetClientId
                    ): void {
                        $copied = $this->inv_service->copyInv(
                            $user, $copy, $invoice_body, $this->sR);
                        $copied->setDateCreated(
                            (string) ($data['modal_created_date'] ?? ''));
                        $d->iR->save($copied);
                        $copied_id = $copied->reqId();
                        if ($copied_id > 0) {
                            $this->invToInvInvItems($invId,
                                $copied_id, $d, $formHydrator);
                            $this->invToInvInvTaxRates($invId,
                                $copied_id, $d, $formHydrator);
                            $this->invToInvInvCustom($invId,
                                $copied_id, $d, $formHydrator);
                            $this->invToInvInvAmount($invId,
                                $copied_id, $d);
                            $d->iR->save($copy);
                            if (!empty($productIds)) {
                                $d->pcS->syncFromInvItems(
                                    $targetClientId, $productIds);
                            }
                        }
                    }
                );
                $anySuccess = true;
            }
        }

        return $this->factory->createResponse(
            Json::encode(['success' => $anySuccess ? 1 : 0])
        );
    }

    private function invToInvInvAllowanceCharges(
        int $inv_id,
        int $copy_id,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): void {
        $inv_allowance_charges = $d->aciR->repoACIquery($inv_id);
        /**
         * @var InvAllowanceCharge $inv_allowance_charge
         */
        foreach ($inv_allowance_charges as $inv_allowance_charge) {
            $copy_inv_allowance_charge = [
                'inv_id' => $copy_id,
                'allowance_charge_id' =>
                    $inv_allowance_charge->reqAllowanceChargeId(),
                'amount' => $inv_allowance_charge->getAmount(),
                'vat_or_tax' => $inv_allowance_charge->getVatOrTax(),
            ];
            $invAllowanceCharge = new InvAllowanceCharge();
            $form = new InvAllowanceChargeForm();
            if ($formHydrator->populateAndValidate($form,
                    $copy_inv_allowance_charge)) {
                $this->inv_allowance_charge_service->saveInvAllowanceCharge(
                     $invAllowanceCharge, $copy_inv_allowance_charge);
            }
        }
    }

    private function invToInvInvAmount(int $invId, int $copiedId, InvCopyDeps $d): void
    {
        $original = $d->iaR->repoInvquery($invId);
        if (null !== $original) {
            $array = [];
            $array['inv_id'] = $original->reqInvId();
            $array['item_subtotal'] = $original->getItemSubtotal();
            $array['item_taxtotal'] = $original->getItemTaxTotal();
            $array['packhandleship_tax'] = $original->getPackhandleshipTax();
            $array['packhandleship_total'] = $original->getPackhandleshipTax();
            $array['tax_total'] = $original->getTaxTotal();
            $array['total'] = $original->getTotal();
            $array['paid'] = 0;
            $array['balance'] = $original->getBalance();
            $copied = $d->iaR->repoInvquery($copiedId);
            null !== $copied ?
                $this->inv_amount_service->saveInvAmountViaCalculations(
                        $copied, $array) : '';
        }
    }

    /**
     * Copy an invoice to one or more clients.
     * Sends client_ids[] from the multiselect modal; falls back to a single
     * client_id for backward-compatibility with any existing callers.
     * After each copy, syncs product_client rows for product-based line items.
     */
    public function invToInvConfirm(
        Request $request,
        FormHydrator $formHydrator,
        InvCopyDeps $d,
    ): Response {
        $data_inv_js = $request->getQueryParams();
        $inv_id = (int) $data_inv_js['inv_id'];
        $original = $d->iR->repoInvUnloadedquery($inv_id);
        // Accept client_ids[] (multiselect) or fall back to single client_id
        /** @var int[] $clientIds */
        $clientIds = array_values(array_filter(array_map('intval',
            (array)($data_inv_js['client_ids'] ?? [$data_inv_js['client_id'] ?? '0']))));

        if (null === $original || empty($clientIds)) {
            return null === $original
                ? $this->webService->getNotFoundResponse()
                : $this->factory->createResponse(Json::encode(['success' => 0]));
        }

        // Collect product IDs from the original invoice once (task items are excluded)
        $productIds = [];
        /** @var InvItem $item */
        foreach ($d->iiR->repoInvItemIdquery($inv_id) as $item) {
            $pid = $item->getProductId();
            if ($pid !== null && $pid > 0) {
                $productIds[] = $pid;
            }
        }

        $group_id = $original->reqGroupId();
        $copyCount = 0;
        $lastCopyId = 0;

        foreach ($clientIds as $clientId) {
            $ajax_body = [
                'quote_id'               => null,
                'client_id'              => $clientId,
                'group_id'               => $group_id,
                'status_id'              => $this->sR->getSetting('mark_invoices_sent_copy') === '1' ? 2 : 1,
                'number'                 => $d->gR->generateNumber($group_id),
                'creditinvoice_parent_id'=> null,
                'discount_amount'        => (float) $original->getDiscountAmount(),
                'url_key'                => Random::string(32),
                'password'               => '',
                'payment_method'         => $original->getPaymentMethod(),
                'terms'                  => $original->getTerms(),
                'document_description'   => $original->getDocumentDescription(),
                'note'                   => $original->getNote(),
                'stand_in_code'          => $this->sR->getSetting('stand_in_code'),
            ];

            $copy = new Inv();
            $form = new InvForm();
            if (!$formHydrator->populateAndValidate($form, $ajax_body)) {
                continue;
            }

            $user = $this->activeUser($clientId, $d->uR, $d->ucR, $d->uiR);
            if (null === $user) {
                continue;
            }

            $copy_id = 0;
            $this->inv_service->withTransaction(
                function () use (
                    $user, $copy, $ajax_body, $inv_id, $d, $formHydrator, &$copy_id
                ): void {
                    $this->inv_service->saveInv($user, $copy, $ajax_body, $this->sR, $d->gR);
                    $copy_id = $copy->reqId();
                    if ($copy_id > 0) {
                        $this->invToInvInvItems($inv_id, $copy_id, $d, $formHydrator);
                        $this->invToInvInvTaxRates($inv_id, $copy_id, $d, $formHydrator);
                        $this->invToInvInvCustom($inv_id, $copy_id, $d, $formHydrator);
                        $this->invToInvInvAllowanceCharges($inv_id, $copy_id, $d, $formHydrator);
                        $this->invToInvInvAmount($inv_id, $copy_id, $d);
                        $d->iR->save($copy);
                    }
                }
            );

            if ($copy_id > 0) {
                $copyCount++;
                $lastCopyId = $copy_id;
                if (!empty($productIds)) {
                    $d->pcS->syncFromInvItems($clientId, $productIds);
                }
            }
        }

        if ($copyCount > 0) {
            $this->flashMessage('info', $this->translator->translate('draft.guest'));
            $parameters = ['success' => 1];
            // Only redirect to the new invoice when exactly one client was selected
            if ($copyCount === 1) {
                $parameters['new_invoice_id'] = $lastCopyId;
            }
            return $this->factory->createResponse(Json::encode($parameters));
        }

        return $this->factory->createResponse(Json::encode(['success' => 0]));
    }

    private function invToInvInvCustom(
        int $inv_id,
        int $copy_id,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): void {
        $inv_customs = $d->icR->repoFields($inv_id);
        /**
         * @var InvCustom $inv_custom
         */
        foreach ($inv_customs as $inv_custom) {
            $copyCustom = [
                'inv_id' => $copy_id,
                'custom_field_id' => $inv_custom->reqCustomFieldId(),
                'value' => $inv_custom->getValue(),
            ];
            $invCustom = new InvCustom();
            $form = new InvCustomForm();
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
        int $inv_id,
        int $copy_id,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): void {
        $items = $d->iiR->repoInvItemIdquery($inv_id);
        /**
         * @var InvItem $inv_item
         */
        foreach ($items as $inv_item) {
            $copy_item = [
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
                'tax_rate_id' => $inv_item->reqTaxRateId(),
                'product_id' => $inv_item->getProductId(),
                'product_unit_id' => $inv_item->getProductUnitId(),
                'date' => $inv_item->getDate(),
                'belongs_to_vat_invoice' =>
                    $inv_item->getBelongsToVatInvoice(),
                'delivery_id' => $inv_item->getDeliveryId(),
                'note' => $inv_item->getNote(),
            ];
            $originalInvItemId = $inv_item->reqId();
            {
                $invItem = new InvItem();
                $form = new InvItemForm();
                if ($formHydrator->populateAndValidate($form, $copy_item)) {
                    $productId = (int) $inv_item->getProductId();
                    if ($productId > 0) {
                        $newInvItemId =
                            $this->inv_item_service->addInvItemProduct(
                                $invItem, $copy_item, (string) $copy_id,
                                new IiAddProductDeps($d->pR, $d->trR, $d->iiaS, $d->iiaR, $this->sR, $d->unR));
                        if (null !== $newInvItemId) {
                            $this->inv_item_service->addInvItemAllowanceCharges(
                                (string) $copy_id, $originalInvItemId, $newInvItemId,
                                    $d->aciiR);
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
                                    $d->iiaS,
                                    $d->iiaR
                                );
                            }
                        }
                    }
                    $taskId = (int) $inv_item->getTaskId();
                    if ($taskId > 0) {
                        $newInvItemId = $this->inv_item_service->addInvItemTask(
                            $invItem, $copy_item, (string) $copy_id, $d->taskR, $d->trR, $d->iiaS,
                                $d->iiaR);
                        if (null !== $newInvItemId) {
                            $this->inv_item_service->addInvItemAllowanceCharges(
                            (string) $copy_id, $originalInvItemId, $newInvItemId, $d->aciiR);
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
                                    $d->iiaS,
                                    $d->iiaR
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
                                . (string) $inv_item->reqId());
                    }
                }
            }
        }
    }

    private function invToInvInvTaxRates(
        int $inv_id,
        int $copy_id,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): void {
        $inv_tax_rates = $d->itrR->repoInvquery($inv_id);
        /**
         * @var InvTaxRate $inv_tax_rate
         */
        foreach ($inv_tax_rates as $inv_tax_rate) {
            $copy_tax_rate = [
                'inv_id' => $copy_id,
                'tax_rate_id' => $inv_tax_rate->reqTaxRateId(),
                'include_item_tax' => $inv_tax_rate->getIncludeItemTax(),
                'amount' => $inv_tax_rate->getInvTaxRateAmount(),
            ];
            $invTaxRate = new InvTaxRate();
            $form = new InvTaxRateForm();
            if ($formHydrator->populateAndValidate($form, $copy_tax_rate)) {
                $this->inv_tax_rate_service->saveInvTaxRate(
                    $invTaxRate, $copy_tax_rate);
            }
        }
    }
}
