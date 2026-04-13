<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\Setting;

use App\Invoice\{
    ClientPeppol\ClientPeppolRepository as cpR,
    Contract\ContractRepository as ContractRepo,
    Delivery\DeliveryRepository as DelRepo,
    DeliveryParty\DeliveryPartyRepository as DelPartyRepo,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Inv\InvRepository as IR,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    PostalAddress\PostalAddressRepository as paR,
    SalesOrder\SalesOrderRepository as SOR,
    SalesOrderItem\SalesOrderItemRepository as SOIR,
    TaxRate\TaxRateRepository as TRR,
    UnitPeppol\UnitPeppolRepository as unpR,
    Upload\UploadRepository as UPR
};
use App\Invoice\Helpers\{
    Peppol\PeppolHelper, Peppol\PeppolValidator
};
use Yiisoft\{Html\Html, Router\HydratorAttribute\RouteArgument, User\CurrentUser
};
use Psr\Http\Message\ResponseInterface as Response;

trait Peppol
{
    /**
     * Purpose: Generate OpenPeppol Ubl Invoice 3.0.15 XML file to 1. screen
     * or 2. file
     */
    public function peppol(
        #[RouteArgument('id')]
        int $id,
        CurrentUser $currentUser,
        cpR $cpR,
        IAR $iaR,
        IIAR $iiaR,
        IIR $iiR,
        IR $invRepo,
        ContractRepo $contractRepo,
        DelRepo $delRepo,
        DelPartyRepo $delPartyRepo,
        DLR $dlR,
        paR $paR,
        SOR $soR,
        unpR $unpR,
        UPR $upR,
        ACIR $aciR,
        ACIIR $aciiR,
        SOIR $soiR,
        TRR $trR,
    ): Response {
        if ($currentUser->isGuest()) {
            return $this->webService->getNotFoundResponse();
        }
        // Load the inv's HASONE relation 'invAmount'
        if ($id) {
            $invoice = $invRepo->repoInvLoadInvAmountquery((string) $id);
            if ($invoice) {
                $client_id = $invoice->getClient()?->getClientId();
                $delLocId = $invoice->getDeliveryLocationId();
                if (null !== $client_id) {
                    if ($this->peppolClientFullySetup(
                            (string) $client_id, $cpR)) {
                        $delloc = $dlR->repoDeliveryLocationquery($delLocId);
                        if (null !== $delloc) {
                            $inv_amount = $invoice->getInvAmount();
                            $peppolhelper = new PeppolHelper(
                                $this->sR,
                                $delRepo,
                                $inv_amount,
                                $delloc,
                                $this->translator,
                            );
                            $uploads_temp_peppol_absolute_path_dot_xml =
                        $peppolhelper->generateInvoicePeppolUblXmlTempFile(
                                $soR,
                                $invoice,
                                $iaR,
                                $iiaR,
                                $iiR,
                                $contractRepo,
                                $delRepo,
                                $delPartyRepo,
                                $paR,
                                $cpR,
                                $unpR,
                                $upR,
                                $aciR,
                                $aciiR,
                                $soiR,
                                $trR,
                            );
                            $xml = $this->peppolOutput($upR,
                                    $uploads_temp_peppol_absolute_path_dot_xml);
                            $pVal = new PeppolValidator($this->translator);
                            // Not saving to file. Showing in Browser
                            if ($this->sR->getSetting('peppol_xml_stream') == '1') {
                                if (($xml !==false) && (strlen($xml) > 0)) {
                                    if ($this->sR->getSetting(
                               'peppol_debug_with_internal_validator') == '1') {
                                        $pVal->loadXML($xml);
                                        // show the e-invoice if it passes
                                        if ($pVal->validate()) {
                                            return $this->factory->createResponse(
                                           '<pre>'. Html::encode($xml) . '</pre>');
                                        // display all the errors
                                        } else {
                                            $parameters = [
                                                'xmlContent' => $xml,
                                                'errors' =>
                                                    $pVal->getErrors(),
                                            ];
                                            return $this->webViewRenderer->render(
                                                'peppolerrors', $parameters);
                                        }
                                    } else {
                                        $pVal->loadXML($xml);
                                        return $this->factory->createResponse(
                                           '<pre>'. Html::encode($xml) . '</pre>');
                                    }
                                }
                            }
                            /**
                             * Previously: echo $this->peppolOutput($upR,
                             * $uploads_temp_peppol_absolute_path_dot_xml);
                             * Related logic:
                             * see https://cwe.mitre.org/data/definitions/79.html
                             *
                             * Unsanitized input from data from a remote
                             * resource flows into the echo statement, where it
                             * is used to render an HTML page returned to the
                             * user. This may result in a Cross-Site Scripting
                             * attack (XSS). Courtesy of Snyk
                             */
                            $this->flashMessage('info', '📁 ' .
                            $uploads_temp_peppol_absolute_path_dot_xml
                            .   Html::a(' Ecosio Validator',
                    'https://ecosio.com/en/peppol-and-xml-document-validator/',
                                    ['target' => '_blank'])
                            );
                            return $this->webService->getRedirectResponse(
                                'inv/view', ['id' => $id]);
                        } // null!== $delivery_location
                        $this->flashMessage('warning',
                            $this->translator->translate(
                                'delivery.location.peppol.output'));
                    } // client_peppol fully setup
                    $this->flashMessage('warning',
                        'Peppol has not been setup for this client');
                    return $this->webService->getRedirectResponse('client/index');
                } // null!== $client_id
            } // invoice
        } // null !==id
        return $this->webService->getNotFoundResponse();
    }

