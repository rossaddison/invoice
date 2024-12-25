<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Form\LoginForm;
use App\Auth\Form\SignupForm;
use App\Auth\IdentityRepository;
use App\Auth\TokenRepository;
use App\Auth\Trait\Oauth2;
use App\Invoice\Entity\UserInv;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use App\User\User;
use App\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Html\Tag\A;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMask;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\Login\Cookie\CookieLogin;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;

final class AuthController
{ 
    use Oauth2;
    
    public const string EMAIL_VERIFICATION_TOKEN = 'email-verification';
    
    public function __construct(
        private readonly AuthService            $authService,
        private readonly WebControllerService   $webService,
        private ViewRenderer                    $viewRenderer,
        private Manager                         $manager,    
        private SettingRepository               $sR,
        private Facebook                        $facebook,
        private GitHub                          $github,
        private Google                          $google,  
        private UrlGenerator                    $urlGenerator,    
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('auth');
        $this->manager = $manager;
        $this->sR = $sR;
        $this->facebook = $facebook;
        $this->github = $github;
        $this->google = $google;        
        $this->initializeOauth2IdentityProviderCredentials($facebook, $github, $google);
        $this->urlGenerator = $urlGenerator;
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
            /**
             * Note currently the Yii3 developers do not have a status field in the user table => use the status field in the userinv extension table
             * This code is subject to change in the future depending on where the status field will be placed i.e in the user or identity table
             * The IdentityInterface id is equivalent to the user_id so we do not need to use the Identity entity here instead of the IdentityInterface
             */
            $userId = $identity->getId();
            if (null!==$userId) {
                $userInv = $uiR->repoUserInvUserIdquery($userId);
                if (null!==$userInv) {
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
                        $userId == 1 ? $this->disableEmailVerificationToken($tR, '1') : '';
                        if ($identity instanceof CookieLoginIdentityInterface && $loginForm->getPropertyValue('rememberMe')) {
                            return $cookieLogin->addCookie($identity, $this->redirectToInvoiceIndex());
                        }
                        return $this->redirectToInvoiceIndex();
                    } else {
                        /**
                         * If the observer user is signing up WITHOUT email (=> userinv account status is 0), e.g. by console ... yii userinv/assignRole observer 2, 
                         * the admin will have to make the user active via Settings Invoice User Account AND assign the user an added client
                         * Also the token that was originally assigned on signup, must now be 'disabled' because the admin is responsible for making the user active
                         */
                        $this->disableEmailVerificationToken($tR, $userId);
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
            }
            $this->authService->logout();
            return $this->redirectToMain();
        };
        $noGithubContinueButton = $this->sR->getSetting('no_github_continue_button') == '1' ? true : false;
        $noGoogleContinueButton = $this->sR->getSetting('no_google_continue_button') == '1' ? true : false;
        $noFacebookContinueButton = $this->sR->getSetting('no_facebook_continue_button') == '1' ? true : false;
        return $this->viewRenderer->render('login', 
            [
                'formModel' => $loginForm,
                'facebookAuthUrl' => strlen($this->facebook->getClientId()) > 0 ? $this->facebook->buildAuthUrl($request, $params = []) : '',
                'githubAuthUrl' => strlen($this->github->getClientId()) > 0 ? $this->github->buildAuthUrl($request, $params = []) : '',   
                'googleAuthUrl' => strlen($this->google->getClientId()) > 0 ? $this->google->buildAuthUrl($request, $params = []) : '',
                'noGithubContinueButton' => $noGithubContinueButton,
                'noGoogleContinueButton' => $noGoogleContinueButton,
                'noFacebookContinueButton' => $noFacebookContinueButton,
            ]);
    }
    
    public function disableEmailVerificationToken(
        TokenRepository $tR,    
        ?string $userId = null
    ) : void 
    {
        if (null !== $userId) {    
            $token = $tR->findTokenByIdentityIdAndType($userId, self::EMAIL_VERIFICATION_TOKEN);
            if (null !== $token) {
                /**
                 * @see https://github.com/search?q=repo%3Ayiisoft%2Fyii2-app-advanced%20already_&type=code
                 */
                $token->setToken('already_used_token_'.time());
                $tR->save($token);
            }
        }    
    }
    
