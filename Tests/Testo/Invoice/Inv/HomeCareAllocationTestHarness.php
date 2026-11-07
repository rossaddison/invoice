<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\BaseController;
use App\Invoice\Inv\Trait\HomeCareAllocation;

/**
 * Test-only harness mixing the real HomeCareAllocation trait into a minimal
 * BaseController subclass (see GuestOfflineTestHarness for why not
 * InvController itself).
 */
final class HomeCareAllocationTestHarness extends BaseController
{
    use HomeCareAllocation;

    public \App\Invoice\Inv\AllocationCellResponder $allocationCells;
}
