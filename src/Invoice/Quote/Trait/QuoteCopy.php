<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Entity\{
    Quote, QuoteAllowanceCharge, QuoteCustom, QuoteItem,
    QuoteItemAllowanceCharge, QuoteTaxRate,
};
use App\Invoice\{
    Group\GroupRepository as GR,
    Quote\QuoteForm,
    Quote\QuoteRepository as QR,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteAllowanceCharge\QuoteAllowanceChargeForm,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteCustom\QuoteCustomForm,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteItem\QuoteItemForm,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteItemAmount\QuoteItemAmountService as QIAS,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    QuoteTaxRate\QuoteTaxRateForm,
    Product\ProductRepository as PR,
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
    Router\HydratorAttribute\RouteArgument,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait QuoteCopy
{
    // Data fed from quote.js->$(document).on('click',
    // '#quote_to_quote_confirm', function () {
    public function quoteToQuoteConfirm(
        Request $request,
        FormHydrator $formHydrator,
        ACQR $acqR,
        ACQIR $acqiR,
        GR $gR,
        QIAS $qiaS,
        PR $pR,
        TASKR $taskR,
        QAR $qaR,
        QCR $qcR,
        QIAR $qiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
        UNR $unR,
    ): Response {
        $data_quote_js = $request->getQueryParams();
        $quote_id = (string) $data_quote_js['quote_id'];
        $original = $qR->repoQuoteUnloadedquery($quote_id);
        if ($original) {
            $group_id = $original->getGroupId();
            $quote_body = [
                'inv_id' => null,
                'client_id' => $data_quote_js['client_id'],
                'group_id' => $group_id,
                'status_id' => 1,
                'number' => $gR->generateNumber((int) $group_id),
                'discount_amount' => (float) $original->getDiscountAmount(),
                'url_key' => '',
                'password' => '',
                'notes' => '',
            ];
            $copy = new Quote();
            $form = new QuoteForm($copy);
            if ($formHydrator->populateAndValidate($form, $quote_body)) {
                /**
                 * @var string $quote_body['client_id']
                 */
                $client_id = $quote_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    // Only one user account per client
                    $user_id = $user_client->getUserId();
                    $user = $uR->findById($user_id);
                    if (null !== $user) {
                        $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                        if (null !== $user_inv && $user_inv->getActive()) {
                            $this->quote_service->saveQuote($user, $copy,
                                $quote_body, $this->sR, $gR);
                            // Transfer each quote_item to quote_item and the
                            // corresponding quote_item_amount to
                            // quote_item_amount for each item
                            $copy_id = $copy->getId();
                            if (null !== $copy_id) {
                                $this->quoteToQuoteQuoteItems($quote_id,
                                        $copy_id, $acqiR, $qiaR, $qiaS, $pR,
                                        $taskR, $qiR, $trR, $unR, $formHydrator);
                                $this->quoteToQuoteQuoteTaxRates($quote_id,
                                    $copy_id, $qtrR, $formHydrator);
                                $this->quoteToQuoteQuoteCustom($quote_id,
                                    $copy_id, $qcR, $formHydrator);
                                $this->quoteToQuoteQuoteAmount(
                                    (int) $quote_id, (int) $copy_id, $qaR);
                                $this->quoteToQuoteQuoteAllowanceCharges(
                                    $quote_id, $copy_id, $acqR,
                                    $formHydrator);
                                $qR->save($copy);
                                $parameters = [
                                    'success' => 1,
                                    'flash_message' =>
                                        $this->translator->translate(
                                            'quote.copied.to.quote'),
                                ];
                                //return response to quote.js to reload page at
                                //location
                                return $this->factory->createResponse(
                                    Json::encode($parameters));
                            } // null!==$copy_id
                        } // null!==$user_inv && $user_inv->getActive()
                    } // null!== $user
                } // null!==$user_client && $user_client_count==1
            } // $formHydrator->populateAndValidate($form, $body)
        } else {
            $parameters = [
                'success' => 0,
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * Note: When a new Quote is created the Quote Amount is created
     * automatically in the Quote Entity Construct
     * so pass the qaR to find this new Quote Amount
     * Related logic: InvController A)
     */
    private function quoteToQuoteQuoteAmount(int $quoteId, int $copiedId,
        QAR $qaR): void
    {
        $original = $qaR->repoQuotequery((string) $quoteId);
        if (null !== $original) {
            $array = [];
            $array['quote_id'] = (int) $original->getQuoteId();
            $array['item_subtotal'] = $original->getItemSubtotal();
            $array['item_taxtotal'] = $original->getItemTaxTotal();
            $array['packhandleship_total'] =
                $original->getPackhandleshipTotal();
            $array['packhandleship_tax'] =
                $original->getPackhandleshipTax();
            $array['tax_total'] = $original->getTaxTotal();
            $array['total'] = $original->getTotal();
            $copied = $qaR->repoQuotequery((string) $copiedId);
            null !== $copied ?
                $this->quote_amount_service->saveQuoteAmountViaCalculations(
                    $copied, $array) : '';
        }
    }

    private function quoteToQuoteQuoteCustom(string $quote_id,
        ?string $copy_id, QCR $qcR, FormHydrator $formHydrator): void
    {
        $quote_customs = $qcR->repoFields($quote_id);
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            $copy_custom = [
                'quote_id' => $copy_id,
                'custom_field_id' => $quote_custom->getCustomFieldId(),
                'value' => $quote_custom->getValue(),
            ];
            $entity = new QuoteCustom();
            $form = new QuoteCustomForm($entity);
            if ($formHydrator->populateAndValidate($form, $copy_custom)) {
                $this->quote_custom_service->saveQuoteCustom(
                    $entity, $copy_custom);
            }
        }
    }

    private function quoteToQuoteQuoteItems(string $quote_id,
        string $new_quote_id, ACQIR $acqiR, QIAR $qiaR, QIAS $qiaS, PR $pR,
        TASKR $taskR, QIR $qiR, TRR $trR, UNR $unR,
            FormHydrator $formHydrator): void
    {
        // Get all items that belong to the original quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $origQuoteItemId = $quote_item->getId();
            $copy_item = [
                'quote_id' => $new_quote_id,
                'tax_rate_id' => $quote_item->getTaxRateId(),
                'product_id' => $quote_item->getProductId(),
                'task_id' => $quote_item->getTaskId(),
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
            $newQuoteItem = new QuoteItem();
            $form = new QuoteItemForm($newQuoteItem, $new_quote_id);
            if ($formHydrator->populateAndValidate($form, $copy_item)) {
                $this->quote_item_service->addQuoteItemProductTask(
                    $newQuoteItem, $copy_item, $new_quote_id, $pR, $taskR, $qiaR,
                        $qiaS, $unR, $trR, $this->translator);
                // All the original allowance charges associated with the quote
                // item will have to be copied as well
                $this->copyQuoteItemAllowanceCharges($origQuoteItemId,
                    $acqiR, $new_quote_id, $newQuoteItem);
            }

        } // items as quote_item
    }

    private function copyQuoteItemAllowanceCharges(string $origQuoteItemId,
        ACQIR $acqiR, string $new_quote_id, QuoteItem $newQuoteItem): void {
        // Note: QuoteAllowanceCharges are irrelevant here since they relate
        // to the final grand totals and not individual items
        // Both the individual item and the grand total inherit from the
        // AllowanceCharge Entity
        //
        // All AllowanceCharges belonging to the current quote item
        // have to be copied as well

        $all = $acqiR->repoQuoteItemquery($origQuoteItemId);
        /**
         * @var QuoteItemAllowanceCharge $quoteItemAllowanceCharge
         */
        foreach ($all as $quoteItemAllowanceCharge) {
            $acqItem = new QuoteItemAllowanceCharge();

            $acqItem->setQuote($newQuoteItem->getQuote());
            $acqItem->setQuoteItem($newQuoteItem);
            $acqItem->setAllowanceCharge($quoteItemAllowanceCharge->getAllowanceCharge());

            // Also set FK IDs for consistency
            $acqItem->setQuoteId((int) $new_quote_id);
            $acqItem->setQuoteItemId((int) $newQuoteItem->getId());
            $acqItem->setAllowanceChargeId(
                (int) $quoteItemAllowanceCharge->getAllowanceCharge()?->getId()
            );

            // Set other properties
            $acqItem->setAmount((float) $quoteItemAllowanceCharge->getAmount());
            $acqItem->setVatOrTax((float) $quoteItemAllowanceCharge->getVatOrTax() ?: 0.00);

            // Save directly via repository
            $acqiR->save($acqItem);
        }
    }

    private function quoteToQuoteQuoteTaxRates(string $quote_id,
        ?string $copy_id, QTRR $qtrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $copy_tax_rate = [
                'quote_id' => $copy_id,
                'tax_rate_id' =>
                    $quote_tax_rate->getTaxRateId(),
                'include_item_tax' =>
                    $quote_tax_rate->getIncludeItemTax(),
                'quote_tax_rate_amount' =>
                    $quote_tax_rate->getQuoteTaxRateAmount(),
            ];
            $entity = new QuoteTaxRate();
            $form = new QuoteTaxRateForm($entity);
            if ($formHydrator->populateAndValidate($form, $copy_tax_rate)) {
                $this->quote_tax_rate_service->saveQuoteTaxRate($entity,
                    $copy_tax_rate);
            }
        }
    }

    private function quoteToQuoteQuoteAllowanceCharges(string $quote_id,
        string $copy_id, ACQR $acqR, FormHydrator $formHydrator): void
    {
        $quote_allowance_charges = $acqR->repoACQquery($quote_id);
        /**
         * @var QuoteAllowanceCharge $quote_allowance_charge
         */
        foreach ($quote_allowance_charges as $quote_allowance_charge) {
            $copy_quote_allowance_charge = [
                'quote_id' => $copy_id,
                'allowance_charge_id' =>
                    $quote_allowance_charge->getAllowanceChargeId(),
                'amount' => $quote_allowance_charge->getAmount(),
                // vat_or_tax amount worked out by qac_Service
            ];
            $quoteAllowanceCharge = new QuoteAllowanceCharge();
            $form = new QuoteAllowanceChargeForm($quoteAllowanceCharge,
                (int) $copy_id);
            if ($formHydrator->populateAndValidate($form,
                    $copy_quote_allowance_charge)) {
                    $this->qac_Service->saveQuoteAllowanceCharge(
                        $quoteAllowanceCharge, $copy_quote_allowance_charge);
            }
        }
    }
}