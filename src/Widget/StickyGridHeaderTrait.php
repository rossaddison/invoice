<?php

declare(strict_types=1);

namespace App\Widget;

/**
 * Shared by every *ListWidget that supports the 'grid_sticky_header'
 * setting via its own standalone withStickyHeader() setter -- Quote/
 * SalesOrder/Product today. InvsListWidget deliberately does NOT use
 * this trait: it's already at its own 20-method S1448 ceiling (see
 * that class's own docblock), so its equivalent is folded into
 * withGridDisplayOptions() instead of a dedicated setter -- using this
 * trait there would add a 21st public method (`use` brings in every
 * trait method as if declared directly on the class) and break that
 * same limit right back.
 *
 * Extracted after CI's real SonarCloud run flagged the three identical
 * copies of this setter (property + docblock + method, verbatim) as
 * duplicated code (new_duplicated_lines_density over its 3% gate) --
 * deduplicating the actual code, not just adding more tests, is the
 * durable fix for that specific condition.
 */
trait StickyGridHeaderTrait
{
    private bool $stickyHeader = false;

    /**
     * The shared 'grid_sticky_header' setting -- see
     * SettingToggleController::gridStickyHeader()'s docblock for why
     * this is one setting for every grid, not one per grid, and
     * src/Invoice/Asset/invoice/css/overrides.css for the CSS rule this
     * class enables by name. Deliberately just the property + this one
     * setter, no companion "build the class string" helper method here
     * -- SalesOrdersListWidget is already exactly at its own 20-method
     * S1448 ceiling (see that class's own docblock), so `use`-ing this
     * trait must add exactly the one public method it's replacing, net
     * zero change in method count, not one more on top. Each render()
     * still inlines `($this->stickyHeader ? ' sticky-grid-header' : '')`
     * itself -- a one-line expression, not the actual duplication this
     * trait exists to remove (that was the property+docblock+method
     * triplet, copy-pasted verbatim across three widget classes).
     */
    public function withStickyHeader(bool $stickyHeader): static
    {
        $new = clone $this;
        $new->stickyHeader = $stickyHeader;
        return $new;
    }
}