    public function disableGithubAccessToken(
        TokenRepository $tR,    
        ?string $userId = null
    ) : void 
    {
        if (null !== $userId) {    
            $token = $tR->findTokenByIdentityIdAndType($userId, self::GITHUB_ACCESS_TOKEN);
            if (null !== $token) {
                $token->setToken('already_used_token_'.time());
                $tR->save($token);
            }
        }    
    }
    
    /**
     * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param string $code
     * @param string $state
     * @param ServerRequestInterface $request
     * @param SignupForm $signupForm,    
     * @param TranslatorInterface $translator
     * @param TokenRepository $tR
     * @param UserInvRepository $uiR
     * @param UserRepository $uR
     * @return ResponseInterface
     */
    public function callbackGithub(
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        #[Query('code')] string $code, 
        #[Query('state')] string $state,
        IdentityRepository $identityRepository,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR
    ) : ResponseInterface 
    {
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one 
            // and use the protected function oauth2->generateAuthState to generate state param
            // which has a session id built into it
            $authorizationUrl = $this->github->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        } elseif ($code == 401){
            return $this->redirectToGithubCallbackResultUnAuthorised();
        } elseif (empty($state)) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);        
        // code and state are both present    
        } else {
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
                /**
                 * @var string $userArray['login']
                 */
                $githubLogin = $userArray['login'] ?? '';
                if (strlen($githubLogin) > 0) {
                    // Append github in case user has used same login for other identity providers
                    $login = 'github'.(string)$githubId.$githubLogin;
                    /**
                     * @var string $userArray['email']
                     */
                    $email = $userArray['email'] ?? 'noemail'.$login.'@github.com';
                    $password = Random::string(32);
                    // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                    // but it will be used later to build a passwordHash
                    if ($this->authService->oauthLogin($login)) {
                        $identity = $this->authService->getIdentity();
                        $userId = $identity->getId();
                        if (null!==$userId) {
                            $userInv = $uiR->repoUserInvUserIdquery($userId);
                            if (null!==$userInv) {
                                // disable the github verification token as soon as the user logs in for the first time
                                $status = $userInv->getActive();
                                if ($status || $userId == 1) {
                                    $userId == 1 ? $this->disableGithubAccessToken($tR, '1') : '';
                                    return $this->redirectToInvoiceIndex();
                                } else {
                                    $this->disableGithubAccessToken($tR, $userId);
                                    return $this->redirectToAdminMustMakeActive();
                                }
                            }
                        }
                        return $this->redirectToMain();
                    } else {
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
                            $_language = $currentRoute->getArgument('_language');
                            /**
                             * @var string $_language
                             * @var array $languageArray
                             * @var string $language
                             */
                            $language = $languageArray[$_language];
                            $randomAndTimeToken = $this->getGithubAccessToken($user, $tR);
                            /**
                             * @see A new UserInv (extension table of user) for the user is created.
                             */
                            $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken);
                            return $this->viewRenderer->render('proceed', [
                                'proceedToMenuButton' => $proceedToMenuButton
                            ]);
                        }     
                    }
                }    
            }    
        }
        $this->authService->logout();
        return $this->redirectToMain();
    }
    
    /**
     * @param TranslatorInterface $translator
     * @param User $user
     * @param UserInvRepository $uiR
     * @param string $language
     * @param string $_language
     * @param string $randomAndTimeToken
     * @return string
     */
    private function proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(TranslatorInterface $translator, User $user, UserInvRepository $uiR, string $language, string $_language, string $randomAndTimeToken): string
    {
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
            $proceedToMenuButton = A::tag()
                // When the url is clicked by the user, return to userinv/signup to activate the user and assign a client to the user
                // depending on whether 'Assign a client to user on signup' has been chosen under View ... Settings...General. The user will be able to
                // edit their userinv details on the client side as well as the client record.
                ->href($this->urlGenerator->generateAbsolute(
                    'userinv/github',
                    ['_language' => $_language, 'language' => $language, 'token' => $tokenWithMask]
                ))
                ->content($translator->translate('invoice.invoice.identity.provider.authentication.successful'))
                ->render();
            return $proceedToMenuButton;
        }
        return '';
    }    

    
    private function redirectToGithubCallbackResultUnAuthorised() : ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/githubcallbackresultunauthorised', ['_language' => 'en']);
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
    
    private function redirectToAdminMustMakeActive() : ResponseInterface {
        return $this->webService->getRedirectResponse('site/adminmustmakeactive', ['_language' => 'en']);
    }
}
