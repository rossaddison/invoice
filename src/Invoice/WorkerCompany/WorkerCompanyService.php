<?php

declare(strict_types=1);

namespace App\Invoice\WorkerCompany;

use App\Infrastructure\Persistence\WorkerCompany\WorkerCompany;

/**
 * Registers workers under the limited businesses (self-employed). The table is a pure
 * link between worker.id and company.id, so one worker can in principle be linked to
 * several businesses; the worker form offers one choice and {@see self::syncFromChoice()}
 * keeps exactly that one.
 */
final readonly class WorkerCompanyService
{
    public function __construct(private WorkerCompanyRepositoryInterface $repository)
    {
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
     * Applies the worker form's business choice so the worker ends up linked to exactly
     * that business: an empty or non-positive choice means "no business".
     */
    public function syncFromChoice(int $workerId, string $choice): void
    {
        $companyId = (int) $choice;
        foreach ($this->repository->repoWorkerquery($workerId) as $link) {
            if ($link->reqCompanyId() !== $companyId) {
                $this->repository->delete($link);
            }
        }
        if ($companyId > 0) {
            $this->link($workerId, $companyId);
        }
    }

    /**
     * @return list<int> the businesses the worker is registered under
     */
    public function companyIdsFor(int $workerId): array
    {
        return array_map(
            static fn (WorkerCompany $link): int => $link->reqCompanyId(),
            $this->repository->repoWorkerquery($workerId)
        );
    }

    /**
     * @return list<int> the workers registered under the business
     */
    public function workerIdsFor(int $companyId): array
    {
        return array_map(
            static fn (WorkerCompany $link): int => $link->reqWorkerId(),
            $this->repository->repoCompanyquery($companyId)
        );
    }

    /**
     * @return array<int, list<int>> worker id => the businesses it is registered under
     */
    public function companyIdsByWorker(): array
    {
        $map = [];
        foreach ($this->repository->repoAllquery() as $link) {
            $map[$link->reqWorkerId()][] = $link->reqCompanyId();
        }
        return $map;
    }
}
