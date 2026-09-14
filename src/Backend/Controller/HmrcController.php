<?php

declare(strict_types=1);

namespace App\Backend\Controller;

use App\Auth\Client\DeveloperSandboxHmrc;
use App\Auth\Client\HmrcApiCatalogue;
use App\Backend\Controller\Trait\HmrcFphTrait;
use App\Backend\Controller\Trait\HmrcHttpTrait;
use App\Backend\Controller\Trait\HmrcIncomeCategoryTrait;
use App\Backend\Controller\Trait\HmrcIncomeTaxTrait;
use App\Backend\Controller\Trait\HmrcVatTrait;
use App\Invoice\BaseController;
use App\Invoice\Setting\SettingRepository as SR;
use App\Service\WebControllerService;
use App\User\UserService;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\AuthClient\RequestUtil;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * php:S1448 note (2026-09-14): this class used to hold all ~45 of its own
 * actions/helpers directly -- split across 5 traits below by domain (FPH
 * fraud-prevention validate/feedback, VAT, income tax
 * obligations/ITSA/calculations, the 8 thin income-category actions, and
 * HTTP/request-building plumbing), mirroring SettingRepository's own
 * established use of traits for exactly this reason. This is a pure code
 * relocation: every method's name, signature, and body is unchanged, so
 * every route (config/common/routes/*.php references
 * [HmrcController::class, 'methodName']) and every call site keeps working
 * identically -- a trait's methods become part of the composing class's
 * own method table, indistinguishable from the caller's perspective.
 */
final class HmrcController extends BaseController
{
    use HmrcFphTrait;
    use HmrcHttpTrait;
    use HmrcIncomeCategoryTrait;
    use HmrcIncomeTaxTrait;
    use HmrcVatTrait;

    /**
     * php:S1192 -- the redirect target for "something went wrong, back to
     * the dashboard" across every action in the 5 traits above (11 call
     * sites total). Declared here rather than on any one trait since it's
     * genuinely shared across all of them.
     */
    private const string INDEX_ROUTE = 'backend/hmrc/index';

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
     * Informational page for the Customs Declarations API -- see
     * HmrcApiCatalogue::all()'s own comment on the 'customs/declarations'
     * entry for the live-confirmed reasons this is the one catalogue
     * entry this app doesn't offer a real live test for: every one of
     * its five endpoints is a write-only submission of a genuine
     * customs/trade-compliance XML document (not JSON like every other
     * API here), and there's no minimal-effort payload analogous to
     * Individual Calculations' empty-body trigger that HMRC would
     * actually accept -- a "test" that submits invalid data would just
     * fail, proving nothing real. Shows the EORI on file, the five
     * endpoints and their real paths/scope, and the header-based
     * identification scheme (X-Badge-Identifier/X-Submitter-Identifier/
     * X-Eori-Identifier -- a URL path parameter the way NINO/VRN/UTR
     * work elsewhere in this app) so a developer can see exactly what
     * this API needs without this page pretending to exercise it.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/customs-declarations/1.0
     */
    public function customsDeclarationsInfo(): Response
    {
        return $this->webViewRenderer->render('customsDeclarationsInfo', [
            'alert' => $this->alert(),
            'eori'  => $this->sR->getSetting('eori'),
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
}
