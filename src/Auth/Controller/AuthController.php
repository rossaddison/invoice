<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Form\LoginForm;
use App\Service\WebControllerService;
use App\Auth\TokenRepository;
use App\Invoice\UserInv\UserInvRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\Login\Cookie\CookieLogin;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class AuthController
{
    public const string EMAIL_VERIFICATION_TOKEN = 'email-verification';
    
    public function __construct(
        private readonly AuthService          $authService,
        private readonly WebControllerService $webService,
        private ViewRenderer                  $viewRenderer,
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('auth');
    }

    public function login(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        FormHydrator $formHydrator,
        CookieLogin $cookieLogin,
        // use the 'active' field of the extension table userinv to verify that the user has been made active through e.g. email verificaiton    
        UserInvRepository $uiR,
        TokenRepository $tR
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
        }

        return $this->viewRenderer->render('login', ['formModel' => $loginForm]);
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
