<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareVisit;

use App\Invoice\BaseController;
use App\Invoice\Client\ClientRepository as ClientR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Admin-visible log of homecare-cleaning QR scan attempts and their
 * outcomes — the public scan-result page (homecare_scan_result.php) is
 * deliberately generic/PII-free, so this is where staff can actually see
 * *why* a scan resulted in "not eligible" or "contact us" for a given
 * client, rather than having no visibility at all. See
 * docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md.
 */
final class HomeCareVisitController extends BaseController
{
    private const int RECENT_LIMIT = 200;

    protected string $controllerName = 'invoice/homecarevisit';

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
        private readonly HomeCareVisitRepositoryInterface $visitRepository,
        private readonly ClientR $clientR,
    ) {
        parent::__construct($webService, $userService, $translator,
                $webViewRenderer, $session, $sR, $flash);
    }

    public function index(): Response
    {
        $visits = [];
        foreach ($this->visitRepository->repoRecentquery(self::RECENT_LIMIT) as $visit) {
            $client = $this->clientR->repoClientqueryOrig($visit->reqClientId());
            $visits[] = [
                'visit' => $visit,
                'client_name' => $client?->getClientFullName() ?? ('#' . $visit->reqClientId()),
            ];
        }

        return $this->webViewRenderer->render('index', [
            'visits' => $visits,
            'alert' => $this->alert(),
        ]);
    }
}
