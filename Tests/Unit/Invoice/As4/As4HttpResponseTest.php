<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4HttpResponse;
use PHPUnit\Framework\TestCase;

class As4HttpResponseTest extends TestCase
{
    // ── Constructor ───────────────────────────────────────────────────────────

    public function testConstructorStoresFields(): void
    {
        $r = new As4HttpResponse(200, '<receipt/>');
        $this->assertSame(200, $r->statusCode);
        $this->assertSame('<receipt/>', $r->body);
        $this->assertSame('', $r->contentType);
    }

    public function testConstructorStoresContentType(): void
    {
        $r = new As4HttpResponse(200, '<receipt/>', 'application/soap+xml; charset=utf-8');
        $this->assertSame('application/soap+xml; charset=utf-8', $r->contentType);
    }

    public function testContentTypeDefaultsToEmpty(): void
    {
        $this->assertSame('', (new As4HttpResponse(202, ''))->contentType);
    }

    public function testEmptyBodyAllowed(): void
    {
        $r = new As4HttpResponse(202, '');
        $this->assertSame('', $r->body);
    }

    // ── isSuccess ─────────────────────────────────────────────────────────────

    public function testIsSuccessTrueFor200(): void
    {
        $this->assertTrue((new As4HttpResponse(200, ''))->isSuccess());
    }

    public function testIsSuccessTrueFor202(): void
    {
        $this->assertTrue((new As4HttpResponse(202, ''))->isSuccess());
    }

    public function testIsSuccessFalseFor201(): void
    {
        $this->assertFalse((new As4HttpResponse(201, ''))->isSuccess());
    }

    public function testIsSuccessFalseFor400(): void
    {
        $this->assertFalse((new As4HttpResponse(400, ''))->isSuccess());
    }

    public function testIsSuccessFalseFor500(): void
    {
        $this->assertFalse((new As4HttpResponse(500, ''))->isSuccess());
    }

    // ── isRetriable ───────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\DataProvider('retriableCodeProvider')]
    public function testIsRetriableTrueForRetriableCodes(int $code): void
    {
        $this->assertTrue((new As4HttpResponse($code, ''))->isRetriable());
    }

    /** @return array<string, array{int}> */
    public static function retriableCodeProvider(): array
    {
        return [
            'request timeout 408'       => [408],
            'too many requests 429'     => [429],
            'internal server error 500' => [500],
            'bad gateway 502'           => [502],
            'service unavailable 503'   => [503],
            'gateway timeout 504'       => [504],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonRetriableCodeProvider')]
    public function testIsRetriableFalseForNonRetriableCodes(int $code): void
    {
        $this->assertFalse((new As4HttpResponse($code, ''))->isRetriable());
    }

    /** @return array<string, array{int}> */
    public static function nonRetriableCodeProvider(): array
    {
        return [
            'success 200'     => [200],
            'accepted 202'    => [202],
            'bad request 400' => [400],
            'forbidden 403'   => [403],
            'not found 404'   => [404],
        ];
    }
}
