<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareDemo;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\HomeCareCrew;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvWorkerAllocation\AllocationSendGate;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\UserInv\UserRbacLinkRepository;
use App\Invoice\Worker\WorkerRepository;
use App\User\UserRepository;
use Cycle\Database\DatabaseManager;
use Yiisoft\Rbac\Manager;

/**
 * Collaborators of HomeCareDemoSeeder, bundled so its constructor stays within
 * SonarQube S107 (the same approach as Peppol\Console\SeedFixtureDeps).
 */
final class HomeCareSeedDeps
{
    public function __construct(
        public readonly UserRepository $uR,
        public readonly UserInvRepository $uiR,
        public readonly UserRbacLinkRepository $urlR,
        public readonly Manager $manager,
        public readonly WorkerRepository $wR,
        public readonly FamilyRepository $fR,
        public readonly DwellingRepository $dwR,
        public readonly ClientRepository $cR,
        public readonly UserClientRepository $ucR,
        public readonly InvRepository $iR,
        public readonly InvItemRepository $iiR,
        public readonly InvItemAmountRepository $iiaR,
        public readonly InvAmountRepository $iaR,
        public readonly GroupRepository $gR,
        public readonly InvWorkerAllocationService $allocations,
        public readonly AllocationSendGate $gate,
        public readonly SettingRepository $sR,
        public readonly HomeCareCrew $crew,
        public readonly DatabaseManager $databases,
    ) {
    }
}
