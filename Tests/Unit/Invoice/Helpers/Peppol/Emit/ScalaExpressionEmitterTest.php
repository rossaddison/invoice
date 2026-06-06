<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Helpers\Peppol\Emit;

use App\Invoice\Helpers\Peppol\Ast\Abs;
use App\Invoice\Helpers\Peppol\Ast\BinaryExpression;
use App\Invoice\Helpers\Peppol\Ast\BinaryOperator;
use App\Invoice\Helpers\Peppol\Ast\Contains;
use App\Invoice\Helpers\Peppol\Ast\Count;
use App\Invoice\Helpers\Peppol\Ast\Decimal;
use App\Invoice\Helpers\Peppol\Ast\Every;
use App\Invoice\Helpers\Peppol\Ast\Exists;
use App\Invoice\Helpers\Peppol\Ast\FunctionCall;
use App\Invoice\Helpers\Peppol\Ast\IfThenElse;
use App\Invoice\Helpers\Peppol\Ast\InCodeList;
use App\Invoice\Helpers\Peppol\Ast\Literal;
use App\Invoice\Helpers\Peppol\Ast\Matches;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\NotExists;
use App\Invoice\Helpers\Peppol\Ast\Path;
use App\Invoice\Helpers\Peppol\Ast\Round;
use App\Invoice\Helpers\Peppol\Ast\Some;
use App\Invoice\Helpers\Peppol\Ast\StartsWith;
use App\Invoice\Helpers\Peppol\Ast\StringCast;
use App\Invoice\Helpers\Peppol\Ast\Sum;
use App\Invoice\Helpers\Peppol\Ast\Union;
use App\Invoice\Helpers\Peppol\Ast\UpperCase;
use App\Invoice\Helpers\Peppol\Ast\VariableRef;
use App\Invoice\Helpers\Peppol\CodeLists;
use App\Invoice\Helpers\Peppol\Emit\ScalaExpressionEmitter;
use App\Invoice\Helpers\Peppol\Emit\ScalaVoPathMapper;
use PHPUnit\Framework\TestCase;

class ScalaExpressionEmitterTest extends TestCase
{
    private ScalaExpressionEmitter $emitter;

    #[\Override]
    protected function setUp(): void
    {
        $this->emitter = new ScalaExpressionEmitter(new ScalaVoPathMapper());
    }

    // ── Literal ────────────────────────────────────────────────────────────────

    public function testLiteralStringIsDoubleQuoted(): void
    {
        $this->assertSame('"hello"', $this->emitter->emit(new Literal('hello')));
    }

    public function testLiteralStringEscapesDoubleQuote(): void
    {
        $this->assertSame('"say \\"hi\\""', $this->emitter->emit(new Literal('say "hi"')));
    }

    public function testLiteralIntEmitsInteger(): void
    {
        $this->assertSame('42', $this->emitter->emit(new Literal(42)));
    }

    public function testLiteralZeroEmitsZero(): void
    {
        $this->assertSame('0', $this->emitter->emit(new Literal(0)));
    }

    public function testLiteralFloatEmitsDecimal(): void
    {
        // number_format with 10 decimals, trailing zeros stripped
        $this->assertSame('3.14', $this->emitter->emit(new Literal(3.14)));
    }

    public function testLiteralTrueEmitsTrue(): void
    {
        $this->assertSame('true', $this->emitter->emit(new Literal(true)));
    }

    public function testLiteralFalseEmitsFalse(): void
    {
        $this->assertSame('false', $this->emitter->emit(new Literal(false)));
    }

    // ── Path ──────────────────────────────────────────────────────────────────

    public function testPathSelfEmitsContextVar(): void
    {
        $this->assertSame('v', $this->emitter->emit(new Path('.')));
    }

    public function testPathCbcIdEmitsDotAccess(): void
    {
        $this->assertSame('v.id', $this->emitter->emit(new Path('cbc:ID')));
    }

    public function testPathWithCustomContextVar(): void
    {
        $this->assertSame('e.id', $this->emitter->emit(new Path('cbc:ID'), 'e'));
    }

    // ── VariableRef ────────────────────────────────────────────────────────────

    public function testVariableRefEmitsName(): void
    {
        $this->assertSame('x', $this->emitter->emit(new VariableRef('x')));
    }

    // ── BinaryExpression ───────────────────────────────────────────────────────

    public function testBinaryEqProducesDoubleEqual(): void
    {
        $expr = new BinaryExpression(BinaryOperator::EQ, new Path('cbc:ID'), new Literal('ABC'));
        $this->assertSame('(v.id == "ABC")', $this->emitter->emit($expr));
    }

    public function testBinaryNeProducesNotEqual(): void
    {
        $expr = new BinaryExpression(BinaryOperator::NE, new Path('cbc:Amount'), new Literal(0));
        $this->assertSame('(v.amount != 0)', $this->emitter->emit($expr));
    }

