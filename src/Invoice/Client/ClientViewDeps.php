<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\ClientCustom\ClientCustomRepository as ccR;
use App\Invoice\ClientNote\ClientNoteRepository as cnR;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as delR;
use App\Invoice\Group\GroupRepository as gR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\Payment\PaymentRepository as pymtR;
use App\Invoice\Quote\QuoteRepository as qR;
use App\Invoice\UserClient\UserClientRepository as ucR;

final class ClientViewDeps
{
    public function __construct(
        public readonly ClientRepository $cR,
        public readonly ccR $ccR,
        public readonly cfR $cfR,
        public readonly cnR $cnR,
        public readonly cpR $cpR,
        public readonly cvR $cvR,
        public readonly delR $delR,
        public readonly gR $gR,
        public readonly iR $iR,
        public readonly pymtR $pymtR,
        public readonly qR $qR,
        public readonly ucR $ucR,
    ) {
    }
}
