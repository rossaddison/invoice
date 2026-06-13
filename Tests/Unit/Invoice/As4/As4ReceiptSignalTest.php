<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4ReceiptSignal;
use PHPUnit\Framework\TestCase;

class As4ReceiptSignalTest extends TestCase
{
    private const string MSG_ID = 'rcpt-001@test.local';
    private const string REF_ID = 'orig-001@test.local';

    private function sut(\DateTimeImmutable $timestamp): As4ReceiptSignal
    {
        return new As4ReceiptSignal(
            messageId:      self::MSG_ID,
            refToMessageId: self::REF_ID,
            timestamp:      $timestamp,
        );
    }

    public function testConstructorStoresMessageId(): void
    {
        $this->assertSame(self::MSG_ID, $this->sut(new \DateTimeImmutable())->messageId);
    }

    public function testConstructorStoresRefToMessageId(): void
    {
        $this->assertSame(self::REF_ID, $this->sut(new \DateTimeImmutable())->refToMessageId);
    }

    public function testConstructorStoresTimestamp(): void
    {
        $ts = new \DateTimeImmutable('2026-06-13T10:00:00Z');
        $this->assertSame($ts, $this->sut($ts)->timestamp);
    }
}
