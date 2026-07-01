<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
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

final class As4MessageController extends BaseController
{
    protected string $controllerName = 'invoice/as4';

    public function __construct(
        private CycleOrmAs4MessageRepository $as4MessageRepository,
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
            'messages' => $this->as4MessageRepository->findAllMessages(),
        ]);
    }

    public function view(CurrentRoute $currentRoute): Response
    {
        $id = (int) $currentRoute->getArgument('id');
        $message = $this->as4MessageRepository->repoFind($id);
        if ($message === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $this->webViewRenderer->render('view', [
            'message' => $message,
        ]);
    }
}
