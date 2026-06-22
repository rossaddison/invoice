<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use App\Auth\{AuthService, CallbackDeps, Form\LoginForm,
    Form\TwoFactorAuthenticationSetupForm,
    Form\TwoFactorAuthenticationVerifyLoginForm, Trait\Callback, Trait\ClassList,
    Trait\Oauth2, Client\OpenBanking, Permissions, TokenRepository};
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Setting\Trait\OpenBankingProviders;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use App\Infrastructure\Persistence\User\User;
use App\User\UserRepository;
use App\User\RecoveryCodeService;
use OTPHP\TOTP;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\{Assets\AssetManager,
    DataResponse\ResponseFactory\DataResponseFactoryInterface, Factory\Factory,
    FormModel\FormHydrator, Html\Html, Html\Tag\A, Html\Tag\Style, Http\Method,
    Json\Json, Rbac\Manager as Manager, Router\FastRoute\UrlGenerator,
    Security\Random, Security\TokenMask, Session\Flash\Flash,
    Session\SessionInterface, Translator\TranslatorInterface,
    User\Login\Cookie\CookieLogin, User\Login\Cookie\CookieLoginIdentityInterface,
    View\WebView, Yii\View\Renderer\WebViewRenderer,
    Yii\AuthClient\StateStorage\StateStorageInterface,
    Yii\AuthClient\Widget\AuthChoice, Yii\RateLimiter\CounterInterface};

final class AuthController
{
    use Callback;
    
    use ClassList;
    
    //initialize .env file at root with oauth2.0 settings
    use Oauth2;

    use OpenBankingProviders;

    public const string
            DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN = 'developersandboxhmrc-access';
    public const string FACEBOOK_ACCESS_TOKEN = 'facebook-access';
    public const string GITHUB_ACCESS_TOKEN = 'github-access';
    public const string GOOGLE_ACCESS_TOKEN = 'google-access';
    public const string GOVUK_ACCESS_TOKEN = 'govuk-access';
    public const string LINKEDIN_ACCESS_TOKEN = 'linkedin-access';
    public const string MICROSOFTONLINE_ACCESS_TOKEN = 'microsoftonline-access';
    public const string OPENBANKING_ACCESS_TOKEN = 'openbanking-access';
    public const string OIDC_ACCESS_TOKEN = 'oidc-access';
    public const string X_ACCESS_TOKEN = 'x-access';
    public const string VKONTAKTE_ACCESS_TOKEN = 'vkontakte-access';
    public const string YANDEX_ACCESS_TOKEN = 'yandex-access';
    public const string EMAIL_VERIFICATION_TOKEN = 'email-verification';
    public string $telegramToken;
    private AuthTfaHelper $tfaHelper;
    private AuthSecurityHelper $secHelper;

    public function __construct(
        private readonly AuthService $authService,
        private readonly RecoveryCodeService $recoveryCodeService,
        private readonly DataResponseFactoryInterface $factory,
        private readonly WebControllerService $webService,
        private WebViewRenderer $webViewRenderer,
        private readonly Manager $manager,
        private readonly SessionInterface $session,
        private readonly SettingRepository $sR,
        private readonly UrlGenerator $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        // trait variables
        private readonly CounterInterface $rateLimiter,
        private readonly ClientInterface $configWebDiAuthGuzzle,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly WebView $webView,
        private readonly AssetManager $assetManager,
        private readonly StateStorageInterface $stateStorage,
        private readonly Factory $yiisoftFactory,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('auth');
        // use the Oauth2 trait function
        $this->initializeOauth2IdentityProviderCredentials();
        $this->initializeOauth2IdentityProviderDualUrls();
        $this->telegramToken = $this->sR->getSetting('telegram_token');
        $this->tfaHelper = new AuthTfaHelper($this->sR, $this->recoveryCodeService);
        $this->secHelper = new AuthSecurityHelper($this->rateLimiter, $this->logger, $this->session);
    }

