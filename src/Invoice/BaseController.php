<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Invoice\Traits\FlashMessage;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

abstract class BaseController
{
    use FlashMessage;

    // New property for controller name
    protected string $controllerName = 'base';

    public function __construct(
        protected WebControllerService $webService,
        protected UserService $userService,
        protected TranslatorInterface $translator,
        protected ViewRenderer $viewRenderer,
        protected SessionInterface $session,
        protected SettingRepository $sR,
        protected Flash $flash
    ) {
        $this->initializeViewRenderer();
    }

    /**
     * Initialize the view renderer based on user permissions.
     */
    protected function initializeViewRenderer(): void
    {
        if (!$this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $this->viewRenderer->withControllerName($this->controllerName)
                                                     ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                     ->withLayout('@views/layout/templates/soletrader/main.php');
        } elseif ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv') && !$this->userService->hasPermission('noEntryToBaseController') && $this->userService->hasPermission('entryToBaseController')) {
            $this->viewRenderer = $this->viewRenderer->withControllerName($this->controllerName)
                                                     ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                     ->withLayout('@views/layout/guest.php');
        } elseif ($this->userService->hasPermission('editInv') && !$this->userService->hasPermission('noEntryToBaseController') && $this->userService->hasPermission('entryToBaseController')) {
            $this->viewRenderer = $this->viewRenderer->withControllerName($this->controllerName)
                                                     ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                     ->withLayout('@views/layout/invoice.php');
        }
    }

    /**
      * Render a view with common parameters.
      *
      * @param string $view
      * @param array<string, mixed> $parameters
      * @return Response
      */
    protected function render(string $view, array $parameters = []): Response
    {
        return $this->viewRenderer->render($view, $parameters);
    }

    /**
     * Create a flash alert partial.
     *
     * @return string
     */
    protected function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash' => $this->flash,
            ]
        );
    }
}
