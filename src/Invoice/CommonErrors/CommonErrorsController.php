<?php

declare(strict_types=1);

namespace App\Invoice\CommonErrors;

use App\Invoice\BaseController;
use App\Invoice\Helpers\FormViewConsistencyChecker;
use App\Invoice\Setting\SettingRepository as SR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CommonErrorsController extends BaseController
{
    protected string $controllerName = 'invoice/commonerrors';

    public function __construct(
        private readonly Aliases $aliases,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct(
            $webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash
        );
    }

    public function index(): Response
    {
        if ($this->sR->getSetting('debug_mode') !== '1') {
            return $this->webService->getNotFoundResponse();
        }

        $srcDir   = $this->aliases->get('@root') . '/src/Invoice';
        $viewsDir = $this->aliases->get('@root') . '/resources/views';

        $checker = new FormViewConsistencyChecker($srcDir, $viewsDir);
        $issues  = $checker->check();

        return $this->webViewRenderer->render('index', [
            'alert'            => $this->alert(),
            'issues'           => $issues,
            'formattedIssues'  => $checker->formatIssues($issues),
            'issueCount'       => count($issues),
        ]);
    }
}