    /**
     * Related logic: see AuthChoice function authRoutedButtons()
     * @param ServerRequestInterface $request
     * @param AuthChoice $authChoice
     * @return ResponseInterface
     */
    public function authclient(
        ServerRequestInterface $request,
        AuthChoice $authChoice,
    ): ResponseInterface {
        $query = $request->getQueryParams();
        $clientName = (string) $query['authclient'];
        $client = $authChoice->getClient($clientName);
        $codeVerifier = Random::string(128);
        $this->session->set('code_verifier', $codeVerifier);
        $rTrim = rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '=');
        $codeChallenge = strtr($rTrim, '+/', '-_');
        $selectedIdentityProviders = $this->idpList($codeChallenge);
        $selectedClient = (array) $selectedIdentityProviders[$clientName];
        $clientParams = (array) $selectedClient['params'];
        $clientAuthUrl = $client->buildAuthUrl($request, $clientParams);
        return $this->factory
                    ->createResponse(null, 302)
                    ->withHeader('Location', $clientAuthUrl);
    }

    public function callback(
        ServerRequestInterface $request,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        string $_language,
    ): ResponseInterface {
        $qp           = $request->getQueryParams();
        $authclient   = $this->getStringQueryParam($qp, 'authclient');
        $code         = $this->getStringQueryParam($qp, 'code');
        $state        = $this->getStringQueryParam($qp, 'state');
        $error        = $this->getStringQueryParam($qp, 'error');
        $errorCode    = $this->getStringQueryParam($qp, 'error_code');
        $errorReason  = $this->getStringQueryParam($qp, 'error_reason');
        $sessionState = $this->getStringQueryParam($qp, 'session_state');
        $deviceId     = $this->getStringQueryParam($qp, 'device_id');

        if ($authclient === null) {
            throw new \InvalidArgumentException("Missing or invalid 'authclient'"
                    . " query parameter.");
        }

        $d = new CallbackDeps($request, $this->translator, $tR, $uiR, $uR);

        return match ($authclient) {
            'developersandboxhmrc' => $this->callbackDeveloperGovSandboxHmrc(
                    $d, $_language, $code, $state),
            'facebook' => $this->callbackFacebook($d, $_language, $code, $state,
                    $error, $errorCode, $errorReason),
            'github' => $this->callbackGithub($d, $_language, $code, $state),
            'google' => $this->callbackGoogle($d, $_language, $code, $state),
            'govuk' => $this->callbackGovUk($d, $_language, $code, $state),
            'linkedin' => $this->callbackLinkedIn($d, $_language, $code, $state),
            'microsoftonline' => $this->callbackMicrosoftOnline($d, $_language,
                    $code, $state, (string) $sessionState),
            'openbanking' => $this->callbackOpenBanking($request,
                    $this->translator, $code, $state),
            'x' => $this->callbackX($d, $_language, $code, $state),
            'vkontakte' => $this->callbackVKontakte($d, $_language, $code,
                    $state, (string) $deviceId),
            'yandex' => $this->callbackYandex($d, $_language, $code, $state),
            default => throw new \InvalidArgumentException(
                    "Unsupported 'authclient' value: {$authclient}"),
        };
    }

    public function login(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        FormHydrator $formHydrator,
        CookieLogin $cookieLogin,
        // use the 'active' field of the extension table userinv to verify that
        // the user has been made active through e.g. email verificaiton
        UserInvRepository $uiR,
        UserRepository $uR,
        TokenRepository $tR,
    ): ResponseInterface {
        if (!$this->authService->isGuest()) {
            return $this->redirectToMain();
        }
        $loginForm = new LoginForm($this->authService, $translator);
        $openBankChoice = $this->sR->getSetting('open_banking_provider');
        $openBankingAuthUrl = $this->buildOpenBankingAuthUrl($openBankChoice);

        $response = null;
        if ($formHydrator->populateFromPostAndValidate($loginForm, $request)) {
            $response = $this->resolveLoginResponse($loginForm, $cookieLogin, $uiR, $tR);
            if ($response === null) {
                $this->logout($uR, $uiR);
            }
        }
        if ($response !== null) {
            return $response;
        }

        $codeVerifier = Random::string(128);
        $this->session->set('code_verifier', $codeVerifier);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256',
                $codeVerifier, true)), '='), '+/', '-_');
        $errors = $loginForm->isValidated()
            ? $loginForm->getValidationResult()->getErrorMessagesIndexedByProperty()
            : [];
        return $this->webViewRenderer->render(
            'login',
            [
                'class' => $this->classList(),
                'formModel' => $loginForm,
                'errors' => $errors,
                'openBankChoice' => $openBankChoice,
                'noOpenBankingContinueButton' =>
                $this->sR->getSetting('no_openbanking_continue_button') == '1',
                'openBankingAuthUrl' => $openBankingAuthUrl,
                //Fade-out CSS for TFA badge
                'styleTagFadeOut' =>  new Style()->content(
                    '.fade-out { opacity: 1; transition: opacity 40s ease-in; }'
                        . ' .fade-out.hidden { opacity: 0; }'),
                'request' => $request,
                'idpList' => $this->idpList(
                    $codeChallenge),
                // Fade-out JS: this will fade out the badge after 2 seconds;
                // adjust as needed
                'fadeOutJS' => Html::script(<<<JS
            document.addEventListener('DOMContentLoaded', function() {
            var badge = document.getElementById('tfa-badge');
                if (badge) {
                    setTimeout(function() {
                        badge.classList.add('hidden');
                    }, 2000);
                }
            });
            JS)
                                ->type('text/javascript')
                                ->charset('utf-8'),
            ],
        );
    }
    
    /**
     * Step 1: Download Aegis 2FA app
     *  https://play.google.com/store/apps/details?id=com.beemdevelopment.
     *  aegis&hl=en-US&pli=1 onto your mobile
     * Step 2: Add a new Qr code by pressing the plus sign at the bottom right
     *  corner
     * Step 3: Scan the Qr code generated by the setup form with your mobile
     *         or enter the long key into the android app
     *         Your app may ask you to overwrite your previous entry or you can
     *         hold down on your current entry (the letter next to the 6 digit
     *         code) and delete it.
     * Step 4: Enter the TOTP (Timed One Time Password) within the limited time
     *
     * @param TranslatorInterface $translator
     * @param UserRepository $userRepository
     * @return ResponseInterface
     */
    public function showSetup(
        TranslatorInterface $translator,
        UserRepository $userRepository,
    ): ResponseInterface {
        $userId = (int) $this->session->get('pending_2fa_user_id');
        $user = $userRepository->findById($userId);
        $email = $user->getEmail();
        if (strlen($email) > 0) {
            $totp = TOTP::create();
            /** @var non-empty-string $email */
            $totp->setLabel($email);
            $secret = $totp->getSecret();
            $this->session->set('2fa_temp_secret', $secret);
            $qrContent = $totp->getProvisioningUri();
            $qrDataUri = $this->tfaHelper->generateQrDataUri($qrContent);
            $form = new TwoFactorAuthenticationSetupForm($translator);
            return $this->webViewRenderer->render('setup', [
                'class' => $this->classList(),
                'qrDataUri' => $qrDataUri,
                'totpSecret' => $secret,
                'error' => '',
                'formModel' => $form,
            ]);
        }
        return $this->redirectToOneTimePasswordError();
    }

    /**
     * Related logic: see src\Auth\Asset\rebuild\js\keypad_copy_to_clipboard.js
     * @return ResponseInterface
     */
    public function ajaxShowSetup(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $parameters = [
            'success' => 1,
            'secretInputType' => $params['secretInputType'],
            'eyeIconClass' => $params['eyeIconClass'],
        ];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * On successful setup, the User table's 'tfa_enabled' field will be set to
     * true and the totp_secret will be set for function's verifyLogin's use
     *
     * During the verifyLogin function TFA is disabled
     *
     * Verify 2FA code during setup process.
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param UserRepository $userRepository
     * @return ResponseInterface
     */
    public function verifySetup(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        UserRepository $userRepository,
    ): ResponseInterface {
        // Apply rate limiting for setup verification attempts
        $clientIp = $this->secHelper->getClientIpAddress($request);
        $rateLimitKey = 'auth_setup_' . hash('sha256', $clientIp);

        $response = $this->redirectToOneTimePasswordError();
        if ($this->secHelper->checkRateLimit($rateLimitKey)) {
            $pendingUserId = (int) $this->session->get('pending_2fa_user_id');
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $result = $this->processSetupVerification(
                    $pendingUserId, $body, $translator, $userRepository);
                if ($result !== null) {
                    $response = $result;
                }
            }
        } else {
            $this->logger->log(LogLevel::WARNING,
                'Rate limit reached for 2FA setup from IP: ' . $clientIp);
        }
        return $response;
    }

    /**
     * Step 4: Enter a different 6-digit code than the setup 6-digit code but
     * with the same secret derived from the original Qr Code. i.e. do not scan
     * in an additional Qr Code.
     *
     * Verify 2FA code during login process if the User:tfa_enabled field is true
     * and the User:totpsecret field has been setup during the qrcode setup.
     *
     * Disable TFA after verification for additional layer of security.
     *
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param UserRepository $userRepository
     * @return ResponseInterface
     */
    public function verifyLogin(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        UserRepository $userRepository,
    ): ResponseInterface {
        $vuid = (int) $this->session->get('verified_2fa_user_id');
        $form = new TwoFactorAuthenticationVerifyLoginForm($translator);
        $codes = [];
        $user = $userRepository->findById($vuid);
        // Only display the recovery codes once i.e. if the user does not have any
        if (!$this->recoveryCodeService->userHasBackupCodes($user)) {
            $codes = $this->tfaHelper->generateBackupRecoveryCodes($user);
            $this->session->set('backup_recovery_codes', $codes);
        }
        if ($this->session->get('regenerate_codes')) {
            $this->tfaHelper->removeBackupRecoveryCodes($user);
            $codes = $this->tfaHelper->generateBackupRecoveryCodes($user);
            $this->session->set('backup_recovery_codes', $codes);
            $this->session->set('regenerate_codes', false);
        }
        $parameters = [
            'title' => $translator->translate('two.factor.authentication.form.verify.login'),
            'actionName' => 'auth/verifyLogin',
            'actionArguments' => [],
            'errors' => [],
            'error' => '',
            'formModel' => $form,
            // empty $codes => show button to regenerate
            'codes' => $codes,
        ];
        $response = null;
        if ($request->getMethod() === Method::POST) {
            $response = $this->handleVerifyLoginPost(
                $request, $translator, $userRepository, $vuid, $form, $codes);
        }
        return $response ?? $this->webViewRenderer->render('verify', $parameters);
    }

    private function disableToken(
        TokenRepository $tR,
        ?string $userId = null,
        string $identityProvider = '',
    ): void {
        if (null !== $userId) {
            $getTTIP = $this->getTokenType($identityProvider);
            $token = $tR->findTokenByIdentityIdAndType((int) $userId, $getTTIP);
            if (null !== $token) {
                $token->setToken('already_used_token_' . (string) time());
                $tR->save($token);
            }
        }
    }

    /**
     * Validates the 'authState' session variable against the 'state' returned by
     * an identity provider. Ensures state integrity and prevents CSRF attacks.
     *
     * @param string $idP
     * @param string $state
     * @psalm-return void
     */
    protected function blockInvalidState(string $idP, string $state): void
    {
        // Early return if state is empty
        if ($state === '') {
            $param = "Invalid or empty OAuth2 state parameter from provider:";
            $this->logger->log(LogLevel::ALERT, $param . " {$idP}");
            exit(1);
        }

        // Sanitize state parameter to prevent injection attacks
        $sanitizedState = preg_replace('/[^a-zA-Z0-9\-_]/', '', $state);
        $chars = "State parameter contains invalid characters from provider:";
        if ($sanitizedState === '') {
            $this->logger->log(LogLevel::ALERT, $chars . " {$idP}");
            exit(1);
        }

        $authChoice = AuthChoice::widget();

        try {
            // raises an exception if the idP is not found
            $client = $authChoice->getClient($idP);
            /**
             * @var string|null $sessionState
             */
            $sessionState = $client->getSessionAuthState();

            if ($sessionState === null) {
                $this->logger->log(LogLevel::ALERT,
                    "Session Auth state is null for provider: {$idP}");
                exit(1);
            }

            // Use constant-time comparison to prevent timing attacks
            if (!$sessionState || !hash_equals($sessionState, $state)) {
                // State is invalid, possible cross-site request forgery.
                // Exit with an error code.
                $this->logger->log(LogLevel::ALERT,
                        "CSRF attack attempt detected for provider: {$idP}");
                exit(1);
            }
        } catch (\Exception $e) {
            // Log exception details for debugging
            $this->logger->log(LogLevel::ALERT,
            "Exception validating OAuth2 state for provider: {$idP}. Error: "
                . $e->getMessage());
            exit(1);
        }
    }

    /**
     * @param TranslatorInterface $translator
     * @param User $user
     * @param UserInvRepository $uiR
     * @param string $language
     * @param string $_language
     * @param string $randomAndTimeToken
     * @param string $provider e.g. github
     * @return string
     */
    protected function proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
            TranslatorInterface $translator,
            User $user,
            UserInvRepository $uiR,
            string $language,
            string $_language,
            string $randomAndTimeToken,
            string $provider): string
    {
        $tokenType = $this->getTokenType($provider);
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        $userInv = new UserInv();
        $ipas = 'identity.provider.authentication.successful';
        $userId = $user->reqId();
        $userInv->setUserId($userId);
        // if the user is administrator assign 0 => 'Administrator',
        // 1 => Not Administrator
        $userInv->setType($userId == 1 ? 0 : 1);
        // when the user clicks on the button, make the user active in the
        // userinv extension table. Initially keep the user inactive.
        $userInv->setActive(false);
        $userInv->setLanguage($language);
        $uiR->save($userInv);
        return new A()
        // When the url is clicked by the user, return to userinv/$provider
        // to activate the user and assign a client to the user
        // depending on whether 'Assign a client to user on signup' has been
        // chosen under View ... Settings...General. The user will be able to
        // edit their userinv details on the client side as well as the
        // client record.
            ->href($this->urlGenerator->generateAbsolute(
                'userinv/signup',
                [
                    '_language' => $_language,
                    'language' => $language,
                    'token' => $tokenWithMask,
                    'tokenType' => $tokenType,
                ],
            ))
            ->addClass('btn btn-success')
            ->content($translator->translate($ipas))
            ->render();
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function logout(
        UserRepository $uR,
        UserInvRepository $uiR,
    ): ResponseInterface {
        $identity = $this->authService->getIdentity();
        $userId = $identity->getId();
        // if enable_tfa_with_disabling setting has changed during login of admin
        // make sure this is reflected in the user setting.
        if ($this->sR->getSetting('enable_tfa_with_disabling') == '1'
                && $this->sR->getSetting('enable_tfa') == '1') {
            $this->clearTfaOnLogout($userId, $uR, $uiR);
            $this->session->remove('verified_2fa_user_id');
        }
        // prevent session fixation
        $this->session->regenerateId();
        // Current — only clears data, keeps session alive
        $this->session->clear();
        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function regenerateCodes(): ResponseInterface
    {
        $this->session->set('regenerate_codes', true);
        return $this->webService->getRedirectResponse('auth/verifyLogin');
    }

    private function redirectToMain(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/index',
                ['_language' => 'en']);
    }

    private function redirectToInvoiceIndex(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('invoice/index');
    }

    protected function redirectToAdminMustMakeActive(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/adminmustmakeactive',
                ['_language' => 'en']);
    }

    /**
     * Related logic: see https://github.com/rossaddison/invoice/discussions/215
     * @param string $provider
     * @throws \InvalidArgumentException
     * @return string
     */
    private function getTokenType(string $provider): string
    {
        // Map special cases
        $specialMap = [
            'developersandboxhmrc' => 'DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN',
            'email-verification' => 'EMAIL_VERIFICATION_TOKEN',
            'openbanking' => 'OPENBANKING_ACCESS_TOKEN',
            // add more if needed
        ];
        if (isset($specialMap[$provider])) {
            $const = $specialMap[$provider];
        } else {
            $const = strtoupper($provider) . '_ACCESS_TOKEN';
        }
        if (!defined(self::class . '::' . $const)) {
            throw new \InvalidArgumentException("Unknown provider: $provider");
        }
        // Dynamic class constant fetch (PHP 8.3+)
        $value = self::{$const};
        assert(is_string($value));
        return $value;
    }

    private function isAdminUser(string $userId): bool
    {
        $userRoles = $this->manager->getRolesByUserId($userId);
        $isAdminUser = false;
        foreach ($userRoles as $role) {
            if ($role->getName() === 'admin') {
                $isAdminUser = true;
                break;
            }
        }
        return $isAdminUser;
    }

    private function redirectToOneTimePasswordError(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/onetimepassworderror',
                ['_language' => 'en']);
    }

    private function getStringQueryParam(array $qp, string $key): ?string
    {
        return (isset($qp[$key]) && is_string($qp[$key]) && $qp[$key] !== '')
            ? $qp[$key]
            : null;
    }

    private function buildOpenBankingAuthUrl(string $openBankChoice): string
    {
        if (strlen($openBankChoice) === 0) {
            return '';
        }
        $providerConfig = $this->getOpenBankingProviderConfig($openBankChoice);
        if ($providerConfig === null) {
            return '';
        }
        $authChoice = AuthChoice::widget();
        /** @var OpenBanking $openBanking */
        $openBanking = $authChoice->getClient('openbanking');
        $openBanking->setAuthUrl((string) $providerConfig['authUrl']);
        $openBanking->setTokenUrl((string) $providerConfig['tokenUrl']);
        $openBanking->setScope(isset($providerConfig['scope']) ?
                (string) $providerConfig['scope'] : null);
        $codeVerifier = Random::string(128);
        $hash = hash('sha256', $codeVerifier, true);
        $rTrim = rtrim(base64_encode($hash), '=');
        $codeChallenge = strtr($rTrim, '+/', '-_');
        $this->session->set('code_verifier', $codeVerifier);
        return $openBanking->getAuthUrl()
                . '?'
                . http_build_query([
            'response_type' => 'code',
            'scope' => $openBanking->getScope(),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    private function resolveLoginResponse(
        LoginForm $loginForm,
        CookieLogin $cookieLogin,
        UserInvRepository $uiR,
        TokenRepository $tR,
    ): ?ResponseInterface {
        $identity = $this->authService->getIdentity();
        $userId = $identity->getId();
        $userInv = null !== $userId ? $uiR->repoUserInvUserIdquery((int) $userId) : null;
        $user = null !== $userInv ? $userInv->getUser() : null;
        if (null === $userId || null === $userInv || null === $user) {
            return null;
        }
        if ($this->sR->getSetting('enable_tfa') == '1') {
            return $this->handleTfaPath($userId, $user);
        }
        return $this->handleNonTfaPath($userId, $userInv, $cookieLogin, $loginForm, $tR);
    }

    private function handleTfaPath(string $userId, User $user): ResponseInterface
    {
        $this->session->set('tfa_verified', false);
        $enabled = $user->is2FAEnabled();
        if (!$enabled) {
            $this->session->set('pending_2fa_user_id', $userId);
            return $this->webService->getRedirectResponse('auth/showSetup');
        }
        $this->session->set('verified_2fa_user_id', $userId);
        return $this->webService->getRedirectResponse('auth/verifyLogin');
    }

    private function handleNonTfaPath(
        string $userId,
        UserInv $userInv,
        CookieLogin $cookieLogin,
        LoginForm $loginForm,
        TokenRepository $tR,
    ): ResponseInterface {
        $this->session->set('tfa_verified', true);
        $status = $userInv->getActive();
        $isAdminUser = $this->isAdminUser($userId);
        if ($status || $isAdminUser) {
            if ($isAdminUser) {
                $this->disableToken($tR, $userId, 'email-verification');
            }
            $this->session->regenerateId();
            $this->session->set('tfa_verified', true);
            $identity = $this->authService->getIdentity();
            return ($identity instanceof CookieLoginIdentityInterface
                    && $loginForm->getPropertyValue('rememberMe'))
                ? $cookieLogin->addCookie($identity, $this->redirectToInvoiceIndex())
                : $this->redirectToInvoiceIndex();
        }
/**
 * If the observer user is signing up WITHOUT email (=> userinv account status is 0),
 *  e.g. by console ... yii userinv/assignRole observer 2,
 * the admin will have to make the user active via Settings Invoice User Account
 *  AND assign the user an added client.
 * Also the token that was originally assigned on signup, must now be 'disabled'
 *  because the admin is responsible for making the user active.
 */
        $this->disableToken($tR, $userId, $this->getTokenType('email-verification'));
        return $this->redirectToAdminMustMakeActive();
    }

    private function processSetupVerification(
        int $pendingUserId,
        array $body,
        TranslatorInterface $translator,
        UserRepository $userRepository,
    ): ?ResponseInterface {
        $tfa = 'two.factor.authentication';
        $inputCode = $this->tfaHelper->sanitizeAndValidateCode($body['code'] ?? '');
        if ($inputCode === null || $pendingUserId <= 0) {
            return null;
        }
        $user = $userRepository->findById($pendingUserId);
        /** @var mixed $tempSecretRaw */
        $tempSecretRaw = $this->session->get('2fa_temp_secret');
        $tempSecret = (is_string($tempSecretRaw) && $tempSecretRaw !== '')
            ? $tempSecretRaw : null;
        if ($tempSecret === null) {
            $error = $translator->translate($tfa . '.no.secret.generated');
        } elseif (!$this->tfaHelper->isValidTotpCode($inputCode)) {
            $error = $translator->translate($tfa . '.invalid.code.format');
        } else {
            $result = $this->verifySetupTotp(
                $inputCode, $tempSecret, $pendingUserId, $user, $userRepository, $translator);
            if ($result === null) {
                return $this->webService->getRedirectResponse('auth/verifyLogin');
            }
            $error = $result;
        }
        return $this->renderSetupWithError($error, $tempSecret, $user, $translator);
    }

    /**
     * @param non-empty-string $tempSecret
     */
    private function verifySetupTotp(
        string $inputCode,
        string $tempSecret,
        int $pendingUserId,
        User $user,
        UserRepository $userRepository,
        TranslatorInterface $translator,
    ): ?string {
        $totp = TOTP::create($tempSecret);
        if ($totp->verify($inputCode)) {
            $user->setTotpSecret($tempSecret);
            $user->set2FAEnabled(true);
            $userRepository->save($user);
            $this->session->remove('2fa_temp_secret');
            $this->session->remove('pending_2fa_user_id');
            // Regenerate session ID on successful setup
            $this->session->regenerateId();
            $this->session->set('verified_2fa_user_id', $pendingUserId);
            return null;
        }
        $tfa = 'two.factor.authentication';
        return $translator->translate(
            $this->sR->getSetting('enable_tfa_with_disabling') == '1'
                ? $tfa . '.attempt.failure.must.setup'
                : $tfa . '.attempt.failure'
        );
    }

    private function renderSetupWithError(
        string $error,
        ?string $tempSecret,
        User $user,
        TranslatorInterface $translator,
    ): ResponseInterface {
        /** @var non-empty-string $safeSecret */
        $safeSecret = $tempSecret ?? TOTP::create()->getSecret();
        $totp = TOTP::create($safeSecret);
        $userEmail = $user->getEmail();
        if ($userEmail !== '') {
            $totp->setLabel($userEmail);
        }
        $qrContent = $totp->getProvisioningUri();
        $qrDataUri = $this->tfaHelper->generateQrDataUri($qrContent);
        $form = new TwoFactorAuthenticationSetupForm($translator);
        return $this->webViewRenderer->render('setup', [
            'class' => $this->classList(),
            'qrDataUri' => $qrDataUri,
            'totpSecret' => $totp->getSecret(),
            'error' => $error,
            'formModel' => $form,
        ]);
    }

    private function handleVerifyLoginPost(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        UserRepository $userRepository,
        int $vuid,
        TwoFactorAuthenticationVerifyLoginForm $form,
        array $codes,
    ): ResponseInterface {
        $tfa = 'two.factor.authentication';
        $clientIp = $this->secHelper->getClientIpAddress($request);
        $rateLimitKey = 'auth_verify_' . hash('sha256', $clientIp);
        /** Related logic: see config/web/di/rate-limit.php */
        if (!$this->secHelper->checkRateLimit($rateLimitKey)) {
            $this->logger->log(LogLevel::WARNING,
                'Rate limit reached for 2FA verification from IP: ' . $clientIp);
            return $this->webViewRenderer->render('verify', [
                'error' => $translator->translate($tfa . '.rate.limit.reached'),
                'formModel' => $form,
                'codes' => $codes,
            ]);
        }
        $body = $request->getParsedBody();
        $inputCode = is_array($body)
            ? $this->tfaHelper->sanitizeAndValidateCode($body['code'] ?? '')
            : null;
        if (null === $inputCode || $vuid <= 0) {
            return $this->webViewRenderer->render('verify', [
                'error' => $translator->translate($tfa . '.attempt.failure'),
                'formModel' => $form,
                'codes' => $codes,
            ]);
        }
        return $this->verifyLoginCode($inputCode, $vuid, $userRepository, $translator, $form, $codes);
    }

    private function verifyLoginCode(
        string $inputCode,
        int $vuid,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        TwoFactorAuthenticationVerifyLoginForm $form,
        array $codes,
    ): ResponseInterface {
        $tfa = 'two.factor.authentication';
        $user = $userRepository->findById($vuid);
        $totpSecretRaw = $user->getTotpSecret();
        $totpSec = (is_string($totpSecretRaw) && $totpSecretRaw !== '')
            ? $totpSecretRaw : null;
        if ($totpSec !== null && $this->tfaHelper->isValidTotpCode($inputCode)) {
            $response = $this->tryTotpLogin($inputCode, $totpSec, $vuid, $user, $userRepository);
            $error = ($response === null) ? $translator->translate($tfa . '.invalid.totp.code') : '';
        } else {
            $response = $this->tryBackupCodeLogin($inputCode, $totpSec, $vuid, $user);
            $error = ($response === null)
                ? $translator->translate($tfa . '.invalid.backup.recovery.code') : '';
        }
        if ($response !== null) {
            return $response;
        }
        return $this->webViewRenderer->render('verify', [
            'error' => $error,
            'formModel' => $form,
            'codes' => $codes,
        ]);
    }

    /**
     * @param non-empty-string $totpSec
     */
    private function tryTotpLogin(
        string $inputCode,
        string $totpSec,
        int $vuid,
        User $user,
        UserRepository $userRepository,
    ): ?ResponseInterface {
        $totp = TOTP::create($totpSec);
        if (!$totp->verify($inputCode)) {
            return null;
        }
        if ($this->sR->getSetting('enable_tfa_with_disabling') == '1') {
            $user->setTotpSecret('');
            $user->set2FAEnabled(false);
            $userRepository->save($user);
        }
        // Regenerate session ID on successful authentication
        $this->session->regenerateId();
        $this->secHelper->remSessTempsPermitEntryBase($vuid);
        /**
         * Related logic: HmrcController function fphValidate
         */
        $tokenApplySec = TokenMask::apply($totpSec);
        $this->session->set('otp', $inputCode);
        $this->session->set('otpRef', $tokenApplySec);
        return $this->redirectToInvoiceIndex();
    }

    private function tryBackupCodeLogin(
        string $inputCode,
        ?string $totpSec,
        int $vuid,
        User $user,
    ): ?ResponseInterface {
        if ($totpSec === null
                || !$this->recoveryCodeService->validateAndMarkCodeAsUsed($user, $inputCode)) {
            return null;
        }
        $tokenApplySec = TokenMask::apply($totpSec);
        $this->secHelper->remSessTempsPermitEntryBase($vuid);
        $this->session->set('otp', $inputCode);
        $this->session->set('otpRef', $tokenApplySec);
        return $this->redirectToInvoiceIndex();
    }

    private function clearTfaOnLogout(?string $userId, UserRepository $uR, UserInvRepository $uiR): void
    {
        $userInv = null !== $userId ? $uiR->repoUserInvUserIdquery((int) $userId) : null;
        if (null === $userInv) {
            return;
        }
        $user = $userInv->getUser();
        if (null === $user || $this->sR->getSetting('enable_tfa') !== '1' || !$user->is2FAEnabled()) {
            return;
        }
        $user->set2FAEnabled(false);
        // Securely clear TOTP secret
        $totpSecret = $user->getTotpSecret();
        $user->setTotpSecret('');
        $uR->save($user);
        // Clear secret from memory
        $this->secHelper->secureClearSensitiveData([&$totpSecret]);
    }
}
