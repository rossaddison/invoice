<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Invoice\Helpers\Peppol\XPathTokenizer;
use PHPUnit\Framework\TestCase;

final class XPathTokenizerTest extends TestCase
{
    private XPathTokenizer $t;

    protected function setUp(): void
    {
        $this->t = new XPathTokenizer();
    }

    public function testSimpleName(): void
    {
        $tokens = $this->t->tokenize('cbc:ID');
        self::assertSame(XPathTokenizer::T_NAME, $tokens[0]['type']);
        self::assertSame('cbc:ID', $tokens[0]['value']);
        self::assertSame(XPathTokenizer::T_EOF, $tokens[1]['type']);
    }

    public function testSingleQuotedStringLiteral(): void
    {
        $tokens = $this->t->tokenize("'VAT'");
        self::assertSame(XPathTokenizer::T_STRING, $tokens[0]['type']);
        self::assertSame('VAT', $tokens[0]['value']);
    }

    public function testDoubleQuotedStringLiteral(): void
    {
        $tokens = $this->t->tokenize('"hello"');
        self::assertSame(XPathTokenizer::T_STRING, $tokens[0]['type']);
        self::assertSame('hello', $tokens[0]['value']);
    }

    public function testIntegerNumber(): void
    {
        $tokens = $this->t->tokenize('42');
        self::assertSame(XPathTokenizer::T_NUMBER, $tokens[0]['type']);
        self::assertSame('42', $tokens[0]['value']);
    }

    public function testDecimalNumber(): void
    {
        $tokens = $this->t->tokenize('3.14');
        self::assertSame(XPathTokenizer::T_NUMBER, $tokens[0]['type']);
        self::assertSame('3.14', $tokens[0]['value']);
    }

    public function testVariable(): void
    {
        $tokens = $this->t->tokenize('$amount');
        self::assertSame(XPathTokenizer::T_VARIABLE, $tokens[0]['type']);
        self::assertSame('amount', $tokens[0]['value']);
    }

    public function testComparisonOperators(): void
    {
        $cases = [
            '='  => XPathTokenizer::T_EQ,
            '!=' => XPathTokenizer::T_NE,
            '<'  => XPathTokenizer::T_LT,
            '<=' => XPathTokenizer::T_LE,
            '>'  => XPathTokenizer::T_GT,
            '>=' => XPathTokenizer::T_GE,
        ];
        foreach ($cases as $op => $expectedType) {
            $tokens = $this->t->tokenize($op);
            self::assertSame($expectedType, $tokens[0]['type'], "Failed for operator: {$op}");
        }
    }

    public function testArithmeticOperators(): void
    {
        $tokens = $this->t->tokenize('+ - *');
        self::assertSame(XPathTokenizer::T_PLUS,  $tokens[0]['type']);
        self::assertSame(XPathTokenizer::T_MINUS, $tokens[1]['type']);
        self::assertSame(XPathTokenizer::T_STAR,  $tokens[2]['type']);
    }

    public function testPathSeparators(): void
    {
        $tokensSlash = $this->t->tokenize('/cbc:ID');
        self::assertSame(XPathTokenizer::T_SLASH, $tokensSlash[0]['type']);

        $tokensDSlash = $this->t->tokenize('//cbc:ID');
        self::assertSame(XPathTokenizer::T_DSLASH, $tokensDSlash[0]['type']);
    }

    public function testParensAndBrackets(): void
    {
        $tokens = $this->t->tokenize('()[]');
        self::assertSame(XPathTokenizer::T_LPAREN,   $tokens[0]['type']);
        self::assertSame(XPathTokenizer::T_RPAREN,   $tokens[1]['type']);
        self::assertSame(XPathTokenizer::T_LBRACKET, $tokens[2]['type']);
        self::assertSame(XPathTokenizer::T_RBRACKET, $tokens[3]['type']);
    }

    public function testDotAndDotDot(): void
    {
        $tokensDot = $this->t->tokenize('.');
        self::assertSame(XPathTokenizer::T_DOT, $tokensDot[0]['type']);

        $tokensDotDot = $this->t->tokenize('..');
        self::assertSame(XPathTokenizer::T_DOTDOT, $tokensDotDot[0]['type']);
    }

    public function testAtSign(): void
    {
        $tokens = $this->t->tokenize('@schemeID');
        self::assertSame(XPathTokenizer::T_AT, $tokens[0]['type']);
    }

    public function testComma(): void
    {
        $tokens = $this->t->tokenize(',');
        self::assertSame(XPathTokenizer::T_COMMA, $tokens[0]['type']);
    }

    public function testPipe(): void
    {
        $tokens = $this->t->tokenize('|');
        self::assertSame(XPathTokenizer::T_PIPE, $tokens[0]['type']);
    }

    public function testAxisSeparator(): void
    {
        // preceding-sibling::cac:Foo — three tokens: NAME, DCOLON, NAME
        $tokens = $this->t->tokenize('preceding-sibling::cac:Foo');
        self::assertSame(XPathTokenizer::T_NAME,   $tokens[0]['type']);
        self::assertSame('preceding-sibling',       $tokens[0]['value']);
        self::assertSame(XPathTokenizer::T_DCOLON,  $tokens[1]['type']);
        self::assertSame('::',                      $tokens[1]['value']);
        self::assertSame(XPathTokenizer::T_NAME,    $tokens[2]['type']);
        self::assertSame('cac:Foo',                 $tokens[2]['value']);
        self::assertSame(XPathTokenizer::T_EOF,     $tokens[3]['type']);
    }

    public function testAxisSeparatorTokenToString(): void
    {
        $token = ['type' => XPathTokenizer::T_DCOLON, 'value' => '::'];
        self::assertSame('::', $this->t->tokenToString($token));
    }

    public function testUnterminatedStringTerminatesGracefully(): void
    {
        // scanString exits at EOF without throwing; the partial content is returned.
        $tokens = $this->t->tokenize("'unterminated");
        self::assertSame(XPathTokenizer::T_STRING, $tokens[0]['type']);
        self::assertSame('unterminated', $tokens[0]['value']);
    }

    public function testEofIsAlwaysLast(): void
    {
        $tokens = $this->t->tokenize('cbc:ID');
        self::assertSame(XPathTokenizer::T_EOF, end($tokens)['type']);
    }
}
