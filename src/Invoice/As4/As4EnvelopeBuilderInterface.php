<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;

interface As4EnvelopeBuilderInterface
{
    public function build(SoapEnvelopeParams $params): DOMDocument;
}
