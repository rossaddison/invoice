<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

final class InvItemAmountData
{
    public function __construct(
        public readonly int $inv_item_id,
        public readonly float $quantity,
        public readonly float $price,
        public readonly float $discount,
        public readonly float $charge,
        public readonly float $allowance,
        public readonly float $tax_rate_percentage,
    ) {
    }
}