    public function testBinaryGtProducesGreaterThan(): void
    {
        $expr = new BinaryExpression(BinaryOperator::GT, new Path('cbc:Amount'), new Literal(0));
        $this->assertSame('(v.amount > 0)', $this->emitter->emit($expr));
    }

    public function testBinaryAndProducesAmpAmp(): void
    {
        $left  = new BinaryExpression(BinaryOperator::GT, new Path('cbc:Amount'), new Literal(0));
        $right = new BinaryExpression(BinaryOperator::LT, new Path('cbc:Amount'), new Literal(100));
        $expr  = new BinaryExpression(BinaryOperator::AND, $left, $right);
        $this->assertSame('((v.amount > 0) && (v.amount < 100))', $this->emitter->emit($expr));
    }

    public function testBinaryOrProducesPipePipe(): void
    {
        $expr = new BinaryExpression(
            BinaryOperator::OR,
            new BinaryExpression(BinaryOperator::EQ, new Path('cbc:ID'), new Literal('A')),
            new BinaryExpression(BinaryOperator::EQ, new Path('cbc:ID'), new Literal('B')),
        );
        $this->assertSame('((v.id == "A") || (v.id == "B"))', $this->emitter->emit($expr));
    }

    public function testBinaryAddProducesPlus(): void
    {
        $expr = new BinaryExpression(BinaryOperator::ADD, new Path('cbc:Amount'), new Literal(10));
        $this->assertSame('(v.amount + 10)', $this->emitter->emit($expr));
    }

    // ── Not ───────────────────────────────────────────────────────────────────

    public function testNotWrapsOperandInBang(): void
    {
        $this->assertSame('!(true)', $this->emitter->emit(new Not(new Literal(true))));
    }

    public function testNotExistsUsesExclamationAndExistsForm(): void
    {
        // Not(Exists(scalar path)) → !((<path>) != null && (<path>) != "")
        $expr = new Not(new Exists(new Path('cbc:ID')));
        $this->assertSame(
            '!((v.id) != null && (v.id) != "")',
            $this->emitter->emit($expr),
        );
    }

    // ── Exists / NotExists ────────────────────────────────────────────────────

    public function testExistsOnScalarPathUsesNullCheck(): void
    {
        $this->assertSame(
            '(v.id) != null && (v.id) != ""',
            $this->emitter->emit(new Exists(new Path('cbc:ID'))),
        );
    }

    public function testExistsOnArrayPathUsesNonEmpty(): void
    {
        $this->assertSame(
            '(v.invoiceLines).nonEmpty',
            $this->emitter->emit(new Exists(new Path('cac:InvoiceLine'))),
        );
    }

    public function testNotExistsOnScalarPathUsesNullOrEmpty(): void
    {
        $this->assertSame(
            '(v.id) == null || (v.id) == ""',
            $this->emitter->emit(new NotExists(new Path('cbc:ID'))),
        );
    }

    public function testNotExistsOnArrayPathUsesIsEmpty(): void
    {
        $this->assertSame(
            '(v.invoiceLines).isEmpty',
            $this->emitter->emit(new NotExists(new Path('cac:InvoiceLine'))),
        );
    }

    // ── Count / Sum / Round / Abs ──────────────────────────────────────────────

    public function testCountProducesLength(): void
    {
        $this->assertSame(
            '(v.invoiceLines).length',
            $this->emitter->emit(new Count(new Path('cac:InvoiceLine'))),
        );
    }

    public function testSumProducesSum(): void
    {
        $this->assertSame(
            '(v.taxTotals).sum',
            $this->emitter->emit(new Sum(new Path('cac:TaxTotal'))),
        );
    }

    public function testRoundProducesMathRound(): void
    {
        $this->assertSame(
            'math.round(v.amount).toDouble',
            $this->emitter->emit(new Round(new Path('cbc:Amount'))),
        );
    }

    public function testAbsProducesMathAbs(): void
    {
        $this->assertSame(
            'math.abs(v.amount)',
            $this->emitter->emit(new Abs(new Path('cbc:Amount'))),
        );
    }

    // ── Decimal / UpperCase / StringCast ──────────────────────────────────────

    public function testDecimalProducesToStringToDouble(): void
    {
        $this->assertSame(
            '(v.amount).toString.toDouble',
            $this->emitter->emit(new Decimal(new Path('cbc:Amount'))),
        );
    }

    public function testUpperCaseProducesToStringToUpperCase(): void
    {
        $this->assertSame(
            '(v.id).toString.toUpperCase',
            $this->emitter->emit(new UpperCase(new Path('cbc:ID'))),
        );
    }

    public function testStringCastProducesToString(): void
    {
        $this->assertSame(
            '(v.id).toString',
            $this->emitter->emit(new StringCast(new Path('cbc:ID'))),
        );
    }

