<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit;

use App\Invoice\Helpers\Peppol\Ast\Assertion;
use App\Invoice\Helpers\Peppol\Ast\Rule;
use App\Invoice\Helpers\Peppol\SchematronDocument;

/**
 * Generates a PHP source file from a SchematronDocument.
 *
 * Output: one function per assertion, all sharing the signature:
 *   function validateXXX(UblInvoiceVO $invoice): Violation[]
 *
 * Functions whose rule context targets a sub-element (InvoiceLine, TaxTotal …)
 * receive the root UblInvoiceVO and iterate over the relevant collection
 * internally, so the caller never needs to know the context topology.
 */
/** @psalm-suppress UnusedClass */
final class PhpRuleEmitter
{
    /** Maps a context sub-element name to the VO array property and inner type. */
    private const CONTEXT_MAP = [
        'InvoiceLine'    => ['invoiceLines',    'UblInvoiceLineVO'],
        'CreditNoteLine' => ['invoiceLines',    'UblInvoiceLineVO'],
        'TaxTotal'       => ['taxTotals',       'UblTaxTotalVO'],
        'TaxSubtotal'    => ['taxSubtotals',    'UblTaxSubtotalVO'],
        'AllowanceCharge'=> ['allowanceCharges','UblAllowanceChargeVO'],
        'PaymentMeans'   => ['paymentMeans',    'UblPaymentMeansVO'],
    ];

    public function __construct(
        private readonly PhpExpressionEmitter $expr,
    ) {}

    /**
     * Emit a complete PHP source file for all rules in $doc.
     *
     * @param string $namespace The PHP namespace for the generated file.
     */
    public function emitFile(SchematronDocument $doc, string $namespace): string
    {
        $blocks = [
            $this->fileHeader($namespace),
            ...$this->emitRules($doc->rules),
        ];

        return implode("\n\n", array_filter($blocks));
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function fileHeader(string $namespace): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$namespace};

            use App\Invoice\Helpers\Peppol\Emit\Vo\UblInvoiceVO;
            use App\Invoice\Helpers\Peppol\Emit\Vo\UblInvoiceLineVO;
            use App\Invoice\Helpers\Peppol\Emit\Vo\UblTaxTotalVO;
            use App\Invoice\Helpers\Peppol\Emit\Vo\UblTaxSubtotalVO;
            use App\Invoice\Helpers\Peppol\Emit\Vo\UblAllowanceChargeVO;
            use App\Invoice\Helpers\Peppol\Emit\Vo\UblPaymentMeansVO;
            use App\Invoice\Helpers\Peppol\Emit\Vo\Violation;
            use App\Invoice\Helpers\Peppol\Rule\Severity;
            PHP;
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
     * Returns ['property', 'VoType'] for sub-element contexts, or null for root invoice.
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
        $severity = $assertion->flag === 'warning' ? 'Severity::Warning' : 'Severity::Fatal';
        $message  = addslashes($assertion->message);
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
        $test = $this->expr->emit($assertion->test, '$v');
        return <<<PHP
            /**
             * {$id}: {$assertion->message}
             */
            function {$fnName}(UblInvoiceVO \$v): array
            {
                return {$test}
                    ? []
                    : [new Violation('{$id}', {$severity}, '{$message}')];
            }
            PHP;
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
        // Emit the negation of the assertion test so the violation fires on failure.
        // Wrapping in Not avoids double-negation when the test already uses !empty / not().
        $failTest = $this->expr->emit(new \App\Invoice\Helpers\Peppol\Ast\Not($assertion->test), '$v');
        return <<<PHP
            /**
             * {$id}: {$assertion->message}
             */
            function {$fnName}(UblInvoiceVO \$invoice): array
            {
                \$violations = [];
                foreach (\$invoice->{$property} as \$v) {
                    /** @var {$innerType} \$v */
                    if ({$failTest}) {
                        \$violations[] = new Violation('{$id}', {$severity}, '{$message}');
                    }
                }
                return \$violations;
            }
            PHP;
    }

    private function toFunctionName(string $ruleId): string
    {
        return 'validate' . str_replace(['-', '.'], '', $ruleId);
    }
}
