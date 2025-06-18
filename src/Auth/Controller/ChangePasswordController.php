<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Identity;
use App\Auth\IdentityRepository;
use App\Auth\Form\ChangePasswordForm;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ChangePasswordController
{
    public function __construct(
        private Session $session,
        private Flash $flash,
        private Translator $translator,
        private CurrentUser $currentUser,
        private readonly WebControllerService $webService,
        private ViewRenderer $viewRenderer,
    ) {
        $this->currentUser = $currentUser;
        $this->session = $session;
        $this->flash = $flash;
        $this->translator = $translator;
        $this->viewRenderer = $viewRenderer->withControllerName('changepassword');
    }

    public function change(
        AuthService $authService,
        Identity $identity,
        IdentityRepository $identityRepository,
        ServerRequestInterface $request,
        FormHydrator $formHydrator,
        ChangePasswordForm $changePasswordForm
    ): ResponseInterface {
        if ($authService->isGuest()) {
            return $this->redirectToMain();
        }

        $identityId = $this->currentUser->getIdentity()->getId();
        if (null !== $identityId) {
            $identity = $identityRepository->findIdentity($identityId);
            if (null !== $identity) {
                /**
                 *  Identity and User are in a HasOne relationship so no null value
                 *  Get the username or emailaddress of the current user
                 *  @see src\User\User function getLogin()
                 */
                $login = $identity->getUser()?->getLogin();
                if ($request->getMethod() === Method::POST
                  && $formHydrator->populate($changePasswordForm, $request->getParsedBody())
                  && $changePasswordForm->change()
                ) {
                    // Identity implements CookieLoginIdentityInterface: ensure the regeneration of the cookie auth key by means of $authService->logout();
                    // @see vendor\yiisoft\user\src\Login\Cookie\CookieLoginIdentityInterface
                    // Specific note: "Make sure to invalidate earlier issued keys when you implement force user logout,
                    // PASSWORD CHANGE and other scenarios, that require forceful access revocation for old sessions.
                    // The authService logout function will regenerate the auth key here => overwriting any auth key
                    $authService->logout();
                    $this->flashMessage('success', $this->translator->translate('validator.password.change'));
                    return $this->redirectToMain();
                }
                return $this->viewRenderer->render('change', [
                    'formModel' => $changePasswordForm,
                    'login' => $login,
                    /**
                     * @see resources\rbac\items.php
                     * @see https://github.com/yiisoft/demo/pull/602
                     */
                    'changePasswordForAnyUser' => $this->currentUser->can('changePasswordForAnyUser'),
                ]);
            } // identity
        } // identityId
        return $this->redirectToMain();
    } // reset

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flashMessage(string $level, string $message): Flash|null
    {
        /**
         * @see Prevent empty messages from being added to the queue
         */
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }

    private function redirectToMain(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/index');
    }
}
