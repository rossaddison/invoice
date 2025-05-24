<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Token;
use App\Auth\TokenRepository as tR;
use App\User\User;
use App\User\UserRepository as uR;
use App\Auth\AuthService;
use App\Auth\Form\SignupForm;
use App\Auth\Trait\Oauth2;
use App\Invoice\Entity\UserInv;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Body;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Rbac\AssignmentsStorageInterface as Assignment;
use Yiisoft\Rbac\ItemsStorageInterface as ItemStorage;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Rbac\RuleFactoryInterface as Rule;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;
use Yiisoft\Yii\AuthClient\Client\GovUk;
use Yiisoft\Yii\AuthClient\Client\LinkedIn;
use Yiisoft\Yii\AuthClient\Client\MicrosoftOnline;
use Yiisoft\Yii\AuthClient\Client\VKontakte;
use Yiisoft\Yii\AuthClient\Client\X;
use Yiisoft\Yii\AuthClient\Client\Yandex;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class SignupController
{
    use Oauth2;

    public const string EMAIL_VERIFICATION_TOKEN = 'email-verification';
    private Manager $manager;
    private Rule $rule;
    public string $telegramToken;

    public function __construct(
        // load assignments and save assignments to resources/rbac/assignment.php
        private Assignment $assignment,
        private Flash $flash,
        // add, save, remove, clear, children, parents
        private ItemStorage $itemstorage,
        Rule $rule,
        private WebControllerService $webService,
        private SessionInterface $session,
        private ViewRenderer $viewRenderer,
        private MailerInterface $mailer,
        private sR $sR,
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
        private Translator $translator,
        private UrlGenerator $urlGenerator,
        private CurrentRoute $currentRoute,
        private LoggerInterface $logger,
    ) {
        // @see yiisoft/rbac-php
        $this->manager = new Manager($this->itemstorage, $this->assignment, $rule);
        $this->rule = $rule;
        $this->session = $session;
        $this->flash = new Flash($this->session);
        $this->viewRenderer = $viewRenderer->withControllerName('signup');
        $this->mailer = $mailer;
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
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->currentRoute = $currentRoute;
        $this->logger = $logger;
        $this->telegramToken = $sR->getSetting('telegram_token');
    }

    /**
     * @see src\ViewInjection\CommonViewInjection.php
     * @see resources\views\site\signupfailed.php and signupsuccess.php
     *
     * @param AuthService $authService
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ServerRequestInterface $request
     * @param SignupForm $signupForm
     * @param tR $tR
     * @param uiR $uiR
     * @param uR $uR
     * @return ResponseInterface
     */
    public function signup(
        AuthService $authService,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ServerRequestInterface $request,
        SignupForm $signupForm,
        tR $tR,
        uiR $uiR,
        uR $uR
    ): ResponseInterface {
        if (!$authService->isGuest()) {
            return $this->webService->getRedirectResponse('site/index');
        }
        if ($formHydrator->populateFromPostAndValidate($signupForm, $request)) {
            $user = $signupForm->signup();
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
                $to = $user->getEmail();
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                $_language = $currentRoute->getArgument('_language');
                /**
                 * @var string $_language
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getEmailVerificationToken($user, $tR);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 * For additional headers to strengthen security refer to:
                 * @see https://en.wikipedia.org/wiki/Email#Message_format
                 * @see https://github.com/yiisoft/mailer/blob/1d3480bc26cbeba47b24e61f9ec0e717c244c0b7/tests/MessageTest.php#L217
                 */
                $htmlBody = $this->htmlBodyWithMaskedRandomAndTimeTokenLink($user, $uiR, $language, $_language, $randomAndTimeToken);
                if (($this->sR->getSetting('email_send_method') == 'symfony') || ($this->sR->mailerEnabled() == true)) {
                    $email = new \Yiisoft\Mailer\Message(
                        charset: 'utf-8',
                        headers: [
                            'X-Origin' => ['0', '1'],
                            'X-Pass' => 'pass',
                        ],
                        subject: $login . ': <' . $to . '>',
                        date: new \DateTimeImmutable('now'),
                        from: [$this->sR->getConfigAdminEmail() => $this->translator->translate('i.administrator')],
                        to: $to,
                        htmlBody: $htmlBody
                    );
                    $email->withAddedHeader('Message-ID', $this->sR->getConfigAdminEmail());

                    try {
                        $this->mailer->send($email);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                        return $this->webService->getRedirectResponse('site/signupfailed');
                    }
                }
            }
            return $this->webService->getRedirectResponse('site/signupsuccess');
        }
        $noDeveloperSandboxHmrcContinueButton = $this->sR->getSetting('no_developer_sandbox_hmrc_continue_button') == '1' ? true : false;
        $noGithubContinueButton = $this->sR->getSetting('no_github_continue_button') == '1' ? true : false;
        $noGoogleContinueButton = $this->sR->getSetting('no_google_continue_button') == '1' ? true : false;
        $noGovUkContinueButton = $this->sR->getSetting('no_govuk_continue_button') == '1' ? true : false;
        $noFacebookContinueButton = $this->sR->getSetting('no_facebook_continue_button') == '1' ? true : false;
        $noLinkedInContinueButton = $this->sR->getSetting('no_linkedin_continue_button') == '1' ? true : false;
        $noMicrosoftOnlineContinueButton = $this->sR->getSetting('no_microsoftonline_continue_button') == '1' ? true : false;

        $noVKontakteContinueButton = $this->sR->getSetting('no_vkontakte_continue_button') == '1' ? true : false;

        $codeVerifier = Random::string(128);

        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $noXContinueButton = $this->sR->getSetting('no_x_continue_button') == '1' ? true : false;
        $noYandexContinueButton = $this->sR->getSetting('no_yandex_continue_button') == '1' ? true : false;

        $this->session->set('code_verifier', $codeVerifier);
        return $this->viewRenderer->render('signup', [
            'formModel' => $signupForm,
            'developerSandboxHmrcAuthUrl' => strlen($this->developerSandboxHmrc->getClientId()) > 0 ?
                $this->developerSandboxHmrc
                     ->buildAuthUrl(
                         $request,
                         $params = [
                             'response_type' => 'code',
                         ]
                     ) : '',
            'sessionOtp' => $this->session->get('otp'),
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
            'noFacebookContinueButton' => $noFacebookContinueButton,
            'noGithubContinueButton' => $noGithubContinueButton,
            'noGoogleContinueButton' => $noGoogleContinueButton,
            'noGovUkContinueButton' => $noGovUkContinueButton,
            'noLinkedInContinueButton' => $noLinkedInContinueButton,
            'noMicrosoftOnlineContinueButton' => $noMicrosoftOnlineContinueButton,
            'noVKontakteContinueButton' => $noVKontakteContinueButton,
            'noXContinueButton' => $noXContinueButton,
            'noYandexContinueButton' => $noYandexContinueButton,
        ]);
    }

    /**
     * @param User $user
     * @param uiR $uiR
     * @param string $language
     * @param string $_language
     * @param string $randomAndTimeToken
     * @return string
     */
    private function htmlBodyWithMaskedRandomAndTimeTokenLink(User $user, uiR $uiR, string $language, string $_language, string $randomAndTimeToken): string
    {
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        $userInv = new UserInv();
        if (null !== ($userId = $user->getId())) {
            $userInv->setUser_id((int)$userId);
            // if the user is administrator assign 0 => 'Administrator', 1 => Not Administrator
            $userInv->setType($user->getId() == 1 ? 0 : 1);
            // when the user clicks on the link click confirm url, make the user active in the userinv extension table. Initially keep the user inactive.
            $userInv->setActive(false);
            $userInv->setLanguage($language);
            $uiR->save($userInv);
            $content = A::tag()
                       // When the url is clicked by the user, return to userinv/signup to activate the user and assign a client to the user
                       // depending on whether 'Assign a client to user on signup' has been chosen under View ... Settings...General. The user will be able to
                       // edit their userinv details on the client side as well as the client record.
                       ->href($this->urlGenerator->generateAbsolute(
                           'userinv/signup',
                           ['_language' => $_language, 'language' => $language, 'token' => $tokenWithMask, 'tokenType' => 'email-verification']
                       ))
                       ->content($this->translator->translate('invoice.invoice.email.link.click.confirm'));
            return Body::tag()
                       ->content($content)
                       ->render();
        }
        return '';
    }

    /**
     * @param User $user
     * @param tR $tR
     * @return string
     */
    private function getEmailVerificationToken(User $user, tR $tR): string
    {
        $identity = $user->getIdentity();
        $identityId = (int)$identity->getId();
        $token = new Token($identityId, self::EMAIL_VERIFICATION_TOKEN);
        // store the token amongst all the other types of tokens e.g. password_reset_token, email_verification_token etc
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string)$token->getCreated_at()->getTimestamp();
        // build the token
        return $emailVerificationToken = null !== $tokenString ? ($tokenString . '_' . $timeString) : '';
    }
}
