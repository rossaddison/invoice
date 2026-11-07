<?php

declare(strict_types=1);

namespace App\Invoice\WorkerCompany;

use App\Infrastructure\Persistence\WorkerCompany\WorkerCompany;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of WorkerCompany
 * @extends Select\Repository<TEntity>
 */
final class WorkerCompanyRepository extends Select\Repository implements WorkerCompanyRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|WorkerCompany|null $workerCompany
     * @throws Throwable
     */
    #[\Override]
    public function save(array|WorkerCompany|null $workerCompany): void
    {
        $this->entityWriter->write([$workerCompany]);
    }

    /**
     * @param array|WorkerCompany|null $workerCompany
     * @throws Throwable
     */
    #[\Override]
    public function delete(array|WorkerCompany|null $workerCompany): void
    {
        $this->entityWriter->delete([$workerCompany]);
    }

    #[\Override]
    public function repoLinkquery(int $workerId, int $companyId): ?WorkerCompany
    {
        return $this->select()
            ->where(['worker_id' => $workerId, 'company_id' => $companyId])
            ->fetchOne() ?: null;
    }

    #[\Override]
    public function repoWorkerquery(int $workerId): array
    {
        return $this->select()
            ->where(['worker_id' => $workerId])
            ->orderBy('company_id', 'ASC')
            ->fetchAll();
    }

    #[\Override]
    public function repoCompanyquery(int $companyId): array
    {
        return $this->select()
            ->where(['company_id' => $companyId])
            ->orderBy('worker_id', 'ASC')
            ->fetchAll();
    }

    #[\Override]
    public function repoAllquery(): array
    {
        return $this->select()
            ->orderBy('worker_id', 'ASC')
            ->orderBy('company_id', 'ASC')
            ->fetchAll();
    }
}
