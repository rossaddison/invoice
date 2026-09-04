<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Invoice\BaseController;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        parent::__construct(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash
        );
    }

    public function index(Request $request): Response
    {
        // filterCombined() returns an EntityReader — IteratorAggregate, so
        // index.php's plain foreach works directly. Deliberately not
        // wrapped in an OffsetPaginator: that only implements
        // ReadableDataInterface (read()), not IteratorAggregate, so a
        // direct foreach over one throws — the view would need rewriting
        // to call ->read() and to render real pagination controls to use
        // one correctly. Left as a known follow-up; this pass only adds
        // the search filtering the Peppol Messages screen redesign
        // reused this same repository shape for.
        return $this->webViewRenderer->render('index', [
            'messages' => $this->as4MessageRepository->filterCombined($request->getQueryParams()),
            'queryParams' => $request->getQueryParams(),
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
