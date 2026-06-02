<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule;

/**
 * Severity level for a Peppol / EN16931 validation violation.
 *
 * Mirrors the Schematron assertion levels used in the official Peppol rule set:
 *   Fatal   → [assert] — the document MUST be rejected; the rule is mandatory.
 *   Warning → [report] — the document SHOULD be corrected; the rule is a recommendation.
 *   Info    → advisory messages introduced by national CIUS or PINT profiles that
 *             carry guidance rather than a hard requirement.
 *
 * PeppolValidator maps Fatal violations to its $errors array and Warning/Info
 * violations to its $warnings array, preserving backward compatibility with callers
 * that use getErrors() / getWarnings().
 */
enum Severity
{
    case Fatal;
    case Warning;
    case Info;
}
