<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Form\LoginForm;
use App\Auth\TokenRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\Login\Cookie\CookieLogin;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;

final class AuthController
{
    public const string EMAIL_VERIFICATION_TOKEN = 'email-verification';
    
    public function __construct(
        private readonly AuthService          $authService,
        private readonly WebControllerService $webService,
        private ViewRenderer                  $viewRenderer,
        private SettingRepository             $sR,
        private Facebook $facebook,
        private GitHub $github,
        private Google $google,  
        private UrlGenerator $urlGenerator,    
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('auth');
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
        return $this->viewRenderer->render('login', 
            [
                'formModel' => $loginForm,
                'facebookAuthUrl' => strlen($this->facebook->getClientId()) > 0 ? $this->facebook->buildAuthUrl($request, $params = []) : '',
                'githubAuthUrl' => strlen($this->github->getClientId()) > 0 ? $this->github->buildAuthUrl($request, $params = []) : '',   
                'googleAuthUrl' => strlen($this->google->getClientId()) > 0 ? $this->google->buildAuthUrl($request, $params = []) : ''
            ]);
    }
    
    private function initializeOauth2IdentityProviderCredentials(Facebook $facebook, Github $github, Google $google) : void {
        $facebook->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('facebook'));
        $github->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('github'));
        $google->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('google'));
        
        $facebook->setClientId($this->sR->getOauth2IdentityProviderClientId('facebook'));
        $github->setClientId($this->sR->getOauth2IdentityProviderClientId('github'));
        $google->setClientId($this->sR->getOauth2IdentityProviderClientId('google'));
        
        $facebook->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('facebook'));
        $github->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('github'));
        $google->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('google'));
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
    
    /**
     * https://yii3i.co.uk/callbackGithub?code=b9f7562bc6b1d214f3c4&state=a15ed92d3f9fc43f7f3bbe46126607ad0a0aba8e1b1a71f56af0695891adafa2
     * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
     * @param string $code
     * @param string $state
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function callbackGithub(
            #[Query('code')] string $code, 
            #[Query('state')] string $state,
            ServerRequestInterface $request
        ) : ResponseInterface {
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
            return $this->redirectToGithubCallbackResult();
        }
    }
    
    private function redirectToGithubCallbackResultUnAuthorised() : ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/githubcallbackresultunauthorised', ['_language' => 'en']);
    }
    
    private function redirectToGithubCallbackResult() : ResponseInterface 
    {
        return $this->webService->getRedirectResponse('site/githubcallbackresult', ['_language' => 'en']);
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
