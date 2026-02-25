<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Token;
use App\Auth\TokenRepository as tR;
use App\User\User;
use App\User\UserRepository as uR;
use App\Auth\AuthService;
use App\Auth\Form\SignupForm;
use App\Auth\Trait\ClassList;
use App\Auth\Trait\Oauth2;
use App\Invoice\Entity\UserInv;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Setting\Trait\OpenBankingProviders;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
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
use App\Auth\Client\OpenBanking;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SignupController
{
    use ClassList;
    
    use Oauth2;

    use OpenBankingProviders;

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
        private readonly DataResponseFactoryInterface $factory,
        private readonly WebControllerService $webService,
        private readonly SessionInterface $session,
        private WebViewRenderer $webViewRenderer,
        private readonly MailerInterface $mailer,
        private readonly sR $sR,
        private readonly Translator $translator,
        private readonly UrlGenerator $urlGenerator,
        private readonly CurrentRoute $currentRoute,
        private readonly LoggerInterface $logger,
    ) {
        $this->manager = new Manager($this->itemstorage, $this->assignment, $rule);
        $this->rule = $rule;
        $this->flash = new Flash($this->session);
        $this->webViewRenderer = $webViewRenderer->withControllerName('signup');
        $this->initializeOauth2IdentityProviderCredentials();
        $this->initializeOauth2IdentityProviderDualUrls();
        $this->telegramToken = $sR->getSetting('telegram_token');
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

    /**
     * Related logic: see src\ViewInjection\CommonViewInjection.php
     * Related logic: see resources\views\site\signupfailed.php and signupsuccess.php
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
        uR $uR,
    ): ResponseInterface {
        if (!$authService->isGuest()) {
            return $this->webService->getRedirectResponse('site/index');
        }

        $openBankingAuthUrl = '';
        $openBankChoice = $this->sR->getSetting('open_banking_provider');
        // If a provider has been selected, configure the client accordingly
        if (strlen($openBankChoice) > 0) {
            $providerConfig = $this->getOpenBankingProviderConfig($openBankChoice);
            if ($providerConfig !== null) {
                /** @var OpenBanking $openBanking */
                $openBanking = (AuthChoice::widget())->getClient('openbanking');
                $openBanking->setAuthUrl((string) $providerConfig['authUrl']);
                $openBanking->setTokenUrl((string) $providerConfig['tokenUrl']);
                $openBanking->setScope(isset($providerConfig['scope']) ?
                    (string) $providerConfig['scope'] : null);
                $codeVerifier = Random::string(128);
                $hash = hash('sha256', $codeVerifier, true);
                $rTrim = rtrim(base64_encode($hash), '=');
                $codeChallenge = strtr($rTrim, '+/', '-_');
                $this->session->set('code_verifier', $codeVerifier);
                $openBankingAuthUrl = $openBanking->getAuthUrl()
                        . '?'
                        . http_build_query([
                    'response_type' => 'code',
                    'scope' => $openBanking->getScope(),
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ]);
            }
        }

        if ($formHydrator->populateFromPostAndValidate($signupForm, $request)) {
            $user = $signupForm->signup();
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of
                // 1 ... and assign the first signed-up user ... admin rights
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
                 * Related logic: see A new UserInv (extension table of user)
                 * for the user is created.
                 * For additional headers to strengthen security refer to:
                 * Related logic:
                 *  see https://en.wikipedia.org/wiki/Email#Message_format
                 * Related logic:
                 *  see https://github.com/yiisoft/mailer/blob/1d3480bc26cbeba
                 *   47b24e61f9ec0e717c244c0b7/tests/MessageTest.php#L217
                 */
                $htmlBody = $this->htmlBodyWithMaskedRandomAndTimeTokenLink($user,
                    $uiR, $language, $_language, $randomAndTimeToken);
                if (($this->sR->getSetting('email_send_method') == 'symfony')
                        || ($this->sR->mailerEnabled() == true)) {
                    $configEmail = $this->sR->getConfigAdminEmail();
                    $tta = $this->translator->translate('administrator');
                    $email = new \Yiisoft\Mailer\Message(
                        charset: 'utf-8',
                        headers: [
                            'X-Origin' => ['0', '1'],
                            'X-Pass' => 'pass',
                        ],
                        subject: $login . ': <' . $to . '>',
                        date: new \DateTimeImmutable('now'),
                        from: [$configEmail => $tta],
                        to: $to,
                        htmlBody: $htmlBody,
                    );
                    $email->withAddedHeader(
                        'Message-ID', $this->sR->getConfigAdminEmail()
                    );
                    $failed = 'site/signupfailed';
                    try {
                        $this->mailer->send($email);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                        return $this->webService->getRedirectResponse($failed);
                    }
                }
            }
            return $this->webService->getRedirectResponse('site/signupsuccess');
        }

        $codeVerifier = Random::string(128);
        $this->session->set('code_verifier', $codeVerifier);
        $rTrim =  rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '=');
        $codeChallenge = strtr($rTrim, '+/', '-_');
        $nocb = $this->sR->getSetting('no_openbanking_continue_button');
        return $this->webViewRenderer->render('signup', [
            'class' => $this->classList(),
            'formModel' => $signupForm,
            'selectedOpenBankingProvider' => $openBankChoice,

            'noOpenBankingContinueButton' => $nocb == '1' ? true : false,
            'openBankingAuthUrl' => $openBankingAuthUrl,

            'sessionOtp' => $this->session->get('otp'),
            'telegramToken' => $this->telegramToken,
            'request' => $request,
            'idpList' => $this->idpList($codeChallenge),
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
    private function htmlBodyWithMaskedRandomAndTimeTokenLink(
        User $user,
        uiR $uiR,
        string $language,
        string $_language,
        string $randomAndTimeToken): string
    {
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        $userInv = new UserInv();
        if (null !== ($userId = $user->getId())) {
            $elcc = $this->translator->translate('email.link.click.confirm');
            $userInv->setUser_id((int) $userId);
            // if the user is administrator assign 0 => 'Administrator',
            // 1 => Not Administrator
            $userInv->setType($user->getId() == 1 ? 0 : 1);
            // when the user clicks on the link click confirm url, make the user
            // active in the userinv extension table. Initially keep the user
            // inactive.
            $userInv->setActive(false);
            $userInv->setLanguage($language);
            $uiR->save($userInv);
            $content = A::tag()
            // When the url is clicked by the user, return to userinv/signup
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
                               'tokenType' => 'email-verification'
                            ],
                       ))
                       ->content($elcc);
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
        $identityId = (int) $identity->getId();
        $token = new Token($identityId, self::EMAIL_VERIFICATION_TOKEN);
        // store the token amongst all the other types of tokens e.g.
        // password_reset_token, email_verification_token etc
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string) $token->getCreated_at()->getTimestamp();
        // build the token
        return $emailVerificationToken = null !== $tokenString ?
            ($tokenString . '_' . $timeString) : '';
    }
}
