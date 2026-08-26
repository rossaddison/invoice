<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Ubl;

use App\Invoice\Ubl\OrderResponseCode;
use App\Invoice\Ubl\OrderResponseLineStatusCode;
use PHPUnit\Framework\TestCase;

final class OrderResponseCodeTest extends TestCase
{
    public function testAllLinesAcceptedDerivesAccepted(): void
    {
        $this->assertSame(
            OrderResponseCode::Accepted,
            OrderResponseCode::deriveFromLineStatusCodes(
                OrderResponseLineStatusCode::Accepted,
                OrderResponseLineStatusCode::Accepted,
            ),
        );
    }

    public function testAllLinesRejectedDerivesRejected(): void
    {
        $this->assertSame(
            OrderResponseCode::Rejected,
            OrderResponseCode::deriveFromLineStatusCodes(
                OrderResponseLineStatusCode::Rejected,
                OrderResponseLineStatusCode::Rejected,
            ),
        );
    }

    public function testMixedAcceptedAndRejectedDerivesAcceptedWithChanges(): void
    {
        $this->assertSame(
            OrderResponseCode::AcceptedWithChanges,
            OrderResponseCode::deriveFromLineStatusCodes(
                OrderResponseLineStatusCode::Accepted,
                OrderResponseLineStatusCode::Rejected,
            ),
        );
    }

    public function testASingleChangedLineDerivesAcceptedWithChanges(): void
    {
        $this->assertSame(
            OrderResponseCode::AcceptedWithChanges,
            OrderResponseCode::deriveFromLineStatusCodes(
                OrderResponseLineStatusCode::Accepted,
                OrderResponseLineStatusCode::Changed,
            ),
        );
    }

    public function testASingleAcceptedLineDerivesAccepted(): void
    {
        $this->assertSame(
            OrderResponseCode::Accepted,
            OrderResponseCode::deriveFromLineStatusCodes(OrderResponseLineStatusCode::Accepted),
        );
    }

    public function testNoLinesFallsBackToAcceptedWithChanges(): void
    {
        $this->assertSame(OrderResponseCode::AcceptedWithChanges, OrderResponseCode::deriveFromLineStatusCodes());
    }
}
