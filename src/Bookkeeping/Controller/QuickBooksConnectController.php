<?php

declare(strict_types=1);

namespace App\Bookkeeping\Controller;

use App\Auth\Client\Intuit;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\BaseController;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\AuthClient\OAuthToken;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The one-time interactive "Connect to QuickBooks" flow, triggered from
 * the new Online Bookkeeping settings tab. Deliberately its own small
 * controller rather than a case in App\Auth\Controller\AuthController's
 * callback `match` -- that controller identifies/links a *user* of this
 * app (see its Callback trait's HMRC/Google/GitHub flows); this connects
 * this app instance to a QuickBooks *company* for bookkeeping export, an
 * unrelated concern. Mirrors AdyenHmacKeyVerificationController's shape
 * (a single settings-page action, compute/act then flash then redirect
 * back to the settings tab it came from) rather than growing
 * SettingController further.
 *
 * The resulting token is persisted straight to Settings, not session --
 * the one deliberate divergence from every other yii-auth-client provider
 * in this app, since QuickBooksGateway needs a token that survives for a
 * background console export job, not just one browser session.
 */
final class QuickBooksConnectController extends BaseController
{
    protected string $controllerName = 'bookkeeping';

    private const string CALLBACK_ROUTE = 'bookkeeping/quickbooksCallback';
    private const string SETTINGS_TAB_ROUTE = 'setting/tabIndex';
    private const string SETTINGS_TAB = 'online-bookkeeping';

    private const string KEY_CLIENT_ID = 'bookkeeping_quickbooks_client_id';
    private const string KEY_CLIENT_SECRET = 'bookkeeping_quickbooks_client_secret';
    private const string KEY_REALM_ID = 'bookkeeping_quickbooks_realm_id';
    private const string KEY_REFRESH_TOKEN = 'bookkeeping_quickbooks_refresh_token';
    private const string KEY_ACCESS_TOKEN = 'bookkeeping_quickbooks_access_token';
    private const string KEY_ACCESS_TOKEN_EXPIRES_AT = 'bookkeeping_quickbooks_access_token_expires_at';

    public function __construct(
        private readonly Intuit $intuit,
        private readonly UrlGenerator $urlGenerator,
        private readonly ResponseFactoryInterface $responseFactory,
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        sR $sR,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    public function connect(Request $request): Response
    {
        $clientId = $this->sR->getSetting(self::KEY_CLIENT_ID);
        $clientSecret = $this->decodedSetting(self::KEY_CLIENT_SECRET);
        if ($clientId === '' || $clientSecret === '') {
            return $this->flashAndRedirect('warning', 'bookkeeping.quickbooks.not.configured');
        }

        $this->configureIntuit($clientId, $clientSecret);
        $authUrl = $this->intuit->buildAuthUrl($request);

        return $this->responseFactory->createResponse(302)->withHeader('Location', $authUrl);
    }

    public function callback(Request $request): Response
    {
        $context = $this->validateCallbackRequest($request);
        if ($context instanceof Response) {
            return $context;
        }

        $this->configureIntuit($context['clientId'], $context['clientSecret']);

        $token = $this->fetchToken($request, $context['code']);
        if ($token instanceof Response) {
            return $token;
        }

        $this->persistSetting(self::KEY_REALM_ID, $context['realmId']);
        $this->persistSetting(self::KEY_REFRESH_TOKEN, (string) $this->sR->encode((string) $token->getParam('refresh_token')));
        $this->persistSetting(self::KEY_ACCESS_TOKEN, (string) $this->sR->encode((string) $token->getParam('access_token')));
        $this->persistSetting(self::KEY_ACCESS_TOKEN_EXPIRES_AT, (string) (time() + (int) $token->getParam('expires_in')));

        return $this->flashAndRedirect('info', 'bookkeeping.quickbooks.connect.success');
    }

    /**
     * First half of callback()'s guard-clause chain -- split out purely to
     * keep both this method and callback() itself under SonarQube's
     * php:S1142 return-count cap (3), matching AdyenPaymentController::
     * loadAdyenInvoiceContext()'s precedent for this exact pattern.
     *
     * @return Response|array{clientId: string, clientSecret: string, code: string, realmId: string}
     */
    private function validateCallbackRequest(Request $request): Response|array
    {
        $query = $request->getQueryParams();
        $error = (string) ($query['error'] ?? '');
        $code = (string) ($query['code'] ?? '');
        $realmId = (string) ($query['realmId'] ?? '');
        if ($error !== '' || $code === '' || $realmId === '') {
            return $this->flashAndRedirect('danger', 'bookkeeping.quickbooks.connect.cancelled');
        }

        $clientId = $this->sR->getSetting(self::KEY_CLIENT_ID);
        $clientSecret = $this->decodedSetting(self::KEY_CLIENT_SECRET);

        return ($clientId === '' || $clientSecret === '')
            ? $this->flashAndRedirect('warning', 'bookkeeping.quickbooks.not.configured')
            : ['clientId' => $clientId, 'clientSecret' => $clientSecret, 'code' => $code, 'realmId' => $realmId];
    }

    /**
     * Second half of callback()'s guard-clause chain -- see
     * validateCallbackRequest()'s docblock.
     */
    private function fetchToken(Request $request, string $code): Response|OAuthToken
    {
        try {
            $token = $this->intuit->fetchAccessToken($request, $code);
        } catch (InvalidArgumentException | ClientExceptionInterface $e) {
            $translationKey = $e instanceof InvalidArgumentException
                ? 'bookkeeping.quickbooks.connect.invalid.state'
                : 'bookkeeping.quickbooks.connect.unexpected.response';
            return $this->flashAndRedirect('danger', $translationKey);
        }

        $accessToken = (string) $token->getParam('access_token');
        $refreshToken = (string) $token->getParam('refresh_token');
        $expiresIn = $token->getParam('expires_in');
        if ($accessToken === '' || $refreshToken === '' || !is_numeric($expiresIn)) {
            return $this->flashAndRedirect('danger', 'bookkeeping.quickbooks.connect.unexpected.response');
        }

        return $token;
    }

    private function flashAndRedirect(string $level, string $translationKey): Response
    {
        $this->flashMessage($level, $this->translator->translate($translationKey));
        return $this->redirectToSettingsTab();
    }

    private function configureIntuit(string $clientId, string $clientSecret): void
    {
        $this->intuit->setClientId($clientId);
        $this->intuit->setClientSecret($clientSecret);
        $this->intuit->setOauth2ReturnUrl($this->urlGenerator->generateAbsolute(self::CALLBACK_ROUTE));
    }

    private function decodedSetting(string $key): string
    {
        $encoded = $this->sR->getSetting($key);
        return $encoded !== '' ? (string) $this->sR->decode($encoded) : '';
    }

    private function persistSetting(string $key, string $value): void
    {
        $setting = $this->sR->withKey($key) ?? new Setting(setting_key: $key);
        $setting->setSettingValue($value);
        $this->sR->save($setting);
    }

    private function redirectToSettingsTab(): Response
    {
        return $this->webService->getRedirectResponse(
            self::SETTINGS_TAB_ROUTE,
            [],
            ['active' => self::SETTINGS_TAB],
        );
    }
}
