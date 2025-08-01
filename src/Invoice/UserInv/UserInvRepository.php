<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use Cycle\ORM\Select;
use App\Invoice\Entity\UserInv;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of UserInv
 * @extends Select\Repository<TEntity>
 */
final class UserInvRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     *
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get userinvs  without filter
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
        return Sort::only(['user_id','name'])->withOrder(['user_id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|UserInv|null $userinv
     * @throws Throwable
     */
    public function save(array|UserInv|null $userinv): void
    {
        $this->entityWriter->write([$userinv]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|UserInv|null $userinv
     * @throws Throwable
     */
    public function delete(array|UserInv|null $userinv): void
    {
        $this->entityWriter->delete([$userinv]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['user_id','name'])
                ->withOrder(['user_id' => 'asc']),
        );
    }

    public function repoUserInvquery(string $id): UserInv|null
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    public function repoUserInvcount(string $id): int
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->count();
    }

    public function repoUserInvUserIdquery(string $user_id): UserInv|null
    {
        $query = $this->select()
                      ->where(['user_id' => $user_id]);
        return  $query->fetchOne() ?: null;
    }

    public function repoUserInvUserIdcount(string $user_id): int
    {
        return $this->select()
                      ->where(['user_id' => $user_id])
                      ->count();
    }

    /**
     * Get Userinv with filter active
     *
     * @psalm-return EntityReader
     */
    public function findAllWithActive(int $active): EntityReader
    {
        if ($active < 2) {
            $query = $this->select()
                   ->where(['active' => $active]);
            return $this->prepareDataReader($query);
        }
        return $this->findAllPreloaded();
    }

    /**
     * Get Userinv with filter all_clients
     *
     * @psalm-return EntityReader
     */

    // Find users that have access to all clients
    public function findAllWithAllClients(): EntityReader
    {
        $query = $this->select()
                      ->where(['all_clients' => 1]);
        return $this->prepareDataReader($query);
    }

    /**
     * @return EntityReader
     */
    public function filterUserInvs(string $login): EntityReader
    {
        $query = $this->select()
                      ->load('user')
                      ->where(['user.login' => $login]);
        return $this->prepareDataReader($query);
    }

    // Find users that have access to all clients
    public function countAllWithAllClients(): int
    {
        $query = $this->select()
                      ->where(['all_clients' => 1]);
        return $query->count();
    }
}
