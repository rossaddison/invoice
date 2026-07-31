<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget\Trait;

/**
 * Backs the HomeCare "hidden columns" Settings checkboxes
 * (homecare_hidden_inv_columns) — extracted from InvsColumnBuilder to stay
 * within S1448 (≤ 20 methods), same technique as the other column traits.
 *
 * Deliberately a no-op when HomeCare is off: the general/desktop grid for
 * non-HomeCare businesses must never be affected by this setting.
 */
trait InvsColumnVisibilityTrait
{
    /**
     * @param list<string> $hiddenKeys
     */
    private function isColumnHidden(string $key, bool $homeCareEnabled, array $hiddenKeys): bool
    {
        return $homeCareEnabled && in_array($key, $hiddenKeys, true);
    }
}
