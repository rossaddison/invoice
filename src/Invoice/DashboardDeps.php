<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\Project\ProjectRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\Task\TaskRepository;

final class DashboardDeps
{
    public function __construct(
        public readonly ClientRepository $cR,
        public readonly InvRepository $iR,
        public readonly InvAmountRepository $iaR,
        public readonly InvRecurringRepository $irR,
        public readonly QuoteRepository $qR,
        public readonly QuoteAmountRepository $qaR,
        public readonly TaskRepository $taskR,
        public readonly ProjectRepository $prjctR,
    ) {
    }
}
