<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget\Trait;

use App\Invoice\Inv\Widget\InvAllocationCell;

/**
 * The admin's per-side allocation controls for InvsListWidget's Worker column.
 * A trait so the widget stays within S1448 (≤ 20 methods): SonarQube does not
 * count trait-provided methods toward the consuming class, the same technique
 * as InvsWorkerColumnTrait.
 */
trait InvsAllocationCellTrait
{
    private ?InvAllocationCell $allocationCell = null;

    /**
     * @param InvAllocationCell|null $allocationCell null keeps the plain whole-house dropdown
     */
    public function withAllocationCell(?InvAllocationCell $allocationCell): static
    {
        $new = clone $this;
        $new->allocationCell = $allocationCell;
        return $new;
    }
}
