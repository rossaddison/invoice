<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvAuditLog;

use App\Invoice\InvAuditLog\InvAuditFieldDiffer;
use Testo\Assert;
use Testo\Test;

#[Test]
final class InvAuditFieldDifferTest
{
    public function returnsEmptyArrayWhenNothingDiffers(): void
    {
        $differ = new InvAuditFieldDiffer();

        $before = ['terms' => 'Net 30', 'discount_amount' => 0.0];
        $after = ['terms' => 'Net 30', 'discount_amount' => 0.0];

        Assert::same([], $differ->diff($before, $after));
    }

    public function reportsOnlyTheFieldsThatActuallyChanged(): void
    {
        $differ = new InvAuditFieldDiffer();

        $before = ['terms' => 'Net 30', 'discount_amount' => 0.0, 'note' => 'same'];
        $after = ['terms' => 'Net 60', 'discount_amount' => 0.0, 'note' => 'same'];

        Assert::same(
            ['terms' => ['old' => 'Net 30', 'new' => 'Net 60']],
            $differ->diff($before, $after),
        );
    }

    public function reportsEveryChangedFieldWhenMultipleDiffer(): void
    {
        $differ = new InvAuditFieldDiffer();

        $before = ['terms' => 'Net 30', 'client_id' => 1, 'status_id' => 1];
        $after = ['terms' => 'Net 60', 'client_id' => 2, 'status_id' => 1];

        Assert::same(
            [
                'terms' => ['old' => 'Net 30', 'new' => 'Net 60'],
                'client_id' => ['old' => 1, 'new' => 2],
            ],
            $differ->diff($before, $after),
        );
    }

    public function treatsAMissingBeforeKeyAsNull(): void
    {
        $differ = new InvAuditFieldDiffer();

        Assert::same(
            ['note' => ['old' => null, 'new' => 'first note']],
            $differ->diff([], ['note' => 'first note']),
        );
    }

    public function ignoresAFieldPresentOnlyInBefore(): void
    {
        // saveInv() only ever snapshots fields it actually controls on both
        // sides, but the differ itself is deliberately permissive here: a
        // key $after doesn't mention at all is never reported, even if
        // $before had a value for it.
        $differ = new InvAuditFieldDiffer();

        Assert::same([], $differ->diff(['note' => 'stale'], []));
    }

    public function distinguishesNullFromAnEmptyStringAndFromZero(): void
    {
        $differ = new InvAuditFieldDiffer();

        Assert::same(
            ['note' => ['old' => null, 'new' => '']],
            $differ->diff(['note' => null], ['note' => '']),
        );
        Assert::same(
            ['discount_amount' => ['old' => null, 'new' => 0.0]],
            $differ->diff(['discount_amount' => null], ['discount_amount' => 0.0]),
        );
    }
}
