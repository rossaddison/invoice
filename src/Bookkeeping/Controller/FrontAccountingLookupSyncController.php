<?php

declare(strict_types=1);

namespace App\Bookkeeping\Controller;

use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupSyncService;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * The "Refresh FrontAccounting lists" button on the Online Bookkeeping
 * settings tab's FrontAccounting card: re-syncs FrontAccountingLookup's
 * cached tax groups, stock/service items, bank accounts and payment terms
 * from the configured FrontAccounting instance, then reports the outcome
 * as flash messages on the tab it came from. Mirrors
 * BookkeepingExportController's own shape.
 */
final class FrontAccountingLookupSyncController
{
    use FlashMessage;

    public function __construct(
        private readonly FrontAccountingLookupSyncService $syncService,
        private readonly Flash $flash,
        private readonly Translator $translator,
        private readonly WebControllerService $webService,
    ) {
    }

    public function sync(): Response
    {
        $summary = $this->syncService->sync();

        $this->flashMessage(
            $summary->messages === [] ? 'info' : 'warning',
            sprintf(
                $this->translator->translate('bookkeeping.frontaccounting.lookup.sync.summary'),
                $summary->syncedCount,
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
