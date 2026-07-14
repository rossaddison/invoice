<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\
{
    Inv\Inv, InvAllowanceCharge\InvAllowanceCharge,
    InvItem\InvItem, InvCustom\InvCustom, InvTaxRate\InvTaxRate,
    Payment\Payment
};

use App\Invoice\{
    InvAllowanceCharge\InvAllowanceChargeForm, InvCustom\InvCustomForm,
    InvItem\IiAddProductDeps, InvItem\InvItemForm, InvTaxRate\InvTaxRateForm,
    Inv\CopyInvOptions,
    Inv\CsvDateNormaliser,
    Inv\InvCopyDeps,
    Inv\InvForm,
    Helpers\InvRecalculator,
    Payment\PaymentService,
};

use Psr\Http\Message\{ResponseFactoryInterface, StreamFactoryInterface};
use Yiisoft\{
    FormModel\FormHydrator, Html\Html, Json\Json, Security\Random
};
use Yiisoft\Router\UrlGeneratorInterface;
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

            $productIds = $this->collectInvProductIds($invId, $d);

            $targets = !empty($selectedClientIds)
                ? $selectedClientIds
                : [$original->reqClientId()];

            $opts = new CopyInvOptions(createdDate: (string) ($data['modal_created_date'] ?? ''));
            foreach ($targets as $targetClientId) {
                if ($this->copyInvToClient(
                    $invId, $original, $targetClientId, $d, $formHydrator,
                    $productIds, $opts)) {
                    $anySuccess = true;
                }
            }
        }

        return $this->factory->createResponse(
            Json::encode(['success' => $anySuccess ? 1 : 0])
        );
    }

    /**
     * @return int[]
     */
    private function collectInvProductIds(int $invId, InvCopyDeps $d): array
    {
        $productIds = [];
        /** @var InvItem $item */
        foreach ($d->iiR->repoInvItemIdquery($invId) as $item) {
            $pid = $item->getProductId();
            if ($pid !== null && $pid > 0) {
                $productIds[] = $pid;
            }
        }
        return $productIds;
    }

    private function resolveCopyCreatedDate(string $createdDate): \DateTimeImmutable
    {
        if ($createdDate === '') {
            return new \DateTimeImmutable('now');
        }
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $createdDate);
        return $parsed !== false ? $parsed : new \DateTimeImmutable('now');
    }

    /** @param array<string, mixed> $invoiceBody */
    private function createPaymentForCopyIfRequired(
        Inv $copied,
        int $copiedId,
        array $invoiceBody,
        InvCopyDeps $d,
        CopyInvOptions $opts,
    ): void {
        if (!$opts->sameAmount || $opts->paymentDate === '' || null === $opts->paymentService) {
            return;
        }
        $invAmount = $d->iaR->repoInvquery($copiedId);
        if (null === $invAmount) {
            return;
        }
        $opts->paymentService->savePayment(new Payment(), [
            'inv_id'            => $copiedId,
            'payment_date'      => $opts->paymentDate,
            'amount'            => $invAmount->getTotal() ?? 0.00,
            'note'              => $invoiceBody['note'] ?? '',
            'payment_method_id' => $copied->getPaymentMethod() ?? 0,
        ]);
    }

    /**
     * @param int[] $productIds
     */
    private function copyInvToClient(
        int $invId,
        Inv $original,
        int $targetClientId,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
        array $productIds,
        CopyInvOptions $opts,
    ): bool {
        $sentCopy = $this->sR->getSetting('mark_invoices_sent_copy') === '1';
        $createdDt = $this->resolveCopyCreatedDate($opts->createdDate);
        $invoice_body = [
            'client_id'               => $targetClientId,
            'group_id'                => $original->reqGroupId(),
            'so_id'                   => $original->getSoId(),
            'quote_id'                => $original->getQuoteId(),
            'status_id'               => $sentCopy ? 2 : 1,
            'is_read_only'            => $sentCopy,
            'password'                => '',
            'date_supplied'           => $createdDt,
            'date_tax_point'          => $createdDt,
            'time_created'            => (new \DateTimeImmutable('now'))->format('H:i:s'),
            'stand_in_code'           => $this->sR->getSetting('stand_in_code'),
            'number'                  => $this->sR->getSetting('generate_invoice_number_for_draft') === '1'
                ? (string) $d->gR->generateNumber($original->reqGroupId(), true)
                : '',
            'discount_amount'         => (float) $original->getDiscountAmount(),
            'terms'                   => $original->getTerms(),
            'note'                    => $opts->note !== '' ? $opts->note : ($original->getNote() ?? ''),
            'document_description'    => $original->getDocumentDescription(),
            'url_key'                 => Random::string(32),
            'payment_method'          => $original->getPaymentMethod(),
            'creditinvoice_parent_id' => null,
            'delivery_id'             => $original->getDeliveryId(),
            'delivery_location_id'    => $original->getDeliveryLocationId(),
            'postal_address_id'       => $original->getPostalAddressId(),
            'contract_id'             => $original->getContractId(),
        ];
        $copy = new Inv();
        $form = new InvForm();
        if (!$formHydrator->populateAndValidate($form, $invoice_body)) {
            return false;
        }
        $user = $this->activeUser($targetClientId, $d->uR, $d->ucR, $d->uiR);
        if (null === $user) {
            return false;
        }
        $this->inv_service->withTransaction(
            function () use (
                $user, $copy, $invoice_body, $opts, $invId,
                $d, $formHydrator, $productIds, $targetClientId
            ): void {
                $copied = $this->inv_service->copyInv(
                    $user, $copy, $invoice_body, $this->sR);
                $copied->setDateCreated($opts->createdDate);
                $d->iR->save($copied);
                $copied_id = $copied->reqId();
                if ($copied_id <= 0) {
                    return;
                }
                $this->invToInvInvItems($invId, $copied_id, $d, $formHydrator);
                $this->invToInvInvTaxRates($invId, $copied_id, $d, $formHydrator);
                $this->invToInvInvCustom($invId, $copied_id, $d, $formHydrator);
                if ($opts->sameAmount) {
                    $this->invToInvInvAmount($invId, $copied_id, $d);
                }
                $d->iR->save($copy);
                if (!empty($productIds)) {
                    $d->pcS->syncFromInvItems($targetClientId, $productIds);
                }
                $this->createPaymentForCopyIfRequired($copied, $copied_id, $invoice_body, $d, $opts);
            }
        );
        return true;
    }

    public function multiplecopyspreadsheet(
        Request $request,
        FormHydrator $formHydrator,
        InvCopyDeps $d,
        PaymentService $paymentService,
    ): Response {
        $data = $request->getQueryParams();

        /** @var list<string> $keyList */
        $keyList = (array) ($data['keylist'] ?? []);
        /** @var int[] $clientIds */
        $clientIds = array_values(
            array_filter(array_map('intval', (array) ($data['client_ids'] ?? [])))
        );
        /** @var list<array{date_created: string, note: string, same_amount: string, payment_date: string}> $rows */
        $rows = (array) json_decode((string) ($data['rows_json'] ?? '[]'), true);

        if ($keyList === [] || $rows === []) {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }

        $anySuccess = false;

        foreach ($keyList as $value) {
            $invId    = (int) $value;
            $original = $d->iR->repoInvUnloadedquery($invId);
            if (!$original) {
                continue;
            }

            if ($this->copyInvForSpreadsheetRows(
                $invId, $original, $clientIds, $rows, $d, $formHydrator, $paymentService
            )) {
                $anySuccess = true;
            }
        }

        return $this->factory->createResponse(
            Json::encode(['success' => $anySuccess ? 1 : 0])
        );
    }

    /**
     * @param int[] $clientIds
     * @param list<array{date_created: string, note: string, same_amount: string, payment_date: string}> $rows
     */
    private function copyInvForSpreadsheetRows(
        int $invId,
        Inv $original,
        array $clientIds,
        array $rows,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
        PaymentService $paymentService,
    ): bool {
        $productIds = $this->collectInvProductIds($invId, $d);
        $targets    = $clientIds !== [] ? $clientIds : [$original->reqClientId()];
        $anySuccess = false;

        foreach ($rows as $row) {
            $opts = new CopyInvOptions(
                createdDate: CsvDateNormaliser::normalise(ltrim(rtrim($row['date_created'] ?? ''))),
                note: ltrim(rtrim($row['note'] ?? '')),
                sameAmount: ($row['same_amount'] ?? '1') !== '0',
                paymentDate: CsvDateNormaliser::normalise(ltrim(rtrim($row['payment_date'] ?? ''))),
                paymentService: $paymentService,
            );
            foreach ($targets as $targetClientId) {
                if ($this->copyInvToClient($invId, $original, $targetClientId, $d, $formHydrator, $productIds, $opts)) {
                    $anySuccess = true;
                }
            }
        }

        return $anySuccess;
    }

    public function csvTemplateInvCopy(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): Response {
        $rows = [
            ['date_created', 'note', 'same_amount', 'payment_date'],
            ['2026-07-01', 'Invoice for services rendered in June', '1', '2026-07-15'],
            ['2026-07-15', 'Second monthly invoice', '1', '2026-07-30'],
            ['2026-08-01', 'August invoice no payment yet', '1', ''],
        ];

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return $responseFactory->createResponse(500);
        }
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="invoice-copy-template.csv"')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withBody($streamFactory->createStream($csv));
    }

    public function bulkquickpay(
        Request $request,
        InvCopyDeps $d,
        PaymentService $paymentService,
        InvRecalculator $invRecalculator,
    ): Response {
        $params  = $request->getQueryParams();
        /** @var list<string> $keyList */
        $keyList = (array) ($params['keylist'] ?? []);
        $date    = CsvDateNormaliser::normalise(trim((string) ($params['date'] ?? '')));
        $note    = trim((string) ($params['note'] ?? ''));

        if ($keyList === [] || $date === '') {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }

        $anySuccess = false;
        foreach ($keyList as $value) {
            $invId     = (int) $value;
            $inv       = $d->iR->repoInvUnloadedquery($invId);
            $invAmount = $d->iaR->repoInvquery($invId);
            if (null === $inv || null === $invAmount) {
                continue;
            }
            $pmId = $inv->getPaymentMethod() ?? 0;
            $paymentService->savePayment(new Payment(), array_filter([
                'inv_id'            => $invId,
                'payment_date'      => $date,
                'amount'            => $invAmount->getTotal() ?? 0.00,
                'note'              => $note,
                'payment_method_id' => $pmId > 0 ? $pmId : null,
            ], static fn (mixed $v): bool => $v !== null));
            $invRecalculator->recalculate($invId);
            $anySuccess = true;
        }

        return $this->factory->createResponse(Json::encode(['success' => $anySuccess ? 1 : 0]));
    }

    public function quickpayform(
        Request $request,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $params   = $request->getQueryParams();
        $invId    = (int) ($params['inv_id'] ?? 0);
        $cancel   = ($params['cancel'] ?? '') === '1';
        $qpId     = 'qp-' . $invId;
        $hxTarget = ' hx-target="#' . $qpId . '"';
        $hxSwap   = ' hx-swap="innerHTML"';

        if ($cancel) {
            $html = '<button class="btn btn-outline-success btn-sm"'
                . ' hx-get="' . Html::encode(
                    $urlGenerator->generate('inv/quickpayform', [], ['inv_id' => $invId])
                ) . '"'
                . $hxTarget . $hxSwap
                . ' data-bs-toggle="tooltip" title="Quick Pay">💰</button>';
        } else {
            $cancelUrl = Html::encode(
                $urlGenerator->generate('inv/quickpayform', [], ['inv_id' => $invId, 'cancel' => '1'])
            );
            $submitUrl = Html::encode($urlGenerator->generate('inv/quickpay'));
            $today     = (new \DateTimeImmutable())->format('Y-m-d');
            $html = '<form hx-get="' . $submitUrl . '"'
                . $hxTarget . $hxSwap
                . ' class="d-flex gap-1 align-items-center">'
                . '<input type="hidden" name="inv_id" value="' . $invId . '">'
                . '<input type="date" name="date" value="' . $today . '"'
                . ' class="form-control form-control-sm" style="width:140px" required>'
                . '<input type="text" name="note"'
                . ' class="form-control form-control-sm" style="width:100px"'
                . ' placeholder="Bank ref">'
                . '<button type="submit" class="btn btn-success btn-sm">✓</button>'
                . '<button type="button"'
                . ' hx-get="' . $cancelUrl . '"'
                . $hxTarget . $hxSwap
                . ' class="btn btn-secondary btn-sm">✕</button>'
                . '</form>';
        }

        $stream = $streamFactory->createStream($html);
        return $responseFactory->createResponse()
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function quickpay(
        Request $request,
        InvCopyDeps $d,
        PaymentService $paymentService,
        InvRecalculator $invRecalculator,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): Response {
        $params = $request->getQueryParams();
        $invId  = (int) ($params['inv_id'] ?? 0);
        $date   = CsvDateNormaliser::normalise(trim((string) ($params['date'] ?? '')));
        $note   = trim((string) ($params['note'] ?? ''));

        $html = '<span class="badge bg-danger">✗</span>';

        if ($invId > 0 && $date !== '') {
            $inv       = $d->iR->repoInvUnloadedquery($invId);
            $invAmount = $d->iaR->repoInvquery($invId);
            if (null !== $inv && null !== $invAmount) {
                $pmId = $inv->getPaymentMethod() ?? 0;
                $paymentService->savePayment(new Payment(), array_filter([
                    'inv_id'            => $invId,
                    'payment_date'      => $date,
                    'amount'            => $invAmount->getTotal() ?? 0.00,
                    'note'              => $note,
                    'payment_method_id' => $pmId > 0 ? $pmId : null,
                ], static fn (mixed $v): bool => $v !== null));
                $invRecalculator->recalculate($invId);
                $html = '<span class="badge bg-success" data-bs-toggle="tooltip" title="'
                    . Html::encode($date) . '">✅ ' . Html::encode($date) . '</span>';
            }
        }

        $stream = $streamFactory->createStream($html);
        return $responseFactory->createResponse()
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');
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

        $productIds = $this->collectInvProductIds($inv_id, $d);

        $copyCount = 0;
        $lastCopyId = 0;

        foreach ($clientIds as $clientId) {
            $copy_id = $this->saveInvConfirmCopy(
                $clientId, $inv_id, $original, $d, $formHydrator, $productIds);
            if ($copy_id > 0) {
                $copyCount++;
                $lastCopyId = $copy_id;
            }
        }

        if ($copyCount > 0) {
            $this->flashMessage('info', $this->translator->translate('draft.guest'));
            $parameters = ['success' => 1];
            if ($copyCount === 1) {
                $parameters['new_invoice_id'] = $lastCopyId;
            }
            return $this->factory->createResponse(Json::encode($parameters));
        }

        return $this->factory->createResponse(Json::encode(['success' => 0]));
    }

    /**
     * @param int[] $productIds
     */
    private function saveInvConfirmCopy(
        int $clientId,
        int $invId,
        Inv $original,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
        array $productIds,
    ): int {
        $groupId = $original->reqGroupId();
        $ajax_body = [
            'quote_id'                => null,
            'client_id'               => $clientId,
            'group_id'                => $groupId,
            'status_id'               => $this->sR->getSetting('mark_invoices_sent_copy') === '1' ? 2 : 1,
            'number'                  => $d->gR->generateNumber($groupId),
            'creditinvoice_parent_id' => null,
            'discount_amount'         => (float) $original->getDiscountAmount(),
            'url_key'                 => Random::string(32),
            'password'                => '',
            'payment_method'          => $original->getPaymentMethod(),
            'terms'                   => $original->getTerms(),
            'document_description'    => $original->getDocumentDescription(),
            'note'                    => $original->getNote(),
            'stand_in_code'           => $this->sR->getSetting('stand_in_code'),
        ];
        $copy = new Inv();
        $form = new InvForm();
        if (!$formHydrator->populateAndValidate($form, $ajax_body)) {
            return 0;
        }
        $user = $this->activeUser($clientId, $d->uR, $d->ucR, $d->uiR);
        if (null === $user) {
            return 0;
        }
        $copy_id = 0;
        $this->inv_service->withTransaction(
            function () use (
                $user, $copy, $ajax_body, $invId, $d, $formHydrator, &$copy_id
            ): void {
                $this->inv_service->saveInv($user, $copy, $ajax_body, $this->sR, $d->gR);
                $copy_id = $copy->reqId();
                if ($copy_id > 0) {
                    $this->invToInvInvItems($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvTaxRates($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvCustom($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvAllowanceCharges($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvAmount($invId, $copy_id, $d);
                    $d->iR->save($copy);
                }
            }
        );
        if ($copy_id > 0 && !empty($productIds)) {
            $d->pcS->syncFromInvItems($clientId, $productIds);
        }
        return $copy_id;
    }

    /**
     * Generates a new invoice for a client from a template invoice's items,
     * always as "sent" (status_id = 2) regardless of the
     * mark_invoices_sent_copy setting, so it is immediately visible to the
     * customer. Used by the public home-care QR scan endpoint
     * (see InvController::homecareScan).
     *
     * @return int New invoice id, or 0 if generation could not proceed
     *             (e.g. no active portal user linked to this client).
     */
    public function generateHomeCareCleaningInvoice(
        int $clientId,
        Inv $templateInvoice,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): int {
        $invId = $templateInvoice->reqId();
        $groupId = $templateInvoice->reqGroupId();
        $productIds = $this->collectInvProductIds($invId, $d);

        $invoice_body = [
            'quote_id'                => null,
            'client_id'               => $clientId,
            'group_id'                => $groupId,
            'status_id'               => 2,
            'number'                  => $d->gR->generateNumber($groupId),
            'creditinvoice_parent_id' => null,
            'discount_amount'         => (float) $templateInvoice->getDiscountAmount(),
            'url_key'                 => Random::string(32),
            'password'                => '',
            'payment_method'          => $templateInvoice->getPaymentMethod(),
            'terms'                   => $templateInvoice->getTerms(),
            'document_description'    => $templateInvoice->getDocumentDescription(),
            'note'                    => $templateInvoice->getNote(),
            'stand_in_code'           => $this->sR->getSetting('stand_in_code'),
        ];
        $copy = new Inv();
        $form = new InvForm();
        if (!$formHydrator->populateAndValidate($form, $invoice_body)) {
            return 0;
        }
        $user = $this->activeUser($clientId, $d->uR, $d->ucR, $d->uiR);
        if (null === $user) {
            return 0;
        }
        $copy_id = 0;
        $this->inv_service->withTransaction(
            function () use (
                $user, $copy, $invoice_body, $invId, $d, $formHydrator, &$copy_id
            ): void {
                $this->inv_service->saveInv($user, $copy, $invoice_body, $this->sR, $d->gR);
                $copy_id = $copy->reqId();
                if ($copy_id > 0) {
                    $this->invToInvInvItems($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvTaxRates($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvCustom($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvAllowanceCharges($invId, $copy_id, $d, $formHydrator);
                    $this->invToInvInvAmount($invId, $copy_id, $d);
                    $d->iR->save($copy);
                }
            }
        );
        if ($copy_id > 0 && !empty($productIds)) {
            $d->pcS->syncFromInvItems($clientId, $productIds);
        }
        return $copy_id;
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
            $invItem = new InvItem();
            $form = new InvItemForm();
            if (!$formHydrator->populateAndValidate($form, $copy_item)) {
                if (!empty($form->getValidationResult()->getErrorMessagesIndexedByProperty())) {
                    $this->flashMessage('danger',
                        'You have validation errors on ' . (string) $inv_item->reqId());
                }
                continue;
            }
            $this->copyProductItem($invItem, $inv_item, $copy_item, $copy_id, $originalInvItemId, $d);
            $this->copyTaskItem($invItem, $inv_item, $copy_item, $copy_id, $originalInvItemId, $d);
        }
    }

    private function copyProductItem(
        InvItem $invItem,
        InvItem $origItem,
        array $copyItem,
        int $copyId,
        int $originalInvItemId,
        InvCopyDeps $d,
    ): void {
        $productId = (int) $origItem->getProductId();
        if ($productId <= 0) {
            return;
        }
        $newInvItemId = $this->inv_item_service->addInvItemProduct(
            $invItem, $copyItem, (string) $copyId,
            new IiAddProductDeps($d->pR, $d->trR, $d->iiaS, $d->iiaR, $this->sR, $d->unR));
        if (null === $newInvItemId) {
            return;
        }
        $this->inv_item_service->addInvItemAllowanceCharges(
            (string) $copyId, $originalInvItemId, $newInvItemId, $d->aciiR);
        $this->saveInvItemAmountIfValid($newInvItemId, $invItem, $origItem, $d);
    }

    private function copyTaskItem(
        InvItem $invItem,
        InvItem $origItem,
        array $copyItem,
        int $copyId,
        int $originalInvItemId,
        InvCopyDeps $d,
    ): void {
        $taskId = (int) $origItem->getTaskId();
        if ($taskId <= 0) {
            return;
        }
        $newInvItemId = $this->inv_item_service->addInvItemTask(
            $invItem, $copyItem, (string) $copyId,
            $d->taskR, $d->trR, $d->iiaS, $d->iiaR);
        if (null === $newInvItemId) {
            return;
        }
        $this->inv_item_service->addInvItemAllowanceCharges(
            (string) $copyId, $originalInvItemId, $newInvItemId, $d->aciiR);
        $this->saveInvItemAmountIfValid($newInvItemId, $invItem, $origItem, $d);
    }

    private function saveInvItemAmountIfValid(
        int $newInvItemId,
        InvItem $invItem,
        InvItem $origItem,
        InvCopyDeps $d,
    ): void {
        $taxRatePercent = $origItem->getTaxRate()?->getTaxRatePercent();
        if ($invItem->getQuantity() >= 0.00
            && $invItem->getPrice() >= 0.00
            && $invItem->getDiscountAmount() >= 0.00
            && $taxRatePercent >= 0.00
        ) {
            $this->inv_item_service->saveInvItemAmount(
                $newInvItemId,
                $invItem->getQuantity() ?? 0.00,
                $invItem->getPrice() ?? 0.00,
                $invItem->getDiscountAmount() ?? 0.00,
                $taxRatePercent ?? 0.00,
                $d->iiaS,
                $d->iiaR
            );
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
