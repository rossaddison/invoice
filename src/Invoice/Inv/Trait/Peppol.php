<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Inv\Inv;
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
use App\Invoice\Helpers\Peppol\Exception\PeppolBuyerPostalAddressNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolTaxCategoryCodeNotFoundException;
use App\Invoice\Helpers\Peppol\Validator\ChecksumValidator;
use App\Invoice\Peppol\PeppolSendServiceInterface;
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
        $resolved = $this->resolveInvoiceAndDeliveryLocation($id, $currentUser, $core);
        if ($resolved instanceof Response) {
            return $resolved;
        }
        [$invoice, $delloc] = $resolved;

        // Load the inv's HASONE relation 'invAmount'
        $peppolhelper = new PeppolHelper(
            $this->sR, $net->delRepo, $invoice->getInvAmount(), $delloc, $this->translator, $core->gR);
        try {
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
        } catch (\Throwable $e) {
            // Same reasoning as transmitPeppolDocument()'s catch: without
            // this, any failure here (e.g. a tax rate missing its Peppol
            // Tax Category code) skipped straight past a flash message to
            // Yii3's generic error page whenever YII_DEBUG wasn't on.
            $this->flashMessage('warning', $this->friendlyPeppolExceptionMessage($e));
            return $this->webService->getRedirectResponse(self::ROUTE_INV_VIEW, ['id' => $id]);
        }
        $xml = $this->peppolOutput($net->upR, $uploads_temp_peppol_absolute_path_dot_xml);
        return $this->peppolRespond($id, $xml,
            $uploads_temp_peppol_absolute_path_dot_xml, new PeppolValidator($this->translator));
    }

    /**
     * Resolves the invoice + delivery location peppol() needs, or produces
     * the Response peppol() should return immediately when either can't
     * be resolved — split out so peppol() itself doesn't carry these two
     * guard-clause returns on top of its own catch/success returns
     * (Sonar S1142: max 3 returns per method).
     *
     * @return Response|array{0: Inv, 1: DeliveryLocation}
     */
    private function resolveInvoiceAndDeliveryLocation(
        int $id,
        CurrentUser $currentUser,
        InvPeppolCoreDeps $core,
    ): Response|array {
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
                // Setup itself is fine — the only problem is the missing
                // delivery location, so that's the only message needed.
                $this->flashDeliveryLocationMissing($id);
            } elseif ($core->cpR->repoClientCount($client_id) == 0) {
                // No ClientPeppol record at all — peppolClientFullySetup()
                // returned false without validateClientPeppolSetup() ever
                // running, so nothing else has flashed a message yet.
                $this->flashClientPeppolMissing($client_id);
            }
            // else: peppolClientFullySetup() already flashed a detailed,
            // deep-linked message via validateClientPeppolSetup() — no
            // need to also flash a generic "not set up" message on top.
            return $this->webService->getRedirectResponse('client/index');
        }
        return [$invoice, $delloc];
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
        if ($cpR->repoClientCount($client_id) == 1) {
            $cp = $cpR->repoClientPeppolLoadedquery($client_id);
            if (null !== $cp) {
                return $this->validateClientPeppolSetup($cp);
            }
        }
        return false;
    }

    /**
     * Flashes a link straight to `clientpeppol/add/{client_id}` — for the
     * "no ClientPeppol record exists at all yet" case, which
     * `validateClientPeppolSetup()` never runs for (there's no record to
     * inspect field-by-field), so nothing else flashes a message here on
     * its own.
     */
    private function flashClientPeppolMissing(int $client_id): void
    {
        $url = $this->webService->generateUrl(
            'clientpeppol/add', ['client_id' => $client_id]);
        $this->flashMessage('warning',
            $this->translator->translate('peppol.client.check.missing')
            . ' ' . Html::a(
                $this->translator->translate('client.peppol.add'),
                $url)->render());
    }

    /**
     * Flashes a link straight to the invoice's own Delivery Location
     * field (`inv/edit/{id}#delivery_location_id`) instead of just saying
     * one is needed and leaving the user to find that field themselves.
     */
    private function flashDeliveryLocationMissing(int $invId): void
    {
        $url = $this->webService->generateUrl(
            'inv/edit', ['id' => $invId], [], 'delivery_location_id');
        $this->flashMessage('warning',
            $this->translator->translate('delivery.location.peppol.output')
            . ' ' . Html::a(
                $this->translator->translate('delivery.location.peppol.output.fix'),
                $url)->render());
    }

    /**
     * Every required ClientPeppol field, keyed by the property name — which
     * is also that field's HTML id in resources/views/invoice/clientpeppol/
     * _form.php (both add and edit share the one template), so it doubles
     * as the URL fragment that scrolls straight to it.
     *
     * @return array<string, string> property => translation key for its label
     */
    private function clientPeppolRequiredFields(): array
    {
        return [
            'endpointid' => 'client.peppol.endpointid',
            'endpointid_schemeid' => 'client.peppol.endpointid.schemeid',
            'identificationid' => 'client.peppol.identificationid',
            'identificationid_schemeid' => 'client.peppol.identificationid.schemeid',
            'taxschemecompanyid' => 'client.peppol.taxschemecompanyid',
            'taxschemeid' => 'client.peppol.taxschemeid',
            'legal_entity_registration_name' => 'client.peppol.legal.entity.registration.name',
            'legal_entity_companyid' => 'client.peppol.legal.entity.companyid',
            'legal_entity_companyid_schemeid' => 'client.peppol.legal.entity.companyid.schemeid',
            'legal_entity_company_legal_form' => 'client.peppol.legal.entity.company.legal.form',
            'financial_institution_branchid' => 'client.peppol.financial.institution.branchid',
            'accounting_cost' => 'client.peppol.accounting.cost',
            'supplier_assigned_accountid' => 'client.peppol.supplier.assigned.account.id',
        ];
    }

    /**
     * Instead of a wall of raw `$cp->getX() ` debug flashes (or a single
     * "something's missing, go look" message) — one flash with a bullet
     * list naming exactly which fields are empty, each linking straight to
     * that field on the client's Peppol edit form via a URL fragment. No
     * more hunting through Client > Options > Edit Peppol details by hand.
     */
    private function validateClientPeppolSetup(ClientPeppol $cp): bool
    {
        /** @var array<string, string|null> $values property => current value */
        $values = [
            'endpointid' => $cp->getEndpointid(),
            'endpointid_schemeid' => $cp->getEndpointidSchemeid(),
            'identificationid' => $cp->getIdentificationid(),
            'identificationid_schemeid' => $cp->getIdentificationidSchemeid(),
            'taxschemecompanyid' => $cp->getTaxschemecompanyid(),
            'taxschemeid' => $cp->getTaxschemeid(),
            'legal_entity_registration_name' => $cp->getLegalEntityRegistrationName(),
            'legal_entity_companyid' => $cp->getLegalEntityCompanyid(),
            'legal_entity_companyid_schemeid' => $cp->getLegalEntityCompanyidSchemeid(),
            'legal_entity_company_legal_form' => $cp->getLegalEntityCompanyLegalForm(),
            'financial_institution_branchid' => $cp->getFinancialInstitutionBranchid(),
            'accounting_cost' => $cp->getAccountingCost(),
            'supplier_assigned_accountid' => $cp->getSupplierAssignedAccountId(),
        ];

        $problemLinks = [];
        foreach ($this->clientPeppolRequiredFields() as $property => $labelKey) {
            $value = $values[$property];
            if (null !== $value && $value !== '') {
                continue;
            }
            $problemLinks[] = $this->clientPeppolFieldLink(
                $cp, $property, $this->translator->translate($labelKey));
        }

        $endpointFormatProblem = $this->endpointidFormatProblem(
            $values['endpointid'], $values['endpointid_schemeid']);
        if (null !== $endpointFormatProblem) {
            $problemLinks[] = $this->clientPeppolFieldLink(
                $cp, 'endpointid',
                $this->translator->translate('client.peppol.endpointid')
                . ' — ' . $endpointFormatProblem);
        }

        if ($problemLinks === []) {
            return true;
        }

        $this->flashMessage('warning',
            $this->translator->translate('peppol.client.check')
            . Html::ul()->items(...$problemLinks)->render());
        return false;
    }

    private function clientPeppolFieldLink(ClientPeppol $cp, string $property, string $label): \Yiisoft\Html\Tag\Li
    {
        $url = $this->webService->generateUrl(
            'clientpeppol/edit',
            ['client_id' => $cp->reqClientId()],
            [],
            $property,
        );
        return Html::li(Html::a($label, $url));
    }

    /**
     * Checksum function per scheme, for the schemes
     * App\Invoice\Helpers\Peppol\Validator\EndpointSchemeValidator already
     * enforces as fatal UBL business rules (PEPPOL-COMMON-R040/R041/R043/
     * R049/R050) — kept in sync with that class's own scheme list
     * deliberately, rather than duplicating its DOM-based logic here.
     *
     * @return array<string, callable(string): bool>
     */
    private function endpointSchemeChecksums(): array
    {
        return [
            '0088' => ChecksumValidator::checkGLN(...),
            '0192' => ChecksumValidator::checkMod11(...),
            '0208' => ChecksumValidator::checkMod97BE(...),
            '0007' => ChecksumValidator::checkSEOrgnr(...),
            '0151' => ChecksumValidator::checkABN(...),
        ];
    }

    /**
     * Catches the exact bug class this session kept hitting live: an
     * Endpoint ID copy-pasted from a sample/demo file (almost always an
     * email address, e.g. 'joe.bloggs@web.com') paired with a scheme that
     * requires a numeric business identifier — Storecove then rejects it
     * with a raw regex-mismatch 422 (e.g. scheme 0106 KVK needs ^\d{6,9}$,
     * scheme 0192 Norway org number needs ^\d{9}$). No real Peppol EAS/ICD
     * scheme in DownloadedXml/eas.xml uses an email-address format, so an
     * '@' is flagged regardless of which scheme is selected; the 5 schemes
     * with a known checksum get that stronger check on top.
     *
     * @return string|null A translated problem description, or null if fine.
     */
    private function endpointidFormatProblem(?string $endpointId, ?string $schemeId): ?string
    {
        if (null === $endpointId || $endpointId === ''
                || null === $schemeId || $schemeId === '') {
            return null;
        }
        $checksums = $this->endpointSchemeChecksums();
        return match (true) {
            str_contains($endpointId, '@') =>
                $this->translator->translate('peppol.endpointid.looks.like.email'),
            isset($checksums[$schemeId]) && !$checksums[$schemeId]($endpointId) =>
                $this->translator->translate('peppol.endpointid.checksum.invalid') . ' (' . $schemeId . ')',
            default => null,
        };
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
        PeppolSendServiceInterface $peppolSendService,
    ): Response {
        $invoice = $id ? $core->invRepo->repoInvLoadInvAmountquery($id) : null;
        if ($currentUser->isGuest() || null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        $client    = $invoice->getClient();
        $client_id = $client?->reqId() ?? 0;

        if ($client_id <= 0) {
            $this->flashMessage('warning',
                $this->translator->translate('peppol.client.check.no.client'));
        } elseif ($this->peppolClientFullySetup($client_id, $core->cpR)) {
            $delLocId = $invoice->getDeliveryLocationId();
            $delloc   = $core->dlR->repoDeliveryLocationquery((int) $delLocId);
            if (null === $delloc) {
                $this->flashDeliveryLocationMissing($id);
            } else {
                $this->transmitPeppolDocument($invoice, $delloc, $core, $net, $charge, $inv, $peppolSendService);
            }
        }

        return $this->webService->getRedirectResponse(self::ROUTE_INV_VIEW, ['id' => $id]);
    }

    private function transmitPeppolDocument(
        \App\Infrastructure\Persistence\Inv\Inv $invoice,
        DeliveryLocation $delloc,
        InvPeppolCoreDeps $core,
        InvPeppolNetworkDeps $net,
        InvPeppolChargeDeps $charge,
        InvPeppolInvDeps $inv,
        PeppolSendServiceInterface $peppolSendService,
    ): void {
        $peppolhelper = new PeppolHelper(
            $this->sR, $net->delRepo, $invoice->getInvAmount(), $delloc, $this->translator, $core->gR);
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
                $client_id = $invoice->getClient()?->reqId() ?? 0;
                $cp = $core->cpR->repoClientPeppolLoadedquery($client_id);
                if (null === $cp) {
                    $this->flashClientPeppolMissing($client_id);
                } else {
                    $recipientId = $cp->getEndpointidSchemeid() . ':' . $cp->getEndpointid();
                    $message = $peppolSendService->send($invoice->reqId(), $ublXml, $recipientId);
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
        } catch (\Throwable $e) {
            // Catch everything here, not just RuntimeException — anything
            // that escapes this block instead hits Yii3's global error
            // handler, which (outside YII_DEBUG=true) shows a generic "an
            // error has occurred" page with no indication of what actually
            // went wrong or how to fix it. Full detail still goes to the
            // server log either way, so real bugs stay diagnosable.
            $this->flashMessage('warning', $this->friendlyPeppolExceptionMessage($e));
        }
    }

    /**
     * Turns any exception from Peppol UBL generation into a message worth
     * showing the user — the translated {@see FriendlyExceptionInterface}
     * name where available, plus a deep link to the exact field to fix for
     * the exception types that know one. Always logs the full exception
     * server-side first, since a friendly one-liner in a flash message is
     * not enough to diagnose an unexpected failure later.
     */
    private function friendlyPeppolExceptionMessage(\Throwable $e): string
    {
        error_log((string) $e);
        $msg = $e instanceof \Yiisoft\FriendlyException\FriendlyExceptionInterface
            ? $e->getName()
            : $e->getMessage();
        if ($e instanceof PeppolTaxCategoryCodeNotFoundException
                && null !== $e->taxRateId) {
            $url = $this->webService->generateUrl(
                'taxrate/edit',
                ['tax_rate_id' => $e->taxRateId],
                [],
                'peppol_tax_rate_code',
            );
            $msg .= ' ' . Html::a(
                $this->translator->translate('peppol.tax.category.not.found.fix'),
                $url)->render();
        }
        if ($e instanceof PeppolBuyerPostalAddressNotFoundException
                && null !== $e->clientId) {
            $url = $this->webService->generateUrl(
                'client/edit',
                ['id' => $e->clientId, 'origin' => 'edit'],
                [],
                'postaladdress_field',
            );
            $msg .= ' ' . Html::a(
                $this->translator->translate('peppol.buyer.postal.address.not.found.fix'),
                $url)->render();
        }
        return $msg;
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
