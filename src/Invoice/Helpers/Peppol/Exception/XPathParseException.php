<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

/**
 * Thrown when the XPathParser encounters a token sequence it cannot parse
 * into the Expression AST.
 */
final class XPathParseException extends \RuntimeException {}
