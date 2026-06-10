<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;

final class PdfCreateContext
{
    public function __construct(
        public readonly ?string $password,
        public readonly ?iiaR $iiaR,
        public readonly ?InvAmount $inv_amount,
        public readonly bool $isInvoice,
        public readonly bool $zugferd_invoice,
        public readonly array $associated_files,
        public readonly ?object $quote_or_invoice,
    ) {
    }
}
