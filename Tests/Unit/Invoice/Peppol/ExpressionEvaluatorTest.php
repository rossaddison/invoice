<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Invoice\Helpers\Peppol\Ast\BinaryExpression;
use App\Invoice\Helpers\Peppol\Ast\BinaryOperator;
use App\Invoice\Helpers\Peppol\Ast\Checksum;
use App\Invoice\Helpers\Peppol\Ast\ChecksumAlgorithm;
use App\Invoice\Helpers\Peppol\Ast\Contains;
use App\Invoice\Helpers\Peppol\Ast\Count;
use App\Invoice\Helpers\Peppol\Ast\Every;
use App\Invoice\Helpers\Peppol\Ast\Exists;
use App\Invoice\Helpers\Peppol\Ast\ExpressionEvaluator;
use App\Invoice\Helpers\Peppol\Ast\IfThenElse;
use App\Invoice\Helpers\Peppol\Ast\Literal;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\NotExists;
use App\Invoice\Helpers\Peppol\Ast\Path;
use App\Invoice\Helpers\Peppol\Ast\Round;
use App\Invoice\Helpers\Peppol\Ast\Some;
use App\Invoice\Helpers\Peppol\Ast\CastableAs;
use App\Invoice\Helpers\Peppol\Ast\ForExpression;
use App\Invoice\Helpers\Peppol\Ast\Sequence;
use App\Invoice\Helpers\Peppol\Ast\NormalizeSpace;
use App\Invoice\Helpers\Peppol\Ast\StartsWith;
use App\Invoice\Helpers\Peppol\Ast\StringLength;
use App\Invoice\Helpers\Peppol\Ast\Sum;
use App\Invoice\Helpers\Peppol\Ast\UpperCase;
use App\Invoice\Helpers\Peppol\Ast\VariableRef;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExpressionEvaluatorTest extends TestCase
{
    private const CBC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    private const CAC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    private ExpressionEvaluator $ev;

    protected function setUp(): void
    {
        $this->ev = new ExpressionEvaluator();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('cbc', self::CBC_NS);
        $xp->registerNamespace('cac', self::CAC_NS);
        return $xp;
    }

    private function invoiceXml(string $innerXml = ''): string
    {
        return '<Invoice xmlns:cbc="' . self::CBC_NS . '" '
            . 'xmlns:cac="' . self::CAC_NS . '">'
            . $innerXml
            . '</Invoice>';
    }

    // ── Literal ───────────────────────────────────────────────────────────────

    public function testLiteralString(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame('hello', $this->ev->evaluate(new Literal('hello'), $xp));
    }

    public function testLiteralInt(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame(42, $this->ev->evaluate(new Literal(42), $xp));
    }

    public function testLiteralBool(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluate(new Literal(true), $xp));
        self::assertFalse($this->ev->evaluate(new Literal(false), $xp));
    }

    // ── Path ─────────────────────────────────────────────────────────────────

    public function testPathReturnsNodeList(): void
    {
        $xp = $this->xpath($this->invoiceXml('<cbc:ID>INV-001</cbc:ID>'));
        $result = $this->ev->evaluate(new Path('//cbc:ID'), $xp);
        self::assertInstanceOf(\DOMNodeList::class, $result);
        self::assertSame(1, $result->length);
    }

    // ── Exists / NotExists ────────────────────────────────────────────────────

    public function testExistsTrueWhenNodePresent(): void
    {
        $xp = $this->xpath($this->invoiceXml('<cbc:ID>X</cbc:ID>'));
        self::assertTrue($this->ev->evaluateBool(new Exists(new Path('//cbc:ID')), $xp));
    }

    public function testExistsFalseWhenNodeAbsent(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertFalse($this->ev->evaluateBool(new Exists(new Path('//cbc:ID')), $xp));
    }

    public function testNotExistsTrueWhenNodeAbsent(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluateBool(new NotExists(new Path('//cbc:ID')), $xp));
    }

    // ── Not ───────────────────────────────────────────────────────────────────

    public function testNotNegatesBoolean(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluateBool(new Not(new Literal(false)), $xp));
        self::assertFalse($this->ev->evaluateBool(new Not(new Literal(true)), $xp));
    }

    // ── BinaryExpression ─────────────────────────────────────────────────────

    public function testEqualityMatch(): void
    {
        $xp = $this->xpath($this->invoiceXml('<cbc:ID>VAT</cbc:ID>'));
        $expr = new BinaryExpression(BinaryOperator::EQ, new Path('//cbc:ID'), new Literal('VAT'));
        self::assertTrue($this->ev->evaluateBool($expr, $xp));
    }

    public function testEqualityNoMatch(): void
    {
        $xp = $this->xpath($this->invoiceXml('<cbc:ID>S</cbc:ID>'));
        $expr = new BinaryExpression(BinaryOperator::EQ, new Path('//cbc:ID'), new Literal('VAT'));
        self::assertFalse($this->ev->evaluateBool($expr, $xp));
    }

    public function testArithmeticAdd(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new BinaryExpression(BinaryOperator::ADD, new Literal(3), new Literal(4));
        self::assertSame(7.0, $this->ev->evaluate($expr, $xp));
    }

    public function testArithmeticDiv(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new BinaryExpression(BinaryOperator::DIV, new Literal(10), new Literal(4));
        self::assertSame(2.5, $this->ev->evaluate($expr, $xp));
    }

    public function testDivByZeroReturnsInf(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new BinaryExpression(BinaryOperator::DIV, new Literal(1), new Literal(0));
        self::assertInfinite($this->ev->evaluate($expr, $xp));
    }

    public function testZeroDivByZeroReturnsNan(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new BinaryExpression(BinaryOperator::DIV, new Literal(0), new Literal(0));
        self::assertNan($this->ev->evaluate($expr, $xp));
    }

    public function testGreaterThan(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new BinaryExpression(BinaryOperator::GT, new Literal(5), new Literal(3));
        self::assertTrue($this->ev->evaluateBool($expr, $xp));
    }

    // ── Count ─────────────────────────────────────────────────────────────────

    public function testCountNodes(): void
    {
        $xml = $this->invoiceXml('<cbc:Note>A</cbc:Note><cbc:Note>B</cbc:Note>');
        $xp  = $this->xpath($xml);
        $result = $this->ev->evaluate(new Count(new Path('//cbc:Note')), $xp);
        self::assertSame(2, $result);
    }

    // ── Sum ───────────────────────────────────────────────────────────────────

    public function testSumNodes(): void
    {
        $xml = $this->invoiceXml('<cbc:Amount>10.00</cbc:Amount><cbc:Amount>5.50</cbc:Amount>');
        $xp  = $this->xpath($xml);
        $result = $this->ev->evaluate(new Sum(new Path('//cbc:Amount')), $xp);
        self::assertEqualsWithDelta(15.5, $result, 0.001);
    }

    // ── Round ─────────────────────────────────────────────────────────────────

    public function testRound(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame(4.0, $this->ev->evaluate(new Round(new Literal(3.7)), $xp));
        self::assertSame(3.0, $this->ev->evaluate(new Round(new Literal(3.2)), $xp));
    }

    // ── UpperCase ─────────────────────────────────────────────────────────────

    public function testUpperCase(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame('HELLO', $this->ev->evaluate(new UpperCase(new Literal('hello')), $xp));
    }

    // ── Contains / StartsWith ─────────────────────────────────────────────────

    public function testContainsTrue(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluateBool(new Contains(new Literal('foobar'), new Literal('oba')), $xp));
    }

    public function testContainsFalse(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertFalse($this->ev->evaluateBool(new Contains(new Literal('foobar'), new Literal('xyz')), $xp));
    }

    public function testStartsWithTrue(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluateBool(new StartsWith(new Literal('urn:fdc:peppol'), new Literal('urn:')), $xp));
    }

    // ── ForExpression ─────────────────────────────────────────────────────────

    public function testForExpressionMapsSequence(): void
    {
        // for $v in (1, 2, 3) return $v * 2  →  [2, 4, 6]
        // Simplified: for $v in //cbc:Amount return $v
        $xml = $this->invoiceXml('<cbc:Amount>10</cbc:Amount><cbc:Amount>20</cbc:Amount>');
        $xp  = $this->xpath($xml);
        $expr = new ForExpression('v', new Path('//cbc:Amount'),
            new BinaryExpression(BinaryOperator::ADD, new VariableRef('v'), new Literal(0))
        );
        $result = $this->ev->evaluate($expr, $xp);
        self::assertIsArray($result);
        self::assertCount(2, $result);
    }

    public function testForExpressionEmptySequenceReturnsEmptyArray(): void
    {
        $xp   = $this->xpath($this->invoiceXml());
        $expr = new ForExpression('v', new Path('//cbc:Amount'), new VariableRef('v'));
        self::assertSame([], $this->ev->evaluate($expr, $xp));
    }

    // ── Sequence constructor ──────────────────────────────────────────────────

    public function testSequenceTrueWhenFirstItemHasNodes(): void
    {
        $xp  = $this->xpath($this->invoiceXml('<cbc:ID>X</cbc:ID>'));
        $seq = new Sequence([new Path('//cbc:ID'), new Path('//cbc:Note')]);
        // First item matches, second does not — sequence is non-empty → truthy.
        self::assertTrue($this->ev->evaluateBool($seq, $xp));
    }

    public function testSequenceTrueWhenSecondItemHasNodes(): void
    {
        $xp  = $this->xpath($this->invoiceXml('<cbc:Note>Hi</cbc:Note>'));
        $seq = new Sequence([new Path('//cbc:ID'), new Path('//cbc:Note')]);
        // First item absent, second matches — sequence is still non-empty → truthy.
        self::assertTrue($this->ev->evaluateBool($seq, $xp));
    }

    public function testSequenceFalseWhenNoItemHasNodes(): void
    {
        $xp  = $this->xpath($this->invoiceXml());
        $seq = new Sequence([new Path('//cbc:ID'), new Path('//cbc:Note')]);
        self::assertFalse($this->ev->evaluateBool($seq, $xp));
    }

    // ── CastableAs ────────────────────────────────────────────────────────────

    public function testCastableAsXsDateValid(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluateBool(new CastableAs(new Literal('2024-06-15'), 'xs:date'), $xp));
    }

    public function testCastableAsXsDateInvalidFormat(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertFalse($this->ev->evaluateBool(new CastableAs(new Literal('15-06-2024'), 'xs:date'), $xp));
    }

    public function testCastableAsXsDateInvalidDate(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertFalse($this->ev->evaluateBool(new CastableAs(new Literal('2024-13-01'), 'xs:date'), $xp));
    }

    public function testCastableAsXsInteger(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluateBool(new CastableAs(new Literal('42'), 'xs:integer'), $xp));
        self::assertFalse($this->ev->evaluateBool(new CastableAs(new Literal('42.5'), 'xs:integer'), $xp));
    }

    public function testCastableAsXsDecimal(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertTrue($this->ev->evaluateBool(new CastableAs(new Literal('3.14'), 'xs:decimal'), $xp));
        self::assertFalse($this->ev->evaluateBool(new CastableAs(new Literal('abc'), 'xs:decimal'), $xp));
    }

    public function testCastableAsXsString(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        // Any string is castable as xs:string.
        self::assertTrue($this->ev->evaluateBool(new CastableAs(new Literal('anything'), 'xs:string'), $xp));
    }

    public function testCastableAsUnknownTypeReturnsFalse(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertFalse($this->ev->evaluateBool(new CastableAs(new Literal('x'), 'xs:gYear'), $xp));
    }

    // ── NormalizeSpace ────────────────────────────────────────────────────────

    public function testNormalizeSpaceStripsEdges(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame('hello world', $this->ev->evaluate(new NormalizeSpace(new Literal('  hello world  ')), $xp));
    }

    public function testNormalizeSpaceCollapsesInternal(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame('a b c', $this->ev->evaluate(new NormalizeSpace(new Literal("a  b\t\nc")), $xp));
    }

    public function testNormalizeSpaceOnWhitespaceOnlyIsEmpty(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame('', $this->ev->evaluate(new NormalizeSpace(new Literal('   ')), $xp));
    }

    // ── StringLength ──────────────────────────────────────────────────────────

    public function testStringLengthAscii(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame(5, $this->ev->evaluate(new StringLength(new Literal('hello')), $xp));
    }

    public function testStringLengthMultibyte(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        // "café" is 4 characters even though it is 5 bytes in UTF-8.
        self::assertSame(4, $this->ev->evaluate(new StringLength(new Literal('café')), $xp));
    }

    public function testStringLengthEmpty(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        self::assertSame(0, $this->ev->evaluate(new StringLength(new Literal('')), $xp));
    }

    // ── IfThenElse ────────────────────────────────────────────────────────────

    public function testIfThenElseTrueBranch(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new IfThenElse(new Literal(true), new Literal('yes'), new Literal('no'));
        self::assertSame('yes', $this->ev->evaluate($expr, $xp));
    }

    public function testIfThenElseFalseBranch(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new IfThenElse(new Literal(false), new Literal('yes'), new Literal('no'));
        self::assertSame('no', $this->ev->evaluate($expr, $xp));
    }

    // ── Some / Every ─────────────────────────────────────────────────────────

    public function testSomeSatisfied(): void
    {
        $xml = $this->invoiceXml('<cbc:Amount>10</cbc:Amount><cbc:Amount>-5</cbc:Amount>');
        $xp  = $this->xpath($xml);
        // some $v in //cbc:Amount satisfies $v > 0
        $expr = new Some('v', new Path('//cbc:Amount'),
            new BinaryExpression(BinaryOperator::GT, new VariableRef('v'), new Literal(0))
        );
        self::assertTrue($this->ev->evaluateBool($expr, $xp));
    }

    public function testEveryNotSatisfied(): void
    {
        $xml = $this->invoiceXml('<cbc:Amount>10</cbc:Amount><cbc:Amount>-5</cbc:Amount>');
        $xp  = $this->xpath($xml);
        $expr = new Every('v', new Path('//cbc:Amount'),
            new BinaryExpression(BinaryOperator::GT, new VariableRef('v'), new Literal(0))
        );
        self::assertFalse($this->ev->evaluateBool($expr, $xp));
    }

    public function testEveryOnEmptySequenceIsVacuouslyTrue(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $expr = new Every('v', new Path('//cbc:Amount'),
            new BinaryExpression(BinaryOperator::GT, new VariableRef('v'), new Literal(0))
        );
        self::assertTrue($this->ev->evaluateBool($expr, $xp));
    }

    // ── Checksum ─────────────────────────────────────────────────────────────

    public function testChecksumDispatchesToHandler(): void
    {
        $called = false;
        $ev = new ExpressionEvaluator([
            ChecksumAlgorithm::GLN->value => function (string $v) use (&$called): bool {
                $called = true;
                return $v === 'TEST';
            },
        ]);
        $xp = $this->xpath($this->invoiceXml());
        $expr = new Checksum(ChecksumAlgorithm::GLN, new Literal('TEST'));
        self::assertTrue($ev->evaluateBool($expr, $xp));
        self::assertTrue($called);
    }

    public function testChecksumThrowsWhenNoHandler(): void
    {
        $xp = $this->xpath($this->invoiceXml());
        $this->expectException(RuntimeException::class);
        $this->ev->evaluate(new Checksum(ChecksumAlgorithm::GLN, new Literal('X')), $xp);
    }
}
