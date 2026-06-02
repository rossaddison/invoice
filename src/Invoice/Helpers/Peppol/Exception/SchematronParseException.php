<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

/**
 * Thrown when a Schematron XML file cannot be loaded or contains a
 * structurally invalid assertion (missing test attribute, etc.).
 */
final class SchematronParseException extends \RuntimeException {}