    /**
     * Purpose: Use the toggle button to convert the Ubl invoice's amounts
     * either to the Sender's currency or the Recipient's currency
     * Settings ... Peppol Electronic Invoicing ... Document Currency changes
     * with each toggle on the View ... options
     * View: resources/views/invoice/inv/view.php Options Dropdown
     */
    public function peppolDocCurrencyToggle(
        #[RouteArgument('id')]
        int $id,
        CurrentUser $currentUser,
    ): Response {
        // Initialize the Peppol Document Currency according to config/common/
        // params.php setting ... DocumentCurrencyCode
        $documentCurrency = $this->sR->getDocumentCurrencyCodeFromPeppolDetails();
        if ($currentUser->isGuest()) {
            return $this->webService->getNotFoundResponse();
        }
        if ($this->sR->repoCount('peppol_doc_currency_toggle') > 0) {
            $record = $this->sR->withKey('peppol_doc_currency_toggle');
            if ($this->sR->getSetting('peppol_doc_currency_toggle') == '1') {
                if ($record instanceof Setting) {
                    $record->setSettingValue('0');
                    $this->sR->save($record);
                    $documentCurrency = $this->sR->getSetting('currency_code_to');
                }
            } else {
                if ($record instanceof Setting) {
                    $record->setSettingValue('1');
                    $this->sR->save($record);
                    $documentCurrency = $this->sR->getSetting('currency_code_from');
                }
            } // else
        } // $this->sR->repoCount
        if ($this->sR->repoCount('peppol_document_currency') > 0) {
            $peppolDocCurrency = $this->sR->withKey('peppol_document_currency');
            if (null !== $peppolDocCurrency) {
                $peppolDocCurrency->setSettingValue($documentCurrency);
                $this->sR->save($peppolDocCurrency);
            }
        } else {
            return $this->webService->getRedirectResponse(
                'setting/tabIndex',
                // Arguments
                ['_language' => 'en'],
                // QueryParameters
                [
                    'active' => $this->translator->translate(
                        'peppol.electronic.invoicing')
                ],
                // Hash String to return to tab_index peppol_document_currency
                // input box for re-entry
                'settings[peppol_document_currency]');
        }
        $this->flashMessage('info',
            $this->translator->translate('peppol.doc.currency.toggle')
                . ' ' . $documentCurrency);
        return $this->webService->getRedirectResponse('inv/view', ['id' => $id]);
    } // peppol document currency toggle

    /**
     * Purpose: Use the toggle button to
     * stream Ubl invoice to screen or alternatively output to file
     *
     * View: resources/views/invoice/inv/view.php
     */
    public function peppolStreamToggle(
        #[RouteArgument('id')]
        int $id,
        CurrentUser $currentUser,
    ): Response {
        if ($currentUser->isGuest()) {
            return $this->webService->getNotFoundResponse();
        }
        if ($this->sR->repoCount('peppol_xml_stream') > 0) {
            $record = $this->sR->withKey('peppol_xml_stream');
            if ($this->sR->getSetting('peppol_xml_stream') === '1') {
                if ($record instanceof Setting) {
                    $record->setSettingValue('0');
                    $this->sR->save($record);
                }
            } else {
                if ($record instanceof Setting) {
                    $record->setSettingValue('1');
                    $this->sR->save($record);
                }
            } // else
        } // $this->sR->repoCount
        $this->flashMessage('info',
            $this->translator->translate('peppol.stream.toggle'));
        return $this->webService->getRedirectResponse('inv/view', ['id' => $id]);
    } // peppol stream toggle
    
