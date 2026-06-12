<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;

final class InvIndexListDeps
{
    public function __construct(
        public readonly InvRepository $invRepo,
        public readonly IRR $irR,
        public readonly ISLR $islR,
        public readonly CR $clientRepo,
        public readonly GR $groupRepo,
    ) {
    }
}
