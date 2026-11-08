<?php

declare(strict_types=1);

namespace App\Invoice\WorkerCompany;

use App\Infrastructure\Persistence\Worker\Worker;
use App\Infrastructure\Persistence\WorkerCompany\WorkerCompany;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Worker\WorkerRepository;

/**
 * Registers workers under the limited businesses. A self-employed worker has to earn from more
 * than one business, so a worker is normally linked to several: the table is a pure link between
 * worker.id and company.id, and {@see self::syncCompanies()} makes the worker's stored links
 * exactly the set the worker form ticked. Nothing here assumes how many businesses there are
 * (one, two or more), or that a worker has any beyond the default.
 *
 * **The default company.** Every worker belongs to the default company, which is the app's
 * current company (`company.current`, the same company that prints on the invoices; which one is
 * current is chosen per run). That membership is a rule here, not stored rows: so existing and
 * future workers all belong to it, nothing needs a backfill, and it follows the current company
 * when the run's company changes. Only the businesses a worker is registered under *in addition*
 * are stored in the table, and {@see self::syncCompanies()} never removes or duplicates the
 * default.
 */
final readonly class WorkerCompanyService
{
    public function __construct(
        private WorkerCompanyRepositoryInterface $repository,
        private WorkerRepository $workers,
        private CompanyRepository $companies,
    ) {
    }

    /**
     * The default company: the one every worker belongs to (the current company), if there is one.
     */
    public function defaultCompanyId(): ?int
    {
        return $this->companies->repoCompanyActivequery()?->reqId();
    }

    /**
     * Links the worker to the business. Linking the same pair again changes nothing.
     */
    public function link(int $workerId, int $companyId): WorkerCompany
    {
        $existing = $this->repository->repoLinkquery($workerId, $companyId);
        if (null !== $existing) {
            return $existing;
        }
        $link = new WorkerCompany($workerId, $companyId);
        $this->repository->save($link);
        return $link;
    }

    /**
     * Removes one link, if it exists.
     */
    public function unlink(int $workerId, int $companyId): void
    {
        $existing = $this->repository->repoLinkquery($workerId, $companyId);
        if (null !== $existing) {
            $this->repository->delete($existing);
        }
    }

    /**
     * Removes every link a worker has (for example when the worker is deleted).
     */
    public function unlinkWorker(int $workerId): void
    {
        foreach ($this->repository->repoWorkerquery($workerId) as $link) {
            $this->repository->delete($link);
        }
    }

    /**
     * Applies the worker form's ticked businesses so the worker ends up linked to exactly those
     * (besides the default, which every worker has regardless): links not ticked are removed,
     * ticked ones added, ones already there left alone. Anything that is not a positive id is
     * ignored, the default is never added or removed (a link to a company that later becomes the
     * default is kept, so it is still there if the default changes again), and an empty set means
     * no business beyond the default.
     *
     * @param iterable<array-key, int|string> $choices the submitted company ids
     */
    public function syncCompanies(int $workerId, iterable $choices): void
    {
        $default = $this->defaultCompanyId();
        $wanted = [];
        foreach ($choices as $choice) {
            $companyId = (int) $choice;
            if ($companyId > 0 && $companyId !== $default) {
                $wanted[$companyId] = true;
            }
        }
        foreach ($this->repository->repoWorkerquery($workerId) as $link) {
            $companyId = $link->reqCompanyId();
            if ($companyId !== $default && !isset($wanted[$companyId])) {
                $this->repository->delete($link);
            }
        }
        foreach (array_keys($wanted) as $companyId) {
            $this->link($workerId, $companyId);
        }
    }

    /**
     * Every business the worker belongs to: the default first, then the others they are
     * registered under, lowest id first.
     *
     * @return list<int>
     */
    public function companyIdsFor(int $workerId): array
    {
        return $this->withDefault($this->storedCompanyIdsFor($workerId), $this->defaultCompanyId());
    }

    /**
     * Only the businesses the worker is explicitly registered under in addition to the default:
     * what the worker form ticks.
     *
     * @return list<int>
     */
    public function storedCompanyIdsFor(int $workerId): array
    {
        $default = $this->defaultCompanyId();
        return array_values(array_filter(
            array_map(
                static fn (WorkerCompany $link): int => $link->reqCompanyId(),
                $this->repository->repoWorkerquery($workerId)
            ),
            static fn (int $companyId): bool => $companyId !== $default
        ));
    }

    /**
     * The workers who belong to the business. For the default company that is every worker.
     *
     * @return list<int>
     */
    public function workerIdsFor(int $companyId): array
    {
        $ids = array_map(
            static fn (WorkerCompany $link): int => $link->reqWorkerId(),
            $this->repository->repoCompanyquery($companyId)
        );
        if ($companyId === $this->defaultCompanyId()) {
            $ids = array_merge($ids, $this->allWorkerIds());
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    /**
     * Every worker, with the businesses each belongs to (the default included, so a worker with no
     * stored link still has one).
     *
     * @return array<int, list<int>> worker id => business ids, the default first
     */
    public function companyIdsByWorker(): array
    {
        $stored = [];
        foreach ($this->repository->repoAllquery() as $link) {
            $stored[$link->reqWorkerId()][] = $link->reqCompanyId();
        }
        $default = $this->defaultCompanyId();
        $map = [];
        foreach ($this->allWorkerIds() as $workerId) {
            $map[$workerId] = $this->withDefault($stored[$workerId] ?? [], $default);
        }
        return $map;
    }

    /**
     * @param list<int> $stored
     * @return list<int> the default (when there is one) first, then the rest without duplicates, ascending
     */
    private function withDefault(array $stored, ?int $default): array
    {
        $others = array_values(array_unique(array_filter($stored, static fn (int $id): bool => $id !== $default)));
        sort($others);
        return null === $default ? $others : [$default, ...$others];
    }

    /**
     * @return list<int>
     */
    private function allWorkerIds(): array
    {
        $ids = [];
        /** @var iterable<array-key, Worker> $workers */
        $workers = $this->workers->findAllPreloaded()->read();
        foreach ($workers as $worker) {
            $ids[] = $worker->reqId();
        }
        return $ids;
    }
}
