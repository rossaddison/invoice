<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Bilateral AS4 SMP resolver — returns a fixed, pre-configured endpoint.
 *
 * Use this instead of As4SmpResolver when no Peppol SMP/SML is involved:
 * bilateral testing between two known nodes (e.g. localhost ↔ yii3i.online).
 *
 * Configure via environment:
 *   AS4_PEER_ENDPOINT           = https://yii3i.online/as4/receive
 *   AS4_PEER_CERT_PATH          = /etc/as4/peer-cert.pem
 *   AS4_PEER_TRANSPORT_PROFILE  = bilateral-as4-test   (any string)
 */
final class StaticAs4SmpResolver implements As4SmpResolverInterface
{
    public function __construct(
        private readonly string $endpointUrl,
        private readonly string $certificatePem,
        private readonly string $transportProfile = 'bilateral-as4-test',
    ) {
    }

    #[\Override]
    public function resolve(As4SmpQuery $query): As4SmpEndpoint
    {
        return new As4SmpEndpoint(
            endpointUrl:      $this->endpointUrl,
            certificatePem:   $this->certificatePem,
            transportProfile: $this->transportProfile,
        );
    }
}
