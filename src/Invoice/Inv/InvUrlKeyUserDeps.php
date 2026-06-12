<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;

final class InvUrlKeyUserDeps
{
    public function __construct(
        public readonly UR $uR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly PMR $pmR,
        public readonly UPR $upR,
        public readonly ACIIR $aciiR,
    ) {
    }
}
