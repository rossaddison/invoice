<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Ubl\InvoicePeriod;
use DateTime;

readonly class PeppolInvoiceDates
{
    public function __construct(
        public DateTime $issueDate,
        public DateTime $dueDate,
        public DateTime $taxPointDate,
        public InvoicePeriod $invoicePeriod,
    ) {
    }
}
