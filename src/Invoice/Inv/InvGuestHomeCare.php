<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Dwelling\DwellingRepository;
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

    public function crewFrontBack(): bool
    {
        return $this->crew->frontBack();
    }

    public function leadWorkerId(): ?int
    {
        return $this->access->leadWorkerId();
    }
}
