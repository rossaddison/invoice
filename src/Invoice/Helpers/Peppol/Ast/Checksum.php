<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A call to one of the Schematron u:* user-defined checksum/validation functions.
 *
 * The Schematron defines these as <xsl:function> elements; this AST node names the
 * algorithm via ChecksumAlgorithm so an evaluator can dispatch to the matching PHP
 * implementation (checkGLN, checkMod11, checkABN, etc. in PeppolValidator).
 *
 * Example Schematron expressions:
 *   u:gln(normalize-space(.))
 *   u:mod11(normalize-space(.))
 *   u:mod97-0208(normalize-space(.))
 */
readonly class Checksum implements Expression
{
    public function __construct(
        public ChecksumAlgorithm $algorithm,
        public Expression        $value,
    ) {}
}
