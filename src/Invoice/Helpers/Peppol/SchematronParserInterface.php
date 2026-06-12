<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Exception\SchematronParseException;

interface SchematronParserInterface
{
    /** @throws SchematronParseException */
    public function parseFile(string $path): SchematronDocument;

    /** @throws SchematronParseException */
    public function parseString(string $xml, string $source = '<string>'): SchematronDocument;
}
