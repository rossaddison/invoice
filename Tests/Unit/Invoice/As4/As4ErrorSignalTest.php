<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4ErrorCategory;
use App\Invoice\As4\As4ErrorSignal;
use App\Invoice\As4\As4ErrorSeverity;
use PHPUnit\Framework\TestCase;

class As4ErrorSignalTest extends TestCase
{
    private const string MSG_ID = 'err-001@test.local';
    private const string REF_ID = 'orig-001@test.local';

    private function sut(As4ErrorSeverity $severity = As4ErrorSeverity::Failure): As4ErrorSignal
    {
        return new As4ErrorSignal(
            messageId:        self::MSG_ID,
            refToMessageId:   self::REF_ID,
            timestamp:        new \DateTimeImmutable(),
            category:         As4ErrorCategory::Content,
            errorCode:        'EBMS:0004',
            severity:         $severity,
            shortDescription: 'Other',
            description:      'Unknown error occurred',
        );
    }

    public function testConstructorStoresMessageId(): void
    {
        $this->assertSame(self::MSG_ID, $this->sut()->messageId);
    }

    public function testConstructorStoresRefToMessageId(): void
    {
        $this->assertSame(self::REF_ID, $this->sut()->refToMessageId);
    }

    public function testConstructorStoresTimestamp(): void
    {
        $ts     = new \DateTimeImmutable('2026-06-13T10:00:00Z');
        $signal = new As4ErrorSignal(
            messageId:        self::MSG_ID,
            refToMessageId:   self::REF_ID,
            timestamp:        $ts,
            category:         As4ErrorCategory::Content,
            errorCode:        'EBMS:0004',
            severity:         As4ErrorSeverity::Failure,
            shortDescription: 'Other',
            description:      'Unknown error occurred',
        );
        $this->assertSame($ts, $signal->timestamp);
    }

    public function testConstructorStoresCategory(): void
    {
        $this->assertSame(As4ErrorCategory::Content, $this->sut()->category);
    }

    public function testConstructorStoresErrorCode(): void
    {
        $this->assertSame('EBMS:0004', $this->sut()->errorCode);
    }

    public function testConstructorStoresSeverity(): void
    {
        $this->assertSame(As4ErrorSeverity::Warning, $this->sut(As4ErrorSeverity::Warning)->severity);
    }

    public function testConstructorStoresShortDescription(): void
    {
        $this->assertSame('Other', $this->sut()->shortDescription);
    }

    public function testConstructorStoresDescription(): void
    {
        $this->assertSame('Unknown error occurred', $this->sut()->description);
    }

    public function testIsFailureReturnsTrueForFailureSeverity(): void
    {
        $this->assertTrue($this->sut(As4ErrorSeverity::Failure)->isFailure());
    }

    public function testIsFailureReturnsFalseForWarningSeverity(): void
    {
        $this->assertFalse($this->sut(As4ErrorSeverity::Warning)->isFailure());
    }
}
