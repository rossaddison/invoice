<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Helpers\Peppol\Emit;

use App\Invoice\Helpers\Peppol\Ast\Assertion;
use App\Invoice\Helpers\Peppol\Ast\Exists;
use App\Invoice\Helpers\Peppol\Ast\Path;
use App\Invoice\Helpers\Peppol\Ast\Rule;
use App\Invoice\Helpers\Peppol\Emit\ScalaExpressionEmitter;
use App\Invoice\Helpers\Peppol\Emit\ScalaRuleEmitter;
use App\Invoice\Helpers\Peppol\Emit\ScalaVoPathMapper;
use App\Invoice\Helpers\Peppol\SchematronDocument;
use PHPUnit\Framework\TestCase;

class ScalaRuleEmitterTest extends TestCase
{
    private ScalaRuleEmitter $emitter;

    #[\Override]
    protected function setUp(): void
    {
        $this->emitter = new ScalaRuleEmitter(
            new ScalaExpressionEmitter(new ScalaVoPathMapper()),
        );
    }

    private function makeDoc(array $rules): SchematronDocument
    {
        return new SchematronDocument(namespaces: [], variables: [], rules: $rules);
    }

    private function rootAssertion(
        string $id = 'BR-01',
        string $message = 'Invoice ID is required',
        string $flag = 'fatal',
    ): Assertion {
        return new Assertion(
            test:    new Exists(new Path('cbc:ID')),
            message: $message,
            id:      $id,
            flag:    $flag,
        );
    }

    // ── File header ────────────────────────────────────────────────────────────

    public function testFileHeaderContainsPackageName(): void
    {
        $output = $this->emitter->emitFile($this->makeDoc([]));
        $this->assertStringContainsString('package peppol.rules', $output);
    }

    public function testFileHeaderContainsVoImport(): void
    {
        $output = $this->emitter->emitFile($this->makeDoc([]));
        $this->assertStringContainsString('import peppol.vo.*', $output);
    }

    public function testFileHeaderContainsCodeListImport(): void
    {
        $output = $this->emitter->emitFile($this->makeDoc([]));
        $this->assertStringContainsString('import peppol.{CodeList, CodeLists}', $output);
    }

    public function testCustomPackageAndVoPackage(): void
    {
        $output = $this->emitter->emitFile($this->makeDoc([]), 'my.pkg', 'my.vo');
        $this->assertStringContainsString('package my.pkg', $output);
        $this->assertStringContainsString('import my.vo.*', $output);
    }

    public function testEmptyDocProducesOnlyHeader(): void
    {
        $output = $this->emitter->emitFile($this->makeDoc([]));
        $this->assertStringNotContainsString('def validate', $output);
    }

    // ── Function name sanitisation ─────────────────────────────────────────────

    public function testFunctionNameStripsHyphens(): void
    {
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$this->rootAssertion('BR-01')])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('def validateBR01(', $output);
    }

    public function testFunctionNameStripsDots(): void
    {
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$this->rootAssertion('BR.CO.15')])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('def validateBRCO15(', $output);
    }

    // ── Root function (invoice-level context) ─────────────────────────────────

    public function testRootFunctionSignature(): void
    {
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$this->rootAssertion('BR-01')])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('def validateBR01(v: UblInvoiceVO): Seq[Violation]', $output);
    }

    public function testRootFunctionEmitsIfElseBody(): void
    {
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$this->rootAssertion('BR-01')])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('Seq.empty', $output);
        $this->assertStringContainsString('Seq(Violation("BR-01",', $output);
    }

    public function testRootFunctionContainsDocComment(): void
    {
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$this->rootAssertion('BR-01', 'Invoice ID is required')])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('/** BR-01: Invoice ID is required */', $output);
    }

    // ── Severity mapping ──────────────────────────────────────────────────────

    public function testFatalFlagProducesSeverityFatal(): void
    {
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$this->rootAssertion('BR-01', 'msg', 'fatal')])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('Severity.Fatal', $output);
    }

    public function testWarningFlagProducesSeverityWarning(): void
    {
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$this->rootAssertion('PEPPOL-01', 'msg', 'warning')])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('Severity.Warning', $output);
    }

    // ── Collection function (sub-element context) ─────────────────────────────

    public function testInvoiceLineContextProducesCollectionFunction(): void
    {
        $doc = $this->makeDoc([
            new Rule(
                '//cac:InvoiceLine',
                [$this->rootAssertion('BR-21', 'InvoiceLine ID is required')],
            ),
        ]);
        $output = $this->emitter->emitFile($doc);

        $this->assertStringContainsString('def validateBR21(invoice: UblInvoiceVO)', $output);
        $this->assertStringContainsString('invoice.invoiceLines', $output);
        $this->assertStringContainsString('.filter((v: UblInvoiceLineVO)', $output);
        $this->assertStringContainsString('.map(_ => Violation("BR-21",', $output);
    }

    public function testCollectionFunctionIteratesOverUblInvoiceLineVO(): void
    {
        $doc = $this->makeDoc([
            new Rule('//cac:InvoiceLine', [$this->rootAssertion('BR-21')]),
        ]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('iterates over UblInvoiceLineVO', $output);
    }

    public function testTaxTotalContextProducesTaxTotalsProperty(): void
    {
        $doc = $this->makeDoc([
            new Rule('//cac:TaxTotal', [$this->rootAssertion('BR-45', 'TaxTotal required')]),
        ]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('invoice.taxTotals', $output);
        $this->assertStringContainsString('UblTaxTotalVO', $output);
    }

    public function testCollectionFunctionNegatesTestForFilter(): void
    {
        // filter receives !(test) — lines that FAIL the assertion
        $doc = $this->makeDoc([
            new Rule('//cac:InvoiceLine', [$this->rootAssertion('BR-21')]),
        ]);
        $output = $this->emitter->emitFile($doc);
        // The filter predicate is Not(Exists(Path('cbc:ID'))) → !((...) != null && ...)
        $this->assertStringContainsString('!((v.id)', $output);
    }

    // ── Multiple assertions ────────────────────────────────────────────────────

    public function testMultipleAssertionsInSameRuleGenerateMultipleFunctions(): void
    {
        $rule = new Rule('//ubl:Invoice', [
            $this->rootAssertion('BR-01', 'ID required'),
            $this->rootAssertion('BR-02', 'Issue date required'),
        ]);
        $output = $this->emitter->emitFile($this->makeDoc([$rule]));

        $this->assertStringContainsString('def validateBR01(', $output);
        $this->assertStringContainsString('def validateBR02(', $output);
    }

    public function testMultipleRulesGenerateAllFunctions(): void
    {
        $doc = $this->makeDoc([
            new Rule('//ubl:Invoice', [$this->rootAssertion('BR-01')]),
            new Rule('//cac:InvoiceLine', [$this->rootAssertion('BR-21')]),
        ]);
        $output = $this->emitter->emitFile($doc);

        $this->assertStringContainsString('def validateBR01(v: UblInvoiceVO)', $output);
        $this->assertStringContainsString('def validateBR21(invoice: UblInvoiceVO)', $output);
    }

    // ── Message escaping ──────────────────────────────────────────────────────

    public function testDoubleQuotesInMessageAreEscaped(): void
    {
        $assertion = new Assertion(
            test:    new Exists(new Path('cbc:ID')),
            message: 'Value must be "true"',
            id:      'BR-99',
        );
        $doc    = $this->makeDoc([new Rule('//ubl:Invoice', [$assertion])]);
        $output = $this->emitter->emitFile($doc);
        $this->assertStringContainsString('Value must be \\"true\\"', $output);
    }
}
