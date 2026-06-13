<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4MimePart;
use PHPUnit\Framework\TestCase;

class As4MimePartTest extends TestCase
{
    public function testConstructorStoresContentId(): void
    {
        $part = new As4MimePart('invoice@as4.local', 'application/xml', '<Invoice/>');
        $this->assertSame('invoice@as4.local', $part->contentId);
    }

    public function testConstructorStoresContentType(): void
    {
        $part = new As4MimePart('invoice@as4.local', 'application/xml', '<Invoice/>');
        $this->assertSame('application/xml', $part->contentType);
    }

    public function testConstructorStoresBody(): void
    {
        $part = new As4MimePart('invoice@as4.local', 'application/xml', '<Invoice/>');
        $this->assertSame('<Invoice/>', $part->body);
    }

    public function testEmptyBodyAllowed(): void
    {
        $part = new As4MimePart('cid@as4.local', 'application/octet-stream', '');
        $this->assertSame('', $part->body);
    }
}
