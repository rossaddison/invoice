<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use DOMDocument;

interface SchematronRunnerInterface
{
    /** @return array<int, ValidationViolation> */
    public function run(SchematronDocument $doc, DOMDocument $document): array;
}
