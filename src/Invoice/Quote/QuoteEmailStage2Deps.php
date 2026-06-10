<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

final class QuoteEmailStage2Deps
{
    public function __construct(
        public readonly QuoteEmailCustomDeps $custom,
        public readonly QuoteEmailStage2CoreDeps $core,
        public readonly QuoteEmailStage2RelationDeps $relation,
    ) {
    }
}
