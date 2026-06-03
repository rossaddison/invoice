<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Invoice\Helpers\Peppol\Ast\Assertion;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\Rule;
use App\Invoice\Helpers\Peppol\Exception\SchematronParseException;
use App\Invoice\Helpers\Peppol\SchematronDocument;
use App\Invoice\Helpers\Peppol\SchematronParser;
use PHPUnit\Framework\TestCase;

final class SchematronParserTest extends TestCase
{
    private SchematronParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SchematronParser();
    }

    private function sch(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<schema xmlns="http://purl.oclc.org/dsdl/schematron">'
            . $body
            . '</schema>';
    }

    // ── Empty document ────────────────────────────────────────────────────────

    public function testEmptySchematronParsesCleanly(): void
    {
        $doc = $this->parser->parseString($this->sch(''));
        self::assertInstanceOf(SchematronDocument::class, $doc);
        self::assertSame([], $doc->rules);
        self::assertSame([], $doc->namespaces);
        self::assertSame([], $doc->variables);
    }

    // ── Namespace declarations ────────────────────────────────────────────────

    public function testNamespacesAreCollected(): void
    {
        $xml = $this->sch(
            '<ns prefix="cbc" uri="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"/>'
            . '<ns prefix="cac" uri="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"/>'
        );
        $doc = $this->parser->parseString($xml);
        self::assertArrayHasKey('cbc', $doc->namespaces);
        self::assertArrayHasKey('cac', $doc->namespaces);
        self::assertStringContainsString('CommonBasicComponents', $doc->namespaces['cbc']);
    }

    // ── Schema-level variables ────────────────────────────────────────────────

    public function testVariablesAreParsed(): void
    {
        $xml = $this->sch(
            '<variable name="profile" select="//cbc:ProfileID"/>'
        );
        $doc = $this->parser->parseString($xml);
        self::assertArrayHasKey('profile', $doc->variables);
    }

    public function testVariableWithEmptyNameIsSkipped(): void
    {
        $xml = $this->sch('<variable name="" select="//cbc:ID"/>');
        $doc = $this->parser->parseString($xml);
        self::assertSame([], $doc->variables);
    }

    public function testLetElementIsParsedAsVariable(): void
    {
        // The Peppol BIS Billing 3.0 Schematron uses <let> not <variable>.
        // e.g.: <let name="documentCurrencyCode" value="/*/cbc:DocumentCurrencyCode"/>
        $xml = $this->sch('<let name="documentCurrencyCode" value="/*/cbc:DocumentCurrencyCode"/>');
        $doc = $this->parser->parseString($xml);
        self::assertArrayHasKey('documentCurrencyCode', $doc->variables);
    }

    public function testLetAndVariableCoexist(): void
    {
        $xml = $this->sch(
            '<variable name="fromSelect" select="//cbc:ID"/>'
            . '<let name="fromValue" value="//cbc:Note"/>'
        );
        $doc = $this->parser->parseString($xml);
        self::assertArrayHasKey('fromSelect', $doc->variables);
        self::assertArrayHasKey('fromValue',  $doc->variables);
    }

    // ── Rules and assertions ──────────────────────────────────────────────────

    public function testSingleAssertIsParsed(): void
    {
        $xml = $this->sch(
            '<pattern>'
            . '<rule context="//ubl:Invoice">'
            . '<assert id="PEPPOL-EN16931-R001" flag="fatal" test="cbc:ProfileID">A business process MUST be provided.</assert>'
            . '</rule>'
            . '</pattern>'
        );
        $doc = $this->parser->parseString($xml);
        self::assertCount(1, $doc->rules);
        /** @var Rule $rule */
        $rule = $doc->rules[0];
        self::assertSame('//ubl:Invoice', $rule->context);
        self::assertCount(1, $rule->assertions);
        /** @var Assertion $a */
        $a = $rule->assertions[0];
        self::assertSame('PEPPOL-EN16931-R001', $a->id);
        self::assertSame('fatal', $a->flag);
        self::assertSame('A business process MUST be provided.', $a->message);
    }

    public function testReportIsNegatedIntoAssert(): void
    {
        $xml = $this->sch(
            '<pattern>'
            . '<rule context="//ubl:Invoice">'
            . '<report id="R-REPORT" flag="warning" test="cbc:Note">Do not include a note.</report>'
            . '</rule>'
            . '</pattern>'
        );
        $doc = $this->parser->parseString($xml);
        self::assertCount(1, $doc->rules);
        $a = $doc->rules[0]->assertions[0];
        // <report> fires on TRUE, so the AST wraps the expression in Not.
        self::assertInstanceOf(Not::class, $a->test);
    }

    public function testRuleWithNoAssertionsIsOmitted(): void
    {
        $xml = $this->sch(
            '<pattern><rule context="//ubl:Invoice"></rule></pattern>'
        );
        $doc = $this->parser->parseString($xml);
        self::assertSame([], $doc->rules);
    }

    public function testMultipleAssertionsInOneRule(): void
    {
        $xml = $this->sch(
            '<pattern>'
            . '<rule context="//ubl:Invoice">'
            . '<assert id="R001" flag="fatal" test="cbc:A">A</assert>'
            . '<assert id="R002" flag="fatal" test="cbc:B">B</assert>'
            . '</rule>'
            . '</pattern>'
        );
        $doc = $this->parser->parseString($xml);
        self::assertCount(2, $doc->rules[0]->assertions);
    }

    public function testAssertWithEmptyTestThrows(): void
    {
        $xml = $this->sch(
            '<pattern>'
            . '<rule context="//ubl:Invoice">'
            . '<assert id="R001" flag="fatal" test="">Bad</assert>'
            . '</rule>'
            . '</pattern>'
        );
        $this->expectException(SchematronParseException::class);
        $this->parser->parseString($xml);
    }

    // ── Error handling ────────────────────────────────────────────────────────

    public function testInvalidXmlThrows(): void
    {
        $this->expectException(SchematronParseException::class);
        $this->parser->parseString('<not valid xml <<');
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(SchematronParseException::class);
        $this->parser->parseFile('/non/existent/file.sch');
    }
}
