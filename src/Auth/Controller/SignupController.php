<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Token;
use App\Auth\TokenRepository as tR;
use App\User\User;
use App\User\UserRepository as uR;
use App\Auth\AuthService;
use App\Auth\Form\SignupForm;
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
use Yiisoft\Mailer\MessageBodyTemplate;
use Yiisoft\Rbac\AssignmentsStorageInterface as Assignment;
use Yiisoft\Rbac\ItemsStorageInterface as ItemStorage;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Rbac\RuleFactoryInterface as Rule;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\TokenMask;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class SignupController
{
    const string EMAIL_VERIFICATION_TOKEN = 'email-verification';    
    private Assignment $assignment;
    private ItemStorage $itemstorage;
    private Manager $manager;
    private Rule $rule;    
    
    public function __construct(
        // load assignments and save assignments to resources/rbac/assignment.php
        Assignment $assignment,
        
        // add, save, remove, clear, children, parents
        ItemStorage $itemstorage,
        Rule $rule,    
        private WebControllerService $webService, 
        private ViewRenderer $viewRenderer, 
        private MailerInterface $mailer,    
        private sR $sR,
        private Translator $translator,
        private UrlGenerator $urlGenerator, 
        private CurrentRoute $currentRoute,
        private LoggerInterface $logger
    )
    {
        $this->assignment = $assignment;
        $this->itemstorage = $itemstorage;
        
        // @see yiisoft/rbac-php
        $this->manager = new Manager($itemstorage, $assignment, $rule);
        $this->rule = $rule;
        $this->viewRenderer = $viewRenderer->withControllerName('signup');
        $this->mailer = $mailer;
        $this->sR = $sR;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->currentRoute = $currentRoute;
        $this->logger = $logger;
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
        // check that symfony under Settings ... Email and the config/common/params.php mailer->senderEmail have been setup
        if (($this->sR->getSetting('email_send_method') !== 'symfony') || empty($this->sR->getConfigSenderEmail()))  {
           return $this->webService->getRedirectResponse('site/forgotemailfailed'); 
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
                if (($this->sR->getSetting('email_send_method') == 'symfony') || ($this->sR->mailerEnabled() == true))  {
                    $email = (new \Yiisoft\Mailer\Message())
                    ->withHeaders(
                        [
                            'X-Origin' => ['0', '1'],
                            'X-Pass' => 'pass',
                        ]  
                    )
                    ->withCharSet('utf-8')                
                    ->withSubject($login. ': <'.$to.'>')
                    ->withDate(new \DateTimeImmutable('now'))
                    ->withFrom([$this->sR->getConfigAdminEmail() => $this->translator->translate('i.administrator')])
                    ->withTo($to)
                    ->withHtmlBody($htmlBody)
                    ->withAddedHeader('Message-ID', $this->sR->getConfigAdminEmail());
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
        return $this->viewRenderer->render('signup', ['formModel' => $signupForm]);
    }
    
    /**
     * @param User $user
     * @param uiR $uiR
     * @param string $language
     * @param string $_language
     * @param string $randomAndTimeToken
     * @return string
     */
    private function htmlBodyWithMaskedRandomAndTimeTokenLink(User $user, uiR $uiR, string $language, string $_language, string $randomAndTimeToken) : string {
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        $userInv = new UserInv();
        if (null!==($userId = $user->getId())) {
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
                       ->href($this->urlGenerator->generateAbsolute('userinv/signup', 
                               ['_language' => $_language, 'language' => $language, 'token' => $tokenWithMask]))
                       ->content($this->translator->translate('invoice.invoice.email.link.click.confirm'));
            $htmlBody = Body::tag()
                       ->content($content)
                       ->render();
            return $htmlBody;
        }
        return '';
    }
    
    /**
     * @param User $user
     * @param tR $tR
     * @return string
     */
    private function getEmailVerificationToken(User $user, tR $tR) : string 
    {        
        $identity = $user->getIdentity();
        $identityId = (int)$identity->getId();
        $token = new Token($identityId, self::EMAIL_VERIFICATION_TOKEN);
        // store the token amongst all the other types of tokens e.g. password_reset_token, email_verification_token etc
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string)($token->getCreated_at())->getTimestamp();
        // build the token
        return $emailVerificationToken = null!==$tokenString ? ($tokenString. '_' . $timeString) : '';
    }
}