    // ── Contains / StartsWith / Matches ───────────────────────────────────────

    public function testContainsProducesToStringContains(): void
    {
        $this->assertSame(
            '(v.id).toString.contains("ABC")',
            $this->emitter->emit(new Contains(new Path('cbc:ID'), new Literal('ABC'))),
        );
    }

    public function testStartsWithProducesToStringStartsWith(): void
    {
        $this->assertSame(
            '(v.id).toString.startsWith("GB")',
            $this->emitter->emit(new StartsWith(new Path('cbc:ID'), new Literal('GB'))),
        );
    }

    public function testMatchesWrapsPatternForPartialMatch(): void
    {
        // Literal('\d+') → emitLiteral escapes '\' → '\\d+' in Scala output
        $this->assertSame(
            '(v.id).toString.matches("(?s).*" + "\\\\d+" + ".*")',
            $this->emitter->emit(new Matches(new Path('cbc:ID'), new Literal('\\d+'))),
        );
    }

    // ── IfThenElse ────────────────────────────────────────────────────────────

    public function testIfThenElseProducesScalaIfExpression(): void
    {
        $expr = new IfThenElse(
            new BinaryExpression(BinaryOperator::GT, new Path('cbc:Amount'), new Literal(0)),
            new Literal(true),
            new Literal(false),
        );
        $this->assertSame(
            '(if ((v.amount > 0)) true else false)',
            $this->emitter->emit($expr),
        );
    }

    // ── Some / Every ──────────────────────────────────────────────────────────

    public function testSomeProducesExistsWithLambda(): void
    {
        $expr = new Some(
            'x',
            new Path('cac:InvoiceLine'),
            new BinaryExpression(BinaryOperator::EQ, new Path('cbc:ID'), new VariableRef('x')),
        );
        $this->assertSame(
            '(v.invoiceLines).exists(x => (x.id == x))',
            $this->emitter->emit($expr),
        );
    }

    public function testEveryProducesForallWithLambda(): void
    {
        $expr = new Every(
            'line',
            new Path('cac:InvoiceLine'),
            new Exists(new Path('cbc:ID')),
        );
        $this->assertSame(
            '(v.invoiceLines).forall(line => (line.id) != null && (line.id) != "")',
            $this->emitter->emit($expr),
        );
    }

    // ── InCodeList ────────────────────────────────────────────────────────────

    public function testInCodeListProducesCodeListContains(): void
    {
        // emitInCodeList uses $list->name (enum case name), not $list->value
        $expr = new InCodeList(new Path('cbc:CurrencyID'), CodeLists::ISO4217);
        $this->assertSame(
            'CodeList.load(CodeLists.ISO4217).contains(v.currencyId)',
            $this->emitter->emit($expr),
        );
    }

    // ── FunctionCall ──────────────────────────────────────────────────────────

    public function testFunctionCallNormalizeSpaceProducesToStringTrim(): void
    {
        $expr = new FunctionCall('normalize-space', [new Path('cbc:ID')]);
        $this->assertSame('(v.id).toString.trim', $this->emitter->emit($expr));
    }

    public function testFunctionCallEndsWithProducesToStringEndsWith(): void
    {
        $expr = new FunctionCall('ends-with', [new Path('cbc:ID'), new Literal('.pdf')]);
        $this->assertSame('(v.id).toString.endsWith(".pdf")', $this->emitter->emit($expr));
    }

    public function testFunctionCallConcatProducesMkString(): void
    {
        $expr = new FunctionCall('concat', [new Literal('A'), new Literal('B')]);
        $this->assertSame('List("A", "B").mkString("")', $this->emitter->emit($expr));
    }

    public function testFunctionCallLowerCaseProducesToLowerCase(): void
    {
        $expr = new FunctionCall('lower-case', [new Path('cbc:ID')]);
        $this->assertSame('(v.id).toString.toLowerCase', $this->emitter->emit($expr));
    }

    public function testFunctionCallTrueProducesTrue(): void
    {
        $this->assertSame('true', $this->emitter->emit(new FunctionCall('true')));
    }

    public function testFunctionCallFalseProducesFalse(): void
    {
        $this->assertSame('false', $this->emitter->emit(new FunctionCall('false')));
    }

    public function testFunctionCallUnknownProducesTodoComment(): void
    {
        $expr = new FunctionCall('u:gln', [new Path('cbc:ID')]);
        $this->assertStringContainsString('TODO', $this->emitter->emit($expr));
    }

    // ── Union ─────────────────────────────────────────────────────────────────

    public function testUnionProducesPlusPlusOperator(): void
    {
        $expr = new Union(new Path('cac:InvoiceLine'), new Path('cac:TaxTotal'));
        $this->assertSame(
            '(v.invoiceLines) ++ (v.taxTotals)',
            $this->emitter->emit($expr),
        );
    }
}
