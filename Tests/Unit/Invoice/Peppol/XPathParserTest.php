<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Invoice\Helpers\Peppol\Ast\Abs;
use App\Invoice\Helpers\Peppol\Ast\BinaryExpression;
use App\Invoice\Helpers\Peppol\Ast\CastableAs;
use App\Invoice\Helpers\Peppol\Ast\Decimal;
use App\Invoice\Helpers\Peppol\Ast\BinaryOperator;
use App\Invoice\Helpers\Peppol\Ast\Checksum;
use App\Invoice\Helpers\Peppol\Ast\ChecksumAlgorithm;
use App\Invoice\Helpers\Peppol\Ast\Contains;
use App\Invoice\Helpers\Peppol\Ast\Count;
use App\Invoice\Helpers\Peppol\Ast\Every;
use App\Invoice\Helpers\Peppol\Ast\ForExpression;
use App\Invoice\Helpers\Peppol\Ast\FunctionCall;
use App\Invoice\Helpers\Peppol\Ast\Exists;
use App\Invoice\Helpers\Peppol\Ast\IfThenElse;
use App\Invoice\Helpers\Peppol\Ast\Literal;
use App\Invoice\Helpers\Peppol\Ast\Matches;
use App\Invoice\Helpers\Peppol\Ast\NormalizeSpace;
use App\Invoice\Helpers\Peppol\Ast\Sequence;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\NotExists;
use App\Invoice\Helpers\Peppol\Ast\Path;
use App\Invoice\Helpers\Peppol\Ast\Round;
use App\Invoice\Helpers\Peppol\Ast\Some;
use App\Invoice\Helpers\Peppol\Ast\StartsWith;
use App\Invoice\Helpers\Peppol\Ast\StringLength;
use App\Invoice\Helpers\Peppol\Ast\Sum;
use App\Invoice\Helpers\Peppol\Ast\UpperCase;
use App\Invoice\Helpers\Peppol\Ast\VariableRef;
use App\Invoice\Helpers\Peppol\Exception\XPathParseException;
use App\Invoice\Helpers\Peppol\XPathParser;
use PHPUnit\Framework\TestCase;

final class XPathParserTest extends TestCase
{
    private XPathParser $p;

    protected function setUp(): void
    {
        $this->p = new XPathParser();
    }

    public function testParsePath(): void
    {
        $ast = $this->p->parse('cbc:ID');
        self::assertInstanceOf(Path::class, $ast);
        self::assertSame('cbc:ID', $ast->xpath);
    }

    public function testParseStringLiteral(): void
    {
        $ast = $this->p->parse("'VAT'");
        self::assertInstanceOf(Literal::class, $ast);
        self::assertSame('VAT', $ast->value);
    }

    public function testParseIntLiteral(): void
    {
        $ast = $this->p->parse('42');
        self::assertInstanceOf(Literal::class, $ast);
        self::assertSame(42, $ast->value);
    }

    public function testParseFloatLiteral(): void
    {
        $ast = $this->p->parse('3.14');
        self::assertInstanceOf(Literal::class, $ast);
        self::assertSame(3.14, $ast->value);
    }

    public function testParseNegativeIntLiteral(): void
    {
        $ast = $this->p->parse('-1');
        self::assertInstanceOf(Literal::class, $ast);
        self::assertSame(-1, $ast->value);
    }

    public function testParseTrueFalse(): void
    {
        $t = $this->p->parse('true()');
        self::assertInstanceOf(Literal::class, $t);
        self::assertTrue($t->value);

        $f = $this->p->parse('false()');
        self::assertInstanceOf(Literal::class, $f);
        self::assertFalse($f->value);
    }

    public function testParseVariableRef(): void
    {
        $ast = $this->p->parse('$amount');
        self::assertInstanceOf(VariableRef::class, $ast);
        self::assertSame('amount', $ast->name);
    }

    public function testParseEqualityComparison(): void
    {
        $ast = $this->p->parse("cbc:ID = 'VAT'");
        self::assertInstanceOf(BinaryExpression::class, $ast);
        self::assertSame(BinaryOperator::EQ, $ast->operator);
        self::assertInstanceOf(Path::class, $ast->left);
        self::assertInstanceOf(Literal::class, $ast->right);
    }

