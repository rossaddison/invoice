<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\BaseController;
use App\Invoice\Inv\InvCopyDeps;
use App\Invoice\Inv\Trait\HomeCareScan;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Log\LoggerInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Test-only harness mixing the real HomeCareScan trait into a minimal BaseController
 * subclass (see HomeCareAllocationTestHarness for why not InvController itself).
 */
final class HomeCareScanTestHarness extends BaseController
{
    use HomeCareScan;

    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        Flash $flash,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    /**
     * The real one (MultipleCopy) builds an invoice; the scan tests only exercise the confirm
     * branch, so this stand-in reports "could not generate".
     */
    public function generateHomeCareCleaningInvoice(
        int $_clientId,
        Inv $_templateInvoice,
        InvCopyDeps $_d,
        FormHydrator $_formHydrator,
    ): int {
        return 0;
    }
}
