<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareRunDay\HomeCareRunDay;
use DateTimeImmutable;

/**
 * The HomeCare run calendar: which run is cleaned on which date, and by which crew. A date with no
 * entry is an open day (a Wednesday kept free for a weather swap, or a rest day).
 */
final readonly class RunCalendarService
{
    /** A crew is never more than three (see HomeCareCrew::MAX_SIZE). */
    private const int MAX_CREW = 3;

    public function __construct(private HomeCareRunDayRepositoryInterface $repository)
    {
    }

    /**
     * The run scheduled on the date (Y-m-d), if any.
     */
    public function forDate(string $date): ?HomeCareRunDay
    {
        return $this->repository->repoDatequery($date);
    }

    /**
     * Every scheduled day of the month, earliest first.
     *
     * @return list<HomeCareRunDay>
     */
    public function forMonth(int $year, int $month): array
    {
        $from = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        return $this->repository->repoRangequery($from->format('Y-m-d'), $from->modify('+1 month')->format('Y-m-d'));
    }

    /**
     * Schedules the run (and crew) on the date, replacing whatever was there. Returns whether the day
     * is new (false: an existing entry was changed). The crew is cut to three distinct workers.
     *
     * @param list<int> $crewWorkerIds
     */
    public function schedule(string $date, int $categorySecondaryId, array $crewWorkerIds, ?string $notes = null): bool
    {
        $crew = array_slice(array_values(array_unique(array_filter($crewWorkerIds, static fn (int $id): bool => $id > 0))), 0, self::MAX_CREW);
        $existing = $this->repository->repoDatequery($date);
        if (null === $existing) {
            $day = new HomeCareRunDay($date, $categorySecondaryId);
            $day->reschedule($categorySecondaryId, $crew, $notes);
            $this->repository->save($day);
            return true;
        }
        $existing->reschedule($categorySecondaryId, $crew, $notes);
        $this->repository->save($existing);
        return false;
    }

    /**
     * Makes the date an open day again.
     */
    public function clear(string $date): void
    {
        $existing = $this->repository->repoDatequery($date);
        if (null !== $existing) {
            $this->repository->delete($existing);
        }
    }

    /**
     * The crew scheduled for the date, in the order given (empty: no run, or no crew written).
     *
     * @return list<int>
     */
    public function crewFor(string $date): array
    {
        return $this->repository->repoDatequery($date)?->getCrewWorkerIds() ?? [];
    }
}
