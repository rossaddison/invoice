<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

final class QuoteEmailStage0Deps
{
    public function __construct(
        public readonly QuoteEmailCustomDeps $custom,
        public readonly QuoteEmailStage0EntityDeps $entity,
    ) {
    }
}