    private function peppolClientFullySetup(string $client_id, cpR $cpR): bool
    {
        $passed = false;
        if ($cpR->repoClientCount($client_id) == 1) {
            $cp = $cpR->repoClientPeppolLoadedquery($client_id);
            // check that each individual field has been completed otherwise
            // raise a flash message
            if (null !== $cp) {
                if (empty($cp->getEndpointid())) {
                    $this->flashMessage('warning',
                        '$cp->getEndpointid() '
                            . $cp->getEndpointid());
                }
                if (empty($cp->getEndpointidSchemeid())) {
                    $this->flashMessage('warning',
                        '$cp->getEndpointidSchemeid() '
                            . $cp->getEndpointidSchemeid());
                }
                if (empty($cp->getIdentificationid())) {
                    $this->flashMessage('warning',
                        '$cp->getIdentificationid() '
                            . $cp->getIdentificationid());
                }
                if (empty($cp->getTaxschemecompanyid())) {
                    $this->flashMessage('warning',
                        '$cp->getTaxschemecompanyid() '
                            . $cp->getTaxschemecompanyid());
                }
                if (empty($cp->getTaxschemeid())) {
                    $this->flashMessage('warning',
                        '$cp->getTaxschemeid() '
                            . $cp->getTaxschemeid());
                }
                if (empty($cp->getLegalEntityRegistrationName())) {
                    $this->flashMessage('warning',
                        '$cp->getLegalEntityRegistrationName() '
                            . $cp->getLegalEntityRegistrationName());
                }
                if (empty($cp->getLegalEntityCompanyid())) {
                    $this->flashMessage('warning',
                        '$cp->getLegalEntityCompanyid() '
                            . $cp->getLegalEntityCompanyid());
                }
                if (empty($cp->getLegalEntityCompanyidSchemeid())) {
                    $this->flashMessage('warning',
                        '$cp->getLegalEntityCompanyidSchemeid() '
                            . $cp->getLegalEntityCompanyidSchemeid());
                }
                if (empty($cp->getLegalEntityCompanyLegalForm())) {
                    $this->flashMessage('warning',
                        '$cp->getLegalEntityCompanyLegalForm() '
                            . $cp->getLegalEntityCompanyLegalForm());
                }
                if (empty($cp->getFinancialInstitutionBranchid())) {
                    $this->flashMessage('warning',
                        '$cp->getFinancialInstitutionBranchid() '
                            . $cp->getFinancialInstitutionBranchid());
                }
                if (empty($cp->getAccountingCost())) {
                    $this->flashMessage('warning',
                        '$cp->getAccountingCost() '
                            . $cp->getAccountingCost());
                }

                if (empty($cp->getSupplierAssignedAccountId())) {
                    $this->flashMessage('warning',
                        '$cp->getSupplierAssignedAccountId() '
                            . $cp->getSupplierAssignedAccountId());
                }

                if ($cp->getEndpointid()
                  && $cp->getEndpointidSchemeid()
                  && $cp->getIdentificationid()
                  && $cp->getIdentificationidSchemeid()
                  && $cp->getTaxschemecompanyid()
                  && $cp->getTaxschemeid()
                  && $cp->getLegalEntityRegistrationName()
                  && $cp->getLegalEntityCompanyid()
                  && $cp->getLegalEntityCompanyidSchemeid()
                  && $cp->getLegalEntityCompanyLegalForm()
                  && $cp->getFinancialInstitutionBranchid()
                  && $cp->getAccountingCost()
                  && $cp->getSupplierAssignedAccountId()) {
                    $passed = true;
                } else {
                    $this->flashMessage('warning',
                        $this->translator->translate('peppol.client.check'));
                    $passed = false;
                }
            } // null!==$cp
        } // $cpR->repoClientCount($client_id) == 1
        return $passed;
    }
    
    private function peppolOutput(UPR $upR,
        string $uploads_temp_peppol_absolute_path_dot_xml): false|string
    {
        $path_parts = pathinfo($uploads_temp_peppol_absolute_path_dot_xml);
        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        $file_ext = $path_parts['extension'];
        $original_file_name = $path_parts['filename'];
        if (file_exists($uploads_temp_peppol_absolute_path_dot_xml)) {
            $file_size = filesize($uploads_temp_peppol_absolute_path_dot_xml);
            if ($file_size != false) {
                // xml is included in the getContentTypes allowed array
                $allowed_content_type_array = $upR->getContentTypes();
                // Check current extension against allowed content file types
                // Related logic: see UploadRepository getContentTypes
                $save_ctype = isset($allowed_content_type_array[$file_ext]);
                /** @var string $ctype */
                $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] :
                    $upR->getContentTypeDefaultOctetStream();
    // https://www.php.net/manual/en/function.header.php
    // Remember that header() must be called before any actual
    // output is sent, either by normal HTML tags,
    // blank lines in a file, or from PHP.
    header('Expires: -1');
    header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
    header("Content-Disposition: attachment; filename=\"$original_file_name\"");
    header('Content-Type: ' . $ctype);
    header('Content-Length: ' . (string) $file_size);
                return file_get_contents(
                    $uploads_temp_peppol_absolute_path_dot_xml, true);
            }
        }
        return '';
    }
}
