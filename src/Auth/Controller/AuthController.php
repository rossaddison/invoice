<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use App\Auth\AuthService;
use App\Auth\Form\LoginForm;
use App\Auth\Form\TwoFactorAuthenticationSetupForm;
use App\Auth\Form\TwoFactorAuthenticationVerifyLoginForm;
use App\Auth\Trait\Callback;
use App\Auth\Trait\Oauth2;
use App\Auth\TokenRepository;
use App\Invoice\Entity\UserInv;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use App\User\User;
use App\User\UserRepository;
use App\User\RecoveryCodeService;
use OTPHP\TOTP;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Html\Tag\A;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\Login\Cookie\CookieLogin;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;
use Yiisoft\Yii\AuthClient\Client\GovUk;
use Yiisoft\Yii\AuthClient\Client\LinkedIn;
use Yiisoft\Yii\AuthClient\Client\MicrosoftOnline;
use Yiisoft\Yii\AuthClient\Client\X;
use Yiisoft\Yii\AuthClient\Client\VKontakte;
use Yiisoft\Yii\AuthClient\Client\Yandex;
use Yiisoft\Yii\RateLimiter\CounterInterface;

final class AuthController
{
    use Callback;
    //initialize .env file at root with oauth2.0 settings
    use Oauth2;

    public const string DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN = 'developersandboxhmrc-access';
    public const string FACEBOOK_ACCESS_TOKEN = 'facebook-access';
    public const string GITHUB_ACCESS_TOKEN = 'github-access';
    public const string GOOGLE_ACCESS_TOKEN = 'google-access';
    public const string GOVUK_ACCESS_TOKEN = 'govuk-access';
    public const string LINKEDIN_ACCESS_TOKEN = 'linkedin-access';
    public const string MICROSOFTONLINE_ACCESS_TOKEN = 'microsoftonline-access';
    public const string X_ACCESS_TOKEN = 'x-access';
    public const string VKONTAKTE_ACCESS_TOKEN = 'vkontakte-access';
    public const string YANDEX_ACCESS_TOKEN = 'yandex-access';
    public const string EMAIL_VERIFICATION_TOKEN = 'email-verification';
    public string $telegramToken;

    public function __construct(
        private readonly AuthService $authService,
        private readonly RecoveryCodeService $recoveryCodeService,
        private readonly DataResponseFactoryInterface $factory,
        private readonly WebControllerService $webService,
        private ViewRenderer $viewRenderer,
        private readonly Manager $manager,
        private readonly SessionInterface $session,
        private readonly SettingRepository $sR,
        private readonly DeveloperSandboxHmrc $developerSandboxHmrc,
        private readonly Facebook $facebook,
        private readonly GitHub $github,
        private readonly Google $google,
        private readonly GovUk $govUk,
        private readonly LinkedIn $linkedIn,
        private readonly MicrosoftOnline $microsoftOnline,
        private readonly VKontakte $vkontakte,
        private readonly X $x,
        private readonly Yandex $yandex,
        private readonly UrlGenerator $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly Flash $flash,
        private readonly CounterInterface $rateLimiter
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('auth');
        // use the Oauth2 trait function
        $this->initializeOauth2IdentityProviderCredentials(
            $developerSandboxHmrc,
            $facebook,
            $github,
            $google,
            $govUk,
            $linkedIn,
            $microsoftOnline,
            $vkontakte,
            $x,
            $yandex
        );
        $this->initializeOauth2IdentityProviderDualUrls($sR, $developerSandboxHmrc);
        $this->telegramToken = $this->sR->getSetting('telegram_token');
    }

