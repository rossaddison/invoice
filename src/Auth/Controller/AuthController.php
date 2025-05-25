<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Form\LoginForm;
use App\Auth\Form\OtpPasswordForm;
use App\Auth\TokenRepository;
use App\Auth\Trait\Oauth2;
use App\Invoice\Entity\UserInv;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use App\User\User;
use App\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Vjik\TelegramBot\Api\FailResult;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Html\Tag\A;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
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

final class AuthController
{
    //initialize .env file at root with oauth2.0 settings
    use Oauth2;

    private Flash $flash;
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
        private readonly WebControllerService $webService,
        private ViewRenderer $viewRenderer,
        private Manager $manager,
        private SessionInterface $session,
        private SettingRepository $sR,
        private DeveloperSandboxHmrc $developerSandboxHmrc,
        private Facebook $facebook,
        private GitHub $github,
        private Google $google,
        private GovUk $govUk,
        private LinkedIn $linkedIn,
        private MicrosoftOnline $microsoftOnline,
        private VKontakte $vkontakte,
        private X $x,
        private Yandex $yandex,
        private UrlGenerator $urlGenerator,
        private LoggerInterface $logger
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('auth');
        $this->manager = $manager;
        $this->session = $session;
        $this->flash = new Flash($this->session);
        $this->sR = $sR;
        $this->developerSandboxHmrc = $developerSandboxHmrc;
        $this->facebook = $facebook;
        $this->github = $github;
        $this->google = $google;
        $this->govUk = $govUk;
        $this->linkedIn = $linkedIn;
        $this->microsoftOnline = $microsoftOnline;
        $this->vkontakte = $vkontakte;
        $this->x = $x;
        $this->yandex = $yandex;
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
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->telegramToken = $this->sR->getSetting('telegram_token');
    }

    public function login(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        FormHydrator $formHydrator,
        CookieLogin $cookieLogin,
        // use the 'active' field of the extension table userinv to verify that the user has been made active through e.g. email verificaiton
        UserInvRepository $uiR,
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
                    /**
                     * @see UserInvController function signup where the userinv active field is made active i.e. true upon a positive email verification
                     */
                    $status = $userInv->getActive();
                    /**
                     * The admin does not automatically have a 'userinv account with status as active' IF
                     * signing up NOT by email e.g by localhost  . The below code, '$userId == 1', => makes allowances for this.
                     * @see UserInvController function signup which is triggered once user's email verification link is clicked in their user account
                     *      and the userinv account's status field is made active i.e. 1
                     */
                    if ($status || $userId == 1) {
                        $userId == 1 ? $this->disableToken($tR, '1', 'email-verification') : '';
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
            $this->authService->logout();
            return $this->redirectToMain();
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
                'sessionOtp' => $this->session->has('otp') ? $this->session->get('otp') : $this->session->set('otp', 0),
                'telegramToken' => $this->telegramToken,
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

    public function callbackDeveloperGovSandboxHmrc(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('developergovsandboxhmrc', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->developerSandboxHmrc->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);
            // code and state are both present
        }

        $codeVerifier = (string)$this->session->get('code_verifier');
        /**
         * @see https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation
         * For user-restricted access, the 'Authorization Code' Grant Type is used
         * Use the code received, to get an access_token
         */
        $oAuthToken = $this->developerSandboxHmrc->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params = [
            'redirect_uri' => $this->developerSandboxHmrc->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
            'grant_type' => 'authorization_code',
        ]);

        // e.g. string '476425f97e53ca1124161e491bee384e'
        $this->session->set('hmrc_access_token', $oAuthToken->getParam('access_token'));
        // e.g. string 'bearer'
        $this->session->set('hmrc_token_type', $oAuthToken->getParam('token_type'));
        // default 'expires_in' int 14400
        $this->session->set('hmrc_token_expires', time() + (int)$oAuthToken->getParam('expires_in'));
        // e.g. read:self-assessment write:self-assessment'
        $this->session->set('hmrc_scope', $oAuthToken->getParam('scope'));
        // e.g. string 'cbe7c4f01a6bc55034237718d3e4ded2'
        $this->session->set('hmrc_refresh_token', $oAuthToken->getParam('refresh_token'));

        if ($this->sR->getEnv() == 'dev') {
            /**
             * @see Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc function getTestUserArray;
             */
            $requestBody = [
                'serviceNames' => ['national-insurance'],
            ];

            $userArray = $this->developerSandboxHmrc->createTestUserIndividual($oAuthToken, $requestBody);
        } else {
            exit(1);
        }

        /**
         * @var int $userArray['userId']
         */
        $hmrcId = $userArray['userId'] ?? 0;

        if ($hmrcId > 0) {
            // the id will be removed in the logout button
            $login = 'hmrc' . (string)$hmrcId;
            /**
             * Depending on the environment i.e. prod or dev, getApiBaseUrl1() will vary between 'https://api.service.hmrc.gov.uk' or 'https://test-api.service.hmrc.gov.uk' respectively
             *
             * @var string $userArray['emailAddress']
             */
            $email = $userArray['emailAddress'] ?? 'noemail' . $login . '@' . str_replace('https://', '', $this->developerSandboxHmrc->getApiBaseUrl1());
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'developersandboxhmrc') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'developersandboxhmrc');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getDeveloperSandboxHmrcAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getDeveloperSandboxHmrcAccessToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'developersandboxhmrc');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    private function setSessionOTP(): void
    {
        $otp = rand(100000, 999999);
        $randomUuidVersion4 = Uuid::uuid4();
        $otpRef = $randomUuidVersion4->toString();
        $this->session->set('otp', $otp);
        $this->session->set('otpRef', $otpRef);
    }

    /**
     * Used in logout function
     */
    private function removeSessionOTP(): void
    {
        $this->session->remove('otp');
        $this->session->remove('otpRef');
    }

    /**
     * This function is used only when the session otp value is out of range i.e. not a 6 digit number
     * because Telegram has just been setup or the otp value has been removed
     *
     * Used in: ..resource/views/auth/login.php Stage 2.
     *
     * Stage 1: Setup: Telegram must be setup before OTP can be sent
     * Stage 2: Send and Validate: One-time-password must be sent by Telegram if not a 6 digit integer and Telegram has been setup
     *          ..resource/views/auth/login.php if ($sessionOtp < 100000 && $sessionOtp >= 0 && strlen($telegramToken) > 0)
     *          {
     *              echo $button->developerSandboxHmrc($urlGenerator, 'auth/sendOtp', 2);
     *          }
     * Stage 3: Continue: One-time-password 6 digit integer has been sent by Telegram.
     *          6 digit integer has been input by user on form
     *          MultifactorAuthentication complete: Fraud prevention headers can be submitted
     *
     * @return ResponseInterface
     */
    public function sendOtp(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        FormHydrator $formHydrator,
    ): ResponseInterface {
        if (strlen($this->telegramToken) > 0 && $this->sR->getSetting('enable_telegram') == '1') {
            $telegramHelper = new TelegramHelper($this->telegramToken, $this->logger);
            $telegramBotApi = $telegramHelper->getBotApi();
            $chatId = $this->sR->getSetting('telegram_chat_id');
            $telegramToken = $this->sR->getSetting('telegram_token');
            if ((strlen($chatId) > 0) && strlen($telegramToken) > 0) {
                // session otp values are out of range therefore set
                $this->setSessionOTP();
                // Never send the OTP reference as well
                $failResultSendMessage = $telegramBotApi->sendMessage($chatId, (string)$this->session->get('otp'));
                if (!$failResultSendMessage instanceof FailResult) {
                    return $this->validateOtp($request, $translator, $formHydrator);
                }
                $this->removeSessionOTP();
                return $this->redirectToOneTimePasswordFailure();
            }
        } else {
            if ($this->sR->getSetting('enable_telegram') == '1') {
                return $this->redirectToTelegramNotSetup();
            }
            return $this->redirectToOneTimePasswordError();
        }
        return $this->redirectToOneTimePasswordError();
    }

    public function validateOtp(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        FormHydrator $formHydrator,
    ): ResponseInterface {
        $otpPasswordForm = new OtpPasswordForm($translator);

        if ($formHydrator->populateFromPostAndValidate($otpPasswordForm, $request)) {
            // Ensure the parsed body is an array
            $parsedBody = $request->getParsedBody();

            if (!is_array($parsedBody)) {
                return $this->redirectToOneTimePasswordError();
            }

            // Ensure the 'otp' key exists and is a string
            if (!array_key_exists('OtpPassword', $parsedBody) || !is_array($parsedBody['OtpPassword'])) {
                return $this->redirectToOneTimePasswordError();
            }

            /**
             * @var array $parsedBody['OtpPassword']
             */
            $otp = (string)$parsedBody['OtpPassword']['otpPassword'];

            // Retrieve the stored OTP from the session and ensure it is a string
            $storedOtp = (string)$this->session->get('otp');

            // Compare the provided OTP with the stored OTP
            if ($otp == $storedOtp) {
                return $this->redirectToOneTimePasswordSuccess();
            }

            // Redirect to the error page if the OTP does not match
            return $this->redirectToOneTimePasswordError();
        }
        return $this->viewRenderer->render(
            'otp',
            [
                'formModel' => $otpPasswordForm,
            ]
        );
    }

    /**
     * @see https://github.com/rossaddison/invoice/discussions/215
     * @param string $provider
     * @return string
     */
    private function getTokenTypeVersion1(string $provider): string
    {
        return $tokenType = match ($provider) {
            'developersandboxhmrc' => self::DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN,
            'email-verification' => self::EMAIL_VERIFICATION_TOKEN,
            'facebook' => self::FACEBOOK_ACCESS_TOKEN,
            'github' => self::GITHUB_ACCESS_TOKEN,
            'google' => self::GOOGLE_ACCESS_TOKEN,
            'govuk' => self::GOVUK_ACCESS_TOKEN,
            'linkedin' => self::LINKEDIN_ACCESS_TOKEN,
            'microsoftonline' => self::MICROSOFTONLINE_ACCESS_TOKEN,
            'vkontakte' => self::VKONTAKTE_ACCESS_TOKEN,
            'x' => self::X_ACCESS_TOKEN,
            'yandex' => self::YANDEX_ACCESS_TOKEN
        };
    }
    
    /**
     * 
     * @param string $provider
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getTokenType(string $provider): string
    {
        // Convert provider name to uppercase and build constant name
        $const = strtoupper($provider) . '_ACCESS_TOKEN';
        if (!defined(static::class . '::' . $const)) {
            throw new \InvalidArgumentException("Unknown provider: $provider");
        }
        // Dynamic class constant fetch (PHP 8.3+)
        $value = static::{$const};
        assert(is_string($value));
        return $value;
    }

    public function callbackX(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('x', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $this->x->buildAuthUrl(
                $request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ]
            );
            header('Location: ' . $authorizationUrl);
            exit;
        }
        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            exit(1);
        }
        $codeVerifier = (string)$this->session->get('code_verifier');
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->x->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];
        $oAuthTokenType = $this->x->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params);
        $userArray = $this->x->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
        /**
         * @var array $userArray['data']
         */
        $data = $userArray['data'] ?? [];
        /**
         * @var int $data['id']
         */
        $xId = $data['id'] ?? 0;
        if ($xId > 0) {
            $xLogin = (string)$data['username'];
            if (strlen($xLogin) > 0) {
                $login = 'twitter' . (string)$xId . $xLogin;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail' . $login . '@x.com';
                $password = Random::string(32);
                if ($this->authService->oauthLogin($login)) {
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null !== $userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null !== $userInv) {
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', 'x') : '';
                                return $this->redirectToInvoiceIndex();
                            }
                            $this->disableToken($tR, $userId, 'x');
                            return $this->redirectToAdminMustMakeActive();
                        }
                    }
                    return $this->redirectToMain();
                }
                $user = new User($login, $email, $password);
                $uR->save($user);
                $userId = $user->getId();
                if ($userId > 0) {
                    if ($uR->repoCount() == 1) {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('admin', $userId);
                    } else {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('observer', $userId);
                    }
                    $login = $user->getLogin();
                    /**
                     * @var array $this->sR->locale_language_array()
                     */
                    $languageArray = $this->sR->locale_language_array();
                    /**
                     * @see Trait\Oauth2 function getXAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getXAccessToken($user, $tR);
                    /**
                     * @see A new UserInv (extension table of user) for the user is created.
                     */
                    $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                        $translator,
                        $user,
                        $uiR,
                        $language,
                        $_language,
                        $randomAndTimeToken,
                        'x'
                    );
                    return $this->viewRenderer->render('proceed', [
                        'proceedToMenuButton' => $proceedToMenuButton,
                    ]);
                }
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    /**
     * Purpose: Once Github redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv extension of the user table
     * @see src/Invoice/UserInv/UserInvController function github
     * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param TokenRepository $tR
     * @param UserInvRepository $uiR
     * @param UserRepository $uR
     * @param string $_language
     * @param string $code
     * @param string $state
     * @return ResponseInterface
     */
    public function callbackGithub(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('github', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one
            // and use the protected function oauth2->generateAuthState to generate state param 'authState'
            // which has a session id built into it
            $authorizationUrl = $this->github->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);
            // code and state are both present
        }
        // Try to get an access token (using the 'authorization code' grant)
        // The 'request_uri' is included by default in $defaultParams[] and therefore not needed in $params
        // The $response->getBody()->getContents() for each Client e.g. Github will be parsed and loaded into an OAuthToken Type
        // For Github we know that the parameter key for the token is 'access_token' and not the default 'oauth_token'
        $oAuthTokenType = $this->github->fetchAccessToken($request, $code, $params = []);
        /**
         * Every time you receive an access token, you should use the token to revalidate the user's identity.
         * A user can change which account they are signed into when you send them to authorize your app,
         * and you risk mixing user data if you do not validate the user's identity after every sign in.
         * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps#3-use-the-access-token-to-access-the-api
         */
        $userArray = $this->github->getCurrentUserJsonArray($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $githubId = $userArray['id'] ?? 0;
        if ($githubId > 0) {
            $githubLogin = 'g';
            if (strlen($githubLogin) > 0) {
                // Append github in case user has used same login for other identity providers
                // the id will be removed in the logout button
                $login = 'github' . (string)$githubId . $githubLogin;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail' . $login . '@github.com';
                $password = Random::string(32);
                // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                // but it will be used later to build a passwordHash
                if ($this->authService->oauthLogin($login)) {
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null !== $userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null !== $userInv) {
                            // disable the github verification token as soon as the user logs in for the first time
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', 'github') : '';
                                return $this->redirectToInvoiceIndex();
                            }
                            $this->disableToken($tR, $userId, 'github');
                            return $this->redirectToAdminMustMakeActive();
                        }
                    }
                    return $this->redirectToMain();
                }
                $user = new User($login, $email, $password);
                $uR->save($user);
                $userId = $user->getId();
                if ($userId > 0) {
                    // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                    if ($uR->repoCount() == 1) {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('admin', $userId);
                    } else {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('observer', $userId);
                    }
                    $login = $user->getLogin();
                    /**
                     * @var array $this->sR->locale_language_array()
                     */
                    $languageArray = $this->sR->locale_language_array();
                    /**
                     * @see Trait\Oauth2 function getGithubAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getGithubAccessToken($user, $tR);
                    /**
                     * @see A new UserInv (extension table of user) for the user is created.
                     */
                    $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'github');
                    return $this->viewRenderer->render('proceed', [
                        'proceedToMenuButton' => $proceedToMenuButton,
                    ]);
                }
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    /**
     * Purpose: Once Facebook redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv extension of the user table
     * @see src/Invoice/UserInv/UserInvController function facebook
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param TokenRepository $tR
     * @param UserInvRepository $uiR
     * @param UserRepository $uR
     * @param string $_language
     * @param string $code
     * @param string $state
     * @param string $error
     * @param string $errorCode
     * @param string $errorReason
     * @return ResponseInterface
     */
    public function callbackFacebook(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
        #[Query('error')] string $error = null,
        #[Query('error_code')] string $errorCode = null,
        #[Query('error_reason')] string $errorReason = null
    ): ResponseInterface {
        // Avoid MissingRequiredArgumentException
        if ($code == null || $state == null) {
            // e.g. User presses cancel button: callbackFacebook?error=access_denied&error_code=200&error_description=Permissions+error&error_reason=user_denied&state=
            if (($errorCode == 200) && ($error == 'access_denied') && ($errorReason == 'user_denied')) {
                return $this->redirectToUserCancelledOauth2();
            }
            return $this->redirectToMain();
        }

        $this->blockInvalidState('facebook', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one
            // and use the protected function oauth2->generateAuthState to generate state param
            // which has a session id built into it
            $authorizationUrl = $this->facebook->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);
            // code and state are both present
        }
        $oAuthTokenType = $this->facebook->fetchAccessToken($request, $code, $params = []);
        /**
         * @var array $userArray
         */
        $userArray = $this->facebook->getCurrentUserJsonArray($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $facebookId = $userArray['id'] ?? 0;
        if ($facebookId > 0) {
            /**
             * @var string $userArray['name']
             */
            $facebookLogin = strtolower($userArray['name'] ?? '');
            if (strlen($facebookLogin) > 0) {
                // the id will be removed in the logout button
                $login = 'facebook' . (string)$facebookId . $facebookLogin;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail' . $login . '@facebook.com';
                $password = Random::string(32);
                // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                // but it will be used later to build a passwordHash
                if ($this->authService->oauthLogin($login)) {
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null !== $userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null !== $userInv) {
                            // disable our facebook access token as soon as the user logs in for the first time
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', 'facebook') : '';
                                return $this->redirectToInvoiceIndex();
                            }
                            $this->disableToken($tR, $userId, 'facebook');
                            return $this->redirectToAdminMustMakeActive();
                        }
                    }
                    return $this->redirectToMain();
                }
                $user = new User($login, $email, $password);
                $uR->save($user);
                $userId = $user->getId();
                if ($userId > 0) {
                    if ($uR->repoCount() == 1) {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('admin', $userId);
                    } else {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('observer', $userId);
                    }
                    $login = $user->getLogin();
                    /**
                     * @var array $this->sR->locale_language_array()
                     */
                    $languageArray = $this->sR->locale_language_array();
                    /**
                     * @see Trait\Oauth2 function getFacebookAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getFacebookAccessToken($user, $tR);
                    /**
                     * @see A new UserInv (extension table of user) for the user is created.
                     */
                    $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'facebook');
                    return $this->viewRenderer->render('proceed', [
                        'proceedToMenuButton' => $proceedToMenuButton,
                    ]);
                }
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    /**
     * @see https://console.cloud.google.com/apis/credentials?project=YOUR_PROJECT
     */
    public function callbackGoogle(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('google', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->google->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);
            // code and state are both present
        }

        $oAuthTokenType = $this->google->fetchAccessToken($request, $code, $params = [
            'grant_type' => 'authorization_code',
        ]);

        /**
         * @var array $userArray
         */
        $userArray = $this->google->getCurrentUserJsonArray($oAuthTokenType);

        /**
         * @var int $userArray['id']
         */
        $googleId = $userArray['id'] ?? 0;

        /**
         * VarDumper::dump($userArray) produces normally
         *
         * 'id' =>  google will produce an id here
         * 'email' => this is the email associated with google
         * 'verified_email' => true
         * 'name' => this is your name in lower case letters
         * 'given_name' => this is your first name
         * 'family_name' => this is your surname
         * 'picture' => 'https://lh3.googleusercontent.com/a/ACg8ocZiJZ8a-fpCKx-H4Dh8k-upEqQV3jSyQGH02--kLP_xZWQqrg=s96-c'
         */

        if ($googleId > 0) {
            // the id will be removed in the logout button
            $login = 'google' . (string)$googleId;
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@google.com';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'google') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'google');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getGoogleAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getGoogleAccessToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'google');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackGovUk(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('govUk', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->govUk->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);
            // code and state are both present
        }
        $oAuthTokenType = $this->govUk->fetchAccessToken($request, $code, $params = []);
        /**
         * @var array $userArray
         */
        $userArray = $this->govUk->getCurrentUserJsonArray($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $govUkId = $userArray['id'] ?? 0;
        if ($govUkId > 0) {
            // the id will be removed in the logout button
            $login = 'govuk' . (string)$govUkId;
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@gov.uk';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'govuk') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'govuk');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getGovUkAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getGovUkAccessToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'govuk');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackLinkedIn(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('linkedIn', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->linkedIn->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);
            // code and state are both present
        }
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->linkedIn->getOauth2ReturnUrl(),
        ];
        $oAuthTokenType = $this->linkedIn->fetchAccessTokenWithCurl($request, $code, $params);
        /**
         * @var array $userArray
         */
        $userArray = $this->linkedIn->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
        /**
         * eg. [
         *      'sub' => 'P1c9jkRFSy',
         *      'email_verified' => true,
         *      'name' => 'Joe Bloggs',
         *      'locale' => ['country' => 'UK', 'language' => 'en'],
         *      'given_name' => 'Joe',
         *      'family_name' => 'Bloggs',
         *      'email' => 'joe.bloggs@website.com'
         *      ]
         *
         * @var string $userArray['sub'] e.g. P1c9jkRFSy   ... A sub string is returned instead of an id
         */
        $linkedInSub = $userArray['sub'] ?? '';
        if (strlen($linkedInSub) > 0) {
            /**
             * @var string $userArray['name']
             */
            $linkedInName = $userArray['name'] ?? 'unknown';
            $login = 'linkedIn' . $linkedInName;
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@linkedin.com';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'linkedin') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'linkedin');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getLinkedInAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getLinkedInAccessToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'linkedin');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackMicrosoftOnline(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
        #[Query('session_state')] string $sessionState = null,
    ): ResponseInterface {
        if ($code == null || $state == null || $sessionState == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('microsoftOnline', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->microsoftOnline->buildAuthUrl($request, $params = ['redirect_uri' => 'https://yii3i.co.uk/callbackMicrosoftOnline']);
            header('Location: ' . $authorizationUrl);
            exit;
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         * @psalm-suppress DocblockTypeContradiction $sessionState
         */
        if (strlen($state) == 0 || strlen($sessionState) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);
            // code and state and stateSession are both present
        }
        $oAuthTokenType = $this->microsoftOnline->fetchAccessTokenWithCurl($request, $code, $params = ['redirect_uri' => 'https://yii3i.co.uk/callbackMicrosoftOnline']);
        /**
         * @var array $userArray
         */
        $userArray = $this->microsoftOnline->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $microsoftOnlineId = $userArray['id'] ?? 0;
        if ($microsoftOnlineId > 0) {
            // Append the last four digits of the Id
            $login = 'ms' . substr((string)$microsoftOnlineId, strlen((string)$microsoftOnlineId) - 4, strlen((string)$microsoftOnlineId));
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@microsoftonline.com';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'microsoftonline') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'microsoftonline');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getMicrosoftOnlineAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getMicrosoftOnlineAccessToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'microsoftonline');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackYandex(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('yandex', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $this->yandex->buildAuthUrl(
                $request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ]
            );
            header('Location: ' . $authorizationUrl);
            exit;
        }
        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            exit(1);
        }
        $codeVerifier = (string)$this->session->get('code_verifier');
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->yandex->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];
        $oAuthTokenType = $this->yandex->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params);
        /**
         * @var array $userArray
         */
        $userArray = $this->yandex->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $id = $userArray['id'] ?? 0;
        if ($id > 0) {
            /**
             * @var string $userArray['login'] e.g john.doe.com
             */
            $userName = $userArray['login'];
            // Append the last four digits of the Id
            $login = 'yx' . $userName . substr((string)$id, strlen((string)$id) - 4, strlen((string)$id));
            $email = 'noemail' . $login . '@yandex.com';
            $password = Random::string(32);
            // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
            // but it will be used later to build a passwordHash
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        // disable the yandex verification token as soon as the user logs in for the first time
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'yandex') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'yandex');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getYandexAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getYandexAccessToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                    $translator,
                    $user,
                    $uiR,
                    $language,
                    $_language,
                    $randomAndTimeToken,
                    'yandex'
                );
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackVKontakte(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')] string $_language,
        #[Query('code')] string $code = null,
        #[Query('state')] string $state = null,
        #[Query('device_id')] string $device_id = null
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }

        $this->blockInvalidState('vkontakte', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $this->vkontakte->buildAuthUrl(
                $request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                    'device_id' => $device_id,
                ]
            );
            header('Location: ' . $authorizationUrl);
            exit;
        }
        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            exit(1);
        }
        $codeVerifier = (string)$this->session->get('code_verifier');
        $params = [
            'device_id' => $device_id,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->vkontakte->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];

        /**
         * $oAuthTokenType = e.g.    'refresh_token' => '{string}'
         *                           'access_token' => '{string}'
         *                           'id_token' => '{string}'
         *                           'token_type' => 'Bearer'
         *                           'expires_in' => 3600
         *                           'user_id' => 1023583333
         *                           'state' => '{string}'
         *                           'scope' => 'vkid.personal_info email'
         */
        $oAuthTokenType = $this->vkontakte->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params);

        /**
         * e.g.  'user' => [
         *          'user_id' => '1023581111'
         *          'first_name' => 'Joe'
         *          'last_name' => 'Bloggs'
         *          'avatar' => 'https://..'
         *          'email' => ''
         *          'sex' => 2
         *          'verified' => false
         *          'birthday' => '09.09.1999'
         *       ]
         * @var array $userArray
         */
        $userArray = $this->vkontakte->step8ObtainingUserDataArrayUsingCurlWithClientId($oAuthTokenType, $this->vkontakte->getClientId());

        /**
         * @var array $userArray['user']
         */
        $user = $userArray['user'] ?? [];

        /**
         * @var int $user['user_id']
         */
        $id = $user['user_id'] ?? 0;
        if ($id > 0) {
            /**
             * @var string $user['first_name']
             */
            $userFirstName = $user['first_name'] ?? 'unknown';
            /**
             * @var string $user['last_name']
             */
            $userLastName = $user['last_name'] ?? 'unknown';
            if (strlen($userFirstName) > 0 && strlen($userLastName) > 0) {
                $userName = $userFirstName . ' ' . $userLastName;
            } else {
                $userName = 'fullname unknown';
            }
            // Append the last four digits of the Id
            $login = '' . $userName . substr((string)$id, strlen((string)$id) - 4, strlen((string)$id));
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@vk.ru';
            $password = Random::string(32);
            // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
            // but it will be used later to build a passwordHash
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        // disable the vkontakte verification token as soon as the user logs in for the first time
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'vkontakte') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'vkontakte');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getYandexAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getVKontakteAccessToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                    $translator,
                    $user,
                    $uiR,
                    $language,
                    $_language,
                    $randomAndTimeToken,
                    'vkontakte'
                );
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
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

        if (null !== $sessionState) {
            if (!$sessionState || ($state !== $sessionState)) {
                // State is invalid, possible cross-site request forgery. Exit with an error code.
                $this->logger->log(LogLevel::ALERT, 'Csrf attack attempt');
                exit(1);
            }
        } else {
            $this->logger->log(LogLevel::ALERT, 'Session Auth state is null.');
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
                ->content($translator->translate('invoice.invoice.identity.provider.authentication.successful'))
                ->render();
        }
        return '';
    }

    private function redirectToOneTimePasswordError(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/onetimepassworderror', ['_language' => 'en']);
    }

    private function redirectToOneTimePasswordFailure(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/onetimepasswordfailure', ['_language' => 'en']);
    }

    private function redirectToOneTimePasswordSuccess(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('auth/login', ['_language' => 'en']);
    }

    private function redirectToTelegramNotSetup(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/telegramnotsetup', ['_language' => 'en']);
    }

    private function redirectToUserCancelledOauth2(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/usercancelledoauth2', ['_language' => 'en']);
    }

    private function redirectToOauth2CallbackResultUnAuthorised(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/oauth2callbackresultunauthorised', ['_language' => 'en']);
    }

    public function logout(): ResponseInterface
    {
        $this->authService->logout();
        return $this->redirectToMain();
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
}
