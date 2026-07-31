<?php

declare(strict_types=1);

namespace App\Invoice\Worker;

use App\Infrastructure\Persistence\Worker\Worker;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Worker
 * @extends Select\Repository<TEntity>
 */
final class WorkerRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get workers without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * Active workers, for the inv/index allocation dropdown.
     *
     * @return array<int, Worker>
     * @psalm-return array<int, TEntity>
     */
    public function findAllActive(): array
    {
        return $this->select()
            ->where(['active' => true])
            ->orderBy(['firstname' => 'asc'])
            ->fetchAll();
    }

    /**
     * Active workers not yet linked to a login, for the userinv/index
     * worker-assignment select — see UserInvRoleTrait::assignWorkerRole().
     *
     * @return array<int, Worker>
     * @psalm-return array<int, TEntity>
     */
    public function findAllActiveUnlinked(): array
    {
        return $this->select()
            ->where(['active' => true, 'user_id' => null])
            ->orderBy(['firstname' => 'asc'])
            ->fetchAll();
    }

    /**
     * @return Worker|null
     * @psalm-return TEntity|null
     */
    public function findByUserId(int $user_id): ?Worker
    {
        return $this->select()
            ->where(['user_id' => $user_id])
            ->fetchOne() ?: null;
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Worker|null $worker
     * @throws Throwable
     */
    public function save(array|Worker|null $worker): void
    {
        $this->entityWriter->write([$worker]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Worker|null $worker
     * @throws Throwable
     */
    public function delete(array|Worker|null $worker): void
    {
        $this->entityWriter->delete([$worker]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'firstname', 'lastname'])
                ->withOrder(['firstname' => 'asc']),
        );
    }

    public function repoCount(int $worker_id): int
    {
        return $this->select()
                      ->where(['id' => $worker_id])
                      ->count();
    }

    /**
     * @return Worker|null
     * @psalm-return TEntity|null
     */
    public function repoWorkerquery(int $worker_id): ?Worker
    {
        $query = $this
            ->select()
            ->where(['id' => $worker_id]);
        return $query->fetchOne() ?: null;
    }
}
