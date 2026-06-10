<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

final class InvEmailStage2Deps
{
    public function __construct(
        public readonly InvEmailCoreDeps $core,
        public readonly InvEmailCustomDeps $custom,
        public readonly InvEmailRelationDeps $relation,
    ) {
    }
}
