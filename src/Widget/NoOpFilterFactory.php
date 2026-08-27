<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Yii\DataView\Filter\Factory\FilterFactoryInterface;

/**
 * Neutralizes GridView's automatic ORM-level filtering for a DataColumn
 * whose `property` is a virtual name (e.g. `filterClient`, `filterStatus`) —
 * not a real column on the entity the grid is reading. Several of this
 * app's grids (Inv, Quote, SalesOrder, ...) name their filterable columns
 * this way; the actual filtering is done by hand in each domain's own
 * repository, entirely independent of GridView's own filter machinery.
 *
 * Without this, enabling ->urlParameterProvider() on a GridView (needed so
 * a filter dropdown correctly redisplays its own selected value — see
 * project_inv_index_filter_combining_fix memory) makes
 * DataColumnRenderer::makeFilter() start resolving a non-null query value
 * for these columns too, and it would otherwise hand that off to
 * EqualsFilterFactory/LikeFilterFactory, producing a Cycle `Equals`/`Like`
 * filter keyed on the fake property name — a
 * `PDOException: Unknown column 'inv.filterClient'` the moment GridView
 * tries to apply it to the query (confirmed by an earlier, abandoned attempt
 * at this same fix on inv/index). Returning `All` instead — Yiisoft\Data\
 * Cycle's own `AllHandler` compiles it to a literal `WHERE 1 = 1` — makes
 * GridView's side of filtering a complete no-op for these columns while
 * leaving `renderFilter()` (and therefore the dropdown's redisplayed value)
 * working normally, since that path never goes through this factory at all.
 */
final class NoOpFilterFactory implements FilterFactoryInterface
{
    #[\Override]
    public function create(string $property, string $value): FilterInterface
    {
        return new All();
    }
}
