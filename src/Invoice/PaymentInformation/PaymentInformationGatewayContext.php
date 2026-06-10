<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Client\ClientRepository as cR;

final class PaymentInformationGatewayContext
{
    public function __construct(
        public readonly string $client_chosen_gateway,
        public readonly string $url_key,
        public readonly float $balance,
        public readonly cR $cR,
        public readonly Inv $invoice,
        public readonly array $items_array,
        public readonly bool $disable_form,
        public readonly bool $is_overdue,
        public readonly string $payment_method_for_this_invoice,
        public readonly float $total,
    ) {
    }
}
