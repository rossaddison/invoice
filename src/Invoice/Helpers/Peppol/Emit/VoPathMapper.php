<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit;

/**
 * Maps a raw XPath path string (from a Path AST node) to a PHP property-access
 * expression against a typed VO variable.
 *
 * Conventions:
 *   cbc:PascalCase   → $var->camelCase          (scalar field)
 *   cac:Singular     → $var->camelCase           (nullable sub-object)
 *   cac:Plural       → $var->camelCases          (array property, pluralised)
 *   cac:P/cbc:X      → array_map(fn($_e)=>$_e->x, $var->ps)
 *   @attrName        → $var->attrCamelCase
 *   $var/cbc:X       → $var->camelCase           (quantifier variable path)
 *   .                → $var                      (self-reference)
 */
final class VoPathMapper
{
    /** cac: local names that are 0..* (array) in UBL BIS Billing. */
    private const PLURAL = [
        'InvoiceLine', 'CreditNoteLine', 'TaxTotal', 'TaxSubtotal',
        'AllowanceCharge', 'PaymentMeans', 'AdditionalDocumentReference',
        'BillingReference', 'ContractDocumentReference', 'Note',
        'PartyIdentification', 'PartyTaxScheme', 'PartyLegalEntity',
        'ClassifiedTaxCategory',
    ];

    /**
     * Explicit overrides for paths that do not follow the automatic convention,
     * or that merge two UBL element names onto one VO property.
     *
     * @var array<string, string>
     */
    private const OVERRIDES = [
        'cac:CreditNoteLine'               => '->invoiceLines',
        'cac:AccountingSupplierParty'      => '->supplier',
        'cac:AccountingCustomerParty'      => '->customer',
        'cac:LegalMonetaryTotal'           => '->legalMonetaryTotal',
        'cac:TaxRepresentativeParty'       => '->taxRepresentativeParty',
        'cac:OrderReference/cbc:ID'        => '->orderReference?->id',
        'cac:TaxScheme/cbc:ID'             => '->taxSchemeId',
        'cac:PayerFinancialAccount/cbc:ID' => '->paymentMandatePayerAccountId',
    ];

    public function map(string $xpathExpr, string $contextVar = '$v'): string
    {
        $path = trim($xpathExpr);

        if ($path === '.' || $path === '') {
            return $contextVar;
        }

        return str_starts_with($path, '$')
            ? $this->mapVariablePath($path)
            : $this->mapAbsolutePath($path, $contextVar);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function mapVariablePath(string $path): string
    {
        $slash = strpos($path, '/');

        return $slash === false
            ? $path
            : $this->mapSteps(explode('/', substr($path, $slash + 1)), substr($path, 0, $slash));
    }

    private function mapAbsolutePath(string $path, string $contextVar): string
    {
        $path = preg_replace('#^/{1,2}(ubl:(Invoice|CreditNote)/)?#', '', ltrim($path, '/')) ?? $path;

        if (str_starts_with($path, '@')) {
            return $contextVar . '->' . $this->toCamel(substr($path, 1));
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
            return $base . '->' . $this->toCamel(substr($step, 1));
        }

        $colonPos = strpos($step, ':');
        $prefix   = $colonPos !== false ? substr($step, 0, $colonPos) : '';
        $local    = $colonPos !== false ? substr($step, $colonPos + 1) : $step;
        $camel    = $this->toCamel($local);

        if ($prefix === 'cac' && in_array($local, self::PLURAL, true)) {
            $arrayExpr = $base . '->' . $camel . 's';
            return $remainingSteps === []
                ? $arrayExpr
                : 'array_map(fn($_e) => ' . $this->mapSteps($remainingSteps, '$_e') . ', ' . $arrayExpr . ')';
        }

        $next = $prefix === 'cac' ? $base . '?->' . $camel : $base . '->' . $camel;
        return $this->mapSteps($remainingSteps, $next);
    }

    private function toCamel(string $local): string
    {
        $local = (string) preg_replace('/ID(s?)$/', 'Id$1', $local);
        return lcfirst($local);
    }
}
