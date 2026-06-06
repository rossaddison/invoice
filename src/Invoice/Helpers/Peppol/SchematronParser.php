<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Ast\Assertion;
use App\Invoice\Helpers\Peppol\Ast\Expression;
use App\Invoice\Helpers\Peppol\Ast\Rule;
use App\Invoice\Helpers\Peppol\Exception\SchematronParseException;
use App\Invoice\Helpers\Peppol\Exception\XPathParseException;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses a PEPPOL BIS Billing 3.0 Schematron XML file into Rule objects.
 *
 * Input:  a Schematron .sch file (ISO Schematron, http://purl.oclc.org/dsdl/schematron)
 * Output: SchematronDocument — schema-level variable bindings + list of Rule objects
 *
 * Each Rule contains the XPath context pattern and a list of Assertion objects.
 * Each Assertion carries the rule id, the human-readable message, and the test
 * expression parsed into the Expression AST via XPathParser.
 *
 * Supported Schematron elements:
 *   <sch:schema>        — root element
 *   <sch:ns>            — namespace prefix declarations (ignored at parse time;
 *                         consumed by the evaluator via DOMXPath::registerNamespace)
 *   <sch:variable>      — schema-level XPath variables (select attribute)
 *   <sch:pattern>       — groups rules (no structural significance here)
 *   <sch:rule>          — context attribute becomes Rule::$context
 *   <sch:assert>        — a violation fires when test evaluates to FALSE
 *   <sch:report>        — a violation fires when test evaluates to TRUE (inverse)
 *
 * Note on <sch:report>: the Peppol Schematron uses <assert> exclusively.
 * <report> is included for completeness; the AST models it by negating the test.
 */

/**
 * @psalm-suppress UnusedClass
 */
final class SchematronParser
{
    // Namespace URI is an opaque identifier mandated by the ISO Schematron spec — must not be changed to https.
    private const SCH_NS = 'http://purl.oclc.org/dsdl/schematron'; // NOSONAR

    private XPathParser $xpathParser;

    public function __construct()
    {
        $this->xpathParser = new XPathParser();
    }

    /**
     * Parse a Schematron file by path.
     *
     * @throws SchematronParseException on XML load failure or malformed assertions.
     */
    public function parseFile(string $path): SchematronDocument
    {
        if (!is_file($path)) {
            throw new SchematronParseException("Schematron file not found: {$path}");
        }
        return $this->parseString((string) file_get_contents($path), $path);
    }

    /**
     * Parse a Schematron document from an XML string.
     *
     * @param string $xml    Raw XML content.
     * @param string $source Human-readable source label for error messages.
     * @throws SchematronParseException on XML parse failure.
     */
    public function parseString(string $xml, string $source = '<string>'): SchematronDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (!$loaded) {
            $msg = $errors !== [] ? $errors[0]->message : 'unknown error';
            throw new SchematronParseException("Failed to parse Schematron XML from {$source}: {$msg}");
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sch', self::SCH_NS);

        $namespaces = $this->collectNamespaces($xpath);
        $variables  = $this->collectVariables($xpath);
        $rules      = $this->collectRules($xpath, $source);

        return new SchematronDocument($namespaces, $variables, $rules);
    }

    // ── Namespace declarations ─────────────────────────────────────────────────

    /**
     * Collect <sch:ns> declarations as prefix → URI map.
     *
     * @return array<string, string>
     */
    private function collectNamespaces(DOMXPath $xpath): array
    {
        $namespaces = [];
        $nodes = $xpath->query('//sch:ns');
        if ($nodes === false) {
            return $namespaces;
        }
        foreach ($nodes as $node) {
            /** @var DOMElement $node */
            $prefix = $node->getAttribute('prefix');
            $uri    = $node->getAttribute('uri');
            if ($prefix !== '' && $uri !== '') {
                $namespaces[$prefix] = $uri;
            }
        }
        return $namespaces;
    }

    // ── Schema-level variables ─────────────────────────────────────────────────

    /**
     * Collect <sch:variable> and <sch:let> declarations at schema/pattern level.
     *
     * The Peppol BIS Billing 3.0 Schematron uses <sch:let name="x" value="expr"/>
     * while some Schematrons use <sch:variable name="x" select="expr"/>.
     * Both are equivalent; this method handles either attribute name.
     *
     * @return array<string, Expression>
     */
    private function collectVariables(DOMXPath $xpath): array
    {
        $variables = [];
        $nodes = $xpath->query('//sch:variable | //sch:let');
        if ($nodes === false) {
            return $variables;
        }
        foreach ($nodes as $node) {
            /** @var DOMElement $node */
            $name   = $node->getAttribute('name');
            // <sch:variable> uses @select; <sch:let> uses @value
            $select = $node->getAttribute('select');
            if ($select === '') {
                $select = $node->getAttribute('value');
            }
            if ($name === '' || $select === '') {
                continue;
            }
            try {
                $variables[$name] = $this->xpathParser->parse($select);
            } catch (XPathParseException $e) {
                throw new SchematronParseException(
                    "Failed to parse variable '\${$name}' select expression: {$select}\n  → {$e->getMessage()}"
                );
            }
        }
        return $variables;
    }

    // ── Rules and assertions ───────────────────────────────────────────────────

    /**
     * Collect all <sch:rule> elements and their child assertions.
     *
     * @return Rule[]
     */
    private function collectRules(DOMXPath $xpath, string $source): array
    {
        $rules     = [];
        $ruleNodes = $xpath->query('//sch:rule');
        if ($ruleNodes === false) {
            return $rules;
        }

        foreach ($ruleNodes as $ruleNode) {
            /** @var DOMElement $ruleNode */
            $context    = $ruleNode->getAttribute('context');
            $assertions = $this->collectAssertions($ruleNode, $xpath, $source);
            if ($assertions !== []) {
                $rules[] = new Rule($context, $assertions);
            }
        }

        return $rules;
    }

    /**
     * Collect <sch:assert> and <sch:report> children of a rule element.
     *
     * @return Assertion[]
     */
    private function collectAssertions(DOMElement $ruleNode, DOMXPath $xpath, string $source): array
    {
        $assertions = [];

        // <sch:assert test="...">message</sch:assert>
        // Fires (violation) when test evaluates to FALSE.
        $assertNodes = $xpath->query('sch:assert', $ruleNode);
        if ($assertNodes !== false) {
            foreach ($assertNodes as $node) {
                /** @var DOMElement $node */
                $assertions[] = $this->buildAssertion($node, false, $source);
            }
        }

        // <sch:report test="...">message</sch:report>
        // Fires (violation) when test evaluates to TRUE.
        // Model by wrapping the test in Not so the evaluator's FALSE-fires-violation
        // convention is preserved.
        $reportNodes = $xpath->query('sch:report', $ruleNode);
        if ($reportNodes !== false) {
            foreach ($reportNodes as $node) {
                /** @var DOMElement $node */
                $assertions[] = $this->buildAssertion($node, true, $source);
            }
        }

        return $assertions;
    }

    /**
     * Build a single Assertion from an <sch:assert> or <sch:report> element.
     *
     * @param bool $negate True for <sch:report> — negate the test so that the
     *                     evaluator's "false = violation" contract still holds.
     */
    private function buildAssertion(DOMElement $node, bool $negate, string $source): Assertion
    {
        $id      = $node->getAttribute('id');
        $flag    = $node->getAttribute('flag');   // 'fatal' or 'warning'
        $test    = $node->getAttribute('test');
        $message = trim($node->textContent);

        if ($test === '') {
            throw new SchematronParseException(
                "Assertion '{$id}' in {$source} has an empty test attribute."
            );
        }

        try {
            $expr = $this->xpathParser->parse($test);
        } catch (XPathParseException $e) {
            throw new SchematronParseException(
                "Failed to parse test for assertion '{$id}' in {$source}:\n"
                . "  test: {$test}\n"
                . "  → {$e->getMessage()}"
            );
        }

        if ($negate) {
            $expr = new \App\Invoice\Helpers\Peppol\Ast\Not($expr);
        }

        return new Assertion($expr, $message, $id !== '' ? $id : $flag, $flag !== '' ? $flag : 'fatal');
    }
}
