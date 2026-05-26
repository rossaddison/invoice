<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\User\UserRepository;

/**
 * Groups the four repositories needed by the cron action to resolve users and
 * send Telegram reminders. Injected as a single DI parameter to keep the cron
 * action-method param count within S107 limits.
 */
final class InvCronUserDeps
{
    public function __construct(
        public readonly InvAmountRepository $iaR,
        public readonly UserClientRepository $uclR,
        public readonly UserInvRepository $uiR,
        public readonly UserRepository $userRepository,
    ) {}
}
