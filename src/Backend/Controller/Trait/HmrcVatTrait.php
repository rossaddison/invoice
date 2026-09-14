<?php

declare(strict_types=1);

namespace App\Backend\Controller\Trait;

use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Yiisoft\Http\Method;
use Yiisoft\Yii\AuthClient\RequestUtil;

trait HmrcVatTrait
{
    /**
     * Retrieve open VAT obligations for the configured VRN and render them.
     * Related logic: https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0/oas/page#tag/VAT/operation/Retrieve-VAT-obligations
     */
    public function vatObligations(): Response
    {
        $vrn = $this->sR->getSetting('vat_registration_number');
        $tokenString = (string) $this->session->get('hmrc_access_token');
        $otpReference = (string) $this->session->get('otpRef');

        if ($vrn === '' || strlen($tokenString) === 0) {
            $this->flashMessage('warning', $this->translator->translate('mtd.vat.obligations.missing.vrn.or.token'));
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        $request = $this->createRequest(
            'GET',
            $this->resolveHmrcApiBaseUrl()
                . '/organisations/vat/' . urlencode($vrn) . '/obligations?status=O',
        );

        $request = RequestUtil::addHeaders($request, array_merge(
            ['Accept' => 'application/vnd.hmrc.1.0+json', 'Authorization' => 'Bearer ' . $tokenString],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        $apiResponse = $this->sendRequest($request);
        /** @var array{obligations?: array<int, array<string, string>>} $parsed */
        $parsed = (array) json_decode($apiResponse->getBody()->getContents(), true);
        $obligations = $parsed['obligations'] ?? [];

        return $this->webViewRenderer->render('vatObligations', [
            'alert'       => $this->alert(),
            'obligations' => $obligations,
            'vrn' => $vrn,
            'statusCode' => $apiResponse->getStatusCode(),
        ]);
    }

    /**
     * Prepare step: auto-fill Boxes 1 and 6 from InvAmount for the period,
     * derive Box 3 and 5 client-side, leave Boxes 4 and 7 for manual entry.
     */
    public function vatReturnPrepare(
        ServerRequest $request,
        \App\Invoice\InvAmount\InvAmountRepository $invAmountRepository,
        PurchaseEntryRepository $purchaseEntryRepository,
    ): Response {
        $vrn = $this->sR->getSetting('vat_registration_number');
        $tokenString = (string) $this->session->get('hmrc_access_token');

        if ($vrn === '' || strlen($tokenString) === 0) {
            $this->flashMessage('warning', $this->translator->translate('mtd.vat.obligations.missing.vrn.or.token'));
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        $queryParams = $request->getQueryParams();
        $periodKey = (string) ($queryParams['periodKey'] ?? '');
        $periodStart = (string) ($queryParams['start'] ?? '');
        $periodEnd = (string) ($queryParams['end'] ?? '');

        $salesTotals = $invAmountRepository->repoVatTotalsForPeriod($periodStart, $periodEnd);
        $purchaseTotals = $purchaseEntryRepository->repoVatTotalsForPeriod($periodStart, $periodEnd);

        return $this->webViewRenderer->render('vatReturnPrepare', [
            'alert'       => $this->alert(),
            'vrn'         => $vrn,
            'periodKey'   => $periodKey,
            'periodStart' => $periodStart,
            'periodEnd'   => $periodEnd,
            'box1'        => $salesTotals['output_vat'],
            'box4'        => $purchaseTotals['input_vat'],
            'box6'        => $salesTotals['sales_ex_vat'],
            'box7'        => $purchaseTotals['purchases_ex_vat'],
        ]);
    }

    /**
     * Show VAT100 form (GET) and submit a VAT return to HMRC (POST).
     * Related logic: https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0/oas/page#tag/VAT/operation/Submit-VAT-return-for-period
     */
    public function vatReturnSubmit(ServerRequest $request): Response
    {
        $vrn = $this->sR->getSetting('vat_registration_number');
        $tokenString = (string) $this->session->get('hmrc_access_token');

        if ($vrn === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.vat.obligations.missing.vrn.or.token')
            );
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            /** @var array<string, string> $body */
            $body = is_array($body) ? $body : [];

            $returnData = [
                'periodKey' => $body['periodKey'] ?? '',
                'vatDueSales' => (float) ($body['vatDueSales'] ?? 0),
                'vatDueAcquisitions' => (float) ($body['vatDueAcquisitions'] ?? 0),
                'totalVatDue' => (float) ($body['totalVatDue'] ?? 0),
                'vatReclaimedCurrPeriod' => (float) ($body['vatReclaimedCurrPeriod'] ?? 0),
                'netVatDue' => (float) ($body['netVatDue'] ?? 0),
                'totalValueSalesExVAT' => (int) ($body['totalValueSalesExVAT'] ?? 0),
                'totalValuePurchasesExVAT' => (int) ($body['totalValuePurchasesExVAT'] ?? 0),
                'totalValueGoodsSuppliedExVAT' => (int) ($body['totalValueGoodsSuppliedExVAT'] ?? 0),
                'totalAcquisitionsExVAT' => (int) ($body['totalAcquisitionsExVAT'] ?? 0),
                'finalised' => isset($body['finalised']),
            ];

            $otpReference = (string) $this->session->get('otpRef');

            $apiRequest = $this->createRequest(
                'POST',
                $this->resolveHmrcApiBaseUrl()
                    . '/organisations/vat/' . urlencode($vrn) . '/returns',
            );

            $apiRequest = RequestUtil::addHeaders($apiRequest, array_merge(
                [
                    'Accept' => 'application/vnd.hmrc.1.0+json',
                    'Authorization' => 'Bearer ' . $tokenString,
                    'Content-Type' => 'application/json',
                ],
                $this->getWebAppViaServerHeaders($otpReference),
            ));

            $apiRequest = $apiRequest->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor(json_encode($returnData)),
            );

            $apiResponse = $this->sendRequest($apiRequest);
            /** @var array<string, mixed> $result */
            $result = (array) json_decode($apiResponse->getBody()->getContents(), true);

            return $this->webViewRenderer->render('vatReturnResult', [
                'alert' => $this->alert(),
                'statusCode' => $apiResponse->getStatusCode(),
                'result' => $result,
                'periodKey' => $returnData['periodKey'],
            ]);
        }

        // GET — show the form, pre-populate period key from query string
        $queryParams = $request->getQueryParams();
        return $this->webViewRenderer->render('vatReturnSubmit', [
            'alert' => $this->alert(),
            'vrn' => $vrn,
            'periodKey' => (string) ($queryParams['periodKey'] ?? ''),
            'periodStart' => (string) ($queryParams['start'] ?? ''),
            'periodEnd' => (string) ($queryParams['end'] ?? ''),
        ]);
    }
}
