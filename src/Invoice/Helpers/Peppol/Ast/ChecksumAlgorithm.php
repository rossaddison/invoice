<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * The user-defined (u:) checksum functions from the Peppol BIS Billing 3.0 Schematron.
 *
 * Each case maps to a Schematron <function> definition that validates a specific
 * national or international identifier format.  The string value is the Schematron
 * function name, e.g. u:gln(...).
 *
 * When evaluating a Checksum node in PHP, dispatch on this enum to call the
 * matching implementation in PeppolValidator (checkGLN, checkMod11, etc.).
 */
enum ChecksumAlgorithm: string
{
    /** GS1 Global Location Number check digit (schemeID 0088). */
    case GLN          = 'u:gln';

    /** Norwegian / Danish mod-11 check digit (schemeID 0192, 0184). */
    case Mod11        = 'u:mod11';

    /** Belgian enterprise number mod-97 (schemeID 0208). */
    case Mod97BE      = 'u:mod97-0208';

    /** Swedish organisation number Luhn-variant (schemeID 0007). */
    case SEOrgnr      = 'u:checkSEOrgnr';

    /** Australian Business Number weighted sum (schemeID 0151). */
    case ABN          = 'u:abn';

    /** Italian codice fiscale (schemeID 0210 / 9907). */
    case CodiceFiscale = 'u:checkCF';

    /** Italian VAT / PIVA check digit (schemeID 0211). */
    case PIVAseIT     = 'u:checkPIVAseIT';

    /** Italian IPA code format (schemeID 0201). */
    case CodiceIPA    = 'u:checkCodiceIPA';

    /** Danish CVR number format (schemeID 9902). */
    case DanishCVR    = 'u:checkDanishCVR';

    /** Numeric tolerance / slack — not a check digit but a u: helper. */
    case Slack        = 'u:slack';

    /** Generic TIN (Tax Identification Number) verification. */
    case TINVerification = 'u:TinVerification';
}
