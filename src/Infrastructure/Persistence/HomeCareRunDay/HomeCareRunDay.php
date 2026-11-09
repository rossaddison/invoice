<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\HomeCareRunDay;

use App\Invoice\HomeCareSchedule\HomeCareRunDayRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\PrimaryKey;

/**
 * One day of the HomeCare run calendar: which run is cleaned on a date, and by which crew.
 *
 * The primary key is the date itself (a natural key, `Y-m-d`), so the database allows one run
 * per day. A date with no row is an open day: a Wednesday kept free for a weather swap, or a
 * rest day. The crew is a comma-separated list of worker ids (never more than three, like
 * HomeCareCrew), in the order the schedule gave them; the run is a category_secondary id.
 *
 * Plain scalar columns, no `#[BelongsTo]` relations and no `#[Index]`, matching the other
 * HomeCare entities.
 */
#[Entity(repository: HomeCareRunDayRepository::class)]
#[Table(primary: new PrimaryKey(columns: ['run_date']))]
class HomeCareRunDay
{
    public function __construct(
        #[Column(type: 'string(10)', nullable: false)]
        private string $run_date,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $category_secondary_id,
        #[Column(type: 'string(30)', nullable: false, default: '')]
        private string $crew_worker_ids = '',
        #[Column(type: 'string(255)', nullable: true)]
        private ?string $notes = null,
    ) {
    }

    /**
     * @return string Y-m-d
     */
    public function getRunDate(): string
    {
        return $this->run_date;
    }

    public function getCategorySecondaryId(): int
    {
        return $this->category_secondary_id;
    }

    /**
     * @return list<int> the crew's worker ids, in the order given
     */
    public function getCrewWorkerIds(): array
    {
        $ids = [];
        foreach (explode(',', $this->crew_worker_ids) as $raw) {
            $id = (int) trim($raw);
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Changes the day's run and crew in place (the date, being the key, never changes).
     *
     * @param list<int> $crewWorkerIds
     */
    public function reschedule(int $categorySecondaryId, array $crewWorkerIds, ?string $notes = null): void
    {
        $this->category_secondary_id = $categorySecondaryId;
        $this->crew_worker_ids = implode(',', array_values(array_unique(array_filter($crewWorkerIds, static fn (int $id): bool => $id > 0))));
        $this->notes = $notes;
    }
}
