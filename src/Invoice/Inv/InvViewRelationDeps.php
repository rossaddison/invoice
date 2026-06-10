<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class InvViewRelationDeps
{
    public function __construct(
        public readonly CR $cR,
        public readonly DLR $dlR,
        public readonly SOR $soR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly UPR $upR,
    ) {
    }
}
