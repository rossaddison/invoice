<?php

declare(strict_types=1);

namespace App\Bookkeeping\Controller;

use App\Bookkeeping\Application\BookkeepingService;
use App\Invoice\Inv\InvLedgerSyncService;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * The "Export now" button on the Online Bookkeeping settings tab: does what
 * `php yii bookkeeping/export` does (sync the ledger from invoice state,
 * then export whatever is queued), then reports the outcome as flash
 * messages on the tab it came from.
 */
final class BookkeepingExportController
{
    use FlashMessage;

    public function __construct(
        private readonly BookkeepingService $bookkeepingService,
        private readonly Flash $flash,
        private readonly InvLedgerSyncService $ledgerSyncService,
        private readonly Translator $translator,
        private readonly WebControllerService $webService,
    ) {
    }

    public function export(): Response
    {
        $this->ledgerSyncService->sync();
        $summary = $this->bookkeepingService->exportDueTransactions();

        $this->flashMessage(
            $summary->failedCount === 0 ? 'info' : 'warning',
            sprintf(
                $this->translator->translate('bookkeeping.export.summary'),
                $summary->exportedCount,
                $summary->failedCount,
            ),
        );
        foreach ($summary->messages as $message) {
            $this->flashMessage('warning', $message);
        }

        return $this->webService->getRedirectResponse(
            'setting/tabIndex',
            [],
            ['active' => 'online-bookkeeping'],
        );
    }
}
