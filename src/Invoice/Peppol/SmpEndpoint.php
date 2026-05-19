<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

final readonly class SmpEndpoint
{
    public function __construct(
        public string $endpointUrl,
        public string $certificate,
        public string $transportProfile,
    ) {}
}
