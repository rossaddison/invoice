<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

interface SmpResolverInterface
{
    /**
     * Resolve the AS4 endpoint for a Peppol participant and document type.
     *
     * @throws SmpLookupException when the participant is not registered,
     *                            the document type is not supported, or the
     *                            SMP response cannot be parsed.
     */
    public function resolve(string $participantId, string $documentTypeId): SmpEndpoint;

    /**
     * Return true when the participant has an AS4 endpoint registered for the
     * given document type; false on any lookup failure.
     */
    public function isRegistered(string $participantId, string $documentTypeId): bool;
}
