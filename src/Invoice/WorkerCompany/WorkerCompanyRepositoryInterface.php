<?php

declare(strict_types=1);

namespace App\Invoice\WorkerCompany;

use App\Infrastructure\Persistence\WorkerCompany\WorkerCompany;

interface WorkerCompanyRepositoryInterface
{
    public function save(array|WorkerCompany|null $workerCompany): void;

    public function delete(array|WorkerCompany|null $workerCompany): void;

    /**
     * The one link between a worker and a business, if it exists.
     */
    public function repoLinkquery(int $workerId, int $companyId): ?WorkerCompany;

    /**
     * Every business a worker is registered under.
     *
     * @return list<WorkerCompany>
     */
    public function repoWorkerquery(int $workerId): array;

    /**
     * Every worker registered under a business.
     *
     * @return list<WorkerCompany>
     */
    public function repoCompanyquery(int $companyId): array;

    /**
     * Every link.
     *
     * @return list<WorkerCompany>
     */
    public function repoAllquery(): array;
}
