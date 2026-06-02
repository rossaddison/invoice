<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit;

use App\Invoice\Helpers\Peppol\Ast\Assertion;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\Rule;
use App\Invoice\Helpers\Peppol\SchematronDocument;

/**
 * Generates a Scala 3 source file from a SchematronDocument.
 *
 * Output: one top-level def per assertion, all sharing the signature:
 *   def validateXXX(v: UblInvoiceVO): Seq[Violation]
 *
 * Functions whose rule context targets a sub-element (InvoiceLine, TaxTotal …)
 * receive the root UblInvoiceVO and iterate over the relevant collection
 * using .filter / .map, so the caller never needs to know the context topology.
 *
 * The emitted file is idiomatic Scala 3 (top-level defs, wildcard import `.*`).
 * It compiles unchanged on Scala 2.13+ when `.*` is replaced with `._`.
 */

/** @psalm-suppress UnusedClass */
final class ScalaRuleEmitter
{
    /** Maps a context sub-element name to the VO Seq property and inner Scala type. */
    private const CONTEXT_MAP = [
        'InvoiceLine'    => ['invoiceLines',    'UblInvoiceLineVO'],
        'CreditNoteLine' => ['invoiceLines',    'UblInvoiceLineVO'],
        'TaxTotal'       => ['taxTotals',       'UblTaxTotalVO'],
        'TaxSubtotal'    => ['taxSubtotals',    'UblTaxSubtotalVO'],
        'AllowanceCharge'=> ['allowanceCharges','UblAllowanceChargeVO'],
        'PaymentMeans'   => ['paymentMeans',    'UblPaymentMeansVO'],
    ];

    public function __construct(
        private readonly ScalaExpressionEmitter $expr,
    ) {}

    /**
     * Emit a complete Scala source file for all rules in $doc.
     *
     * @param string $packageName The Scala package for the generated file (e.g. 'peppol.rules').
     * @param string $voPackage   Import path for VO types (e.g. 'peppol.vo').
     */
    public function emitFile(SchematronDocument $doc, string $packageName = 'peppol.rules', string $voPackage = 'peppol.vo'): string
    {
        $blocks = [
            $this->fileHeader($packageName, $voPackage),
            ...$this->emitRules($doc->rules),
        ];

        return implode("\n\n", array_filter($blocks));
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function fileHeader(string $packageName, string $voPackage): string
    {
        return <<<SCALA
            package {$packageName}

            import {$voPackage}.*
            import peppol.{CodeList, CodeLists}
            SCALA;
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
     * Returns ['property', 'ScalaType'] for sub-element contexts, or null for root invoice.
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
        $message  = str_replace(["\\", '"'], ["\\\\", '\\"'], $assertion->message);
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
        return <<<SCALA
            /** {$id}: {$assertion->message} */
            def {$fnName}(v: UblInvoiceVO): Seq[Violation] =
              if ({$test})
                Seq.empty
              else
                Seq(Violation("{$id}", {$severity}, "{$message}"))
            SCALA;
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
        return <<<SCALA
            /** {$id}: {$assertion->message} — iterates over {$innerType} */
            def {$fnName}(invoice: UblInvoiceVO): Seq[Violation] =
              invoice.{$property}
                .filter((v: {$innerType}) => {$failTest})
                .map(_ => Violation("{$id}", {$severity}, "{$message}"))
            SCALA;
    }

    private function toFunctionName(string $ruleId): string
    {
        return 'validate' . str_replace(['-', '.'], '', $ruleId);
    }
}
