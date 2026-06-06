<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Invoice\Helpers\Peppol\Ast\ExpressionEvaluator;
use App\Invoice\Helpers\Peppol\Rule\Severity;
use App\Invoice\Helpers\Peppol\SchematronParser;
use App\Invoice\Helpers\Peppol\SchematronRuleRunner;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class SchematronRuleRunnerTest extends TestCase
{
    private const CBC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function runner(): SchematronRuleRunner
    {
        return new SchematronRuleRunner(new ExpressionEvaluator());
    }

    private function dom(string $innerXml): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->loadXML('<Invoice xmlns:cbc="' . self::CBC_NS . '">' . $innerXml . '</Invoice>');
        return $dom;
    }

    private function sch(string $body): string
    {
        return '<?xml version="1.0"?>'
            . '<schema xmlns="http://purl.oclc.org/dsdl/schematron">'
            . '<ns prefix="cbc" uri="' . self::CBC_NS . '"/>'
            . $body
            . '</schema>';
    }

    // ── No violations ─────────────────────────────────────────────────────────

    public function testNoViolationsWhenAssertPasses(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<pattern><rule context="//cbc:ID">'
            . '<assert id="R-TEST" flag="fatal" test=". != \'\'">ID must not be empty.</assert>'
            . '</rule></pattern>'
        ));

        $violations = $this->runner()->run($schDoc, $this->dom('<cbc:ID>INV-001</cbc:ID>'));
        self::assertSame([], $violations);
    }

    // ── Assert fires on false ─────────────────────────────────────────────────

    public function testViolationWhenAssertFails(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<pattern><rule context="//cbc:ProfileID">'
            . '<assert id="PEPPOL-EN16931-R001" flag="fatal"'
            . ' test="normalize-space(.) != \'\'">A business process MUST be provided.</assert>'
            . '</rule></pattern>'
        ));

        // Empty ProfileID — assertion test evaluates to false → violation fires.
        $violations = $this->runner()->run($schDoc, $this->dom('<cbc:ProfileID></cbc:ProfileID>'));
        self::assertCount(1, $violations);
        self::assertSame('PEPPOL-EN16931-R001', $violations[0]->ruleId);
        self::assertSame(Severity::Fatal, $violations[0]->severity);
        self::assertSame('A business process MUST be provided.', $violations[0]->message);
    }

    // ── Severity mapping ──────────────────────────────────────────────────────

    public function testWarningFlagProducesWarningSeverity(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<pattern><rule context="//cbc:Note">'
            . '<assert id="R-WARN" flag="warning" test="false()">Always warn.</assert>'
            . '</rule></pattern>'
        ));

        $violations = $this->runner()->run($schDoc, $this->dom('<cbc:Note>text</cbc:Note>'));
        self::assertCount(1, $violations);
        self::assertSame(Severity::Warning, $violations[0]->severity);
    }

    // ── Multiple context nodes ────────────────────────────────────────────────

    public function testViolationPerContextNode(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<pattern><rule context="//cbc:Note">'
            . '<assert id="R-NOTE" flag="fatal" test="string-length(.) &lt;= 5">Note too long.</assert>'
            . '</rule></pattern>'
        ));

        // Two notes: first is short (pass), second is long (fail).
        $dom = $this->dom('<cbc:Note>Hi</cbc:Note><cbc:Note>This note is too long</cbc:Note>');
        $violations = $this->runner()->run($schDoc, $dom);
        self::assertCount(1, $violations);
    }

    // ── Schema-level variable binding ─────────────────────────────────────────

    public function testSchemaVariableIsResolved(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<variable name="profile" select="//cbc:ProfileID"/>'
            . '<pattern><rule context="//cbc:ID">'
            . '<assert id="R-VAR" flag="fatal" test="$profile != \'\'">Profile must exist.</assert>'
            . '</rule></pattern>'
        ));

        // ProfileID is present — variable resolves to a non-empty node-set → pass.
        $dom = $this->dom('<cbc:ProfileID>urn:fdc:peppol</cbc:ProfileID><cbc:ID>X</cbc:ID>');
        self::assertSame([], $this->runner()->run($schDoc, $dom));
    }

    // ── normalize-space ───────────────────────────────────────────────────────

    public function testNormalizeSpaceCollapsesPasses(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<pattern><rule context="//cbc:ID">'
            . '<assert id="R-NS" flag="fatal" test="normalize-space(.) != \'\'">ID must not be blank.</assert>'
            . '</rule></pattern>'
        ));

        // Whitespace-only content normalises to '' → violation.
        $violations = $this->runner()->run($schDoc, $this->dom('<cbc:ID>   </cbc:ID>'));
        self::assertCount(1, $violations);
    }

    // ── string-length ─────────────────────────────────────────────────────────

    public function testStringLengthConstraintPasses(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<pattern><rule context="//cbc:ID">'
            . '<assert id="R-LEN" flag="fatal" test="string-length(.) &lt;= 10">ID max 10 chars.</assert>'
            . '</rule></pattern>'
        ));

        self::assertSame([], $this->runner()->run($schDoc, $this->dom('<cbc:ID>SHORT</cbc:ID>')));

        $violations = $this->runner()->run($schDoc, $this->dom('<cbc:ID>TOOLONGVALUE</cbc:ID>'));
        self::assertCount(1, $violations);
    }

    // ── Empty document ────────────────────────────────────────────────────────

    public function testNoContextNodesProducesNoViolations(): void
    {
        $schDoc = (new SchematronParser())->parseString($this->sch(
            '<pattern><rule context="//cbc:ProfileID">'
            . '<assert id="R001" flag="fatal" test=". != \'\'">Must exist.</assert>'
            . '</rule></pattern>'
        ));

        // No ProfileID element → context query returns nothing → no violations.
        self::assertSame([], $this->runner()->run($schDoc, $this->dom('')));
    }
}
