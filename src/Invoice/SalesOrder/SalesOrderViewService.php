<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

final readonly class SalesOrderViewService
{
    public function __construct(
        public readonly SoViewCoreDeps $core,
        public readonly SoViewItemDeps $items,
        public readonly SoViewMetaDeps $meta,
        public readonly SoViewRelationDeps $relation,
    ) {
    }
}
