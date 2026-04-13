<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Entity\{
    CustomField, Inv, InvAllowanceCharge, InvAmount, InvCustom, InvItem,
    InvTaxRate, QuoteCustom, QuoteItem, QuoteTaxRate,
};
use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    Group\GroupRepository as GR,
    Inv\InvForm,
    InvAllowanceCharge\InvAllowanceChargeForm,
    InvAmount\InvAmountForm,
    InvAmount\InvAmountRepository as IAR,
    InvCustom\InvCustomForm,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvItemAmount\InvItemAmountService,
    InvItem\InvItemForm,
    InvTaxRate\InvTaxRateForm,
    InvTaxRate\InvTaxRateRepository as ITRR,
    Product\ProductRepository as PR,
    Quote\QuoteRepository as QR,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    Setting\SettingRepository as SR,
    Task\TaskRepository as TASKR,
    TaxRate\TaxRateRepository as TRR,
    Unit\UnitRepository as UNR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\User\UserRepository as UR;
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
        ACIIR $aciiR,
        ACQIR $acqiR,
        ACQR $acqR,
        CFR $cfR,
        GR $gR,
        IIAR $iiaR,
        InvItemAmountService $iiaS,
        PR $pR,
        TASKR $taskR,
        QAR $qaR,
        QCR $qcR,
        QIR $qiR,
        QIAR $qiaR,
        QR $qR,
        QTRR $qtrR,
        TRR $trR,
        UNR $unR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): Response {
        $body = $request->getQueryParams();
        $quote_id = (string) $body['quote_id'];
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if ($quote) {
            // Check if quote has already been converted to an invoice
            if ($quote->getInvId() !== '0' && $quote->getInvId() !== '') {
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
                'quote_id' => $quote->getId(),
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
            $inv = new Inv();
            $form = new InvForm($inv);
            if ($formHydrator->populateAndValidate($form, $ajax_body)
            ) {
                /**
                 * @var string $ajax_body['client_id']
                 */
                $client_id = $ajax_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    // Only one user account per client
                    $user_id = $user_client->getUserId();
                    $user = $uR->findById($user_id);
                    if (null !== $user) {
                        $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                        if (null !== $user_inv && $user_inv->getActive()) {
                            // Generate number only after validation passes
                            $ajax_body['number'] = (string) $gR->generateNumber((int) $body['group_id']);
                            $this->inv_service->saveInv($user, $inv, $ajax_body,
                                $this->sR, $gR);
                            $inv_id = $inv->getId();
                            if (null !== $inv_id) {
                                // Transfer each quote_item to inv_item and the
                                // corresponding quote_item_amount to
                                // inv_item_amount for each item
                                $this->quoteToInvoiceQuoteItems(
                                    $quote_id, $inv_id, $acqiR, $aciiR, $iiaR,
                                        $iiaS, $pR, $taskR, $qiR, $qiaR,
                                        $trR, $formHydrator, $this->sR, $unR);
                                $this->quoteToInvoiceQuoteTaxRates(
                                    $quote_id, $inv_id, $qtrR, $formHydrator);
                                $this->quoteToInvoiceQuoteCustom(
                                    $quote_id, $inv_id, $qcR, $cfR,
                                        $formHydrator);
                                $this->quoteToInvoiceQuoteAmount(
                                    $quote_id, $inv_id, $qaR, $formHydrator);
                                $this->quoteToInvoiceQuoteAllowanceCharges(
                                    $quote_id, $inv_id, $acqR, $formHydrator);
                                // Update the quotes inv_id.
                                $quote->setInvId($inv_id);
                                $qR->save($quote);
                                // Update the quote amounts after conversion
                                $this->quote_amount_service->updateQuoteAmount(
                                    (int) $quote_id, $qaR, $qiaR, $qtrR,
                                    $this->numberHelper);
                                $parameters = [
                                    'success' => 1,
                                    'flash_message' =>
                                        $this->translator->translate(
                                            'quote.copied.to.invoice'),
                                    'redirect_url' => $this->url_generator->generate('inv/view', ['_language' => (string) $this->session->get('_language'), 'id' => $inv_id]),
                                ];
                                return $this->factory->createResponse(
                                    Json::encode($parameters));
                            } //null!==$inv_id
                        } // null!==$user_inv && $user_inv->getActive()
                    } // null!==$user
                } // null!==$user_client && $user_client_count==1
            } else {
                $parameters = [
                    'success' => 0,
                    'flash_message' => $this->translator->translate(
                        'quote.not.copied.to.invoice'),
                ];
                //return response to quote.js to reload page at location
                return $this->factory->createResponse(
                    Json::encode($parameters));
            }
        } // quote
        return $this->webService->getNotFoundResponse();
    }

    private function quoteToInvoiceQuoteItems(string $quote_id,
        string $inv_id, ACQIR $acqiR, ACIIR $aciiR, IIAR $iiaR,
            InvItemAmountService $iiaS,
        PR $pR, TASKR $taskR, QIR $qiR, QIAR $qiaR, TRR $trR,
            FormHydrator $formHydrator, SR $sR, UNR $unR): void
    {
        // Get all items that belong to the quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $origQuoteItemId = $quote_item->getId();
            $product_id = $quote_item->getProductId() ?: null;
            $task_id = $quote_item->getTaskId() ?: null;
            $inv_item = [
                'inv_id' => $inv_id,
                'tax_rate_id' => $quote_item->getTaxRateId(),
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
                'product_unit_id' => $quote_item->getProductUnitId(),
                // Recurring date
                'date' => '',
            ];
            // Create an equivalent invoice item for the quote item
            $invItem = new InvItem();
            $form = new InvItemForm($invItem, (int) $inv_id);
            if ($formHydrator->populateAndValidate($form, $inv_item)) {
                null!== $product_id && null === $task_id ?
                $this->inv_item_service->addInvItemProduct($invItem, $inv_item,
                    $inv_id, $pR, $trR, $iiaS, $iiaR, $sR, $unR):
                $this->inv_item_service->addInvItemTask($invItem, $inv_item,
                    $inv_id, $taskR, $trR, $iiaS, $iiaR);
                $invItemId = $invItem->getId();
                if (null !== $invItemId) {
                    // Copy the quote item amounts to the invoice item amounts
                    $quoteItemAmount = $qiaR->repoQuoteItemAmountquery(
                        $origQuoteItemId);
                    if (null !== $quoteItemAmount) {
                        $invItemAmount = $iiaR->repoInvItemAmountquery(
                            (string) $invItemId);
                        if (null !== $invItemAmount) {
                            $invItemAmount->setSubtotal(
                                $quoteItemAmount->getSubtotal() ?? 0.00);
                            $invItemAmount->setTaxTotal(
                                $quoteItemAmount->getTaxTotal() ?? 0.00);
                            $invItemAmount->setDiscount(
                                $quoteItemAmount->getDiscount() ?? 0.00);
                            $invItemAmount->setTotal(
                                $quoteItemAmount->getTotal() ?? 0.00);
                            $iiaR->save($invItemAmount);
                        }
                    }
                    $this->inv_item_service->addInvItemAllowanceChargesFromQuote(
                        $inv_id, (int) $origQuoteItemId, $invItemId, $acqiR,
                        $aciiR);
                }
            }
        } // items
    }

    private function quoteToInvoiceQuoteTaxRates(string $quote_id,
        ?string $inv_id, QTRR $qtrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $inv_tax_rate = [
                'inv_id' => (string) $inv_id,
                'tax_rate_id' => $quote_tax_rate->getTaxRateId(),
                'include_item_tax' => $quote_tax_rate->getIncludeItemTax(),
                'inv_tax_rate_amount' =>
                    $quote_tax_rate->getQuoteTaxRateAmount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm($entity);
            if ($formHydrator->populateAndValidate($form, $inv_tax_rate)) {
                $this->inv_tax_rate_service->saveInvTaxRate(
                    $entity, $inv_tax_rate);
            }
        } // foreach
    }

    private function quoteToInvoiceQuoteCustom(
        string $quote_id,
        ?string $inv_id,
        QCR $qcR,
        CFR $cfR,
        FormHydrator $formHydrator,
    ): void {
        $quote_customs = $qcR->repoFields($quote_id);
        // For each quote custom field, build a new custom field for
        // 'inv_custom' using the custom_field_id to find details
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            // For each quote custom field, build a new custom field
            // for 'inv_custom'
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery(
                $quote_custom->getCustomFieldId());
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
                    'custom_field_id' => $custom_field->getId(),
                    'value' => $quote_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm($entity);
                if ($formHydrator->populateAndValidate(
                    $form, $inv_custom)) {
                    $this->inv_custom_service
                         ->saveInvCustom($entity, $inv_custom);
                }
            } // existing_custom_field
        } // foreach
    }

    private function quoteToInvoiceQuoteAmount(string $quote_id,
        ?string $inv_id, QAR $qaR, FormHydrator $formHydrator): void
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
        $form = new InvAmountForm($entity);
        if ($formHydrator->populateAndValidate($form, $inv_amount)) {
            $this->inv_amount_service->saveInvAmount($entity, $inv_amount);
        }
    }

    private function quoteToInvoiceQuoteAllowanceCharges(string $quote_id,
        string $copy_id, ACQR $acqR, FormHydrator $formHydrator): void
    {
        $quote_allowance_charges = $acqR->repoACQquery($quote_id);
        /**
         * @var \App\Invoice\Entity\QuoteAllowanceCharge $quote_allowance_charge
         */
        foreach ($quote_allowance_charges as $quote_allowance_charge) {
            $copy_inv_ac = [
                'inv_id' => $copy_id,
                'allowance_charge_id' =>
                    $quote_allowance_charge->getAllowanceChargeId(),
                'amount' => $quote_allowance_charge->getAmount(),
                'vat_or_tax' => $quote_allowance_charge->getVatOrTax(),
            ];
            $invAllowanceCharge = new InvAllowanceCharge();
            $form = new InvAllowanceChargeForm($invAllowanceCharge,
                (int) $copy_id);
            if ($formHydrator->populateAndValidate($form, $copy_inv_ac)) {
                $this->inv_allowance_charge_service->saveInvAllowanceCharge(
                    $invAllowanceCharge, $copy_inv_ac);
            }
        }
    }
}