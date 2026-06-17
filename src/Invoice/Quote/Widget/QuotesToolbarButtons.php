<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Widget;

final readonly class QuotesToolbarButtons
{
    public function __construct(
        public string $toolbarReset,
        public string $allVisible,
        public string $enabledAddBtn,
        public string $disabledAddBtn,
        public string $changeStatusDropdown,
    ) {}
}
