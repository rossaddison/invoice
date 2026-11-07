<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\BaseController;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\Trait\Guest;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Data\Reader\DataReaderInterface as DRI;
use Yiisoft\Data\Reader\SortableDataInterface as SDI;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Test-only harness — mixes in the real Guest trait against a
 * minimal BaseController subclass, avoiding InvController's much larger
 * unrelated dependency graph. $factory matches InvController's own
 * private readonly DataResponseFactoryInterface property exactly, since
 * PHP traits resolve $this->factory against whatever the host class
 * declares, regardless of visibility.
 */
final class GuestOfflineTestHarness extends BaseController
{
    use Guest;

    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        Flash $flash,
        private readonly DataResponseFactoryInterface $factory,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    // Guest::open() (a different action from the two this harness actually
    // tests, guestOfflineData()/guestOffline()) calls these via $this->,
    // requiring App\Invoice\Inv\Trait\OptionsData's methods to resolve
    // against *some* declared shape — real signatures, never actually
    // invoked by guestOfflineData()/guestOffline() themselves. Declared
    // directly rather than `use OptionsData;`, which would pull in that
    // trait's other methods (editOptionsData() etc., needing a $dateHelper
    // property this harness deliberately doesn't have) too.

    /** @return array<string, string> */
    public function optionsDataUserClientsFilter(UCR $ucR, int $userId): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }

    /** @return array<string, string> */
    public function optionsDataInvNumberGuestFilter(SDI&DRI $invs): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }

    /** @return array<string, string> */
    public function optionsDataCreditInvNumberGuestFilter(SDI&DRI $invs, InvRepository $iR): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }

    /** @return array<string, string> */
    public function optionsDataStatusFilter(InvRepository $iR): array
    {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }

    // Guest::guestRunSheetPdf() (added in #1304, another action this
    // harness doesn't test) calls $this->renderRunSheetPdf() expecting
    // Trait\RunSheetPdf to be composed on the same class -- real on
    // InvController, not here. `use RunSheetPdf;` was considered instead
    // of this stub, but that trait's own public runSheetPdf() method
    // calls $this->indexHomeCareRunContext() (Trait\Index, the exact
    // "much larger unrelated dependency graph" this harness is built to
    // avoid per its own docblock above), so it would trade one missing-
    // method Psalm error for another. Same real-signature-stub approach
    // as the optionsData* methods above.
    /** @return Response|\Mpdf\Mpdf|array<array-key, mixed>|string */
    public function renderRunSheetPdf(
        iterable $invs,
        \App\Invoice\Dwelling\DwellingRepository $dwR,
        \App\Invoice\Family\FamilyRepository $fR,
        \App\Invoice\Inv\RunSheetPdfRowBuilder $rowBuilder,
        \App\Invoice\InvWorkerAllocation\AllocationSidesSummary $_sides,
    ): Response|\Mpdf\Mpdf|array|string {
        throw new \LogicException('Not exercised by GuestOfflineTest.');
    }
}
