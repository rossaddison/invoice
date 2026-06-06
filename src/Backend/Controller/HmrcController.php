<?php

declare(strict_types=1);

namespace App\Backend\Controller;

use App\Invoice\BaseController;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use App\Invoice\Setting\SettingRepository as SR;
use App\Service\WebControllerService;
use App\User\UserService;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\AuthClient\RequestUtil;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class HmrcController extends BaseController
{
    protected string $controllerName = 'hmrc';

    public function __construct(
        Flash $flash,
        private HttpClient $httpClient,
        private RequestFactoryInterface $requestFactory,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->session = $session;
        $this->sR = $sR;
        $this->translator = $translator;
        $this->userService = $userService;
        $this->webViewRenderer = $webViewRenderer->withViewPath('@hmrc');
    }

    public function index(): Response
    {
        $parameters = [
            'vrn' => $this->sR->getSetting('vat_registration_number'),
            'fphConnectionMethod' => $this->sR->getSetting('fph_connection_method'),
            'govVendorProductName' => $this->sR->getGovVendorProductName(),
            'govVendorVersion' => $this->sR->getGovVendorVersion(),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * Not tested yet 23/05/2025
     * $api e.g. 'self-assessment', 'vat', 'employment', 'customs', 'individuals'
     */
    public function fphFeedback(
        #[RouteArgument('api')]
        string $api,
    ): Response {
        $logFile = $this->sR->specificCommonConfigAliase('@hmrc') . '/hmrc-requests.log';
        $otpReference = (string) $this->session->get('otpRef');
        $client = $this->createLoggedGuzzleClient($logFile);

        return $client->post($this->getFphValidationFeedbackUrl($api), [
            'headers' => $this->getWebAppViaServerHeaders($otpReference),
        ]);
    }

    public function fphValidate(): Response
    {
        $otp = (int) $this->session->get('otp');
        $otpReference = (string) $this->session->get('otpRef');
        if ($otp > 99999 && $otp < 1000000 && strlen($otpReference) > 0) {
            $headers = $this->getWebAppViaServerHeaders($otpReference);

            $tokenString = (string) $this->session->get('hmrc_access_token');

            if (strlen($tokenString) > 0) {
                $requestPartOne = $this->createRequest('GET', $this->getFphValidateHeadersUrl());

                $acceptAndAuthorizationArray = [
                    'Accept' => 'application/vnd.hmrc.1.0+json',
                    'Authorization' => 'Bearer ' . $tokenString,
                ];

                $mergedArray = array_merge($acceptAndAuthorizationArray, $headers);

                $requestPartTwo = RequestUtil::addHeaders($requestPartOne, $mergedArray);

                return $this->sendRequest($requestPartTwo);
            }

            return $this->webService->getRedirectResponse('invoice/index');
        }

        return $this->webService->getRedirectResponse('invoice/index');
    }

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
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $request = $this->createRequest(
            'GET',
            'https://api.service.hmrc.gov.uk/organisations/vat/' . urlencode($vrn) . '/obligations?status=O',
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
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $queryParams = $request->getQueryParams();
        $periodKey = (string) ($queryParams['periodKey'] ?? '');
        $periodStart = (string) ($queryParams['start'] ?? '');
        $periodEnd = (string) ($queryParams['end'] ?? '');

        $salesTotals = $invAmountRepository->repoVatTotalsForPeriod($periodStart, $periodEnd);
        $purchaseTotals = $purchaseEntryRepository->repoVatTotalsForPeriod($periodStart, $periodEnd);

        return $this->webViewRenderer->render('vatReturnPrepare', [
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
            $this->flashMessage('warning', $this->translator->translate('mtd.vat.obligations.missing.vrn.or.token'));
            return $this->webService->getRedirectResponse('backend/hmrc/index');
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
                'https://api.service.hmrc.gov.uk/organisations/vat/' . urlencode($vrn) . '/returns',
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
                'statusCode' => $apiResponse->getStatusCode(),
                'result' => $result,
                'periodKey' => $returnData['periodKey'],
            ]);
        }

        // GET — show the form, pre-populate period key from query string
        $queryParams = $request->getQueryParams();
        return $this->webViewRenderer->render('vatReturnSubmit', [
            'vrn' => $vrn,
            'periodKey' => (string) ($queryParams['periodKey'] ?? ''),
            'periodStart' => (string) ($queryParams['start'] ?? ''),
            'periodEnd' => (string) ($queryParams['end'] ?? ''),
        ]);
    }

    public function createTestUserIndividual(array $requestBody = []): array
    {
        /**
         * Related logic: see src\Auth\Controller\AuthController
         *      function callbackDeveloperSandboxHmrc
         */
        $tokenString = (string) $this->session->get('hmrc_access_token');

        if (strlen($tokenString) > 0) {
            $url = 'https://test-api.service.hmrc.gov.uk/create-test-user/individuals';

            $request = $this->createRequest('POST', $url);

            $request = RequestUtil::addHeaders(
                $request,
                [
                    'Authorization' => 'Bearer ' . $tokenString,
                    'Content-Type' => 'application/json',
                ],
            );

            $request = $request->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor(json_encode($requestBody)),
            );

            $response = $this->sendRequest($request);

            return (array) json_decode($response->getBody()->getContents(), true);
        }

        return [];
    }

    private function getWebAppViaServerHeaders(string $otpReference): array
    {
        return $this->webAppViaServerBuildArrayFromStrings(
            $this->sR->getSetting('fph_connection_method'),
            $this->sR->getSetting('fph_client_browser_js_user_agent'),
            $this->sR->getSetting('fph_client_device_id'),
            $this->sR->fphGenerateMultiFactor('TOTP', $otpReference),
            $this->sR->getGovClientPublicIp() ?? '',
            $this->sR->getGovClientPublicIpTimestamp() ?? '',
            $this->sR->getGovClientPublicPort(),
            $this->sR->getGovClientScreens(),
            $this->sR->getGovClientTimezone(),
            $this->sR->getGovClientUserIDs(),
            $this->sR->getSetting('fph_window_size'),
            $this->sR->getGovVendorForwarded(),
            $this->sR->getGovVendorLicenseIDs(),
            $this->sR->getGovVendorProductName(),
            $this->sR->getGovVendorPublicIP(),
            $this->sR->getGovVendorVersion(),
        );
    }

    private function getFphValidateHeadersUrl(): string
    {
        return 'https://test-api.service.hmrc.gov.uk/test/fraud-prevention-headers/validate';
    }

    /** $api = "self-assessment" / "vat" / "employment" / "customs" / "individuals" */
    private function getFphValidationFeedbackUrl(string $api): string
    {
        return 'https://test-api.service.hmrc.gov.uk/test/fraud-prevention-headers/' . $api . '/validation-feedback';
    }

    private function createRequest(string $method, string $uri): Request
    {
        return $this->requestFactory->createRequest($method, $uri);
    }

    private function sendRequest(Request $request): Response
    {
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Note: The connection method determines what headers are included.
     *       16 headers are required for the WEB_APP_VIA_SERVER method.
     * Related logic: https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/web-app-via-server/
     */
    private function webAppViaServerBuildArrayFromStrings( // NOSONAR php:S107 — HMRC fraud-prevention spec mandates exactly 16 header strings
        string $govClientConnectionMethod,
        string $govClientBrowserJsUserAgent,
        string $govClientDeviceID,
        string $govClientMultiFactor,
        string $govClientPublicIp,
        string $govClientPublicIpTimestamp,
        int $govClientPublicPort,
        string $govClientScreens,
        string $govClientTimezone,
        string $govClientUserIds,
        string $govClientWindowSize,
        string $govVendorForwarded,
        string $govVendorLicenseIDs,
        string $govVendorProductName,
        string $govVendorPublicIP,
        string $govVendorVersion,
    ): array {
        return [
            'Gov-Client-Connection-Method' => $govClientConnectionMethod,
            'Gov-Client-Browser-JS-User-Agent' => $govClientBrowserJsUserAgent,
            'Gov-Client-Device-ID' => $govClientDeviceID,
            'Gov-Client-Multi-Factor' => $govClientMultiFactor,
            'Gov-Client-Public-Ip' => $govClientPublicIp,
            'Gov-Client-Public-IP-Timestamp' => $govClientPublicIpTimestamp,
            'Gov-Client-Public-Port' => $govClientPublicPort,
            'Gov-Client-Screens' => $govClientScreens,
            'Gov-Client-Timezone' => $govClientTimezone,
            'Gov-Client-User-IDs' => $govClientUserIds,
            'Gov-Client-Window-Size' => $govClientWindowSize,
            'Gov-Vendor-Forwarded' => $govVendorForwarded,
            'Gov-Vendor-License-IDs' => $govVendorLicenseIDs,
            'Gov-Vendor-Product-Name' => $govVendorProductName,
            'Gov-Vendor-Public-IP' => $govVendorPublicIP,
            'Gov-Vendor-Version' => $govVendorVersion,
        ];
    }

    /** @psalm-return HandlerStack */
    private function buildHandlerStackWithLogging(string $logFile): HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push($this->getRequestLoggingMiddleware($logFile));
        return $stack;
    }

    /** @return callable(callable): callable */
    private function getRequestLoggingMiddleware(string $logFile): callable
    {
        return fn (callable $handler): callable => function (Request $request, array $options) use ($handler, $logFile): PromiseInterface {
            $headersJson = json_encode($request->getHeaders(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($headersJson === false) {
                $headersJson = 'Error encoding headers';
            }

            $logEntry = sprintf(
                "[%s] %s %s\nHeaders: %s\n\n",
                date('c'),
                $request->getMethod(),
                (string) $request->getUri(),
                $headersJson,
            );
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

            /** @psalm-suppress MixedReturnStatement */
            return $handler($request, $options);
        };
    }

    /** @psalm-return HttpClient */
    private function createLoggedGuzzleClient(string $logFile): HttpClient
    {
        $stack = $this->buildHandlerStackWithLogging($logFile);
        return new HttpClient(['handler' => $stack]);
    }
}
