<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Widget\QuoteToolbar;

final class QuoteControllerUIDeps
{
    public function __construct(
        public readonly QuoteCustomFieldProcessor $quoteCustomFieldProcessor,
        public readonly QuoteToolbar $quoteToolbar,
    ) {
    }
}
