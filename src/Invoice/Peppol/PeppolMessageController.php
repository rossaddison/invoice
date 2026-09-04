<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

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

/**
 * Browsable, filterable log of every PeppolMessage — the record
 * StorecovePeppolSendService/OxalisPeppolSendService write on every send,
 * previously visible only via a one-time flash message on inv/view and
 * otherwise invisible without querying the DB directly. Views and routes
 * for this already existed (resources/views/invoice/peppol/messages/*,
 * routes-peppol-message.php) — this controller was the one missing piece
 * wiring them to PeppolMessageRepository.
 *
 * Kept as a separate screen from /as4/messages rather than one unified
 * table: the two entities are genuinely different shapes (PeppolMessage —
 * Storecove/Oxalis outbound sends; As4Message — the self-hosted AS4
 * stack, both directions) and Cycle ORM's EntityReader is per-entity, so
 * a true merge would need a hand-rolled cross-entity reader rather than
 * reusing either repository's existing one directly.
 */
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
        // index.php's plain foreach works directly. See
        // As4MessageController::index()'s comment for why this isn't
        // wrapped in an OffsetPaginator (not itself iterable — a direct
        // foreach over one throws); real pagination is a known follow-up.
        return $this->webViewRenderer->render('index', [
            'messages' => $this->peppolMessageRepository->filterCombined($request->getQueryParams()),
            'queryParams' => $request->getQueryParams(),
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
