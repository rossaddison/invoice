<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;

interface As4EnvelopeSignerInterface
{
    /** Returns a new signed DOMDocument. The original is not modified. */
    public function sign(DOMDocument $envelope): DOMDocument;
}
