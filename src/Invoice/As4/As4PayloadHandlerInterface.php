<?php

declare(strict_types=1);

namespace App\Invoice\As4;

interface As4PayloadHandlerInterface
{
    public function handle(string $payloadXml, string $senderPartyId, string $action): void;
}