    public function testParseAndExpression(): void
    {
        $ast = $this->p->parse('cbc:A and cbc:B');
        self::assertInstanceOf(BinaryExpression::class, $ast);
        self::assertSame(BinaryOperator::AND, $ast->operator);
    }

    public function testParseOrExpression(): void
    {
        $ast = $this->p->parse('cbc:A or cbc:B');
        self::assertInstanceOf(BinaryExpression::class, $ast);
        self::assertSame(BinaryOperator::OR, $ast->operator);
    }

    public function testParseAddition(): void
    {
        $ast = $this->p->parse('cbc:A + cbc:B');
        self::assertInstanceOf(BinaryExpression::class, $ast);
        self::assertSame(BinaryOperator::ADD, $ast->operator);
    }

    public function testParseSubtraction(): void
    {
        $ast = $this->p->parse('cbc:A - cbc:B');
        self::assertInstanceOf(BinaryExpression::class, $ast);
        self::assertSame(BinaryOperator::SUB, $ast->operator);
    }

    public function testParseMultiplication(): void
    {
        $ast = $this->p->parse('cbc:A * cbc:B');
        self::assertInstanceOf(BinaryExpression::class, $ast);
        self::assertSame(BinaryOperator::MUL, $ast->operator);
    }

    public function testParseExists(): void
    {
        $ast = $this->p->parse('exists(cbc:ID)');
        self::assertInstanceOf(Exists::class, $ast);
    }

    public function testParseNotExistsFunction(): void
    {
        $ast = $this->p->parse('not(exists(cbc:ID))');
        self::assertInstanceOf(NotExists::class, $ast);
    }

    public function testParseNotNonExists(): void
    {
        $ast = $this->p->parse('not(cbc:ID = 1)');
        self::assertInstanceOf(Not::class, $ast);
    }

    public function testParseCount(): void
    {
        $ast = $this->p->parse('count(cac:TaxTotal)');
        self::assertInstanceOf(Count::class, $ast);
    }

    public function testParseSum(): void
    {
        $ast = $this->p->parse('sum(cac:TaxTotal/cbc:TaxAmount)');
        self::assertInstanceOf(Sum::class, $ast);
    }

    public function testParseRound(): void
    {
        $ast = $this->p->parse('round(cbc:Amount)');
        self::assertInstanceOf(Round::class, $ast);
    }

    public function testParseAbs(): void
    {
        $ast = $this->p->parse('abs(cbc:Amount)');
        self::assertInstanceOf(Abs::class, $ast);
    }

    public function testParseUpperCase(): void
    {
        $ast = $this->p->parse('upper-case(cbc:ID)');
        self::assertInstanceOf(UpperCase::class, $ast);
    }

    public function testParseContains(): void
    {
        $ast = $this->p->parse("contains(cbc:ID, 'VAT')");
        self::assertInstanceOf(Contains::class, $ast);
    }

    public function testParseStartsWith(): void
    {
        $ast = $this->p->parse("starts-with(cbc:ID, 'urn:')");
        self::assertInstanceOf(StartsWith::class, $ast);
    }

    public function testParseMatches(): void
    {
        $ast = $this->p->parse("matches(cbc:ID, '\\d+')");
        self::assertInstanceOf(Matches::class, $ast);
    }

    public function testParseIfThenElse(): void
    {
        $ast = $this->p->parse('if (cbc:A) then cbc:B else cbc:C');
        self::assertInstanceOf(IfThenElse::class, $ast);
    }

    public function testParseSome(): void
    {
        $ast = $this->p->parse('some $v in cac:Lines satisfies cbc:ID');
        self::assertInstanceOf(Some::class, $ast);
        self::assertSame('v', $ast->variable);
    }

    public function testParseFor(): void
    {
        $ast = $this->p->parse('for $x in cac:Lines return cbc:ID');
        self::assertInstanceOf(ForExpression::class, $ast);
        self::assertSame('x', $ast->variable);
        self::assertInstanceOf(Path::class, $ast->in);
        self::assertInstanceOf(Path::class, $ast->return);
    }

    public function testParseForInsideFunctionCall(): void
    {
        // The DE-R-019 pattern: string-join(for $x in seq return expr, sep)
        $ast = $this->p->parse('string-join(for $x in cac:Line return cbc:ID, \'\')');
        self::assertInstanceOf(FunctionCall::class, $ast);
        self::assertCount(2, $ast->arguments);
        self::assertInstanceOf(ForExpression::class, $ast->arguments[0]);
    }

