<?php

declare(strict_types=1);

namespace App\User;

use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of RecoveryCode
 *
 * @extends Select\Repository<TEntity>
 */
final class RecoveryCodeRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get backups  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    public function findByUser(User $user): EntityReader
    {
        $userId = $user->getId();
        $query  = $this->select()
            ->where(['user_id' => $userId]);

        return $this->prepareDataReader($query);
    }

    public function findByUserCount(User $user): int
    {
        $userId = $user->getId();

        return $this->select()
            ->where(['user_id' => $userId])
            ->count();
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @psalm-param TEntity $backup
     *
     * @throws \Throwable
     */
    public function save(array|RecoveryCode|null $backup): void
    {
        $this->entityWriter->write([$backup]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|RecoveryCode|null $backup): void
    {
        $this->entityWriter->delete([$backup]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoRecoveryCodeLoadedquery(string $id): ?RecoveryCode
    {
        $query = $this->select()->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }
}
