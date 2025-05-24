<?php

declare(strict_types=1);

namespace App\Backend\Controller;

use App\Invoice\BaseController;
use App\Invoice\Setting\SettingRepository as SR;
use App\Service\WebControllerService;
use App\User\UserService;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\AuthClient\RequestUtil;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class HmrcController extends BaseController
{
    protected string $controllerName = 'hmrc';

    public function __construct(
        private HttpClient $httpClient,
        private RequestFactoryInterface $requestFactory,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->session = $session;
        $this->sR = $sR;
        $this->translator = $translator;
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer->withViewPath('@hmrc');
    }

    public function index(): \Yiisoft\DataResponse\DataResponse
    {
        $parameters = [
            'text' => 'This is a text',
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * Not tested yet 23/05/2025
     * $api e.g. 'self-assessment', 'vat', 'employment', 'customs', 'individuals'
     * @param string $api
     * @return Response
     */
    public function fphFeedback(
        #[RouteArgument('api')] string $api
    ): Response {
        $logFile = $this->sR->specificCommonConfigAliase('@hmrc') . '/hmrc-requests.log';
        $otpReference = (string)$this->session->get('otpRef');
        $client = $this->createLoggedGuzzleClient($logFile);

        return $client->post($this->getFphValidationFeedbackUrl($api), [
            'headers' => $this->getWebAppViaServerHeaders($otpReference),
        ]);
    }

    public function fphValidate(): Response
    {
        $headers = [];
        $otp = (int)$this->session->get('otp');
        $otpReference = (string)$this->session->get('otpRef');
        if ($otp > 99999 && $otp < 1000000 && strlen($otpReference) > 0) {
            $headers = $this->getWebAppViaServerHeaders($otpReference);

            $tokenString = (string)$this->session->get('hmrc_access_token');

            if (strlen($tokenString) > 0) {
                $requestPartOne = $this->createRequest('GET', $this->getfphValidateHeadersUrl());

                $acceptAndAuthorizationArray = [
                    'Accept' => 'application/vnd.hmrc.1.0+json',
                    'Authorization' => 'Bearer ' . $tokenString,
                ];

                $mergedArray = array_merge($acceptAndAuthorizationArray, $headers);

                $requestPartTwo = RequestUtil::addHeaders(
                    $requestPartOne,
                    $mergedArray,
                );

                return $this->sendRequest($requestPartTwo);
            }

            return $this->webService->getRedirectResponse('invoice/index');
        }

        return $this->webService->getRedirectResponse('invoice/index');
    }

    private function getWebAppViaServerHeaders(string $otpReference): array
    {
        return
            $this->WebAppViaServerBuildArrayFromStrings(
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

    /**
     * @see scope
     * $api = "self-assessment" / "vat" / "employment" / "customs" / "individuals"
     * @param string $api
     * @return string
     */
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

    public function createTestUserIndividual(array $requestBody = []): array
    {
        /**
         * @see src\Auth\Controller\AuthController
         *      function callbackDeveloperSandboxHmrc
         */
        $tokenString = (string)$this->session->get('hmrc_access_token');

        if (strlen($tokenString) > 0) {
            // Define the URL for the create-test-user/individuals endpoint
            $url = 'https://test-api.service.hmrc.gov.uk/create-test-user/individuals';

            // Create a POST request
            $request = $this->createRequest('POST', $url);

            // Add necessary headers, including the access token
            $request = RequestUtil::addHeaders(
                $request,
                [
                    'Authorization' => 'Bearer ' . $tokenString,
                    'Content-Type' => 'application/json',
                ]
            );

            // Add the JSON payload to the request body
            $request = $request->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor(json_encode($requestBody))
            );

            // Send the request and retrieve the response
            $response = $this->sendRequest($request);

            // Decode the JSON response into an array and return it
            return (array)json_decode($response->getBody()->getContents(), true);
        }

        return [];
    }

    /**
     * Note: The connection method determines what headers are included
     *       16 headers are required for the WebAppViaServer Method
     *       Insert this array into TestFraudPreventionHeaders function below
     * @see https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/
     * @see https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/web-app-via-server/
     * @param string $govClientConnectionMethod
     * @param string $govClientBrowserJsUserAgent
     * @param string $govClientDeviceID
     * @param string $govClientMultiFactor
     * @param string $govClientPublicIp
     * @param string $govClientPublicIpTimestamp
     * @param int $govClientPublicPort
     * @param string $govClientScreens
     * @param string $govClientTimezone
     * @param string $govClientUserIds
     * @param string $govClientWindowSize
     * @param string $govVendorForwarded
     * @param string $govVendorLicenseIDs
     * @param string $govVendorProductName
     * @param string $govVendorPublicIP
     * @param string $govVendorVersion
     * @return array
     */
    public function WebAppViaServerBuildArrayFromStrings(
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
        string $govVendorVersion
    ): array {
        return [
            // Example: "WEB_APP_VIA_SERVER"
            'Gov-Client-Connection-Method' => $govClientConnectionMethod,

            // Example: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36"
            'Gov-Client-Browser-JS-User-Agent' => $govClientBrowserJsUserAgent,

            // Example: "c1a8843e-1e2c-4b9e-8d8a-6e2b1f6f8b96"
            'Gov-Client-Device-ID' => $govClientDeviceID,

            // Example: "type=totp;timestamp=2021-07-21T17:32:28Z"
            'Gov-Client-Multi-Factor' => $govClientMultiFactor,

            // Example: "203.0.113.0"
            'Gov-Client-Public-Ip' => $govClientPublicIp,

            // Example: "2025-05-17T10:08:05Z"
            'Gov-Client-Public-IP-Timestamp' => $govClientPublicIpTimestamp,

            // Example: 54321
            'Gov-Client-Public-Port' => $govClientPublicPort,

            // Example: "1920x1080,1280x1024"
            'Gov-Client-Screens' => $govClientScreens,

            // Example: "Europe/London"
            'Gov-Client-Timezone' => $govClientTimezone,

            'Gov-Client-User-IDs' => $govClientUserIds,

            // Example: "1920x1040"
            'Gov-Client-Window-Size' => $govClientWindowSize,

            // Example: "for=203.0.113.43;proto=https;by=203.0.113.1"
            'Gov-Vendor-Forwarded' => $govVendorForwarded,

            // Example: ["lic-12345", "lic-67890"]
            'Gov-Vendor-License-IDs' => $govVendorLicenseIDs,

            // Example: "invoice-app"
            'Gov-Vendor-Product-Name' => $govVendorProductName,

            // Example: "198.51.100.1"
            'Gov-Vendor-Public-IP' => $govVendorPublicIP,

            // Example: "1.0.0"
            'Gov-Vendor-Version' => $govVendorVersion];
    }

    /**
     * @psalm-return HandlerStack
     */
    private function buildHandlerStackWithLogging(string $logFile): HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push($this->getRequestLoggingMiddleware($logFile));
        return $stack;
    }

    /**
     * Returns a Guzzle middleware that logs request details.
     *
     * @param string $logFile
     * @return callable(callable): callable
     */
    private function getRequestLoggingMiddleware(string $logFile): callable
    {
        return function (callable $handler) use ($logFile): callable {
            return function (Request $request, array $options) use ($handler, $logFile): PromiseInterface {
                $headersJson = json_encode($request->getHeaders(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                // Prevent log corruption if encoding fails
                if ($headersJson === false) {
                    $headersJson = 'Error encoding headers';
                }

                $logEntry = sprintf(
                    "[%s] %s %s\nHeaders: %s\n\n",
                    date('c'),
                    $request->getMethod(),
                    (string) $request->getUri(),
                    $headersJson
                );
                // Use file_put_contents with LOCK_EX to prevent concurrent writes
                file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

                /**
                 * Call the next handler in the stack
                 * @psalm-suppress MixedReturnStatement
                 */
                return $handler($request, $options);
            };
        };
    }

    /**
     * @psalm-return HttpClient
     */
    private function createLoggedGuzzleClient(string $logFile): HttpClient
    {
        $stack = $this->buildHandlerStackWithLogging($logFile);
        return new HttpClient([
            'handler' => $stack,
        ]);
    }
}
