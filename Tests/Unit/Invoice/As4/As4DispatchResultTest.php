<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4DispatchResult;
use App\Invoice\As4\As4ErrorCategory;
use App\Invoice\As4\As4ErrorSignal;
use App\Invoice\As4\As4ErrorSeverity;
use App\Invoice\As4\As4ReceiptSignal;
use PHPUnit\Framework\TestCase;

class As4DispatchResultTest extends TestCase
{
    private const string MSG_ID = 'msg-001@test.local';

    private function receiptSignal(): As4ReceiptSignal
    {
        return new As4ReceiptSignal(
            messageId:      'rcpt-001@test.local',
            refToMessageId: self::MSG_ID,
            timestamp:      new \DateTimeImmutable(),
        );
    }

    private function errorSignal(As4ErrorSeverity $severity = As4ErrorSeverity::Failure): As4ErrorSignal
    {
        return new As4ErrorSignal(
            messageId:        'err-001@test.local',
            refToMessageId:   self::MSG_ID,
            timestamp:        new \DateTimeImmutable(),
            category:         As4ErrorCategory::Content,
            errorCode:        'EBMS:0004',
            severity:         $severity,
            shortDescription: 'Other',
            description:      'Unknown error',
        );
    }

    public function testConstructorStoresMessageId(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 200, null, true);
        $this->assertSame(self::MSG_ID, $result->messageId);
    }

    public function testConstructorStoresHttpStatus(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 202, null, true);
        $this->assertSame(202, $result->httpStatus);
    }

    public function testConstructorStoresSignal(): void
    {
        $signal = $this->receiptSignal();
        $result = new As4DispatchResult(self::MSG_ID, 200, $signal, true);
        $this->assertSame($signal, $result->signal);
    }

    public function testConstructorStoresNullSignal(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 202, null, true);
        $this->assertNull($result->signal);
    }

    public function testConstructorStoresSuccess(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 500, null, false);
        $this->assertFalse($result->success);
    }

    public function testHasErrorReturnsTrueForFailureErrorSignal(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 200, $this->errorSignal(As4ErrorSeverity::Failure), true);
        $this->assertTrue($result->hasError());
    }

    public function testHasErrorReturnsFalseForWarningErrorSignal(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 200, $this->errorSignal(As4ErrorSeverity::Warning), true);
        $this->assertFalse($result->hasError());
    }

    public function testHasErrorReturnsFalseForReceiptSignal(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 200, $this->receiptSignal(), true);
        $this->assertFalse($result->hasError());
    }

    public function testHasErrorReturnsFalseForNullSignal(): void
    {
        $result = new As4DispatchResult(self::MSG_ID, 202, null, true);
        $this->assertFalse($result->hasError());
    }
}
