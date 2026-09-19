<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use Testo\Assert;
use Testo\Test;

#[Test]
final class InvStatusForwardOnlyTest
{
    private function persisted(int $status): Inv
    {
        $inv = new Inv(status_id: $status);
        $inv->setId(1);
        return $inv;
    }

    public function sentCannotRevertToDraft(): void
    {
        $inv = $this->persisted(2);
        $inv->setStatusId(1);
        Assert::same($inv->reqStatusId(), 2);
    }

    public function viewedCannotRevertToSentOrDraft(): void
    {
        $inv = $this->persisted(3);
        $inv->setStatusId(2);
        Assert::same($inv->reqStatusId(), 3);
        $inv->setStatusId(1);
        Assert::same($inv->reqStatusId(), 3);
    }

    public function paidCannotRevertToAnEarlierStage(): void
    {
        $inv = $this->persisted(4);
        $inv->setStatusId(2);
        Assert::same($inv->reqStatusId(), 4);
    }

    public function draftMovesForwardAndDunningStagesAreUnrestricted(): void
    {
        $inv = $this->persisted(1);
        $inv->setStatusId(2);
        Assert::same($inv->reqStatusId(), 2);
        $inv->setStatusId(5);
        Assert::same($inv->reqStatusId(), 5);
        $inv->setStatusId(4);
        Assert::same($inv->reqStatusId(), 4);
    }

    public function unpersistedInvoicesAreNotRestricted(): void
    {
        $inv = new Inv(status_id: 3);
        $inv->setStatusId(1);
        Assert::same($inv->reqStatusId(), 1);
    }
}