    public function login(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        FormHydrator $formHydrator,
        CookieLogin $cookieLogin,
        // use the 'active' field of the extension table userinv to verify that the user has been made active through e.g. email verificaiton
        UserInvRepository $uiR,
        UserRepository $uR,
        TokenRepository $tR,
    ): ResponseInterface {
        if (!$this->authService->isGuest()) {
            return $this->redirectToMain();
        }
        $loginForm = new LoginForm($this->authService, $translator);

        if ($formHydrator->populateFromPostAndValidate($loginForm, $request)) {
            $identity = $this->authService->getIdentity();
            $userId = $identity->getId();
            if (null !== $userId) {
                $userInv = $uiR->repoUserInvUserIdquery($userId);
                if (null !== $userInv) {
                    $user = $userInv->getUser();
                    if (null !== $user) {
                        // if using two-factor-authentication
                        if ($this->sR->getSetting('enable_tfa') == '1') {
                            $this->tfaIsEnabledBlockBaseController($userId);
                            $enabled = $user->is2FAEnabled();
                            // setup if not enabled already
                            if ($enabled == false) {
                                $this->session->set('pending_2fa_user_id', $userId);
                                // show the setup form so that the user can register
                                return $this->webService->getRedirectResponse('auth/showSetup');
                            }
                            $this->session->set('verified_2fa_user_id', $userId);
                            return $this->webService->getRedirectResponse('auth/verifyLogin');
                        }
                        $this->tfaNotEnabledUnblockBaseController($userId);
                        /**
                         * @see UserInvController function signup where the userinv active field is made active i.e. true upon a positive email verification
                         */
                        $status = $userInv->getActive();
                        /**
                         * The admin does not automatically have a 'userinv account with status as active' IF
                         * signing up NOT by email e.g by localhost  . The below code, if ($isAdminUser) {
                            }, => makes allowances for this.
                         * @see UserInvController function signup which is triggered once user's email verification link is clicked in their user account
                         *      and the userinv account's status field is made active i.e. 1
                         */
                        $userRoles = $this->manager->getRolesByUserId($userId);
                        $isAdminUser = false;
                        foreach ($userRoles as $role) {
                            if ($role->getName() === 'admin') {
                                $isAdminUser = true;
                                break;
                            }
                        }

                        if ($status || $isAdminUser) {
                            // Disable email verification token for admin users who don't need email verification
                            if ($isAdminUser) {
                                $this->disableToken($tR, $userId, 'email-verification');
                            }
                            // Regenerate session ID on successful login
                            $this->session->regenerateId();
                            if ($identity instanceof CookieLoginIdentityInterface && $loginForm->getPropertyValue('rememberMe')) {
                                return $cookieLogin->addCookie($identity, $this->redirectToInvoiceIndex());
                            }
                            return $this->redirectToInvoiceIndex();
                        }

                        /**
                         * If the observer user is signing up WITHOUT email (=> userinv account status is 0), e.g. by console ... yii userinv/assignRole observer 2,
                         * the admin will have to make the user active via Settings Invoice User Account AND assign the user an added client
                         * Also the token that was originally assigned on signup, must now be 'disabled' because the admin is responsible for making the user active
                         */
                        $this->disableToken($tR, $userId, $this->getTokenType('email-verification'));
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
            }
            $this->logout($uR, $uiR);
        }
        $noDeveloperSandboxHmrcContinueButton = $this->sR->getSetting('no_developer_sandbox_hmrc_continue_button') == '1' ? true : false;
        $noGithubContinueButton = $this->sR->getSetting('no_github_continue_button') == '1' ? true : false;
        $noGoogleContinueButton = $this->sR->getSetting('no_google_continue_button') == '1' ? true : false;
        $noGovUkContinueButton = $this->sR->getSetting('no_govuk_continue_button') == '1' ? true : false;
        $noFacebookContinueButton = $this->sR->getSetting('no_facebook_continue_button') == '1' ? true : false;
        $noLinkedInContinueButton = $this->sR->getSetting('no_linkedin_continue_button') == '1' ? true : false;
        $noMicrosoftOnlineContinueButton = $this->sR->getSetting('no_microsoftonline_continue_button') == '1' ? true : false;
        $noVKontakteContinueButton = $this->sR->getSetting('no_vkontakte_continue_button') == '1' ? true : false;
        $noXContinueButton = $this->sR->getSetting('no_x_continue_button') == '1' ? true : false;
        $noYandexContinueButton = $this->sR->getSetting('no_yandex_continue_button') == '1' ? true : false;

        $codeVerifier = Random::string(128);

        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $this->session->set('code_verifier', $codeVerifier);
        return $this->viewRenderer->render(
            'login',
            [
                'formModel' => $loginForm,
                'developerSandboxHmrcAuthUrl' => strlen($this->developerSandboxHmrc->getClientId()) > 0 ? $this->developerSandboxHmrc->buildAuthUrl(
                    $request,
                    $params = [
                        'code_challenge' => $codeChallenge,
                        'code_challenge_method' => 'S256',
                    ]
                ) : '',
                'facebookAuthUrl' => strlen($this->facebook->getClientId()) > 0 ? $this->facebook->buildAuthUrl($request, $params = []) : '',
                'githubAuthUrl' => strlen($this->github->getClientId()) > 0 ? $this->github->buildAuthUrl($request, $params = []) : '',
                'googleAuthUrl' => strlen($this->google->getClientId()) > 0 ? $this->google->buildAuthUrl($request, $params = []) : '',
                'govUkAuthUrl' => strlen($this->govUk->getClientId()) > 0 ? $this->govUk->buildAuthUrl(
                    $request,
                    $params = [
                        'return_type' => 'id_token',
                        'code_challenge' => $codeChallenge,
                        'code_challenge_method' => 'S256',
                    ]
                ) : '',
                'linkedInAuthUrl' => strlen($this->linkedIn->getClientId()) > 0 ? $this->linkedIn->buildAuthUrl($request, $params = []) : '',
                'microsoftOnlineAuthUrl' => strlen($this->microsoftOnline->getClientId()) > 0 ? $this->microsoftOnline->buildAuthUrl($request, $params = []) : '',
                'vkontakteAuthUrl' => strlen($this->vkontakte->getClientId()) > 0 ? $this->vkontakte->buildAuthUrl(
                    $request,
                    $params = [
                        'code_challenge' => $codeChallenge,
                        'code_challenge_method' => 'S256',
                    ]
                ) : '',
                /**
                 * PKCE: An extension to the authorization code flow to prevent several attacks and to be able
                 * to perform the OAuth exchange from public clients securely using two parameters code_challenge and
                 * code_challenge_method.
                 * @link https://developer.x.com/en/docs/authentication/oauth-2-0/user-access-token
                 */
                'xAuthUrl' => strlen($this->x->getClientId()) > 0 ? $this->x->buildAuthUrl(
                    $request,
                    $params = [
                        'code_challenge' => $codeChallenge,
                        'code_challenge_method' => 'S256',
                    ]
                ) : '',
                'yandexAuthUrl' => strlen($this->yandex->getClientId()) > 0 ? $this->yandex->buildAuthUrl(
                    $request,
                    $params = [
                        'code_challenge' => $codeChallenge,
                        'code_challenge_method' => 'S256',
                    ]
                ) : '',
                'noDeveloperSandboxHmrcContinueButton' => $noDeveloperSandboxHmrcContinueButton,
                'noGithubContinueButton' => $noGithubContinueButton,
                'noGoogleContinueButton' => $noGoogleContinueButton,
                'noGovUkContinueButton' => $noGovUkContinueButton,
                'noFacebookContinueButton' => $noFacebookContinueButton,
                'noLinkedInContinueButton' => $noLinkedInContinueButton,
                'noMicrosoftOnlineContinueButton' => $noMicrosoftOnlineContinueButton,
                'noVKontakteContinueButton' => $noVKontakteContinueButton,
                'noXContinueButton' => $noXContinueButton,
                'noYandexContinueButton' => $noYandexContinueButton,
            ]
        );
    }

    /**
     * Step 1: Download Aegis 2FA app https://play.google.com/store/apps/details?id=com.beemdevelopment.aegis&hl=en-US&pli=1 onto your mobile
     * Step 2: Add a new Qr code by pressing the plus sign at the bottom right corner
     * Step 3: Scan the Qr code generated by the setup form with your mobile
     *         or enter the long key into the android app
     *         Your app may ask you to overwrite your previous entry or you can
     *         hold down on your current entry (the letter next to the 6 digit code)
     *         and delete it.
     * Step 4: Enter the TOTP (Timed One Time Password) within the limited time
     *
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param UserRepository $userRepository
     * @return ResponseInterface
     */
    public function showSetup(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        UserRepository $userRepository,
    ): ResponseInterface {
        $userId = (int)$this->session->get('pending_2fa_user_id');
        $user = $userRepository->findById((string)$userId);
        if (null !== $user) {
            $email = $user->getEmail();
            if (strlen($email) > 0) {
                $totp = TOTP::create();
                $totp->setLabel($email);
                $secret = $totp->getSecret();
                $this->session->set('2fa_temp_secret', $secret);
                $qrContent = $totp->getProvisioningUri();
                $qrDataUri = $this->generateQrDataUri($qrContent);
                $form = new TwoFactorAuthenticationSetupForm($translator);
                return $this->viewRenderer->render('setup', [
                    'qrDataUri' => $qrDataUri,
                    'totpSecret' => $secret,
                    'error' => '',
                    'formModel' => $form,
                ]);
            }
        }
        return $this->redirectToOneTimePasswordError();
    }

    /**
     * @see src\Auth\Asset\rebuild\js\keypad_copy_to_clipboard.js
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function ajaxShowSetup(ServerRequestInterface $request): \Yiisoft\DataResponse\DataResponse
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
     * On successful setup, the User table's 'tfa_enabled' field will be set to true
     * and the totp_secret will be set for function's verifyLogin's use
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
        UserRepository $userRepository
    ): ResponseInterface {
        // Apply rate limiting for setup verification attempts
        $clientIp = $this->getClientIpAddress($request);
        $rateLimitKey = 'auth_setup_' . hash('sha256', $clientIp);

        if (!$this->checkRateLimit($rateLimitKey)) {
            $this->logger->log(LogLevel::WARNING, 'Rate limit reached for 2FA setup from IP: ' . $clientIp);
            return $this->redirectToOneTimePasswordError();
        }

        $pendingUserId = (int)$this->session->get('pending_2fa_user_id');
        $body = $request->getParsedBody() ?? [];
        if (is_array($body)) {
            $inputCode = $this->sanitizeAndValidateCode($body['text'] ?? '');
            if ($inputCode !== null) {
                if ($pendingUserId > 0) {
                    $user = $userRepository->findById((string)$pendingUserId);
                    if (null !== $user) {
                        /** @var mixed $tempSecretRaw */
                        $tempSecretRaw = $this->session->get('2fa_temp_secret');
                        $tempSecret = (\is_string($tempSecretRaw) && $tempSecretRaw !== '') ? $tempSecretRaw : null;
                        $error = '';
                        if ($tempSecret === null) {
                            $error = $translator->translate('two.factor.authentication.no.secret.generated');
                        } elseif (!$this->isValidTotpCode($inputCode)) {
                            $error = $translator->translate('two.factor.authentication.invalid.code.format');
                        } else {
                            /** @var non-empty-string $tempSecret */
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
                                return $this->webService->getRedirectResponse('auth/verifyLogin', );
                            }
                            if ($this->sR->getSetting('enable_tfa_with_disabling') == '1') {
                                $error = $translator->translate('two.factor.authentication.attempt.failure.must.setup');
                            } else {
                                $error = $translator->translate('two.factor.authentication.attempt.failure');
                            }
                        }

                        // Re-render the setup page with error and QR code
                        $safeSecret = $tempSecret ?? TOTP::create()->getSecret();
                        /** @var non-empty-string $safeSecret */
                        $totp = TOTP::create($safeSecret);
                        // Set the label again here!
                        $totp->setLabel($user->getEmail());

                        $qrContent = $totp->getProvisioningUri();
                        $qrDataUri = $this->generateQrDataUri($qrContent);

                        return $this->viewRenderer->render('setup', [
                            'qrDataUri' => $qrDataUri,
                            'totpSecret' => $totp->getSecret(),
                            'error' => $error,
                            'formModel' => new TwoFactorAuthenticationSetupForm($translator),
                        ]);
                    }
                }
            } // null!==$inputCode
        }
        return $this->redirectToOneTimePasswordError();
    }

    /**
     * Step 4: Enter a different 6-digit code than the setup 6-digit code but with the same secret
     * derived from the original Qr Code. i.e. do not scan in an additional Qr Code.
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
        $verifiedUserId = (int)$this->session->get('verified_2fa_user_id');
        $form = new TwoFactorAuthenticationVerifyLoginForm($translator);
        $codes = [];
        $user = $userRepository->findById((string)$verifiedUserId);
        if (null !== $user) {
            // Only display the recovery codes once i.e. if the user does not have any
            if (!$this->recoveryCodeService->userHasBackupCodes($user)) {
                $codes = $this->generateBackupRecoveryCodes($user);
                $this->session->set('backup_recovery_codes', $codes);
            }
            if ($this->session->get('regenerate_codes') == true) {
                $this->removeBackupRecoveryCodes($user);
                $codes = $this->generateBackupRecoveryCodes($user);
                $this->session->set('backup_recovery_codes', $codes);
                $this->session->set('regenerate_codes', false);
            }
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
        if ($request->getMethod() === Method::POST) {
            // Apply rate limiting for authentication attempts
            $clientIp = $this->getClientIpAddress($request);
            $rateLimitKey = 'auth_verify_' . hash('sha256', $clientIp);
            /** @see config/web/di/rate-limit.php */
            if (!$this->checkRateLimit($rateLimitKey)) {
                $this->logger->log(LogLevel::WARNING, 'Rate limit reached for 2FA verification from IP: ' . $clientIp);
                $error = $translator->translate('two.factor.authentication.rate.limit.reached');
                return $this->viewRenderer->render('verify', [
                    'error' => $error,
                    'formModel' => $form,
                    'codes' => $codes,
                ]);
            }

            $body = $request->getParsedBody();
            if (is_array($body)) {
                $inputCode = $this->sanitizeAndValidateCode($body['text'] ?? '');
                if (null !== $inputCode) {
                    if ($verifiedUserId > 0) {
                        $user = $userRepository->findById((string)$verifiedUserId);
                        if (null !== $user) {
                            $totpSecretRaw = $user->getTotpSecret();
                            $totpSecret = (\is_string($totpSecretRaw) && $totpSecretRaw !== '') ? $totpSecretRaw : null;
                            $error = '';
                            if ($totpSecret !== null && $this->isValidTotpCode($inputCode)) {
                                /** @var non-empty-string $totpSecret */
                                $totp = TOTP::create($totpSecret);
                                if ($totp->verify($inputCode)) {
                                    if ($this->sR->getSetting('enable_tfa_with_disabling') == '1') {
                                        $user->setTotpSecret('');
                                        $user->set2FAEnabled(false);
                                        $userRepository->save($user);
                                    }
                                    // Regenerate session ID on successful authentication
                                    $this->session->regenerateId();
                                    $this->removeSessionTempsAndPermitEntryToBaseController($verifiedUserId);
                                    /** @see HmrcController function fphValidate */
                                    $this->session->set('otp', $inputCode);
                                    $this->session->set('otpRef', TokenMask::apply($totpSecret));
                                    return $this->redirectToInvoiceIndex();
                                }
                                $error = $translator->translate('two.factor.authentication.attempt.failure');
                            } else {
                                // The user has forgotten their $inputCode so try a backup code
                                if ($totpSecret !== null && $this->recoveryCodeService->validateAndMarkCodeAsUsed($user, $inputCode)) {
                                    $this->removeSessionTempsAndPermitEntryToBaseController($verifiedUserId);
                                    $this->session->set('otp', $inputCode);
                                    $this->session->set('otpRef', TokenMask::apply($totpSecret));
                                    return $this->redirectToInvoiceIndex();
                                }
                                $error = $translator->translate('two.factor.authentication.missing.code.or.secret');
                            }
                            return $this->viewRenderer->render('verify', [
                                'error' => $error,
                                'formModel' => $form,
                                'codes' => $codes,
                            ]);
                        }
                    }
                } // null!==$inputCode
                $parameters['error'] = $translator->translate('two.factor.authentication.missing.code.or.secret');
            }
        }
        return $this->viewRenderer->render('verify', $parameters);
    }

    private function removeSessionTempsAndPermitEntryToBaseController(int $verifiedUserId): void
    {
        // Validate session integrity before proceeding
        if (!$this->validateSessionIntegrity($verifiedUserId)) {
            $this->logger->log(LogLevel::WARNING, 'Session integrity validation failed for user ID: ' . $verifiedUserId);
            // Clear all session data instead of invalidate which doesn't exist
            $this->session->clear();
            return;
        }

        $this->session->remove('pending_2fa_user_id');
        $this->session->remove('backup_recovery_codes');
        $this->session->remove('verified_2fa_user_id');
        $roles = $this->manager->getRolesByUserId($verifiedUserId);
        foreach ($roles as $role) {
            $this->manager->removeChild($role->getName(), 'noEntryToBaseController');
            $this->manager->addChild($role->getName(), 'entryToBaseController');
        }
    }

    /**
     * Validate session integrity to prevent session fixation and hijacking.
     *
     * @param int $userId
     * @return bool
     */
    private function validateSessionIntegrity(int $userId): bool
    {
        // Check if user ID is valid
        if ($userId <= 0) {
            return false;
        }

        // Verify session has required 2FA data
        /** @var int $verifiedUserId */
        $verifiedUserId = (int)$this->session->get('verified_2fa_user_id');
        return $verifiedUserId === $userId;
    }

    public function disableToken(
        TokenRepository $tR,
        ?string $userId = null,
        string $identityProvider = ''
    ): void {
        if (null !== $userId) {
            $token = $tR->findTokenByIdentityIdAndType($userId, $this->getTokenType($identityProvider));
            if (null !== $token) {
                $token->setToken('already_used_token_' . (string)time());
                $tR->save($token);
            }
        }
    }

    /**
     * Get the 'authState' session variable created automatically (validateAuthState always true)
     * in the callback function yii-auth-client\src\OAuth2\buildAuthUrl
     * and compare it with the 'state' variable returned by an identity Provider e.g. Facebook
     * @param string $identityProvider
     * @param string $state
     * @psalm-return void
     */
    private function blockInvalidState(string $identityProvider, string $state): void
    {
        // Validate and sanitize the state parameter
        if ($state === '') {
            $this->logger->log(LogLevel::ALERT, 'Invalid or empty OAuth2 state parameter from provider: ' . $identityProvider);
            exit(1);
        }

        // Sanitize state parameter to prevent injection attacks
        $state = preg_replace('/[^a-zA-Z0-9\-_]/', '', $state);
        if ($state === '') {
            $this->logger->log(LogLevel::ALERT, 'State parameter contains invalid characters from provider: ' . $identityProvider);
            exit(1);
        }

        /**
         * @psalm-suppress MixedMethodCall,
         * @psalm-suppress MixedAssignment $sessionState
         */
        $sessionState = match ($identityProvider) {
            'developergovsandboxhmrc' => $this->developerSandboxHmrc->getSessionAuthState() ?? null,
            'facebook' => $this->facebook->getSessionAuthState() ?? null,
            'github' => $this->github->getSessionAuthState() ?? null,
            'google' => $this->google->getSessionAuthState() ?? null,
            'govUk' => $this->govUk->getSessionAuthState() ?? null,
            'linkedIn' => $this->linkedIn->getSessionAuthState() ?? null,
            'microsoftOnline' => $this->microsoftOnline->getSessionAuthState() ?? null,
            'vkontakte' => $this->vkontakte->getSessionAuthState() ?? null,
            'x' => $this->x->getSessionAuthState() ?? null,
            'yandex' => $this->yandex->getSessionAuthState() ?? null
        };

        if (null !== $sessionState && null !== $state) {
            // Use constant-time comparison to prevent timing attacks
            if (!$sessionState || !hash_equals((string)$sessionState, $state)) {
                // State is invalid, possible cross-site request forgery. Exit with an error code.
                $this->logger->log(LogLevel::ALERT, 'CSRF attack attempt detected for provider: ' . $identityProvider);
                exit(1);
            }
        } else {
            $this->logger->log(LogLevel::ALERT, 'Session Auth state is null for provider: ' . $identityProvider);
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
    private function proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(TranslatorInterface $translator, User $user, UserInvRepository $uiR, string $language, string $_language, string $randomAndTimeToken, string $provider): string
    {
        $tokenType = $this->getTokenType($provider);
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        $userInv = new UserInv();
        if (null !== ($userId = $user->getId())) {
            $userInv->setUser_id((int)$userId);
            // if the user is administrator assign 0 => 'Administrator', 1 => Not Administrator
            $userInv->setType($user->getId() == 1 ? 0 : 1);
            // when the user clicks on the button, make the user active in the userinv extension table. Initially keep the user inactive.
            $userInv->setActive(false);
            $userInv->setLanguage($language);
            $uiR->save($userInv);
            return A::tag()
                // When the url is clicked by the user, return to userinv/$provider to activate the user and assign a client to the user
                // depending on whether 'Assign a client to user on signup' has been chosen under View ... Settings...General. The user will be able to
                // edit their userinv details on the client side as well as the client record.
                ->href($this->urlGenerator->generateAbsolute(
                    'userinv/signup',
                    [
                        '_language' => $_language,
                        'language' => $language,
                        'token' => $tokenWithMask,
                        'tokenType' => $tokenType,
                    ]
                ))
                ->addClass('btn btn-success')
                ->content($translator->translate('identity.provider.authentication.successful'))
                ->render();
        }
        return '';
    }

    public function logout(
        UserRepository $uR,
        UserInvRepository $uiR,
    ): ResponseInterface {
        $identity = $this->authService->getIdentity();
        $userId = $identity->getId();

        // if enable_tfa_with_disabling setting has changed during login of admin
        // make sure this is reflected in the user setting.
        if (($this->sR->getSetting('enable_tfa_with_disabling') == '1') && $this->sR->getSetting('enable_tfa') == '1') {
            if (null !== $userId) {
                $userInv = $uiR->repoUserInvUserIdquery($userId);
                if (null !== $userInv) {
                    $user = $userInv->getUser();
                    if (null !== $user) {
                        if ($this->sR->getSetting('enable_tfa') == '1') {
                            $enabled = $user->is2FAEnabled();
                            if ($enabled == true) {
                                $user->set2FAEnabled(false);
                                // Securely clear TOTP secret
                                $totpSecret = $user->getTotpSecret();
                                $user->setTotpSecret('');
                                $uR->save($user);

                                // Clear secret from memory
                                $this->secureClearSensitiveData([&$totpSecret]);
                            }
                        }
                    }
                }
            }
            $this->session->remove('verified_2fa_user_id');
        }

        if (null !== $userId) {
            if ($this->manager->userHasPermission($userId, 'entryToBaseController')) {
                $roles = $this->manager->getRolesByUserId($userId);
                foreach ($roles as $role) {
                    $this->manager->removeChild($role->getName(), 'entryToBaseController');
                }
            }
        }

        // Secure cleanup of session data
        $this->secureClearSensitiveData();

        // Clear all session data completely for security
        $this->session->clear();

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function regenerateCodes(UserRepository $uR): ResponseInterface
    {
        $this->session->set('regenerate_codes', true);
        return $this->webService->getRedirectResponse('auth/verifyLogin');
    }

    private function tfaIsEnabledBlockBaseController(string $userId): void
    {
        // see config/common/routes.php 'entryToBaseController'
        // Prevent access to the BaseController during tfa with an additional permission 'noEntryToBaseController'
        // which has been incorporated in the BaseController
        // Replace this permission with 'entryToBaseController' on completion
        if (!$this->manager->userHasPermission($userId, 'noEntryToBaseController')) {
            $roles = $this->manager->getRolesByUserId($userId);
            foreach ($roles as $role) {
                $this->manager->addChild($role->getName(), 'noEntryToBaseController');
            }
        }
        if ($this->manager->userHasPermission($userId, 'entryToBaseController')) {
            $roles = $this->manager->getRolesByUserId($userId);
            foreach ($roles as $role) {
                $this->manager->removeChild($role->getName(), 'entryToBaseController');
            }
        }
    }

    private function tfaNotEnabledUnblockBaseController(string $userId): void
    {
        // If tfa is not been used, the 'noEntryToBaseController' must be removed
        if ($this->manager->userHasPermission($userId, 'noEntryToBaseController')) {
            $roles = $this->manager->getRolesByUserId($userId);
            foreach ($roles as $role) {
                $this->manager->removeChild($role->getName(), 'noEntryToBaseController');
            }
        }
        // If tfa is not been used, the 'entryToBaseController' permission can be added now
        if (!$this->manager->userHasPermission($userId, 'entryToBaseController')) {
            $roles = $this->manager->getRolesByUserId($userId);
            foreach ($roles as $role) {
                $this->manager->addChild($role->getName(), 'entryToBaseController');
            }
        }
    }

    private function redirectToMain(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/index', ['_language' => 'en']);
    }

    private function redirectToInvoiceIndex(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('invoice/index');
    }

    private function redirectToAdminMustMakeActive(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/adminmustmakeactive', ['_language' => 'en']);
    }

    /**
     * @see https://github.com/rossaddison/invoice/discussions/215
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

    /**
     * Validate TOTP code format (strictly: 6 digits).
     *
     * @param string $code
     * @return bool
     */
    private function isValidTotpCode(string $code): bool
    {
        return \preg_match('/^\d{6}$/', $code) === 1;
    }

    /**
     * Validate backup recovery code format (8 alphanumeric characters).
     *
     * @param string $code
     * @return bool
     */
    private function isValidBackupCode(string $code): bool
    {
        return \preg_match('/^[A-Za-z0-9]{8}$/', $code) === 1;
    }

    /**
     * Sanitize and validate user input code (TOTP or backup recovery code).
     *
     * @param mixed $input
     * @return string|null Returns sanitized code or null if invalid
     */
    private function sanitizeAndValidateCode(mixed $input): ?string
    {
        if (!is_string($input) && !is_numeric($input)) {
            return null;
        }

        // Convert to string and trim whitespace
        $code = trim((string)$input);

        // Remove any non-alphanumeric characters
        $code = preg_replace('/[^A-Za-z0-9]/', '', $code);

        if ($code === null || $code === '') {
            return null;
        }

        // Validate length (6 for TOTP, 8 for backup codes)
        $length = strlen($code);
        if ($length !== 6 && $length !== 8) {
            return null;
        }

        // Validate format
        if ($length === 6 && !$this->isValidTotpCode($code)) {
            return null;
        }

        if ($length === 8 && !$this->isValidBackupCode($code)) {
            return null;
        }

        return $code;
    }

    /**
     * Generate a QR code as a data URI using chillerlan/php-qrcode.
     *
     * @param string $content
     * @return string
     */
    private function generateQrDataUri(string $content): string
    {
        $eccLevel = $this->sR->getSetting('qr_ecc_level');
        // @psalm-suppress RedundantCondition
        $options = new QROptions([
            'eccLevel' => strlen($eccLevel) > 0 ? (int)$eccLevel : 0b01,
            'imageBase64' => true,
            'scale' => 4,
        ]);
        /** @var string */
        return (new QRCode($options))->render($content);
    }

    private function redirectToOneTimePasswordError(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/onetimepassworderror', ['_language' => 'en']);
    }

    private function removeBackupRecoveryCodes(User $user): void
    {
        if ($this->recoveryCodeService->userHasBackupCodes($user)) {
            $this->recoveryCodeService->removeBackupRecoveryCodes($user);
        }
    }

    /**
     * Securely clear sensitive data from memory and session.
     *
     * @param array $sensitiveVars Array of variable references to clear
     */
    private function secureClearSensitiveData(array $sensitiveVars = []): void
    {
        // Clear sensitive session data
        $sensitiveSessionKeys = [
            '2fa_temp_secret',
            'otp',
            'otpRef',
            'code_verifier',
            'backup_recovery_codes',
        ];

        foreach ($sensitiveSessionKeys as $key) {
            if ($this->session->has($key)) {
                $this->session->remove($key);
            }
        }

        // Overwrite sensitive variables with random data before unsetting
        /** @var string[] $sensitiveVars */
        foreach ($sensitiveVars as &$var) {
            /** @var string $var */
            $var = str_repeat('0', strlen($var));
            $var = Random::string(strlen($var));
            unset($var);
        }
    }

    private function generateBackupRecoveryCodes(User $user): array
    {
        $codes = $this->recoveryCodeService->generateBackupCodes(5, 8);
        $this->recoveryCodeService->persistBackupCodes($user, $codes);
        return $codes;
    }

    /**
     * Check rate limit for authentication operations.
     *
     * @param string $key Rate limit key
     * @return bool True if within rate limit, false if exceeded
     */
    private function checkRateLimit(string $key): bool
    {
        try {
            $result = $this->rateLimiter->hit($key);
            // The hit method returns a CounterState object, check if the limit is not reached
            /** @see config/web/di/rate-limit ... adjust down to 2 for testing ... default 4 **/
            return !$result->isLimitReached();
        } catch (\Exception $e) {
            // Log error but don't block authentication if rate limiter fails
            $this->logger->log(LogLevel::ERROR, 'Rate limiter error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Extract client IP address from request with security considerations.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getClientIpAddress(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        // Check for IP address from headers in order of preference
        $ipHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($serverParams[$header]) && is_string($serverParams[$header])) {
                $ip = trim($serverParams[$header]);

                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]); // Use the first IP
                }

                // Validate IP address format
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback to REMOTE_ADDR or default
        /** @var string|null $serverParams['REMOTE_ADDR'] */
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
