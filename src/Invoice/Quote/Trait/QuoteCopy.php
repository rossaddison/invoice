<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\{
    QuoteAllowanceCharge\QuoteAllowanceCharge,
    Quote\Quote, QuoteCustom\QuoteCustom, QuoteItem\QuoteItem,
    QuoteItemAllowanceCharge\QuoteItemAllowanceCharge,
    QuoteTaxRate\QuoteTaxRate
};
use App\Invoice\{
    Quote\QuoteConvertCoreDeps,
    Quote\QuoteConvertItemDeps,
    Quote\QuoteConvertUserDeps,
    Quote\QuoteForm,
    QuoteItem\QiAddProductTaskDeps,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteAllowanceCharge\QuoteAllowanceChargeForm,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteCustom\QuoteCustomForm,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteItemAmount\QuoteItemAmountService as QIAS,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    QuoteTaxRate\QuoteTaxRateForm,
    QuoteItem\QuoteItemForm,
};
use Yiisoft\{
    FormModel\FormHydrator,
    Json\Json,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait QuoteCopy
{
    /**
     * Copy a quote to one or more clients.
     * Accepts client_ids[] from the multiselect modal; falls back to a single
     * client_id for backward-compatibility. Syncs product_client after each copy.
     */
    public function quoteToQuoteConfirm(
        Request $request,
        FormHydrator $formHydrator,
        QuoteConvertCoreDeps $core,
        QuoteConvertItemDeps $items,
        QuoteConvertUserDeps $userDeps,
        QIAS $qiaS,
        QIAR $qiaR,
    ): Response {
        $data_quote_js = $request->getQueryParams();
        $quote_id = (int) $data_quote_js['quote_id'];
        $original = $core->qR->repoQuoteUnloadedquery($quote_id);
        if (null === $original) {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }

        // Accept client_ids[] (multiselect) or fall back to single client_id
        $rawIds = $data_quote_js['client_ids'] ?? [$data_quote_js['client_id'] ?? '0'];
        /** @var int[] $clientIds */
        $clientIds = array_values(array_filter(array_map('intval', (array) $rawIds)));

        if (empty($clientIds)) {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }

        // Collect product IDs from the original quote's items once
        $productIds = [];
        foreach ($items->qiR->repoQuoteItemIdquery($quote_id) as $quoteItem) {
            $pid = $quoteItem->getProduct()?->reqId();
            if ($pid !== null && $pid > 0) {
                $productIds[] = $pid;
            }
        }

        $group_id = $original->reqGroupId();
        $copyCount = 0;

        foreach ($clientIds as $clientId) {
            $quote_body = [
                'inv_id'          => null,
                'client_id'       => $clientId,
                'group_id'        => $group_id,
                'status_id'       => 1,
                'number'          => $core->gR->generateNumber($group_id),
                'discount_amount' => (float) $original->getDiscountAmount(),
                'url_key'         => '',
                'password'        => '',
                'notes'           => '',
            ];

            $copy = new Quote();
            $form = new QuoteForm();
            if (!$formHydrator->populateAndValidate($form, $quote_body)) {
                continue;
            }

            $user_client = $userDeps->ucR->repoUserquery($clientId);
            $user_client_count = $userDeps->ucR->repoUserquerycount($clientId);
            if (null === $user_client || $user_client_count !== 1) {
                continue;
            }

            $user_id = $user_client->reqUserId();
            $user = $userDeps->uR->findById($user_id);
            $user_inv = $userDeps->uiR->repoUserInvUserIdquery($user_id);
            if (null === $user_inv || !$user_inv->getActive()) {
                continue;
            }

            $this->quote_service->saveQuote($user, $copy, $quote_body, $this->sR, $core->gR);
            $copy_id = $copy->reqId();
            $this->quoteToQuoteQuoteItems($quote_id, $copy_id, $qiaR, $qiaS, $core, $items, $formHydrator);
            $this->quoteToQuoteQuoteTaxRates($quote_id, $copy_id, $items->qtrR, $formHydrator);
            $this->quoteToQuoteQuoteCustom($quote_id, $copy_id, $core->qcR, $formHydrator);
            $this->quoteToQuoteQuoteAmount($quote_id, $copy_id, $core->qaR);
            $this->quoteToQuoteQuoteAllowanceCharges($quote_id, $copy_id, $core->acqR, $formHydrator);
            $core->qR->save($copy);

            if (!empty($productIds)) {
                $core->pcS->syncFromInvItems($clientId, $productIds);
            }

            $copyCount++;
        }

        if ($copyCount > 0) {
            return $this->factory->createResponse(Json::encode([
                'success'       => 1,
                'flash_message' => $this->translator->translate('quote.copied.to.quote'),
            ]));
        }

        return $this->factory->createResponse(Json::encode(['success' => 0]));
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
        $original = $qaR->repoQuotequery($quoteId);
        if (null !== $original) {
            $array = [];
            $array['quote_id'] = $original->reqQuoteId();
            $array['item_subtotal'] = $original->getItemSubtotal();
            $array['item_taxtotal'] = $original->getItemTaxTotal();
            $array['packhandleship_total'] =
                $original->getPackhandleshipTotal();
            $array['packhandleship_tax'] =
                $original->getPackhandleshipTax();
            $array['tax_total'] = $original->getTaxTotal();
            $array['total'] = $original->getTotal();
            $copied = $qaR->repoQuotequery($copiedId);
            null !== $copied ?
                $this->quote_amount_service->saveQuoteAmountViaCalculations(
                    $copied, $array) : '';
        }
    }

    private function quoteToQuoteQuoteCustom(int $quote_id,
        int $copy_id, QCR $qcR, FormHydrator $formHydrator): void
    {
        $quote_customs = $qcR->repoFields($quote_id);
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            $copy_custom = [
                'quote_id' => $copy_id,
                'custom_field_id' => $quote_custom->reqCustomFieldId(),
                'value' => $quote_custom->getValue(),
            ];
            $entity = new QuoteCustom();
            $form = new QuoteCustomForm();
            if ($formHydrator->populateAndValidate($form, $copy_custom)) {
                $this->quote_custom_service->saveQuoteCustom(
                    $entity, $copy_custom);
            }
        }
    }

    private function quoteToQuoteQuoteItems(
        int $quote_id,
        int $new_quote_id,
        QIAR $qiaR,
        QIAS $qiaS,
        QuoteConvertCoreDeps $core,
        QuoteConvertItemDeps $items,
        FormHydrator $formHydrator,
    ): void {
        // Get all items that belong to the original quote
        $itemList = $items->qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($itemList as $quote_item) {
            $origQuoteItemId = $quote_item->reqId();
            $product_unit_id = null;
            try {
                $product_unit_id = $quote_item->getProductUnitId();
            } catch (\LogicException) {
                // product_unit_id remains null when the item is not persisted
            }
            $copy_item = [
                'quote_id' => $new_quote_id,
                'tax_rate_id' => $quote_item->getTaxRate()?->reqId(),
                'product_id' => $quote_item->getProduct()?->reqId(),
                'task_id' => $quote_item->getTask()?->reqId(),
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
            $newQuoteItem = new QuoteItem();
            $form = new QuoteItemForm();
            if ($formHydrator->populateAndValidate($form, $copy_item)) {
                $this->quote_item_service->addQuoteItemProductTask(
                    $newQuoteItem, $copy_item, (string) $new_quote_id,
                    new QiAddProductTaskDeps($items->pR, $items->taskR, $qiaR, $qiaS, $items->unR, $items->trR, $this->translator));
                // All the original allowance charges associated with the quote
                // item will have to be copied as well
                $this->copyQuoteItemAllowanceCharges($origQuoteItemId,
                    $core->acqiR, $new_quote_id, $newQuoteItem);
            }

        } // items as quote_item
    }

    private function copyQuoteItemAllowanceCharges(int $origQuoteItemId,
        ACQIR $acqiR, int $new_quote_id, QuoteItem $newQuoteItem): void {
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
            $acqItem->setQuoteId($new_quote_id);
            $acqItem->setQuoteItemId($newQuoteItem->reqId());
            $acqItem->setAllowanceChargeId(
                (int) $quoteItemAllowanceCharge->getAllowanceCharge()?->reqId()
            );

            // Set other properties
            $acqItem->setAmount((float) $quoteItemAllowanceCharge->getAmount());
            $acqItem->setVatOrTax((float) $quoteItemAllowanceCharge->getVatOrTax() ?: 0.00);

            // Save directly via repository
            $acqiR->save($acqItem);
        }
    }

    private function quoteToQuoteQuoteTaxRates(int $quote_id,
        int $copy_id, QTRR $qtrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $copy_tax_rate = [
                'quote_id' => $copy_id,
                'tax_rate_id' =>
                    $quote_tax_rate->reqTaxRateId(),
                'include_item_tax' =>
                    $quote_tax_rate->getIncludeItemTax(),
                'quote_tax_rate_amount' =>
                    $quote_tax_rate->getQuoteTaxRateAmount(),
            ];
            $entity = new QuoteTaxRate();
            $form = new QuoteTaxRateForm();
            if ($formHydrator->populateAndValidate($form, $copy_tax_rate)) {
                $this->quote_tax_rate_service->saveQuoteTaxRate($entity,
                    $copy_tax_rate);
            }
        }
    }

    private function quoteToQuoteQuoteAllowanceCharges(int $quote_id,
        int $copy_id, ACQR $acqR, FormHydrator $formHydrator): void
    {
        $quote_allowance_charges = $acqR->repoACQquery($quote_id);
        /**
         * @var QuoteAllowanceCharge $quote_allowance_charge
         */
        foreach ($quote_allowance_charges as $quote_allowance_charge) {
            $copy_quote_allowance_charge = [
                'quote_id' => $copy_id,
                'allowance_charge_id' =>
                    $quote_allowance_charge->reqAllowanceChargeId(),
                'amount' => $quote_allowance_charge->getAmount(),
                // vat_or_tax amount worked out by qac_Service
            ];
            $quoteAllowanceCharge = new QuoteAllowanceCharge();
            $form = new QuoteAllowanceChargeForm();
            if ($formHydrator->populateAndValidate($form,
                    $copy_quote_allowance_charge)) {
                    $this->qac_Service->saveQuoteAllowanceCharge(
                        $quoteAllowanceCharge, $copy_quote_allowance_charge);
            }
        }
    }
}
