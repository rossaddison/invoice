<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Infrastructure\Persistence\Worker\Worker;

/**
 * What a logged-in inv/guest visitor is allowed to see: either a set of
 * client ids (the ordinary client-linked guest) or a single HomeCare Worker
 * (whose visible invoices are scoped by worker_id instead — see
 * InvRepository::repoWorkerVisible()).
 */
final readonly class InvGuestAccess
{
    public function __construct(
        public UserInv $userInv,
        public array $clients,
        public ?Worker $worker = null,
    ) {
    }
}
