<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object holding the AS4 endpoint details resolved from an SMP lookup.
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4SmpEndpoint
{
    public function __construct(
        /** AS4 endpoint URL to POST the signed SOAP envelope to */
        public string $endpointUrl,
        /** Receiver's certificate in PEM format (for TLS/encryption validation) */
        public string $certificatePem,
        /** transportProfile attribute from the SMP Endpoint element */
        public string $transportProfile,
    ) {}
}
