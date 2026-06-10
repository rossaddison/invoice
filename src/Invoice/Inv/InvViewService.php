<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

final readonly class InvViewService
{
    public function __construct(
        public readonly InvViewCoreDeps $core,
        public readonly InvViewItemDeps $items,
        public readonly InvViewMetaDeps $meta,
        public readonly InvViewAllowanceDeps $allowance,
        public readonly InvViewRelationDeps $relation,
    ) {
    }
}
