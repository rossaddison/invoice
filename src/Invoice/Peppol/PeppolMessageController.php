<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Invoice\BaseController;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class PeppolMessageController extends BaseController
{
    protected string $controllerName = 'invoice/peppol/messages';

    public function __construct(
        private PeppolMessageRepository $peppolMessageRepository,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
    }

    public function index(): Response
    {
        return $this->webViewRenderer->render('index', [
            'messages' => $this->peppolMessageRepository->findAllPreloaded(),
        ]);
    }

    public function view(CurrentRoute $currentRoute): Response
    {
        $id = (int) $currentRoute->getArgument('id');
        $message = $this->peppolMessageRepository->repoFind($id);
        if ($message === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $this->webViewRenderer->render('view', [
            'message' => $message,
        ]);
    }
}
