<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\InvSentLog\InvSentLogRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\Setting\SettingRepository;

final readonly class InvsColumnParams
{
    public function __construct(
        public InvRepository $iR,
        public InvRecurringRepository $irR,
        public InvSentLogRepository $islR,
        public SettingRepository $sR,
        public int $dp,
        public float $totalAmount,
        public float $totalPaid,
        public float $totalBalance,
        public ?QuoteRepository $qR = null,
        public ?SalesOrderRepository $soR = null,
        public ?DeliveryLocationRepository $dlR = null,
    ) {}
}
