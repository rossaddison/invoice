<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit;

use App\Invoice\Helpers\Peppol\Ast\Assertion;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\Rule;
use App\Invoice\Helpers\Peppol\SchematronDocument;

/**
 * Generates a TypeScript source file from a SchematronDocument.
 *
 * Output: one exported function per assertion, all sharing the signature:
 *   export function validateXXX(v: UblInvoiceVO): Violation[]
 *
 * Functions whose rule context targets a sub-element (InvoiceLine, TaxTotal …)
 * receive the root UblInvoiceVO and iterate over the relevant collection
 * internally, so the caller never needs to know the context topology.
 */

/** @psalm-suppress UnusedClass */
final class TypeScriptRuleEmitter
{
    /** Maps a context sub-element name to the VO array property and inner TS type. */
    private const CONTEXT_MAP = [
        'InvoiceLine'    => ['invoiceLines',    'UblInvoiceLineVO'],
        'CreditNoteLine' => ['invoiceLines',    'UblInvoiceLineVO'],
        'TaxTotal'       => ['taxTotals',       'UblTaxTotalVO'],
        'TaxSubtotal'    => ['taxSubtotals',    'UblTaxSubtotalVO'],
        'AllowanceCharge'=> ['allowanceCharges','UblAllowanceChargeVO'],
        'PaymentMeans'   => ['paymentMeans',    'UblPaymentMeansVO'],
    ];

    public function __construct(
        private readonly TypeScriptExpressionEmitter $expr,
    ) {}

    /**
     * Emit a complete TypeScript source file for all rules in $doc.
     *
     * @param string $modulePath Import path prefix for VO types (e.g. '../vo').
     */
    public function emitFile(SchematronDocument $doc, string $modulePath = '../vo'): string
    {
        $blocks = [
            $this->fileHeader($modulePath),
            ...$this->emitRules($doc->rules),
        ];

        return implode("\n\n", array_filter($blocks));
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function fileHeader(string $modulePath): string
    {
        return <<<TS
            import { UblInvoiceVO } from '{$modulePath}/UblInvoiceVO';
            import { UblInvoiceLineVO } from '{$modulePath}/UblInvoiceLineVO';
            import { UblTaxTotalVO } from '{$modulePath}/UblTaxTotalVO';
            import { UblTaxSubtotalVO } from '{$modulePath}/UblTaxSubtotalVO';
            import { UblAllowanceChargeVO } from '{$modulePath}/UblAllowanceChargeVO';
            import { UblPaymentMeansVO } from '{$modulePath}/UblPaymentMeansVO';
            import { Violation } from '{$modulePath}/Violation';
            import { Severity } from '{$modulePath}/Severity';
            import { CodeList } from '../CodeList';
            import { CodeLists } from '../CodeLists';
            TS;
    }

    /**
     * @param Rule[] $rules
     * @return string[]
     */
    private function emitRules(array $rules): array
    {
        $functions = [];
        foreach ($rules as $rule) {
            $context = $this->resolveContext($rule->context);
            foreach ($rule->assertions as $assertion) {
                $functions[] = $this->emitFunction($assertion, $context);
            }
        }
        return $functions;
    }

    /**
     * Determine the sub-element context (if any) from the Schematron rule context string.
     * Returns ['property', 'TsType'] for sub-element contexts, or null for root invoice.
     *
     * @return array{string, string}|null
     */
    private function resolveContext(string $context): ?array
    {
        foreach (self::CONTEXT_MAP as $localName => [$property, $type]) {
            if (str_contains($context, 'cac:' . $localName)) {
                return [$property, $type];
            }
        }
        return null;
    }

    /**
     * @param array{string, string}|null $context
     */
    private function emitFunction(Assertion $assertion, ?array $context): string
    {
        $fnName   = $this->toFunctionName($assertion->id);
        $severity = $assertion->flag === 'warning' ? 'Severity.Warning' : 'Severity.Fatal';
        $message  = str_replace(["\\", "'"], ["\\\\", "\\'"], $assertion->message);
        $id       = $assertion->id;

        if ($context === null) {
            return $this->emitRootFunction($fnName, $id, $severity, $message, $assertion);
        }

        [$property, $innerType] = $context;
        return $this->emitCollectionFunction($fnName, $id, $severity, $message, $assertion, $property, $innerType);
    }

    private function emitRootFunction(
        string    $fnName,
        string    $id,
        string    $severity,
        string    $message,
        Assertion $assertion
    ): string {
        $test = $this->expr->emit($assertion->test, 'v');
        return <<<TS
            /**
             * {$id}: {$assertion->message}
             */
            export function {$fnName}(v: UblInvoiceVO): Violation[] {
              return {$test}
                ? []
                : [{ id: '{$id}', severity: {$severity}, message: '{$message}' }];
            }
            TS;
    }

    private function emitCollectionFunction(
        string    $fnName,
        string    $id,
        string    $severity,
        string    $message,
        Assertion $assertion,
        string    $property,
        string    $innerType
    ): string {
        $failTest = $this->expr->emit(new Not($assertion->test), 'v');
        return <<<TS
            /**
             * {$id}: {$assertion->message}
             */
            export function {$fnName}(invoice: UblInvoiceVO): Violation[] {
              const violations: Violation[] = [];
              for (const v of invoice.{$property} as {$innerType}[]) {
                if ({$failTest}) {
                  violations.push({ id: '{$id}', severity: {$severity}, message: '{$message}' });
                }
              }
              return violations;
            }
            TS;
    }

    private function toFunctionName(string $ruleId): string
    {
        return 'validate' . str_replace(['-', '.'], '', $ruleId);
    }
}
