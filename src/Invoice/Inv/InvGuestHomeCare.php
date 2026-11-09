<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\HomeCareSchedule\RunCalendarService;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Worker\WorkerRepository;

/**
 * The HomeCare collaborators inv/guest needs, bundled so Trait\Guest's
 * guest()/renderGuestView() stay within SonarQube S107 (≤ 7 parameters).
 */
final readonly class InvGuestHomeCare
{
    public function __construct(
        public DwellingRepository $dwR,
        public InvWorkerAllocationService $allocations,
        private WorkerInvAccess $access,
        private WorkerRepository $wR,
        private HomeCareCrew $crew,
        private RunCalendarService $calendar,
        private CategorySecondaryRepository $csR,
        private GuestPullPointOrder $pullPointOrder,
    ) {
    }

    /**
     * Active workers, for naming allocations and for the lead's allocate form.
     *
     * @return array<int, string> worker id => "Firstname Lastname"
     */
    public function workerNames(): array
    {
        $names = [];
        foreach ($this->wR->findAllActive() as $worker) {
            $names[$worker->reqId()] = trim($worker->getFirstname() . ' ' . $worker->getLastname());
        }
        return $names;
    }

    /**
     * Today's crew (the lead's chips) and the Front/Back mode.
     *
     * @return list<int>
     */
    public function crewIds(): array
    {
        return $this->crew->workerIds();
    }

    /**
     * Today's run from the run calendar, for the lead's Start button: its name and starting postcode
     * (null when the calendar has no run today).
     *
     * @return array{name: string, postcode: string|null}|null
     */
    public function todayRun(): ?array
    {
        $day = $this->calendar->forDate(date('Y-m-d'));
        if (null === $day) {
            return null;
        }
        $run = $this->csR->repoCategorySecondaryQuery($day->getCategorySecondaryId());
        if (null === $run) {
            return null;
        }
        $postcode = $run->getStartPostcode();
        return ['name' => $run->getName() ?? '', 'postcode' => '' === $postcode ? null : $postcode];
    }

    /**
     * The worker's invoices under their pull points (see {@see GuestPullPointOrder}).
     *
     * @param iterable<\App\Infrastructure\Persistence\Inv\Inv> $invs
     * @return \Yiisoft\Data\Reader\Iterable\IterableDataReader<int<0, max>, \App\Infrastructure\Persistence\Inv\Inv>
     */
    public function byPullPoint(iterable $invs): \Yiisoft\Data\Reader\Iterable\IterableDataReader
    {
        return $this->pullPointOrder->order($invs);
    }

    public function crewFrontBack(): bool
    {
        return $this->crew->frontBack();
    }

    public function leadWorkerId(): ?int
    {
        return $this->access->leadWorkerId();
    }
}
