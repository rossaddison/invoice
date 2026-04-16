<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Entity\{
    CustomField, QuoteCustom, QuoteItemAllowanceCharge, QuoteTaxRate,
    SalesOrderAllowanceCharge,
    SalesOrderItem as SoItem,
    SalesOrderItemAllowanceCharge,
    SalesOrderCustom as SoCustom,
    SalesOrderTaxRate as SoTaxRate,
    SalesOrder as SoEntity,
};
use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    Group\GroupRepository as GR,
    Group\Exception\GroupException,
    Quote\QuoteRepository as QR,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    SalesOrder\SalesOrderRepository as SOR,
    SalesOrder\SalesOrderForm as SoForm,
    SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository as ACSOR,
    SalesOrderAllowanceCharge\SalesOrderAllowanceChargeForm,
    SalesOrderCustom\SalesOrderCustomForm as SoCustomForm,
    SalesOrderItem\SalesOrderItemForm as SoItemForm,
    SalesOrderItem\SalesOrderItemService as soIS,
    SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR,
    SalesOrderItemAmount\SalesOrderItemAmountRepository as soIAR,
    SalesOrderItemAmount\SalesOrderItemAmountService as soIAS,
    SalesOrderTaxRate\SalesOrderTaxRateForm as SoTaxRateForm,
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

trait QuoteToSo
{
    public function approve(
        Request $request,
        FormHydrator $formHydrator,
        ACQIR $acqiR,
        ACQR $acqR,
        ACSOIR $acsoiR,
        CFR $cfR,
        GR $gR,
        soIAS $soiaS,
        PR $pR,
        TASKR $taskR,
        QAR $qaR,
        QCR $qcR,
        soIAR $soiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        SOR $soR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
        UNR $unR,
    ): Response {
        $body = $request->getQueryParams();
        $url_key = (string) $body['url_key'];
        $purchase_order_number = (string) $body['client_po_number'];
        $purchase_order_person = (string) $body['client_po_person'];
        if (!empty($url_key)) {
            if ($qR->repoUrlKeyGuestCount($url_key) > 0) {
                $quote = $qR->repoUrlKeyGuestLoaded($url_key);
                // default_invoice_group 1,
                // default_quote_group 2,
                // default_sales_order_group 3, default_
                $number = $gR->generateNumber(
                    (int) $this->sR->getSetting(
                        'default_sales_order_group'), true);
                if (null !== $number) {
                    if ($quote && null !== $quote->getId()) {
                        $quote_id = $quote->getId();
                        $so_body = [
                            'quote_id' => $quote_id,
                            'inv_id' => 0,
                            'client_id' => $quote->getClientId(),
                            'group_id' => (int) $this->sR->getSetting(
                                'default_sales_order_group'),
                            'status_id' => 4,
                            'client_po_number' => $purchase_order_number,
                            'client_po_person' => $purchase_order_person,
                            'number' => $number,
                            'discount_amount' =>
                                (float) $quote->getDiscountAmount(),
                            // The quote's url will be the same for the
                            // po allowing for a trace
                            'url_key' => $quote->getUrlKey(),
                            'password' => $quote->getPassword() ?? '',
                            'notes' => $quote->getNotes(),
                        ];
                        $this->flashMessage('info',
                            $this->translator->translate(
                                'salesorder.agree.to.terms'));
                        $new_so = new SoEntity();
                        $form = new SoForm($new_so);
                        if ($formHydrator->populateAndValidate($form, $so_body)
                            && ($quote->getSoId() === (string) 0)) {
                            $quote_id = $so_body['quote_id'];
                            $client_id = $so_body['client_id'];
                            $user = $this->activeUser(
                                $client_id, $uR, $ucR, $uiR);
                            if (null !== $user) {
                                $this->so_service->addSo(
                                    $user, $new_so, $so_body);
                                // Ensure that the quote has a specific po and
                                // therefore cannot be copied again.
                                $new_so_id = $new_so->getId();
                                // Transfer each quote_item to so_item and the
                                // corresponding so_item_amount to
                                // so_item_amount for each item
                                if (null !== $new_so_id && null !== $quote_id) {
                                    $this->quoteToSoQuoteItems(
                                        $quote_id, $new_so_id, $acqiR, $acsoiR, $soiaR,
                                        $soiaS, $pR, $taskR, $qiR, $trR, $unR,
                                        $formHydrator);
                                    $this->quoteToSoQuoteTaxRates(
                                        $quote_id, $new_so_id, $qtrR,
                                            $formHydrator);
                                    $this->quoteToSoQuoteCustom($quote_id,
                                        $new_so_id, $qcR, $cfR, $formHydrator);
                                    $this->quoteToSoQuoteAmount($quote_id,
                                        $new_so_id, $qaR, $soR);
                                    $this->quoteToSoQuoteAllowanceCharges($quote_id,
                                $new_so_id, $acqR, $formHydrator);
                                    // Set the quote's sales order id so that
                                    // it cannot be copied in the future
                                    $quote->setSoId($new_so_id);
                                    // The quote has been approved with purchase
                                    // order number
                                    $quote->setStatusId(4);
                                    $qR->save($quote);
                                    $parameters = ['success' => 1];
                                    //return response to quote.js to reload page
                                    //at location
                                    return $this->factory->createResponse(
                                        Json::encode($parameters));
                                } // null!==$new_so_id
                            } // null!==$user
                        } else {
                            $parameters = [
                                'success' => 0,
                            ];
                            //return response to quote.js to reload page at
                            //location
                            return $this->factory->createResponse(
                                Json::encode($parameters));
                        }
                    } // quote
                    return $this->webService->getNotFoundResponse();
                }
                throw new GroupException($this->translator);
            } // if $qR
            return $this->webService->getNotFoundResponse();
        } // null!==$url_key
        return $this->webService->getNotFoundResponse();
    } // approve_with

