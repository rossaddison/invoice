<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Setting\Setting;

use App\Invoice\{
    ClientPeppol\ClientPeppolRepository as cpR,
    Inv\InvPeppolChargeDeps,
    Inv\InvPeppolCoreDeps,
    Inv\InvPeppolInvDeps,
    Inv\InvPeppolNetworkDeps,
    Upload\UploadRepository as UPR,
};
use App\Invoice\Helpers\Peppol\{
    PeppolHelper,
    PeppolHelperChargeDeps,
    PeppolHelperInvDeps,
    PeppolHelperNetDeps,
    PeppolValidator,
};
use App\Invoice\Peppol\PeppolSendService;
use Yiisoft\{Html\Html, Router\HydratorAttribute\RouteArgument, User\CurrentUser
};
use Psr\Http\Message\ResponseInterface as Response;

trait Peppol
{
    private const string ROUTE_INV_VIEW = 'inv/view';

    /**
     * Purpose: Generate OpenPeppol Ubl Invoice 3.0.15 XML file to 1. screen
     * or 2. file
     */
    public function peppol(
        #[RouteArgument('id')]
        int $id,
        CurrentUser $currentUser,
        InvPeppolCoreDeps $core,
        InvPeppolNetworkDeps $net,
        InvPeppolChargeDeps $charge,
        InvPeppolInvDeps $inv,
    ): Response {
        $invoice = $id ? $core->invRepo->repoInvLoadInvAmountquery($id) : null;
        $client_id = $invoice?->getClient()?->reqId() ?? 0;
        if ($currentUser->isGuest() || null === $invoice || $client_id <= 0) {
            return $this->webService->getNotFoundResponse();
        }
        $delLocId = $invoice->getDeliveryLocationId();
        $fullySetup = $this->peppolClientFullySetup($client_id, $core->cpR);
        $delloc = $fullySetup
            ? $core->dlR->repoDeliveryLocationquery((int) $delLocId)
            : null;
        if (null === $delloc) {
            if ($fullySetup) {
                $this->flashMessage('warning',
                    $this->translator->translate('delivery.location.peppol.output'));
            }
            $this->flashMessage('warning', 'Peppol has not been setup for this client');
            return $this->webService->getRedirectResponse('client/index');
        }
        // Load the inv's HASONE relation 'invAmount'
        $peppolhelper = new PeppolHelper(
            $this->sR, $net->delRepo, $invoice->getInvAmount(), $delloc, $this->translator);
        $uploads_temp_peppol_absolute_path_dot_xml =
            $peppolhelper->generateInvoicePeppolUblXmlTempFile(
                $invoice,
                new PeppolHelperInvDeps(
                    $core->soR, $inv->iaR, $core->iiaR,
                    $inv->iiR, $core->paR, $core->cpR,
                ),
                new PeppolHelperNetDeps(
                    $net->contractRepo, $net->delRepo,
                    $net->delPartyRepo, $net->unpR, $net->upR,
                ),
                new PeppolHelperChargeDeps(
                    $charge->aciR, $charge->aciiR,
                    $charge->soiR, $charge->trR,
                ),
            );
        $xml = $this->peppolOutput($net->upR, $uploads_temp_peppol_absolute_path_dot_xml);
        return $this->peppolRespond($id, $xml,
            $uploads_temp_peppol_absolute_path_dot_xml, new PeppolValidator($this->translator));
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
        return $this->webService->getRedirectResponse(self::ROUTE_INV_VIEW, ['id' => $id]);
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
        return $this->webService->getRedirectResponse(self::ROUTE_INV_VIEW, ['id' => $id]);
    } // peppol stream toggle
    
    private function peppolClientFullySetup(int $client_id, cpR $cpR): bool
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

    private function peppolStreamOutput(string $xml, PeppolValidator $pVal): Response
    {
        $pVal->loadXML($xml);
        if ($this->sR->getSetting('peppol_debug_with_internal_validator') == '1') {
            if ($pVal->validate()) {
                return $this->webService->getHtmlResponse('<pre>' . Html::encode($xml) . '</pre>');
            }
            return $this->webViewRenderer->render('peppolerrors', [
                'xmlContent' => $xml,
                'errors'     => $pVal->getErrors(),
            ]);
        }
        return $this->factory->createResponse('<pre>' . Html::encode($xml) . '</pre>');
    }

