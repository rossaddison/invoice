<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\HomeCareRunSheetItem;

use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Invoice\Enum\DoNotSendReason;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers HomeCareRunSheetItem, with most of the weight on hasDetectedChange()
 * — the single predicate both the staging-screen query
 * (HomeCareRunSheetItemRepository::repoChangedForRunSheetquery()) and
 * HomeCareRunSheetApplyService::apply() key off, per that method's own
 * docblock. Getting this wrong either hides a real field change from the
 * office, or silently drops a paper-confirmed one during Apply.
 */
#[Test]
final class HomeCareRunSheetItemTest
{
    public function defaultsToUnpersistedAcceptedRowWithNoDetection(): void
    {
        $item = new HomeCareRunSheetItem();

        Assert::false($item->isPersisted());
        Assert::true($item->getAccepted());
        Assert::null($item->getExpectedWorkerId());
        Assert::null($item->getDetectedWorkerId());
        Assert::null($item->getDetectedCompleted());
        Assert::null($item->getDetectedReasonCode());
        Assert::false($item->hasDetectedChange());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new HomeCareRunSheetItem())->reqId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqRunSheetIdThrowsWhenNotSet(): void
    {
        (new HomeCareRunSheetItem())->reqRunSheetId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqInvIdThrowsWhenNotSet(): void
    {
        (new HomeCareRunSheetItem())->reqInvId();
    }

    public function setIdMakesEntityPersisted(): void
    {
        $item = new HomeCareRunSheetItem();
        $item->setId(11);

        Assert::true($item->isPersisted());
        Assert::same(11, $item->reqId());
    }

    public function constructorSetsAllFields(): void
    {
        $item = new HomeCareRunSheetItem(
            run_sheet_id: 5,
            inv_id: 311,
            expected_worker_id: 2,
        );

        Assert::same(5, $item->reqRunSheetId());
        Assert::same(311, $item->reqInvId());
        Assert::same(2, $item->getExpectedWorkerId());
        Assert::true($item->getAccepted());
    }

    public function noDetectedChangeUntilAScanHasBeenRead(): void
    {
        // detected_completed still null (no scan read yet) — even though
        // detected_worker_id already differs from expected here, per the
        // docblock's own explanation of why the gate is detected_completed,
        // not detected_worker_id.
        $item = new HomeCareRunSheetItem(expected_worker_id: 2);

        Assert::false($item->hasDetectedChange());
    }

    public function noChangeWhenDetectedWorkerMatchesExpectedAndCompleted(): void
    {
        $item = new HomeCareRunSheetItem(expected_worker_id: 2);
        $item->setDetection(2, true, null);

        Assert::false($item->hasDetectedChange());
    }

    public function changeWhenDetectedWorkerDiffersFromExpected(): void
    {
        $item = new HomeCareRunSheetItem(expected_worker_id: 2);
        $item->setDetection(9, true, null);

        Assert::true($item->hasDetectedChange());
    }

    public function changeWhenDetectedIncompleteEvenWithNoWorkerDrift(): void
    {
        $item = new HomeCareRunSheetItem(expected_worker_id: 2);
        $item->setDetection(2, false, DoNotSendReason::JobIncomplete->value);

        Assert::true($item->hasDetectedChange());
    }

    public function changeWhenReasonCodeSetEvenIfMarkedCompleted(): void
    {
        // Shouldn't normally happen from a real vision read, but the
        // predicate is an OR of three independent signals — a stray reason
        // code alone is still "changed" and worth surfacing to the office.
        $item = new HomeCareRunSheetItem(expected_worker_id: 2);
        $item->setDetection(2, true, DoNotSendReason::CustomerDispute->value);

        Assert::true($item->hasDetectedChange());
    }

    public function changeWhenWorkerIllegibleButJobMarkedIncomplete(): void
    {
        // The exact bug hasDetectedChange() was refactored to fix (see
        // memory): a null detected_worker_id must not be read as "no
        // change" when the row was genuinely read as incomplete.
        $item = new HomeCareRunSheetItem(expected_worker_id: 2);
        $item->setDetection(null, false, DoNotSendReason::SafetyConcern->value);

        Assert::true($item->hasDetectedChange());
    }

    public function noChangeWhenBothExpectedAndDetectedWorkerAreNull(): void
    {
        $item = new HomeCareRunSheetItem();
        $item->setDetection(null, true, null);

        Assert::false($item->hasDetectedChange());
    }

    public function acceptedDefaultsTrueAndIsSettable(): void
    {
        $item = new HomeCareRunSheetItem();

        Assert::true($item->getAccepted());

        $item->setAccepted(false);
        Assert::false($item->getAccepted());
    }
}