    public function reject(#[RouteArgument('url_key')] string $url_key, QR $qR,
            UCR $ucR, UIR $uiR):
        Response
    {
        if ($url_key) {
            if ($qR->repoUrlKeyGuestCount($url_key) > 0) {
                $quote = $qR->repoUrlKeyGuestLoaded($url_key);
                if ($quote) {
                    $quote_id = $quote->getId();
                    if ($this->rbacObserver($quote, $ucR, $uiR)) {
                        $quote->setStatusId(5);
                        $qR->save($quote);
                        return $this->factory->createResponse(
                            $this->webViewRenderer->renderPartialAsString(
                            '//invoice/setting/quote_successful',
                            ['heading' => $this->translator->translate(
                                'record.successfully.updated'),'url' =>
                                'quote/view','id' => $quote_id],
                        ));
                    }
                }
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    public function quoteToSoConfirm(
        Request $request,
        FormHydrator $formHydrator,
        ACQIR $acqiR,
        ACQR $acqR,
        ACSOIR $acsoiR,
        CFR $cfR,
        GR $gR,
        soIAS $soiaS,
        PR $pR,
        TASKR $taskR,
        QAR $qaR,
        QCR $qcR,
        soIAR $soiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        SOR $soR,
        TRR $trR,
        UNR $unR,
        UCR $ucR,
        UR $uR,
    ): Response {
        $body = $request->getQueryParams();
        $quote_id = (string) $body['quote_id'];
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if ($quote) {
            // Check if quote has already been converted to a sales order
            if ($quote->getSoId() !== '0' && $quote->getSoId() !== '') {
                $parameters = [
                    'success' => 0,
                    'flash_message' => $this->translator->translate(
                        'quote.sales.order.already.created.from.quote'),
                ];
                return $this->factory->createResponse(Json::encode($parameters));
            }
            $so_body = [
                'quote_id' => $quote_id,
                'inv_id' => null,
                'client_id' => $body['client_id'],
                'group_id' => $body['group_id'],
                'client_po_number' => $body['po_number'],
                'client_po_person' => $body['po_person'],
                'status_id' => 1,
                'number' => '',
                'discount_amount' => (float) $quote->getDiscountAmount(),
                // The quote's url will be the same for the so allowing
                // for a trace
                'url_key' => $quote->getUrlKey(),
                'password' => $body['password'] ?? '',
                'notes' => '',
            ];
            $new_so = new SoEntity();
            $form = new SoForm($new_so);
            if ($formHydrator->populateAndValidate($form, $so_body)) {
                /**
                 * @var string $so_body['client_id']
                 */
                $client_id = $so_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    // Only one user account per client
                    $user_id = $user_client->getUserId();
                    $user = $uR->findById($user_id);
                    if (null !== $user) {
                        // Generate number only after validation passes
                        $so_body['number'] =
                                (string) $gR->generateNumber(
                                    (int) $body['group_id'], true);
                        $so = $this->so_service->addSo($user, $new_so, $so_body);
                        $new_so_id = $so->getId();
                        // Ensure that the quote has a specific po and therefore
                        // cannot be copied again.
                        // Transfer each quote_item to so_item and the
                        // corresponding so_item_amount to so_item_amount
                        // for each item
                        if (null !== $new_so_id) {
                            $this->quoteToSoQuoteItems($quote_id,
                                $new_so_id, $acqiR, $acsoiR, $soiaR, $soiaS, $pR,
                                    $taskR, $qiR, $trR, $unR, $formHydrator);
                            $this->quoteToSoQuoteTaxRates($quote_id,
                                $new_so_id, $qtrR, $formHydrator);
                            $this->quoteToSoQuoteCustom($quote_id,
                                $new_so_id, $qcR, $cfR, $formHydrator);
                            $this->quoteToSoQuoteAmount($quote_id,
                                $new_so_id, $qaR, $soR);
                            $this->quoteToSoQuoteAllowanceCharges($quote_id,
                                $new_so_id, $acqR, $formHydrator);
                            // Set the quote's sales order id so that it
                            // cannot be copied in the future
                            $quote->setSoId($new_so_id);
                            $qR->save($quote);
                            $parameters = [
                                'success' => 1,
                                'flash_message' => $this->translator->translate(
                                    'quote.sales.order.created.from.quote'),
                                'redirect_url' => $this->url_generator->generate(
                                    'salesorder/view',
                                        ['_language' =>
                                            (string) $this->session->get(
                                                    '_language'),
                                            'id' => $new_so_id]),
                            ];
                            //return response to quote.js to reload page at
                            //location
                            return $this->factory->createResponse(
                                Json::encode($parameters));
                        } // null!==$new_so_id
                    }  // null!==$user
                } // null!==$user_client && $user_client_count==1
            } else {
                $parameters = [
                    'success' => 0,
                    'flash_message' => $this->translator->translate(
                        'quote.sales.order.not.created.from.quote'),
                ];
                //return response to quote.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));
            }
        } // original
        return $this->webService->getNotFoundResponse();
    }

    private function quoteToSoQuoteTaxRates(
        string $quote_id, ?string $so_id, QTRR $qtrR,
            FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $so_tax_rate = [
                'sales_order_id' => (string) $so_id,
                'tax_rate_id' =>
                    $quote_tax_rate->getTaxRateId(),
                'include_item_tax' =>
                    $quote_tax_rate->getIncludeItemTax(),
                'sales_order_tax_rate_amount' =>
                    $quote_tax_rate->getQuoteTaxRateAmount(),
            ];
            $entity = new SoTaxRate();
            $form = new SoTaxRateForm($entity);
            if ($formHydrator->populateAndValidate($form, $so_tax_rate)) {
                $this->so_tax_rate_service->saveSoTaxRate(
                    $entity, $so_tax_rate);
            }
        } // foreach
    }

    private function quoteToSoQuoteCustom(
        string $quote_id,
        ?string $so_id,
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
            // for 'so_custom'
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery(
                $quote_custom->getCustomFieldId());
            if ($cfR->repoTableAndLabelCountquery(
                'inv_custom', (string) $existing_custom_field->getLabel()
                ) !== 0) {
                // Build an identitcal custom field for the po
                $custom_field = new CustomField();
                $custom_field->setTable('so_custom');
                $custom_field->setLabel(
                    (string) $existing_custom_field->getLabel());
                $custom_field->setType($existing_custom_field->getType());
                $custom_field->setLocation(
                    (int) $existing_custom_field->getLocation());
                $custom_field->setOrder(
                    (int) $existing_custom_field->getOrder());
                $cfR->save($custom_field);
                // Build the so_custom field record
                $so_custom = [
                    'so_id' => $so_id,
                    'custom_field_id' => $custom_field->getId(),
                    'value' => $quote_custom->getValue(),
                ];
                $entity = new SoCustom();
                $form = new SoCustomForm($entity);
                if ($formHydrator->populateAndValidate($form, $so_custom)) {
                    $this->so_custom_service->saveSoCustom($entity, $so_custom);
                }
            }   // existing_custom_field
        } // foreach
    }