    public function testParseEvery(): void
    {
        $ast = $this->p->parse('every $v in cac:Lines satisfies cbc:ID');
        self::assertInstanceOf(Every::class, $ast);
        self::assertSame('v', $ast->variable);
    }

    public function testParseChecksumGln(): void
    {
        $ast = $this->p->parse('u:gln(cbc:ID)');
        self::assertInstanceOf(Checksum::class, $ast);
        self::assertSame(ChecksumAlgorithm::GLN, $ast->algorithm);
    }

    public function testParseChecksumMod11(): void
    {
        $ast = $this->p->parse('u:mod11(cbc:ID)');
        self::assertInstanceOf(Checksum::class, $ast);
        self::assertSame(ChecksumAlgorithm::Mod11, $ast->algorithm);
    }

    public function testParseAxisExpression(): void
    {
        // preceding-sibling:: and following-sibling:: must survive as raw Path nodes
        $ast = $this->p->parse('preceding-sibling::cac:InvoiceLine');
        self::assertInstanceOf(Path::class, $ast);
        self::assertSame('preceding-sibling::cac:InvoiceLine', $ast->xpath);
    }

    public function testParseAxisInPredicate(): void
    {
        // axis step inside a bracket — collectPath collects the whole expression
        $ast = $this->p->parse('cac:Foo[not(@id = preceding-sibling::cac:Foo/@id)]');
        self::assertInstanceOf(Path::class, $ast);
        self::assertStringContainsString('preceding-sibling::', $ast->xpath);
    }

    public function testParseSequenceConstructor(): void
    {
        $ast = $this->p->parse('(cac:TaxRepresentativeParty, cbc:BuyerReference)');
        self::assertInstanceOf(Sequence::class, $ast);
        self::assertCount(2, $ast->items);
        self::assertInstanceOf(Path::class, $ast->items[0]);
        self::assertInstanceOf(Path::class, $ast->items[1]);
    }

    public function testParseSequenceThreeItems(): void
    {
        $ast = $this->p->parse("(cac:A, cac:B, cac:C)");
        self::assertInstanceOf(Sequence::class, $ast);
        self::assertCount(3, $ast->items);
    }

    public function testParseCastableAs(): void
    {
        $ast = $this->p->parse("string(cbc:ID) castable as xs:date");
        self::assertInstanceOf(CastableAs::class, $ast);
        self::assertSame('xs:date', $ast->typeName);
    }

    public function testParseCastableAsInsideAnd(): void
    {
        // The IS-R-008 pattern: (...) castable as xs:date used inside and/or
        $ast = $this->p->parse("string(cbc:ID) castable as xs:date and exists(cbc:ID)");
        // castable as has higher precedence than and, so the and wraps the two operands
        self::assertInstanceOf(BinaryExpression::class, $ast);
        self::assertSame(BinaryOperator::AND, $ast->operator);
        self::assertInstanceOf(CastableAs::class, $ast->left);
    }

    public function testParseNormalizeSpaceWithArg(): void
    {
        $ast = $this->p->parse('normalize-space(cbc:ID)');
        self::assertInstanceOf(NormalizeSpace::class, $ast);
    }

    public function testParseNormalizeSpaceWithNoArg(): void
    {
        $ast = $this->p->parse('normalize-space()');
        self::assertInstanceOf(NormalizeSpace::class, $ast);
        // Default arg should be the context-node self-reference Path('.').
        self::assertInstanceOf(Path::class, $ast->value);
        self::assertSame('.', $ast->value->xpath);
    }

    public function testParseStringLength(): void
    {
        $ast = $this->p->parse('string-length(cbc:ID)');
        self::assertInstanceOf(StringLength::class, $ast);
    }

    public function testParseNumber(): void
    {
        // number() reuses the Decimal AST node.
        $ast = $this->p->parse('number(cbc:Amount)');
        self::assertInstanceOf(Decimal::class, $ast);
    }

    public function testUnparsedTokensThrow(): void
    {
        $this->expectException(XPathParseException::class);
        // Two adjacent literals with no operator between them.
        $this->p->parse("'a' 'b'");
    }
}