    private function peppolRespond(
        int $id,
        false|string $xml,
        string $uploadsPath,
        PeppolValidator $pVal,
    ): Response {
        if ($this->sR->getSetting('peppol_xml_stream') == '1' && $xml !== false && strlen($xml) > 0) {
            return $this->peppolStreamOutput($xml, $pVal);
        }
        // see https://cwe.mitre.org/data/definitions/79.html — output sanitised via Html::encode in peppolStreamOutput
        $this->flashMessage('info', '📁 ' . $uploadsPath
            . Html::a(' Ecosio Validator',
                'https://ecosio.com/en/peppol-and-xml-document-validator/',
                ['target' => '_blank']));
        return $this->webService->getRedirectResponse(self::ROUTE_INV_VIEW, ['id' => $id]);
    }

    /**
     * Generate UBL XML for an invoice and transmit it to the recipient's
     * Peppol access point via the local Oxalis AS4 gateway.
     *
     * The Peppol participant ID is read from ClientPeppol (scheme:endpoint,
     * e.g. "0088:1234567890123").  A PeppolMessage record is written before
     * and after the HTTP call so every attempt is auditable regardless of
     * outcome.
     */
    public function peppolSend(
        #[RouteArgument('id')]
        int $id,
        CurrentUser $currentUser,
        InvPeppolCoreDeps $core,
        InvPeppolNetworkDeps $net,
        InvPeppolChargeDeps $charge,
        InvPeppolInvDeps $inv,
        PeppolSendService $peppolSendService,
    ): Response {
        $invoice = $id ? $core->invRepo->repoInvLoadInvAmountquery($id) : null;
        if ($currentUser->isGuest() || null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        $client    = $invoice->getClient();
        $client_id = $client?->reqId() ?? 0;

        if ($client_id <= 0) {
            $this->flashMessage('warning',
                $this->translator->translate('peppol.client.check'));
        } elseif ($this->peppolClientFullySetup($client_id, $core->cpR)) {
            $delLocId = $invoice->getDeliveryLocationId();
            $delloc   = $core->dlR->repoDeliveryLocationquery((int) $delLocId);
            if (null === $delloc) {
                $this->flashMessage('warning',
                    $this->translator->translate('delivery.location.peppol.output'));
            } else {
                $peppolhelper = new PeppolHelper(
                    $this->sR,
                    $net->delRepo,
                    $invoice->getInvAmount(),
                    $delloc,
                    $this->translator,
                );
                try {
                    $xmlPath = $peppolhelper->generateInvoicePeppolUblXmlTempFile(
                        $invoice,
                        new PeppolHelperInvDeps(
                            $core->soR, $inv->iaR, $core->iiaR,
                            $inv->iiR, $core->paR, $core->cpR,
                        ),
                        new PeppolHelperNetDeps(
                            $net->contractRepo, $net->delRepo,
                            $net->delPartyRepo, $net->unpR, $net->upR,
                        ),
                        new PeppolHelperChargeDeps(
                            $charge->aciR, $charge->aciiR,
                            $charge->soiR, $charge->trR,
                        ),
                    );
                    $ublXml = file_get_contents($xmlPath);
                    if ($ublXml === false || strlen($ublXml) === 0) {
                        $this->flashMessage('warning',
                            $this->translator->translate('peppol.xml.generation.failed'));
                    } else {
                        $cp = $core->cpR->repoClientPeppolLoadedquery($client_id);
                        if (null === $cp) {
                            $this->flashMessage('warning',
                                $this->translator->translate('peppol.client.check'));
                        } else {
                            $recipientId = $cp->getEndpointidSchemeid() . ':' . $cp->getEndpointid();
                            $message = $peppolSendService->send($id, $ublXml, $recipientId);
                            if ($message->getStatus() === 'SENT') {
                                $this->flashMessage('info',
                                    '📨 ' . $this->translator->translate('sent')
                                    . ' — ' . $this->translator->translate('peppol.message.id')
                                    . ': ' . ($message->getMessageId() ?? ''));
                            } else {
                                $this->flashMessage('warning',
                                    '⚠️ ' . $this->translator->translate('peppol.send.failed')
                                    . ': ' . ($message->getErrorMessage() ?? ''));
                            }
                        }
                    }
                } catch (\RuntimeException $e) {
                    $msg = $e instanceof \Yiisoft\FriendlyException\FriendlyExceptionInterface
                        ? $e->getName()
                        : $e->getMessage();
                    $this->flashMessage('warning', $msg);
                }
            }
        }

        return $this->webService->getRedirectResponse(self::ROUTE_INV_VIEW, ['id' => $id]);
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
            if ($file_size > 0) {
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