    private function quoteToSoQuoteAmount(string $quote_id,
        string $copy_id, QAR $qaR, SOR $soR): void
    {
        $basis_quote = $qaR->repoQuotequery($quote_id);
        $newSo = $soR->repoSalesOrderUnLoadedquery($copy_id);
        if (null!==$newSo && null!==$basis_quote) {
            // use the hasOne Relationship to retrieve the Sales Order Amount
            // relation
            $soA = $newSo->getSalesOrderAmount();
            // hydrate
            $soA->setSalesOrderId((int) $copy_id);
            $soA->setItemSubtotal(
                $basis_quote->getItemSubtotal() ?? 0.00);
            $soA->setItemTaxTotal(
                $basis_quote->getItemTaxTotal() ?? 0.00);
            $soA->setPackhandleshipTotal(
                $basis_quote->getPackhandleshipTotal() ?: 0.00);
            $soA->setPackhandleshipTax(
                $basis_quote->getPackhandleshipTax() ?: 0.00);
            $soA->setTaxTotal(
                $basis_quote->getTaxTotal() ?? 0.00);
            $soA->setTotal(
                $basis_quote->getTotal() ?? 0.00);
        }
        $soR->save($newSo);
    }

    private function quoteToSoQuoteItems(string $quote_id, string $new_so_id,
        ACQIR $acqiR, ACSOIR $acsoiR, soIAR $soiaR, soIAS $soiaS, PR $pR,
        TASKR $taskR, QIR $qiR, TRR $trR, UNR $unR, FormHydrator $formHydrator):
        void
    {
        // Note: The $soiaR variable will be used to see if there are
        // pre-existing amounts later towards the end of this function
        // Get all items that belong to the original quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var \App\Invoice\Entity\QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $origQuoteItemId = $quote_item->getId();
            $newSoItem = new SoItem();
            $so_item = [
                'sales_order_id' => $new_so_id,
                'tax_rate_id' => $quote_item->getTaxRateId(),
                'product_id' => $quote_item->getProductId(),
                'task_id' => $quote_item->getTaskId(),
                'product_unit' => $quote_item->getProductUnit(),
                'product_unit_id' => $quote_item->getProductUnitId(),
                'peppol_po_itemid' => '',
                'peppol_po_lineid' => '',
                'name' => $quote_item->getName(),
                'description' => $quote_item->getDescription(),
                'quantity' => $quote_item->getQuantity(),
                'price' => $quote_item->getPrice(),
                'discount_amount' => $quote_item->getDiscountAmount(),
                'order' => $quote_item->getOrder(),
                'date_added' => new \DateTimeImmutable(),
            ];
            $form = new SoItemForm($newSoItem, $new_so_id);
            if ($formHydrator->populateAndValidate($form, $so_item)) {
                // Save the SO item without calculating amounts yet
                $this->so_item_service->addSoItemProductTask($newSoItem, $so_item, $new_so_id,
                        $pR, $taskR, $unR, $this->translator);

                // Copy allowances/charges from quote item to sales order item
                $this->copyQuoteItemAllowanceChargesToSo(
                        $origQuoteItemId, $acqiR, $new_so_id,
                        $newSoItem, $acsoiR);

                // Now calculate amounts INCLUDING the allowances/charges
                $tax_rate_percentage = $this->so_item_service->taxratePercentage(
                        (int) $so_item['tax_rate_id'], $trR);
                if (isset($so_item['quantity'], $so_item['price'],
                    $so_item['discount_amount'])
                    && null !== $tax_rate_percentage
                ) {
                    $this->so_item_service->saveSalesOrderItemAmount(
                        (int) $newSoItem->getId(),
                        $so_item['quantity'],
                        $so_item['price'],
                        $so_item['discount_amount'],
                        $tax_rate_percentage,
                        $soiaR,
                        $soiaS
                    );
                }
            }
        } // items as quote_item
    }

    private function copyQuoteItemAllowanceChargesToSo(
        string $origQuoteItemId, ACQIR $acqiR, string $new_so_id,
            SoItem $newSalesOrderItem, ACSOIR $acsoiR): void {

        $all = $acqiR->repoQuoteItemquery($origQuoteItemId);
        /**
         * @var QuoteItemAllowanceCharge $quoteItemAllowanceCharge
         */
        foreach ($all as $quoteItemAllowanceCharge) {
            $acsoItem = new SalesOrderItemAllowanceCharge();

            $acsoItem->setSalesOrder($newSalesOrderItem->getSalesOrder());
            $acsoItem->setSalesOrderItem($newSalesOrderItem);
            $acsoItem->setAllowanceCharge(
                    $quoteItemAllowanceCharge->getAllowanceCharge());

            $acsoItem->setSalesOrderId((int) $new_so_id);
            $acsoItem->setSalesOrderItemId((int) $newSalesOrderItem->getId());
            $acsoItem->setAllowanceChargeId(
                (int) $quoteItemAllowanceCharge->getAllowanceCharge()?->reqId()
            );

            $acsoItem->setAmount((float) $quoteItemAllowanceCharge->getAmount());
            $acsoItem->setVatOrTax((float) $quoteItemAllowanceCharge->getVatOrTax()
                    ?: 0.00);

            $acsoiR->save($acsoItem);
        }
    }

    private function quoteToSoQuoteAllowanceCharges(string $quote_id,
        string $new_so_id, ACQR $acqR, FormHydrator $formHydrator): void
    {
        $quote_allowance_charges = $acqR->repoACQquery($quote_id);
        /**
         * @var \App\Invoice\Entity\QuoteAllowanceCharge $quote_allowance_charge
         */
        foreach ($quote_allowance_charges as $quote_allowance_charge) {
            $new_so_ac = [
                'sales_order_id' => $new_so_id,
                'allowance_charge_id' =>
                    $quote_allowance_charge->getAllowanceChargeId(),
                'amount' => $quote_allowance_charge->getAmount(),
            ];
            $salesOrderAllowanceCharge = new SalesOrderAllowanceCharge();
            $form = new SalesOrderAllowanceChargeForm($salesOrderAllowanceCharge,
                (int) $new_so_id);
            if ($formHydrator->populateAndValidate($form,
                $new_so_ac)) {
                $this->soac_service->saveSalesOrderAllowanceCharge(
                    $salesOrderAllowanceCharge, $new_so_ac
                );
            }
        }
    }
}