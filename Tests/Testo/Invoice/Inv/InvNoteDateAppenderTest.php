<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvNoteDateAppender;
use App\Invoice\Inv\InvRepository;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * Quick Pay's "Append date to Note" option: the inv/index tooltip shows the invoice note, so the payment
 * date is added to it.
 */
#[Test]
final class InvNoteDateAppenderTest
{
    public function aDateIsAddedAfterAnExistingNote(): void
    {
        Assert::same(InvNoteDateAppender::append('WK 2ND NOV', '2026-11-21'), 'WK 2ND NOV 2026-11-21');
    }

    public function anEmptyNoteBecomesJustTheDate(): void
    {
        Assert::same(InvNoteDateAppender::append('', '2026-11-21'), '2026-11-21');
    }

    public function aMissingNoteBecomesJustTheDate(): void
    {
        Assert::same(InvNoteDateAppender::append(null, '2026-11-21'), '2026-11-21');
    }

    public function aWhitespaceOnlyNoteBecomesJustTheDate(): void
    {
        Assert::same(InvNoteDateAppender::append("  \n ", '2026-11-21'), '2026-11-21');
    }

    public function surroundingWhitespaceIsTrimmed(): void
    {
        Assert::same(InvNoteDateAppender::append('  paid  ', ' 2026-11-21 '), 'paid 2026-11-21');
    }

    public function theSameDateIsNotAppendedTwice(): void
    {
        Assert::same(
            InvNoteDateAppender::append('WK 2ND NOV 2026-11-21', '2026-11-21'),
            'WK 2ND NOV 2026-11-21',
        );
    }

    public function aDifferentDateIsAppendedAfterAnEarlierOne(): void
    {
        Assert::same(
            InvNoteDateAppender::append('WK 2ND NOV 2026-11-14', '2026-11-21'),
            'WK 2ND NOV 2026-11-14 2026-11-21',
        );
    }

    public function anEmptyDateLeavesTheNoteUnchanged(): void
    {
        Assert::same(InvNoteDateAppender::append('WK 2ND NOV', ''), 'WK 2ND NOV');
    }

    public function applyToSetsTheNoteOnTheInvoiceAndSavesIt(): void
    {
        $inv = new Inv();
        $inv->setNote('WK 2ND NOV');
        $repository = TypedMock::of(InvRepository::class);
        $repository->shouldReceive('save')->once()->with($inv);

        InvNoteDateAppender::applyTo($inv, '2026-11-21', $repository);

        Assert::same($inv->getNote(), 'WK 2ND NOV 2026-11-21');
    }

    public function applyToOnAnInvoiceWithNoNoteSetsJustTheDate(): void
    {
        $inv = new Inv();
        $repository = TypedMock::of(InvRepository::class);
        $repository->shouldReceive('save')->once()->with($inv);

        InvNoteDateAppender::applyTo($inv, '2026-11-21', $repository);

        Assert::same($inv->getNote(), '2026-11-21');
    }
}
