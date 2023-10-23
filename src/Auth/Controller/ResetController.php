<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Identity;
use App\Auth\IdentityRepository;
use App\Auth\Form\ResetForm;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\ViewRenderer;

final class ResetController
{
    public function __construct(
      private Session $session,
      private Flash $flash,
      private Translator $translator,
      private CurrentUser $current_user,
      private WebControllerService $webService, 
      private ViewRenderer $viewRenderer,
    )
    {
      $this->current_user = $current_user;
      $this->session = $session;      
      $this->flash = new Flash($session);
      $this->translator = $translator;
      $this->viewRenderer = $viewRenderer->withControllerName('reset');
    }
    
    public function reset(
      AuthService $authService,
      Identity $identity,
      IdentityRepository $identityRepository,
      ServerRequestInterface $request,
      FormHydrator $formHydrator,
      ResetForm $resetForm
    ): ResponseInterface {
      // permit an authenticated user, ie. not a guest, only and null!== current user
      if (!$authService->isGuest()) {
        if ($this->current_user->can('viewInv',[])) {
          // readonly the login detail on the reset form
          $identity_id = $this->current_user->getIdentity()->getId();
          if (null!==$identity_id) {
            $identity = $identityRepository->findIdentity($identity_id);
            if (null!==$identity) {
              // Identity and User are in a HasOne relationship so no null value
              $login = $identity->getUser()?->getLogin();
              if ($request->getMethod() === Method::POST
                && $formHydrator->populate($resetForm, $request->getParsedBody())
                && $resetForm->reset() 
              ) {
                // Identity implements CookieLoginIdentityInterface: ensure the regeneration of the cookie auth key by means of $authService->logout();
                // @see vendor\yiisoft\user\src\Login\Cookie\CookieLoginIdentityInterface 

                // Specific note: "Make sure to invalidate earlier issued keys when you implement force user logout,
                // PASSWORD CHANGE and other scenarios, that require forceful access revocation for old sessions.
                // The authService logout function will regenerate the auth key here => overwriting any auth key
                $authService->logout();
                $this->flash_message('success', $this->translator->translate('validator.password.reset'));
                return $this->redirectToMain();
              }
              return $this->viewRenderer->render('reset', ['formModel' => $resetForm, 'login' => $login]);
            } // identity
          } // identity_id 
        } // current user
      } // auth service  
      return $this->redirectToMain();
    } // reset
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }
    
    private function redirectToMain(): ResponseInterface
    {
      return $this->webService->getRedirectResponse('site/index');
    }
}
