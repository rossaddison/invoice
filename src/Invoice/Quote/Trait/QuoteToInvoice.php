<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\{
    CustomField\CustomField,
    InvAllowanceCharge\InvAllowanceCharge,
    InvAmount\InvAmount,
    InvCustom\InvCustom,
    InvItem\InvItem,
    InvTaxRate\InvTaxRate,
    QuoteCustom\QuoteCustom,
    QuoteItem\QuoteItem,
    QuoteTaxRate\QuoteTaxRate
};
use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    Inv\InvForm,
    InvAllowanceCharge\InvAllowanceChargeForm,
    InvAmount\InvAmountForm,
    InvCustom\InvCustomForm,
    InvItem\IiAddProductDeps,
    InvItem\InvItemForm,
    InvTaxRate\InvTaxRateForm,
    Quote\QuoteConvertCoreDeps,
    Quote\QuoteConvertItemDeps,
    Quote\QuoteConvertUserDeps,
    Quote\QuoteToInvTransferDeps,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
};
use Yiisoft\{
    FormModel\FormHydrator,
    Json\Json,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait QuoteToInvoice
{
    // Data fed from quote.js->$(document).on('click',
    // '#quote_to_invoice_confirm', function () {

    public function quoteToInvoiceConfirm(
        Request $request,
        FormHydrator $formHydrator,
        QuoteConvertCoreDeps $core,
        QuoteConvertItemDeps $items,
        QuoteConvertUserDeps $userDeps,
        QuoteToInvTransferDeps $transfer,
    ): Response {
        $body = $request->getQueryParams();
        $quote_id = (int) $body['quote_id'];
        $quote = $core->qR->repoQuoteUnloadedquery($quote_id);
        if (!$quote) {
            return $this->webService->getNotFoundResponse();
        }
        // Check if quote has already been converted to an invoice
        if ($quote->getInvId() !== 0) {
            $parameters = [
                'success' => 0,
                'flash_message' => $this->translator->translate(
                    'quote.invoice.already.created.from.quote'),
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }
        $ajax_body = [
            'client_id' => $body['client_id'],
            'group_id' => $body['group_id'],
            'status_id' => 1,
            'quote_id' => $quote->reqId(),
            'is_read_only' => 0,
            'date_created' =>
                (new \DateTimeImmutable('now'))->format('Y-m-d'),
            'password' => $body['password'] ?? '',
            'number' => '',
            'discount_amount' => (float) $quote->getDiscountAmount(),
            'url_key' => $quote->getUrlKey(),
            'payment_method' => 0,
            'terms' => '',
            'creditinvoice_parent_id' => '',
        ];
        $inv = new \App\Infrastructure\Persistence\Inv\Inv();
        $form = new InvForm();
        $result = null;
        if ($formHydrator->populateAndValidate($form, $ajax_body)) {
            /**
             * @var string $ajax_body['client_id']
             */
            $client_id = (int) $ajax_body['client_id'];
            $user_client = $userDeps->ucR->repoUserquery($client_id);
            $user_client_count = $userDeps->ucR->repoUserquerycount($client_id);
            if (null !== $user_client && $user_client_count == 1) {
                // Only one user account per client
                $user_id = $user_client->reqUserId();
                $user = $userDeps->uR->findById($user_id);
                $user_inv = $userDeps->uiR->repoUserInvUserIdquery($user_id);
                if (null !== $user_inv && $user_inv->getActive()) {
                    // Generate number only after validation passes
                    $ajax_body['number'] =
                        (string) $core->gR->generateNumber((int) $body['group_id']);
                    $this->inv_service->saveInv($user, $inv, $ajax_body,
                        $this->sR, $core->gR);
                    $inv_id = $inv->reqId();
                    // Transfer each quote_item to inv_item and the
                    // corresponding quote_item_amount to
                    // inv_item_amount for each item
                    $this->quoteToInvoiceQuoteItems(
                        $quote_id, $inv_id, $formHydrator,
                        $core, $items, $transfer);
                    $this->quoteToInvoiceQuoteTaxRates(
                        $quote_id, $inv_id, $items->qtrR, $formHydrator);
                    $this->quoteToInvoiceQuoteCustom(
                        $quote_id, $inv_id, $core->qcR,
                        $transfer->cfR, $formHydrator);
                    $this->quoteToInvoiceQuoteAmount(
                        $quote_id, $inv_id, $core->qaR, $formHydrator);
                    $this->quoteToInvoiceQuoteAllowanceCharges(
                        $quote_id, $inv_id, $core->acqR, $formHydrator);
                    // Update the quotes inv_id.
                    $quote->setInvId($inv_id);
                    $core->qR->save($quote);
                    // Update the quote amounts after conversion
                    $this->quote_amount_service->updateQuoteAmount(
                        $quote_id, $core->qaR, $transfer->qiaR, $items->qtrR,
                        $this->numberHelper);
                    $result = [
                        'success' => 1,
                        'flash_message' =>
                            $this->translator->translate(
                                'quote.copied.to.invoice'),
                        'redirect_url' =>
                        $this->url_generator->generate('inv/view',
                                ['_language' =>
                                    (string) $this->session->get('_language'),
                                    'id' => $inv_id]),
                    ];
                } // null!==$user_inv && $user_inv->getActive()
            } // null!==$user_client && $user_client_count==1
        } else {
            $result = [
                'success' => 0,
                'flash_message' => $this->translator->translate(
                    'quote.not.copied.to.invoice'),
            ];
        }
        //return response to quote.js to reload page at location
        return $result !== null
            ? $this->factory->createResponse(Json::encode($result))
            : $this->webService->getNotFoundResponse();
    }

    private function quoteToInvoiceQuoteItems(
        int $quote_id,
        int $inv_id,
        FormHydrator $formHydrator,
        QuoteConvertCoreDeps $core,
        QuoteConvertItemDeps $items,
        QuoteToInvTransferDeps $transfer,
    ): void {
        // Get all items that belong to the quote
        $itemList = $items->qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($itemList as $quote_item) {
            $origQuoteItemId = $quote_item->reqId();
            $product_id = $quote_item->getProduct()?->reqId();
            $task_id = $quote_item->getTask()?->reqId();
            $product_unit_id = null;
            try {
                $product_unit_id = $quote_item->getProductUnitId();
            } catch (\LogicException) {
                // product_unit_id remains null when the item is not persisted
            }
            $inv_item = [
                'inv_id' => $inv_id,
                'tax_rate_id' => $quote_item->getTaxRate()?->reqId(),
                'product_id' => $product_id,
                'task_id' => $task_id,
                'name' => $quote_item->getName(),
                'description' => $quote_item->getDescription(),
                'quantity' => $quote_item->getQuantity(),
                'price' => $quote_item->getPrice(),
                'discount_amount' => $quote_item->getDiscountAmount(),
                'order' => $quote_item->getOrder(),
                'is_recurring' => 0,
                'product_unit' => $quote_item->getProductUnit(),
                'product_unit_id' => $product_unit_id,
                // Recurring date
                'date' => '',
            ];
            // Create an equivalent invoice item for the quote item
            $invItem = new InvItem();
            $form = InvItemForm::show($invItem, $inv_id);
            if ($formHydrator->populateAndValidate($form, $inv_item)) {
                null !== $product_id && null === $task_id ?
                $this->inv_item_service->addInvItemProduct($invItem, $inv_item,
                    (string) $inv_id,
                    new IiAddProductDeps($items->pR, $items->trR, $transfer->iiaS, $transfer->iiaR, $this->sR, $items->unR)):
                $this->inv_item_service->addInvItemTask($invItem, $inv_item,
                    (string) $inv_id, $items->taskR, $items->trR,
                    $transfer->iiaS, $transfer->iiaR);
                $invItemId = $invItem->reqId();
                // Copy the quote item amounts to the invoice item amounts
                $quoteItemAmount = $transfer->qiaR->repoQuoteItemAmountquery(
                    $origQuoteItemId);
                if (null !== $quoteItemAmount) {
                    $invItemAmount = $transfer->iiaR->repoInvItemAmountquery(
                        $invItemId);
                    if (null !== $invItemAmount) {
                        $invItemAmount->setSubtotal(
                            $quoteItemAmount->getSubtotal() ?? 0.00);
                        $invItemAmount->setTaxTotal(
                            $quoteItemAmount->getTaxTotal() ?? 0.00);
                        $invItemAmount->setDiscount(
                            $quoteItemAmount->getDiscount() ?? 0.00);
                        $invItemAmount->setTotal(
                            $quoteItemAmount->getTotal() ?? 0.00);
                        $transfer->iiaR->save($invItemAmount);
                    }
                }
                $this->inv_item_service->addInvItemAllowanceChargesFromQuote(
                    (string) $inv_id, $origQuoteItemId, $invItemId,
                    $core->acqiR, $transfer->aciiR);
            }
        } // items
    }

    private function quoteToInvoiceQuoteTaxRates(int $quote_id,
        int $inv_id, QTRR $qtrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $inv_tax_rate = [
                'inv_id' => $inv_id,
                'tax_rate_id' => $quote_tax_rate->reqTaxRateId(),
                'include_item_tax' => $quote_tax_rate->getIncludeItemTax(),
                'inv_tax_rate_amount' =>
                    $quote_tax_rate->getQuoteTaxRateAmount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm();
            if ($formHydrator->populateAndValidate($form, $inv_tax_rate)) {
                $this->inv_tax_rate_service->saveInvTaxRate(
                    $entity, $inv_tax_rate);
            }
        } // foreach
    }

    private function quoteToInvoiceQuoteCustom(
        int $quote_id,
        int $inv_id,
        QCR $qcR,
        CFR $cfR,
        FormHydrator $formHydrator,
    ): void {
        $quote_customs = $qcR->repoFields($quote_id);
        // For each quote custom field, build a new custom field for
        // 'inv_custom' using the custom_field_id to find details
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery(
                $quote_custom->reqCustomFieldId());
            if ($cfR->repoTableAndLabelCountquery('inv_custom',
                (string) $existing_custom_field->getLabel()) !== 0) {
                // Build an identitcal custom field for the invoice
                $custom_field = new CustomField();
                $custom_field->setTable('inv_custom');
                $custom_field->setLabel((string)
                    $existing_custom_field->getLabel());
                $custom_field->setType(
                    $existing_custom_field->getType());
                $custom_field->setLocation(
                    (int) $existing_custom_field->getLocation());
                $custom_field->setOrder(
                    (int) $existing_custom_field->getOrder());
                $cfR->save($custom_field);
                // Build the inv_custom field record
                $inv_custom = [
                    'inv_id' => $inv_id,
                    'custom_field_id' => $custom_field->reqId(),
                    'value' => $quote_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm();
                if ($formHydrator->populateAndValidate(
                    $form, $inv_custom)) {
                    $this->inv_custom_service
                         ->saveInvCustom($entity, $inv_custom);
                }
            } // existing_custom_field
        } // foreach
    }

    private function quoteToInvoiceQuoteAmount(int $quote_id,
        int $inv_id, QAR $qaR, FormHydrator $formHydrator): void
    {
        $quote_amount = $qaR->repoQuotequery($quote_id);
        $inv_amount = [];
        if ($quote_amount) {
            $inv_amount = [
                'inv_id' => $inv_id,
                'sign' => 1,
                'item_subtotal' => $quote_amount->getItemSubtotal(),
                'item_tax_total' => $quote_amount->getItemTaxTotal(),
                'tax_total' => $quote_amount->getTaxTotal(),
                'total' => $quote_amount->getTotal(),
                'paid' => 0.00,
                'balance' => $quote_amount->getTotal(),
            ];
        }
        $entity = new InvAmount();
        $form = new InvAmountForm();
        if ($formHydrator->populateAndValidate($form, $inv_amount)) {
            $this->inv_amount_service->saveInvAmount($entity, $inv_amount);
        }
    }

    private function quoteToInvoiceQuoteAllowanceCharges(int $quote_id,
        int $copy_id, ACQR $acqR, FormHydrator $formHydrator): void
    {
        $quote_allowance_charges = $acqR->repoACQquery($quote_id);
        /**
         * @var \App\Infrastructure\Persistence\QuoteAllowanceCharge\QuoteAllowanceCharge $quote_allowance_charge
         */
        foreach ($quote_allowance_charges as $quote_allowance_charge) {
            $copy_inv_ac = [
                'inv_id' => $copy_id,
                'allowance_charge_id' =>
                    $quote_allowance_charge->reqAllowanceChargeId(),
                'amount' => $quote_allowance_charge->getAmount(),
                'vat_or_tax' => $quote_allowance_charge->getVatOrTax(),
            ];
            $invAllowanceCharge = new InvAllowanceCharge();
            $form = InvAllowanceChargeForm::show($invAllowanceCharge, $copy_id);
            if ($formHydrator->populateAndValidate($form, $copy_inv_ac)) {
                $this->inv_allowance_charge_service->saveInvAllowanceCharge(
                    $invAllowanceCharge, $copy_inv_ac);
            }
        }
    }
}
