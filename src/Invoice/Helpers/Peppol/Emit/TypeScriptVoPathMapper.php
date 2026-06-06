<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit;

/**
 * Maps a raw XPath path string to a TypeScript property-access expression.
 *
 * Produces the same structural results as VoPathMapper but with TypeScript syntax:
 *   cbc:PascalCase  → v.camelCase
 *   cac:Singular    → v.camelCase           (optional: v?.camelCase)
 *   cac:Plural      → v.camelCases          (array)
 *   cac:P/cbc:X     → v.ps.map(_e => _e.x)
 *   @attrName       → v.attrCamelCase
 *   $x/cbc:Y        → x.camelCase(Y)        (quantifier variable)
 *   .               → v                     (self)
 */

/**
 * @psalm-suppress UnusedClass
 */
final class TypeScriptVoPathMapper
{
    private const PLURAL = [
        'InvoiceLine', 'CreditNoteLine', 'TaxTotal', 'TaxSubtotal',
        'AllowanceCharge', 'PaymentMeans', 'AdditionalDocumentReference',
        'BillingReference', 'ContractDocumentReference', 'Note',
        'PartyIdentification', 'PartyTaxScheme', 'PartyLegalEntity',
        'ClassifiedTaxCategory',
    ];

    /** @var array<string, string> */
    private const OVERRIDES = [
        'cac:CreditNoteLine'               => '.invoiceLines',
        'cac:AccountingSupplierParty'      => '.supplier',
        'cac:AccountingCustomerParty'      => '.customer',
        'cac:LegalMonetaryTotal'           => '.legalMonetaryTotal',
        'cac:TaxRepresentativeParty'       => '.taxRepresentativeParty',
        'cac:OrderReference/cbc:ID'        => '.orderReference?.id',
        'cac:TaxScheme/cbc:ID'             => '.taxSchemeId',
        'cac:PayerFinancialAccount/cbc:ID' => '.paymentMandatePayerAccountId',
    ];

    public function map(string $xpathExpr, string $contextVar = 'v'): string
    {
        $path = trim($xpathExpr);

        if ($path === '.' || $path === '') {
            return $contextVar;
        }

        return str_starts_with($path, '$')
            ? $this->mapVariablePath($path)
            : $this->mapAbsolutePath($path, $contextVar);
    }

    /**
     * True when the path resolves to an array property (0..* aggregate element).
     * Used by the expression emitter to choose between .length > 0 and != null.
     */
    public function isArrayPath(string $xpathExpr): bool
    {
        $path = trim(preg_replace('#^/{1,2}#', '', trim($xpathExpr)) ?? $xpathExpr);
        $steps = explode('/', $path);
        $last = end($steps);
        $colonPos = strpos($last, ':');
        $local    = $colonPos !== false ? substr($last, $colonPos + 1) : $last;
        return in_array($local, self::PLURAL, true);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function mapVariablePath(string $path): string
    {
        $slash = strpos($path, '/');
        if ($slash === false) {
            return ltrim($path, '$'); // bare $x → x
        }
        $var  = ltrim(substr($path, 0, $slash), '$');
        $rest = substr($path, $slash + 1);
        return $this->mapSteps(explode('/', $rest), $var);
    }

    private function mapAbsolutePath(string $path, string $contextVar): string
    {
        $path = preg_replace('#^/{1,2}(ubl:(Invoice|CreditNote)/)?#', '', ltrim($path, '/')) ?? $path;

        if (str_starts_with($path, '@')) {
            return $contextVar . '.' . $this->toCamel(substr($path, 1));
        }

        foreach (self::OVERRIDES as $pattern => $accessor) {
            if ($path === $pattern || str_ends_with($path, '/' . $pattern)) {
                return $contextVar . $accessor;
            }
        }

        return $this->mapSteps(explode('/', $path), $contextVar);
    }

    /**
     * @param string[] $steps
     */
    private function mapSteps(array $steps, string $base): string
    {
        if ($steps === []) {
            return $base;
        }

        $step = (string) preg_replace('/\[[^\]]*+\]/', '', array_shift($steps));

        return ($step === '' || $step === '.')
            ? $this->mapSteps($steps, $base)
            : $this->mapNextStep($step, $steps, $base);
    }

    /**
     * @param string[] $remainingSteps
     */
    private function mapNextStep(string $step, array $remainingSteps, string $base): string
    {
        if (str_starts_with($step, '@')) {
            return $base . '.' . $this->toCamel(substr($step, 1));
        }

        $colonPos = strpos($step, ':');
        $prefix   = $colonPos !== false ? substr($step, 0, $colonPos) : '';
        $local    = $colonPos !== false ? substr($step, $colonPos + 1) : $step;
        $camel    = $this->toCamel($local);

        if ($prefix === 'cac' && in_array($local, self::PLURAL, true)) {
            $arrayExpr = $base . '.' . $camel . 's';
            return $remainingSteps === []
                ? $arrayExpr
                : $arrayExpr . '.map(_e => ' . $this->mapSteps($remainingSteps, '_e') . ')';
        }

        $next = $prefix === 'cac' ? $base . '?.' . $camel : $base . '.' . $camel;
        return $this->mapSteps($remainingSteps, $next);
    }

    private function toCamel(string $local): string
    {
        $local = (string) preg_replace('/ID(s?)$/', 'Id$1', $local);
        return lcfirst($local);
    }
}
