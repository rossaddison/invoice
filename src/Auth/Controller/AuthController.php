<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Form\LoginForm;
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
use Yiisoft\Yii\AuthClient\Client\LinkedIn;
use Yiisoft\Yii\AuthClient\Client\MicrosoftOnline;
use Yiisoft\Yii\AuthClient\Client\X;
//use Yiisoft\Yii\AuthClient\Client\VKontakte;
use Yiisoft\Yii\AuthClient\Client\Yandex;

final class AuthController
{ 
    use Oauth2;
    
    public const string FACEBOOK_ACCESS_TOKEN = 'facebook-access';
    public const string GITHUB_ACCESS_TOKEN = 'github-access';
    public const string GOOGLE_ACCESS_TOKEN = 'google-access';
    public const string LINKEDIN_ACCESS_TOKEN = 'linkedin-access';    
    public const string MICROSOFTONLINE_ACCESS_TOKEN = 'microsoftonline-access';    
    public const string X_ACCESS_TOKEN = 'x-access';    
    public const string VKONTAKTE_ACCESS_TOKEN = 'vkontakte-access';    
    public const string YANDEX_ACCESS_TOKEN = 'yandex-access';
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
        private LinkedIn                        $linkedIn,
        private MicrosoftOnline                 $microsoftOnline,
        private X                               $x,
        private Yandex                          $yandex,    
        private UrlGenerator                    $urlGenerator,    
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('auth');
        $this->manager = $manager;
        $this->sR = $sR;
        $this->facebook = $facebook;
        $this->github = $github;
        $this->google = $google;
        $this->linkedIn = $linkedIn;
        $this->microsoftOnline = $microsoftOnline;
        $this->x = $x;
        $this->yandex = $yandex;
        // use the Oauth2 trait function
        $this->initializeOauth2IdentityProviderCredentials(
            $facebook, 
            $github, 
            $google, 
            $linkedIn, 
            $microsoftOnline, 
            $x,
            $yandex
        );
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
                        $userId == 1 ? $this->disableToken($tR, '1', 'email') : '';
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
                        $this->disableToken($tR, $userId, $this->getTokenType('email'));
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
        $noLinkedInContinueButton = $this->sR->getSetting('no_linkedin_continue_button') == '1' ? true : false;
        $noMicrosoftOnlineContinueButton = $this->sR->getSetting('no_microsoftonline_continue_button') == '1' ? true : false;
        $noXContinueButton = $this->sR->getSetting('no_x_continue_button') == '1' ? true : false;
        $noYandexContinueButton = $this->sR->getSetting('no_yandex_continue_button') == '1' ? true : false;
        return $this->viewRenderer->render('login', 
            [
                'formModel' => $loginForm,
                'facebookAuthUrl' => strlen($this->facebook->getClientId()) > 0 ? $this->facebook->buildAuthUrl($request, $params = []) : '',
                'githubAuthUrl' => strlen($this->github->getClientId()) > 0 ? $this->github->buildAuthUrl($request, $params = []) : '',   
                'googleAuthUrl' => strlen($this->google->getClientId()) > 0 ? $this->google->buildAuthUrl($request, $params = []) : '',
                'linkedInAuthUrl' => strlen($this->linkedIn->getClientId()) > 0 ? $this->linkedIn->buildAuthUrl($request, $params = []) : '',
                'microsoftOnlineAuthUrl' => strlen($this->microsoftOnline->getClientId()) > 0 ? $this->microsoftOnline->buildAuthUrl($request, $params = []) : '',
                /**
                 * PKCE: An extension to the authorization code flow to prevent several attacks and to be able
                 * to perform the OAuth exchange from public clients securely using two parameters code_challenge and 
                 * code_challenge_method. 
                 * @link https://developer.x.com/en/docs/authentication/oauth-2-0/user-access-token
                 */
                'xAuthUrl' => strlen($this->x->getClientId()) > 0 ? $this->x->buildAuthUrl($request, $params = ['code_challenge' => 'challenge', 'code_challenge_method' => 'plain']) : '',
                'yandexAuthUrl' => strlen($this->yandex->getClientId()) > 0 ? $this->yandex->buildAuthUrl($request, $params = []) : '',
                'noGithubContinueButton' => $noGithubContinueButton,
                'noGoogleContinueButton' => $noGoogleContinueButton,
                'noFacebookContinueButton' => $noFacebookContinueButton,
                'noLinkedInContinueButton' => $noLinkedInContinueButton,
                'noMicrosoftOnlineContinueButton' => $noMicrosoftOnlineContinueButton,
                'noXContinueButton' => $noXContinueButton,
                'noYandexContinueButton' => $noYandexContinueButton
            ]);
    }
    
    public function disableToken(
        TokenRepository $tR,    
        ?string $userId = null,
        string $identityProvider = ''
    ) : void 
    {
        if (null !== $userId) {    
            $token = $tR->findTokenByIdentityIdAndType($userId, $this->getTokenType($identityProvider));
            if (null !== $token) {
                $token->setToken('already_used_token_'.time());
                $tR->save($token);
            }
        }    
    }
    
    public function callbackX(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[Query('code')] string $code = null, 
        #[Query('state')] string $state = null
    ) : ResponseInterface 
    {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one 
            // and use the protected function oauth2->generateAuthState to generate state param
            // which has a session id built into it
            $authorizationUrl = $this->x->buildAuthUrl($request, ['code_challenge' => 'challenge', 'code_challenge_method' => 'plain']);
            header('Location: ' . $authorizationUrl);
            exit;
        } elseif ($code == 401){
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);        
        // code and state are both present    
        } else {
            /**
             * @see https://docs.x.com/resources/fundamentals/authentication/oauth-2-0/user-access-token#steps-to-connect-using-oauth-2-0
             */
            $params = [
                'grant_type' => 'authorization_code', 
                'redirect_uri' => $this->x->getOauth2ReturnUrl(),
                'code_verifier' => 'challenge'
            ];
            /**
             * Developer Portal: User Authentication Settings: Type of App: Native App: Public Client (NOT Web app)
             * @see https://developer.twitter.com/en/portal/projects/{}/apps/{}/auth-settings
             * @see https://developer.x.com/en/docs/authentication/oauth-2-0/user-access-token
             */
            $oAuthTokenType = $this->x->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params);
            
            $userArray = $this->x->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
            /**
             * @var array $userArray['data']
             */
            $data = $userArray['data'];
            /**
             * @var int $data['id']
             */
            $xId = $data['id'] ?: 0;
            if ($xId > 0) { 
                /**
                 * @var string $data['username']
                 */
                $xLogin = $data['username'];
                if (strlen($xLogin) > 0) {
                    $login = 'twitter'.(string)$xId.$xLogin;
                    /**
                     * @var string $userArray['email']
                     */
                    $email = $userArray['email'] ?? 'noemail'.$login.'@x.com';
                    $password = Random::string(32);
                    // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                    // but it will be used later to build a passwordHash
                    if ($this->authService->oauthLogin($login)) {
                        $identity = $this->authService->getIdentity();
                        $userId = $identity->getId();
                        if (null!==$userId) {
                            $userInv = $uiR->repoUserInvUserIdquery($userId);
                            if (null!==$userInv) {
                                // disable the x verification token as soon as the user logs in for the first time
                                $status = $userInv->getActive();
                                if ($status || $userId == 1) {
                                    $userId == 1 ? $this->disableToken($tR, '1', $this->getTokenType('x')) : '';
                                    return $this->redirectToInvoiceIndex();
                                } else {
                                    $this->disableToken($tR, $userId, $this->getTokenType('x'));
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
                             * @see Trait\Oauth2 function getXAccessToken
                             * @var string $_language
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
                                    $language, $_language, $randomAndTimeToken, 'x');
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
     * Purpose: Once Github redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv extension of the user table
     * @see src/Invoice/UserInv/UserInvController function github
     * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
     * @param CurrentRoute $currentRoute
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param TokenRepository $tR
     * @param UserInvRepository $uiR
     * @param UserRepository $uR
     * @param string $code
     * @param string $state     
     * @return ResponseInterface
     */
    public function callbackGithub(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[Query('code')] string $code = null, 
        #[Query('state')] string $state = null,        
    ) : ResponseInterface 
    {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one 
            // and use the protected function oauth2->generateAuthState to generate state param
            // which has a session id built into it
            $authorizationUrl = $this->github->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        } elseif ($code == 401){
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
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
                $githubLogin = 'g';
                if (strlen($githubLogin) > 0) {
                    // Append github in case user has used same login for other identity providers
                    // the id will be removed in the logout button
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
                                    $userId == 1 ? $this->disableToken($tR, '1', $this->getTokenType('github')) : '';
                                    return $this->redirectToInvoiceIndex();
                                } else {
                                    $this->disableToken($tR, $userId, $this->getTokenType('github'));
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
                             * @see Trait\Oauth2 function getGithubAccessToken
                             * @var string $_language
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
     * Purpose: Once Facebook redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv extension of the user table
     * @see src/Invoice/UserInv/UserInvController function facebook
     * @param CurrentRoute $currentRoute
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param TokenRepository $tR
     * @param UserInvRepository $uiR
     * @param UserRepository $uR
     * @param string $code
     * @param string $state
     * @param string $error
     * @param string $errorCode
     * @param string $errorReason     
     * @return ResponseInterface
     */
    public function callbackFacebook(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,    
        #[Query('code')] string $code = null, 
        #[Query('state')] string $state = null,
        #[Query('error')] string $error = null,
        #[Query('error_code')] string $errorCode = null,
        #[Query('error_reason')] string $errorReason = null        
    ) : ResponseInterface 
    {
        // Avoid MissingRequiredArgumentException
        if ($code == null || $state == null) {
            // e.g. User presses cancel button: callbackFacebook?error=access_denied&error_code=200&error_description=Permissions+error&error_reason=user_denied&state=
            if (($errorCode == 200) && ($error == 'access_denied') && ($errorReason == 'user_denied')) {
                return $this->redirectToUserCancelledOauth2();
            } else {
                return $this->redirectToMain();
            }
        }
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
        } elseif ($code == 401){
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);        
        // code and state are both present    
        } else {
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
                    $login = 'facebook'.(string)$facebookId.$facebookLogin;
                    /**
                     * @var string $userArray['email']
                     */
                    $email = $userArray['email'] ?? 'noemail'.$login.'@facebook.com';
                    $password = Random::string(32);
                    // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                    // but it will be used later to build a passwordHash
                    if ($this->authService->oauthLogin($login)) {
                        $identity = $this->authService->getIdentity();
                        $userId = $identity->getId();
                        if (null!==$userId) {
                            $userInv = $uiR->repoUserInvUserIdquery($userId);
                            if (null!==$userInv) {
                                // disable our facebook access token as soon as the user logs in for the first time
                                $status = $userInv->getActive();
                                if ($status || $userId == 1) {
                                    $userId == 1 ? $this->disableToken($tR, '1', $this->getTokenType('facebook')) : '';
                                    return $this->redirectToInvoiceIndex();
                                } else {
                                    $this->disableToken($tR, $userId, $this->getTokenType('facebook'));
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
                             * @see Trait\Oauth2 function getFacebookAccessToken
                             * @var string $_language
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
     * @see https://console.cloud.google.com/apis/credentials?project=YOUR_PROJECT
     */
    public function callbackGoogle(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[Query('code')] string $code = null, 
        #[Query('state')] string $state = null,        
    ) : ResponseInterface 
    {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->google->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        } elseif ($code == 401){
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);        
        // code and state are both present    
        } else {
            $oAuthTokenType = $this->google->fetchAccessToken($request, $code, $params = []);
            /**
             * @var array $userArray
             */
            $userArray = $this->google->getCurrentUserJsonArray($oAuthTokenType);
            /**
             * @var int $userArray['id']
             */
            $googleId = $userArray['id'] ?? 0;
            if ($googleId > 0) {
                // the id will be removed in the logout button
                $login = 'google'.(string)$googleId;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail'.$login.'@google.com';
                $password = Random::string(32);
                if ($this->authService->oauthLogin($login)) {
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null!==$userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null!==$userInv) {
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', $this->getTokenType('google')) : '';
                                return $this->redirectToInvoiceIndex();
                            } else {
                                $this->disableToken($tR, $userId, $this->getTokenType('google'));
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
                         * @see Trait\Oauth2 function getGoogleAccessToken
                         * @var string $_language
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
                            'proceedToMenuButton' => $proceedToMenuButton
                        ]);
                    }     
                }   
            }    
        }
        $this->authService->logout();
        return $this->redirectToMain();
    }
    
    // This funtion has not been tested yet
    public function callbackLinkedIn(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[Query('code')] string $code = null, 
        #[Query('state')] string $state = null,        
    ) : ResponseInterface 
    {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->linkedIn->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        } elseif ($code == 401){
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);        
        // code and state are both present    
        } else {
            $oAuthTokenType = $this->linkedIn->fetchAccessToken($request, $code, $params = []);
            /**
             * @var array $userArray
             */
            $userArray = $this->linkedIn->getCurrentUserJsonArray($oAuthTokenType);
            /**
             * @var int $userArray['id']
             */
            $linkedInId = $userArray['id'] ?? 0;
            if ($linkedInId > 0) { 
                $login = 'linkedIn'.(string)$linkedInId;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail'.$login.'@linkedin.com';
                $password = Random::string(32);
                if ($this->authService->oauthLogin($login)) {
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null!==$userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null!==$userInv) {
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', $this->getTokenType('linkedin')) : '';
                                return $this->redirectToInvoiceIndex();
                            } else {
                                $this->disableToken($tR, $userId, $this->getTokenType('linkedin'));
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
                         * @see Trait\Oauth2 function getLinkedInAccessToken
                         * @var string $_language
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
                            'proceedToMenuButton' => $proceedToMenuButton
                        ]);
                    }     
                }   
            }    
        }
        $this->authService->logout();
        return $this->redirectToMain();
    }
    
    public function callbackMicrosoftOnline(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[Query('code')] string $code = null, 
        #[Query('state')] string $state = null, 
        #[Query('session_state')] string $sessionState = null,
    ) : ResponseInterface 
    {
        if ($code == null || $state == null || $sessionState == null) {
            return $this->redirectToMain();
        }
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->microsoftOnline->buildAuthUrl($request, $params = ['redirect_uri' => 'https://yii3i.co.uk/callbackMicrosoftOnline']);
            header('Location: ' . $authorizationUrl);
            exit;
        } elseif ($code == 401){
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0 || strlen($sessionState) == 0) {
            /**
             * State is invalid, possible cross-site request forgery. Exit with an error code.
             */
            exit(1);        
        // code and state and stateSession are both present    
        } else {
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
                $login = 'ms'. substr((string)$microsoftOnlineId, strlen((string)$microsoftOnlineId) - 4, strlen((string)$microsoftOnlineId));
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail'.$login.'@microsoftonline.com';
                $password = Random::string(32);
                if ($this->authService->oauthLogin($login)) {
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null!==$userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null!==$userInv) {
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', $this->getTokenType('microsoftonline')) : '';
                                return $this->redirectToInvoiceIndex();
                            } else {
                                $this->disableToken($tR, $userId, $this->getTokenType('microsoftonline'));
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
                         * @see Trait\Oauth2 function getMicrosoftOnlineAccessToken
                         * @var string $_language
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
                            'proceedToMenuButton' => $proceedToMenuButton
                        ]);
                    }     
                }   
            }    
        }
        $this->authService->logout();
        return $this->redirectToMain();
    }
    
    public function callbackYandex(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        TranslatorInterface $translator,    
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[Query('code')] string $code = null, 
        #[Query('state')] string $state = null,        
    ) : ResponseInterface 
    {
        if ($code == null || $state == null) {
            return $this->redirectToMain();
        }
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->yandex->buildAuthUrl($request, []);
            header('Location: ' . $authorizationUrl);
            exit;
        } elseif ($code == 401){
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            exit(1);
        } else {
            $oAuthTokenType = $this->yandex->fetchAccessToken($request, $code, $params = []);
            $userArray = $this->yandex->applyAccessTokenToRequest($request, $oAuthTokenType);
            /**
             * @var int $userArray['id']
             */
            $xId = $userArray['id'] ?? 0;
            if ($xId > 0) { 
                $xLogin = 'yandex';
                if (strlen($xLogin) > 0) {
                    $login = 'yandex'.(string)$xId.$xLogin;
                    /**
                     * @var string $userArray['email']
                     */
                    $email = $userArray['email'] ?? 'noemail'.$login.'@yandex.com';
                    $password = Random::string(32);
                    // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                    // but it will be used later to build a passwordHash
                    if ($this->authService->oauthLogin($login)) {
                        $identity = $this->authService->getIdentity();
                        $userId = $identity->getId();
                        if (null!==$userId) {
                            $userInv = $uiR->repoUserInvUserIdquery($userId);
                            if (null!==$userInv) {
                                // disable the yandex verification token as soon as the user logs in for the first time
                                $status = $userInv->getActive();
                                if ($status || $userId == 1) {
                                    $userId == 1 ? $this->disableToken($tR, '1', $this->getTokenType('yandex')) : '';
                                    return $this->redirectToInvoiceIndex();
                                } else {
                                    $this->disableToken($tR, $userId, $this->getTokenType('yandex'));
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
                             * @see Trait\Oauth2 function getXAccessToken
                             * @var string $_language
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
            $proceedToMenuButton = A::tag()
                // When the url is clicked by the user, return to userinv/$provider to activate the user and assign a client to the user
                // depending on whether 'Assign a client to user on signup' has been chosen under View ... Settings...General. The user will be able to
                // edit their userinv details on the client side as well as the client record.
                ->href($this->urlGenerator->generateAbsolute(
                    'userinv/signup',
                    [
                        '_language' => $_language, 
                        'language' => $language, 
                        'token' => $tokenWithMask, 
                        'tokenType' => $tokenType
                    ]
                ))
                ->addClass('btn btn-success')
                ->content($translator->translate('invoice.invoice.identity.provider.authentication.successful'))
                ->render();
            return $proceedToMenuButton;
        }
        return '';
    }
    
    private function getTokenType(string $provider) : string 
    {
        return $tokenType = match ($provider) {
            'email' => self::EMAIL_VERIFICATION_TOKEN,
            'facebook' => self::FACEBOOK_ACCESS_TOKEN,
            'github' => self::GITHUB_ACCESS_TOKEN,
            'google' => self::GOOGLE_ACCESS_TOKEN,
            'linkedin' => self::LINKEDIN_ACCESS_TOKEN,
            'microsoftonline' => self::MICROSOFTONLINE_ACCESS_TOKEN,
            'x' => self::X_ACCESS_TOKEN,
            'yandex' => self::YANDEX_ACCESS_TOKEN
        };
    }
    
    private function redirectToUserCancelledOauth2() : ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/usercancelledoauth2', ['_language' => 'en']);
    }
    
    private function redirectToOauth2CallbackResultUnAuthorised() : ResponseInterface
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
    
    private function redirectToAdminMustMakeActive() : ResponseInterface {
        return $this->webService->getRedirectResponse('site/adminmustmakeactive', ['_language' => 'en']);
    }
}
