<?php

declare(strict_types=1);

namespace App\Backend\Controller;

use App\Auth\Client\DeveloperSandboxHmrc;
use App\Auth\Client\HmrcApiCatalogue;
use App\Auth\Client\HmrcDeveloperHubLinks;
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
use Yiisoft\Html\Html;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
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
        private DeveloperSandboxHmrc $developerSandboxHmrc,
        private HttpClient $httpClient,
        private RequestFactoryInterface $requestFactory,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
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
        // Live-testing fix 2026-09-10: this used to chain off the raw
        // $webViewRenderer constructor parameter, which discarded the
        // permission-based layout parent::__construct() already picked
        // via BaseController::initializeViewRenderer() (the same
        // mechanism every other BaseController subclass relies on to
        // get invoice.php/guest.php instead of the DI-default public
        // soletrader/main.php layout) -- reported live as a large blank
        // gap between the navbar and page content, since this
        // controller had silently been rendering every page with the
        // public marketing layout regardless of the logged-in admin's
        // actual permissions. Chaining off $this->webViewRenderer
        // (already correctly configured by parent::__construct())
        // preserves that layout selection while still applying the
        // @hmrc view path on top of it.
        //
        // Live-testing fix 2026-09-10 (same day, second pass): that fix
        // alone broke every view with ViewNotFoundException ("...
        // hmrc/hmrc/index.php does not exist") -- initializeViewRenderer()
        // also calls withControllerName($this->controllerName) (='hmrc'
        // for this class), and WebViewRenderer::getViewPath() appends
        // '/' . $name onto the resolved viewPath alias unconditionally
        // (see vendor/yiisoft/yii-view-renderer's own source). The
        // '@hmrc' alias (config/common/params.php) already resolves
        // straight to resources/backend/views/hmrc -- the exact final
        // view directory, no controller-name subfolder underneath it --
        // so that automatic suffix duplicated it. withControllerName('')
        // clears it (WebViewRenderer treats an empty name as "append
        // nothing"), same as the raw constructor parameter effectively
        // had before this whole layout fix (it never had
        // withControllerName() applied to it at all).
        $this->webViewRenderer = $this->webViewRenderer
            ->withViewPath('@hmrc')
            ->withControllerName('');
    }

    public function index(): Response
    {
        $grantedScope  = (string) $this->session->get('hmrc_scope', '');
        $subscriptions = $this->fetchApplicationSubscriptions();
        if ($grantedScope !== '') {
            $availableApis = HmrcApiCatalogue::fromGrantedScopeString($grantedScope);
        } elseif ($subscriptions !== []) {
            $availableApis = HmrcApiCatalogue::fromSubscriptions($subscriptions);
        } else {
            $availableApis = [];
        }
        $hmrcAuthUrl = $this->hmrcAuthUrl();
        $developerHubAppId = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_APPLICATION_ID']
            ?? '';

        return $this->webViewRenderer->render('index', [
            'alert'                => $this->alert(),
            'vrn'                  => $this->sR->getSetting('vat_registration_number'),
            'nino'                 => $this->sR->getSetting('nino'),
            'fphConnectionMethod'  => $this->sR->getSetting('fph_connection_method'),
            'govVendorProductName' => $this->sR->getGovVendorProductName(),
            'govVendorVersion'     => $this->sR->getGovVendorVersion(),
            'grantedScope'         => $grantedScope,
            'availableApis'        => $availableApis,
            'fullCatalogue'        => HmrcApiCatalogue::all(),
            'subscriptionsLoaded'  => $subscriptions !== [],
            'hmrcAuthUrl'          => $hmrcAuthUrl,
            'developerHubAppId'    => $developerHubAppId,
        ]);
    }

    /**
     * Live-tested 2026-09-09 against HMRC's real Test Fraud Prevention
     * Headers API (`txm-fph-validator-api`) OAS spec -- two real bugs found
     * and fixed here: this endpoint is GET, not POST, and `$api` must be
     * one of the spec's real `{service}-mtd` identifiers (e.g. `vat-mtd`),
     * not a bare `vat`/`self-assessment`/etc. -- both would 404 with
     * `MATCHING_RESOURCE_NOT_FOUND` otherwise (confirmed live). See
     * `resources/backend/views/hmrc/index.php`'s own link for the caller.
     * Full enum: https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/txm-fph-validator-api/1.0/oas/resolved
     *
     * Live-testing fix 2026-09-10: two more real bugs found together.
     *
     * 1. This called Guzzle's own get() (http_errors defaults to true
     *    there, unlike the PSR-18 sendRequest() every other action in
     *    this controller uses, which never throws for an HTTP error
     *    status) -- any non-2xx crashed with an uncaught
     *    GuzzleHttp\Exception\ClientException rendered as a raw stack
     *    trace, confirmed live (401 INVALID_CREDENTIALS). Switched to
     *    the same createRequest()/sendRequest() pattern as everything
     *    else here.
     *
     * 2. That 401 was real, not just badly presented: this endpoint
     *    (GET .../validation-feedback) is application-restricted per
     *    its own OAS spec -- it needs a Client Credentials grant token
     *    (this app's own client_id/secret exchanged directly, no user
     *    involved), not the user-restricted 3-legged OAuth token
     *    (hmrc_access_token) every other action here correctly uses.
     *    Fetches its own token via requestClientCredentialsToken()
     *    rather than reading the session's user token.
     *
     * Also added the Accept header the spec requires (missing before)
     * and a proper results view instead of returning HMRC's raw
     * response -- same reasoning as fphValidate()'s own fix.
     */
    public function fphFeedback(
        #[RouteArgument('api')]
        string $api,
    ): Response {
        $clientId = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_SECRET']
            ?? '';
        if ($clientId === '' || $clientSecret === '') {
            $this->flashMessage(
                'danger',
                $this->translator->translate(
                    'mtd.fph.feedback.no.client.credentials.token',
                ),
            );
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $tokenResponse = $this->requestClientCredentialsToken(
            $clientId,
            $clientSecret,
        );
        /** @var array<string, mixed> $tokenParsed */
        $tokenParsed = (array) json_decode(
            $tokenResponse->getBody()->getContents(),
            true,
        );

        if ($tokenResponse->getStatusCode() !== 200) {
            $this->flashClientCredentialsTokenError($tokenParsed);
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $hasAccessToken = isset($tokenParsed['access_token'])
            && is_string($tokenParsed['access_token'])
            && $tokenParsed['access_token'] !== '';
        if (!$hasAccessToken) {
            $this->flashMessage(
                'danger',
                $this->translator->translate(
                    'mtd.fph.feedback.unexpected.token.response',
                ),
            );
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $accessToken = $tokenParsed['access_token'];

        $otpReference = (string) $this->session->get('otpRef');
        $logFile = $this->sR->specificCommonConfigAliase('@hmrc')
            . '/hmrc-requests.log';

        $request = $this->createRequest(
            'GET',
            $this->getFphValidationFeedbackUrl($api),
        );
        $request = RequestUtil::addHeaders($request, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.1.0+json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        // PSR-18 sendRequest() (not Guzzle's own get()) so an HTTP error
        // status comes back as a normal Response, not a thrown
        // exception -- same reasoning as the docblock above. Uses its
        // own logged client rather than $this->sendRequest() purely so
        // this specific request is still written to hmrc-requests.log,
        // same as before this fix.
        $loggedClient = $this->createLoggedGuzzleClient($logFile);
        $apiResponse = $loggedClient->sendRequest($request);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode(
            $apiResponse->getBody()->getContents(),
            true,
        );

        if ($apiResponse->getStatusCode() !== 200) {
            $this->flashFphValidateError($parsed);
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        /** @var list<array<string, mixed>> $requests */
        $requests = $parsed['requests'] ?? [];

        return $this->webViewRenderer->render('fphFeedback', [
            'alert'    => $this->alert(),
            'api'      => $api,
            'requests' => $requests,
        ]);
    }

    /**
     * Application-restricted OAuth 2.0 Client Credentials grant -- this
     * app's own client_id/secret exchanged directly for a token, no
     * user session involved (distinct from the 3-legged
     * hmrc_access_token every user-restricted action here uses). Returns
     * the raw response rather than just a token/null -- fphFeedback()
     * needs the body on failure too, to show the real reason (see
     * flashClientCredentialsTokenError()'s own docblock for why a
     * generic "could not get a token" message wasn't good enough live).
     *
     * Live-testing fix 2026-09-10: this used to hardcode the production
     * token host (api.service.hmrc.gov.uk) unconditionally -- confirmed
     * live as wrong ("invalid_client -- invalid client id or secret")
     * and against HMRC's own application-restricted-endpoints guide
     * (https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation/application-restricted-endpoints,
     * "The example URLs shown below are for the sandbox environment
     * only. In the production environment you should use
     * https://api.service.hmrc.gov.uk") that sandbox testing must POST
     * to test-api.service.hmrc.gov.uk/oauth/token instead. The only
     * caller of this method (fphFeedback(), via
     * getFphValidationFeedbackUrl()) exists purely to exercise HMRC's
     * Test Fraud Prevention Headers API, which this file's own
     * resolveHmrcApiBaseUrl() docblock already documents as sandbox-only
     * tooling that doesn't exist in production at all -- so this app's
     * client_id/secret for it is necessarily a sandbox-only Developer
     * Hub application, one production's own OAuth server has never
     * heard of. Always using the sandbox host here, unconditionally,
     * matches getFphValidateHeadersUrl()/getFphValidationFeedbackUrl()'s
     * own deliberate hardcoding for the exact same reason.
     */
    private function requestClientCredentialsToken(
        string $clientId,
        string $clientSecret,
    ): Response {
        $body = http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $tokenUrl = 'https://test-api.service.hmrc.gov.uk/oauth/token';
        $request = $this->createRequest('POST', $tokenUrl);
        $request = RequestUtil::addHeaders($request, [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept'       => 'application/json',
        ]);
        $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($body));

        return $this->sendRequest($request);
    }

    /**
     * Live-testing fix 2026-09-10: the first version of this flow just
     * flashed a generic "could not get a token" message for every
     * token-request failure -- reported live as unhelpful when
     * client_id/secret were genuinely configured and the real problem
     * was HMRC rejecting the request for a different reason (e.g. the
     * application not being enabled for the Client Credentials grant
     * type). This endpoint's errors use the plain OAuth 2.0 RFC 6749
     * shape (error/error_description) -- confirmed via HMRC's own
     * reference guide -- not the code/message shape every other HMRC
     * endpoint in this app uses (flashFphValidateError() handles that
     * one), so it needs its own parsing.
     *
     * @param array<string, mixed> $parsed
     */
    private function flashClientCredentialsTokenError(array $parsed): void
    {
        $error = (string) ($parsed['error'] ?? '');
        $description = (string) ($parsed['error_description'] ?? '');
        if ($error === '' && $description === '') {
            $description = 'HMRC returned an unreadable error response.';
        }

        $this->flashMessage('danger', $this->translator->translate(
            'mtd.fph.feedback.client.credentials.error',
            ['error' => $error, 'description' => $description],
        ));
    }

    /**
     * Live-testing fix 2026-09-10: this used to return HMRC's raw
     * response as-is -- fine for a 200 (still just raw JSON, now
     * rendered properly below instead), but an HMRC platform-level
     * error like RESOURCE_FORBIDDEN ("The application is not
     * subscribed to the API which it is attempting to invoke") showed
     * up as an unstyled raw JSON blob with no explanation. Now parsed
     * and turned into an explicit flash message -- including a direct
     * link to this application's own Subscriptions page
     * (https://developer.service.hmrc.gov.uk/developer/applications/{id}/subscriptions)
     * when RESOURCE_FORBIDDEN is the specific cause, since that's
     * exactly the page that fixes it.
     *
     * Live-testing fix 2026-09-10 (later same day): both early-exit
     * guards below used to redirect straight to invoice/index -- the
     * general dashboard, not even back to backend/hmrc -- with no flash
     * message at all. From the "Test FPH Headers" button on
     * backend/hmrc that read as the button "going nowhere" (reported
     * live): the page changes, but to something that doesn't look like
     * anything happened, no explanation why. Both guards now flash an
     * explicit reason and redirect back to backend/hmrc/index, matching
     * the pattern already used for the HMRC-side errors above.
     */
    public function fphValidate(): Response
    {
        $otp = (int) $this->session->get('otp');
        $otpReference = (string) $this->session->get('otpRef');
        if ($otp <= 99999 || $otp >= 1000000 || strlen($otpReference) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.fph.missing.otp.session'),
            );
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $tokenString = (string) $this->session->get('hmrc_access_token');
        if (strlen($tokenString) === 0) {
            $this->flashMessage('warning', $this->translator->translate(
                'mtd.fph.missing.hmrc.token',
                ['link' => $this->hmrcLoginLink()],
            ));
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $headers = $this->getWebAppViaServerHeaders($otpReference);
        $requestPartOne = $this->createRequest(
            'GET',
            $this->getFphValidateHeadersUrl(),
        );

        $acceptAndAuthorizationArray = [
            'Accept' => 'application/vnd.hmrc.1.0+json',
            'Authorization' => 'Bearer ' . $tokenString,
        ];

        $mergedArray = array_merge($acceptAndAuthorizationArray, $headers);

        $requestPartTwo = RequestUtil::addHeaders($requestPartOne, $mergedArray);

        $apiResponse = $this->sendRequest($requestPartTwo);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode(
            $apiResponse->getBody()->getContents(),
            true,
        );

        if ($apiResponse->getStatusCode() !== 200) {
            $this->flashFphValidateError($parsed);
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        /** @var list<array<string, mixed>> $errors */
        $errors = $parsed['errors'] ?? [];
        /** @var list<array<string, mixed>> $warnings */
        $warnings = $parsed['warnings'] ?? [];

        return $this->webViewRenderer->render('fphValidate', [
            'alert'       => $this->alert(),
            'specVersion' => (string) ($parsed['specVersion'] ?? ''),
            'code'        => (string) ($parsed['code'] ?? ''),
            'message'     => (string) ($parsed['message'] ?? ''),
            'errors'      => $errors,
            'warnings'    => $warnings,
        ]);
    }

    /**
     * The "Log in with HMRC" OAuth2 authorization URL, or '' if the
     * OAuth client isn't configured (no client_id) -- same condition
     * index() has always gated its own "Log in with HMRC" button on.
     * Extracted so both index() and hmrcLoginLink() below share the
     * exact same check rather than duplicating it.
     */
    private function hmrcAuthUrl(): string
    {
        if ($this->developerSandboxHmrc->getClientId() === '') {
            return '';
        }

        return $this->urlGenerator->generate(
            'auth/authclient',
            ['authclient' => 'developersandboxhmrc'],
        );
    }

    /**
     * "Log in with HMRC" as a real link when OAuth is configured (so a
     * flash message can send the user straight there, not just tell
     * them to find the button themselves), plain text otherwise.
     */
    private function hmrcLoginLink(): string
    {
        $url = $this->hmrcAuthUrl();
        if ($url === '') {
            return 'Log in with HMRC';
        }

        return (string) Html::a('Log in with HMRC', $url);
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function flashFphValidateError(array $parsed): void
    {
        $code = (string) ($parsed['code'] ?? '');
        $message = (string) ($parsed['message'] ?? '');
        if ($code === '' && $message === '') {
            $message = 'HMRC returned an unreadable error response.';
        }

        $developerHubAppId = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_APPLICATION_ID']
            ?? '';
        $subscriptionsUrl = HmrcDeveloperHubLinks::applicationSubscriptionsUrl(
            $developerHubAppId,
        );

        if ($code === 'RESOURCE_FORBIDDEN' && $subscriptionsUrl !== '') {
            $this->flashMessage('danger', $this->translator->translate(
                'mtd.fph.validate.error.not.subscribed',
                [
                    'code'    => $code,
                    'message' => $message,
                    'link'    => Html::a(
                        $this->translator->translate(
                            'mtd.fph.manage.subscriptions.link.text',
                        ),
                        $subscriptionsUrl,
                        ['target' => '_blank', 'rel' => 'noopener noreferrer'],
                    ),
                ],
            ));
            return;
        }

        $this->flashMessage('danger', $this->translator->translate(
            'mtd.fph.validate.error.generic',
            ['code' => $code, 'message' => $message],
        ));
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
            return $this->webService->getRedirectResponse('backend/hmrc/index');
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

    /**
     * List every business (of any type) registered for the configured NINO.
     *
     * Live-testing fix 2026-09-10 (third pass): this read the NINO from
     * session ('hmrc_nino'), which nothing anywhere in this app ever
     * wrote -- every request landed here with an empty NINO and bounced
     * straight back to backend/hmrc's own "missing vrn or token" flash,
     * regardless of which API was picked. NINO now comes from the same
     * place VRN already does (Settings -> Making Tax Digital, a plain
     * getSetting() call), matching partial_settings_making_tax_digital.php's
     * own new 'nino' field.
     *
     * Live-testing fix 2026-09-10: this used to call the
     * self-employment-business-api's own "list all businesses" endpoint
     * (GET .../self-employment/{nino}/self-employments) -- confirmed
     * against that API's real current OAS spec (version 5.0) that this
     * endpoint no longer exists; every endpoint there now needs an
     * already-known businessId (annual/period/cumulative submissions),
     * nothing to discover one with. Business discovery moved to a
     * different API entirely -- Business Details (MTD)'s own /list
     * endpoint, which returns every business type (self-employment,
     * uk-property, foreign-property, property-unspecified) keyed by
     * typeOfBusiness. Needs its own Developer Hub subscription (Business
     * Details (MTD)), separate from Self Employment Business.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/business-details-api/2.0
     *
     * Live-testing fix 2026-09-10 (same day, second pass): this used to
     * filter the /list response down to typeOfBusiness === 'self-employment'
     * -- reasonable when the only way here was the "Self-employed Business"
     * catalogue entry, but HmrcApiCatalogue::routeFor() also sends the
     * "Business Details" catalogue entry to this same action (deliberately,
     * per its own docblock -- Business Details is the API that actually
     * backs this page now), and a NINO whose businesses are all
     * uk-property/foreign-property made that entry point look like it
     * "returned nothing". No filtering now -- every business type is
     * shown, with its type as its own column, so both catalogue entries
     * get a correct result.
     */
    public function selfEmploymentBusinesses(): Response
    {
        $nino        = $this->sR->getSetting('nino');
        $tokenString = (string) $this->session->get('hmrc_access_token');
        $otpReference = (string) $this->session->get('otpRef');

        if ($nino === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.business.missing.nino.or.token')
            );
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $request = $this->createRequest(
            'GET',
            $this->resolveHmrcApiBaseUrl() . '/individuals/business/details/'
                . urlencode($nino) . '/list',
        );

        $request = RequestUtil::addHeaders($request, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.2.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        $apiResponse = $this->sendRequest($request);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode($apiResponse->getBody()->getContents(), true);
        /** @var list<array<string, mixed>> $allBusinesses */
        $allBusinesses = $parsed['listOfBusinesses'] ?? [];

        return $this->webViewRenderer->render('selfEmploymentBusinesses', [
            'alert'         => $this->alert(),
            'nino'          => $nino,
            'statusCode'    => $apiResponse->getStatusCode(),
            'businesses'    => $allBusinesses,
            'raw'           => $parsed,
        ]);
    }

    /**
     * Retrieve every MTD obligation for the configured NINO across all
     * income sources -- quarterly-update/EOPS deadlines via
     * income-and-expenditure, plus the once-per-NINO final declaration
     * deadline via crystallisation. Confirmed live against HMRC's real
     * obligations-api/3.0 OAS spec (fetched 2026-09-10, not guessed):
     * GET .../obligations/details/{nino}/income-and-expenditure and
     * GET .../obligations/details/{nino}/crystallisation, both needing
     * only read:self-assessment -- the same NINO/token every other
     * ITSA-family action here already uses.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/obligations-api/3.0
     */
    public function incomeTaxObligations(): Response
    {
        $nino         = $this->sR->getSetting('nino');
        $tokenString  = (string) $this->session->get('hmrc_access_token');
        $otpReference = (string) $this->session->get('otpRef');

        if ($nino === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.business.missing.nino.or.token'),
            );
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $headers = array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.3.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        );
        $base = $this->resolveHmrcApiBaseUrl() . '/obligations/details/'
            . urlencode($nino);

        $incomeResponse = $this->sendRequest(RequestUtil::addHeaders(
            $this->createRequest('GET', $base . '/income-and-expenditure'),
            $headers,
        ));
        /** @var array<string, mixed> $incomeParsed */
        $incomeParsed = (array) json_decode(
            $incomeResponse->getBody()->getContents(),
            true,
        );

        $crystallisationResponse = $this->sendRequest(RequestUtil::addHeaders(
            $this->createRequest('GET', $base . '/crystallisation'),
            $headers,
        ));
        /** @var array<string, mixed> $crystallisationParsed */
        $crystallisationParsed = (array) json_decode(
            $crystallisationResponse->getBody()->getContents(),
            true,
        );

        return $this->webViewRenderer->render('incomeTaxObligations', [
            'alert'                      => $this->alert(),
            'nino'                       => $nino,
            'incomeStatusCode'           => $incomeResponse->getStatusCode(),
            'incomeObligations'          =>
                $this->flattenIncomeExpenditureObligations($incomeParsed),
            'crystallisationStatusCode'  =>
                $crystallisationResponse->getStatusCode(),
            'crystallisationObligations' =>
                $this->normalizeCrystallisationObligations($crystallisationParsed),
        ]);
    }

    /**
     * Flattens the income-and-expenditure endpoint's per-business
     * grouping (obligations[].obligationDetails[]) into one flat list
     * for the view -- see incomeTaxObligations()'s own docblock for the
     * real response shape this mirrors.
     *
     * @param array<string, mixed> $parsed
     * @return list<array{
     *     typeOfBusiness: string,
     *     businessId: string,
     *     periodStartDate: string,
     *     periodEndDate: string,
     *     dueDate: string,
     *     status: string,
     *     receivedDate: string,
     * }>
     */
    private function flattenIncomeExpenditureObligations(array $parsed): array
    {
        /** @var list<array<string, mixed>> $groups */
        $groups    = (array) ($parsed['obligations'] ?? []);
        $flattened = [];
        foreach ($groups as $group) {
            $typeOfBusiness = isset($group['typeOfBusiness'])
                ? (string) $group['typeOfBusiness']
                : '';
            $businessId = isset($group['businessId'])
                ? (string) $group['businessId']
                : '';
            /** @var list<array<string, mixed>> $details */
            $details = (array) ($group['obligationDetails'] ?? []);
            foreach ($details as $detail) {
                $flattened[] = [
                    'typeOfBusiness'  => $typeOfBusiness,
                    'businessId'      => $businessId,
                    'periodStartDate' =>
                        $this->stringOrEmpty($detail, 'periodStartDate'),
                    'periodEndDate' =>
                        $this->stringOrEmpty($detail, 'periodEndDate'),
                    'dueDate' => $this->stringOrEmpty($detail, 'dueDate'),
                    'status' => $this->stringOrEmpty($detail, 'status'),
                    'receivedDate' =>
                        $this->stringOrEmpty($detail, 'receivedDate'),
                ];
            }
        }
        return $flattened;
    }

    /**
     * Normalizes the crystallisation endpoint's already-flat obligations
     * list -- no per-business grouping there, unlike
     * income-and-expenditure (it's once per NINO, not per business).
     *
     * @param array<string, mixed> $parsed
     * @return list<array{
     *     periodStartDate: string,
     *     periodEndDate: string,
     *     dueDate: string,
     *     status: string,
     *     receivedDate: string,
     * }>
     */
    private function normalizeCrystallisationObligations(array $parsed): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows       = (array) ($parsed['obligations'] ?? []);
        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = [
                'periodStartDate' => $this->stringOrEmpty($row, 'periodStartDate'),
                'periodEndDate'   => $this->stringOrEmpty($row, 'periodEndDate'),
                'dueDate'         => $this->stringOrEmpty($row, 'dueDate'),
                'status'          => $this->stringOrEmpty($row, 'status'),
                'receivedDate'    => $this->stringOrEmpty($row, 'receivedDate'),
            ];
        }
        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function stringOrEmpty(array $row, string $key): string
    {
        return isset($row[$key]) ? (string) $row[$key] : '';
    }

    /**
     * Retrieve the MTD ITSA status for the configured NINO and a tax
     * year (defaults to the current UK tax year, overridable via
     * ?taxYear=YYYY-YY). Confirmed live against HMRC's real
     * self-assessment-api/3.0 OAS spec (fetched 2026-09-11, not
     * guessed): GET .../individuals/person/itsa-status/{nino}/{taxYear},
     * needing only read:self-assessment -- already correctly requested
     * via the existing itsaEntry() bundle, no catalogue fix needed for
     * this one.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/self-assessment-api/3.0
     */
    public function itsaStatus(ServerRequest $request): Response
    {
        $nino         = $this->sR->getSetting('nino');
        $tokenString  = (string) $this->session->get('hmrc_access_token');
        $otpReference = (string) $this->session->get('otpRef');

        if ($nino === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.business.missing.nino.or.token'),
            );
            return $this->webService->getRedirectResponse('backend/hmrc/index');
        }

        $queryParams = $request->getQueryParams();
        $taxYear = (string) ($queryParams['taxYear'] ?? $this->currentUkTaxYear());

        $apiRequest = $this->createRequest(
            'GET',
            $this->resolveHmrcApiBaseUrl()
                . '/individuals/person/itsa-status/' . urlencode($nino)
                . '/' . urlencode($taxYear),
        );

        $apiRequest = RequestUtil::addHeaders($apiRequest, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.3.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        $apiResponse = $this->sendRequest($apiRequest);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode(
            $apiResponse->getBody()->getContents(),
            true,
        );

        return $this->webViewRenderer->render('itsaStatus', [
            'alert'       => $this->alert(),
            'nino'        => $nino,
            'taxYear'     => $taxYear,
            'statusCode'  => $apiResponse->getStatusCode(),
            'itsaStatuses' => $this->normalizeItsaStatuses($parsed),
        ]);
    }

    /**
     * Normalizes the itsa-status endpoint's per-tax-year grouping
     * (itsaStatuses[].itsaStatusDetails[]) into one flat list for the
     * view -- see itsaStatus()'s own docblock for the real response
     * shape this mirrors.
     *
     * @param array<string, mixed> $parsed
     * @return list<array{
     *     taxYear: string,
     *     submittedOn: string,
     *     status: string,
     *     statusReason: string,
     *     businessIncome2YearsPrior: string,
     * }>
     */
    private function normalizeItsaStatuses(array $parsed): array
    {
        /** @var list<array<string, mixed>> $groups */
        $groups     = (array) ($parsed['itsaStatuses'] ?? []);
        $normalized = [];
        foreach ($groups as $group) {
            $taxYear = isset($group['taxYear']) ? (string) $group['taxYear'] : '';
            /** @var list<array<string, mixed>> $details */
            $details = (array) ($group['itsaStatusDetails'] ?? []);
            foreach ($details as $detail) {
                $businessIncome = isset($detail['businessIncome2YearsPrior'])
                    ? (string) $detail['businessIncome2YearsPrior']
                    : '';
                $normalized[] = [
                    'taxYear'      => $taxYear,
                    'submittedOn'  => $this->stringOrEmpty($detail, 'submittedOn'),
                    'status'       => $this->stringOrEmpty($detail, 'status'),
                    'statusReason' =>
                        $this->stringOrEmpty($detail, 'statusReason'),
                    'businessIncome2YearsPrior' => $businessIncome,
                ];
            }
        }
        return $normalized;
    }

    /**
     * The current UK tax year in HMRC's own "YYYY-YY" format (e.g.
     * "2026-27"), used as itsaStatus()'s default when no ?taxYear=
     * query parameter is given. The UK tax year runs 6 April to 5
     * April; deliberately a fresh calculation from today's date rather
     * than DateHelper::taxYearToImmutable() -- that method reads a
     * configurable "this_tax_year_from_date" setting used for internal
     * sales-by-year reporting, not necessarily 6 April, whereas HMRC's
     * own tax year boundary is fixed and not user-configurable.
     */
    private function currentUkTaxYear(): string
    {
        $today = new \DateTimeImmutable('today');
        $year = (int) $today->format('Y');
        $boundary = $today->setDate($year, 4, 6);
        $startYear = $today < $boundary ? $year - 1 : $year;
        return $startYear . '-' . substr((string) ($startYear + 1), 2, 2);
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

    /**
     * Calls the HMRC Developer Hub subscriptions endpoint using this app's
     * client_id. Returns the raw subscriptions array, or [] if the endpoint
     * is unreachable or requires developer-portal authentication.
     *
     * @return array<array-key, mixed>
     */
    private function fetchApplicationSubscriptions(): array
    {
        $clientId = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_ID'] ?? '';
        if ($clientId === '') {
            return [];
        }
        $url     = 'https://developer.service.hmrc.gov.uk/developer/api/applications/'
            . urlencode($clientId) . '/subscriptions';
        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Accept', 'application/json');
        try {
            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() === 200) {
                return (array) json_decode($response->getBody()->getContents(), true);
            }
        } catch (\Throwable) {
            // network or auth failure — fall through to empty array
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

    /**
     * Real live-testing bug fixed 2026-09-09: vatObligations()/
     * vatReturnSubmit()/selfEmploymentBusinesses() used to hardcode either
     * the production (`api.service.hmrc.gov.uk`) or sandbox
     * (`test-api.service.hmrc.gov.uk`) host directly, independent of which
     * one the OAuth login actually authenticated against -- a sandbox
     * (dev) token sent to the production host always 401s, which is
     * exactly what surfaced testing against a real HMRC sandbox test user
     * (VRN 931392528). `DeveloperSandboxHmrc::getApiBaseUrl1()` already
     * resolves the correct host, but `setEnvironment()` is only ever
     * called from AuthController/SignupController
     * (`Oauth2::initializeOauth2IdentityProviderDualUrls()`) -- on a
     * later, separate request (like this one) that never happened, so
     * this resolves it fresh from the same `SettingRepository::getEnv()`
     * check that method uses, rather than assuming an earlier request
     * left the injected instance in the right state.
     *
     * Deliberately not used by createTestUserIndividual() or the two
     * fraud-prevention-headers helpers below -- HMRC's Create Test User
     * and Test Fraud Prevention Headers APIs are sandbox-only tooling
     * that doesn't exist in production at all, so those stay hardcoded to
     * test-api.service.hmrc.gov.uk regardless of environment.
     */
    private function resolveHmrcApiBaseUrl(): string
    {
        $environment = $this->sR->getEnv() === 'dev' ? 'dev' : 'prod';
        $this->developerSandboxHmrc->setEnvironment($environment);
        return $this->developerSandboxHmrc->getApiBaseUrl1();
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
    private function webAppViaServerBuildArrayFromStrings(// NOSONAR php:S107 — HMRC fraud-prevention spec mandates exactly 16 header strings
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
            // Redact the bearer token before it ever reaches the log file --
            // the real $request (unmodified) is still what's actually sent
            // to HMRC below; this only affects what gets written to disk.
            $headersForLog = $request->getHeaders();
            foreach (array_keys($headersForLog) as $name) {
                if (strtolower((string) $name) === 'authorization') {
                    $headersForLog[$name] = ['[REDACTED]'];
                }
            }
            $headersJson = json_encode(
                $headersForLog,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );

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
