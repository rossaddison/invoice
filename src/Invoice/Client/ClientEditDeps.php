<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\ClientCustom\ClientCustomRepository as ccR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;

final class ClientEditDeps
{
    public function __construct(
        public readonly ClientRepository $cR,
        public readonly ccR $ccR,
        public readonly cfR $cfR,
        public readonly cvR $cvR,
        public readonly paR $paR,
    ) {
    }
}
