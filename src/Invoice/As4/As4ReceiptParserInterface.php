<?php

declare(strict_types=1);

namespace App\Invoice\As4;

interface As4ReceiptParserInterface
{
    public function parse(string $body, string $contentType = ''): As4ReceiptSignal|As4ErrorSignal|null;
}
