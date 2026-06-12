<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;

interface As4HttpTransportInterface
{
    /**
     * @param As4MimePart[] $attachments
     * @throws \UnexpectedValueException                  When the envelope cannot be serialized to XML
     * @throws \Psr\Http\Client\ClientExceptionInterface  On network/transport failure
     */
    public function send(
        string $endpointUrl,
        DOMDocument $signedEnvelope,
        array $attachments = [],
    ): As4HttpResponse;
}
