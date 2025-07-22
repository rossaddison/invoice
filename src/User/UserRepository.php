<?php

declare(strict_types=1);

namespace App\User;

use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of User
 *
 * @extends Select\Repository<TEntity>
 */
final class UserRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(private EntityWriter $entityWriter, Select $select)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id', 'login'])->withOrder(['id' => 'asc']);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'login'])
                ->withOrder([
                    'id'    => 'desc',
                    'login' => 'desc',
                ]),
        );
    }

    /**
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    public function findAllUsers(array $scope = [], array $orderBy = []): EntityReader
    {
        return new EntityReader($this
            ->select()
            ->where($scope)
            ->orderBy($orderBy));
    }

    /**
     * @psalm-return TEntity|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', $email);
    }

    /**
     * @psalm-return TEntity|null
     */
    public function findById(string $id): ?User
    {
        return $this->findByPK($id);
    }

    /**
     * @psalm-return TEntity|null
     */
    public function findByLogin(string $login): ?User
    {
        return $this->findBy('login', $login);
    }

    /**
     * @psalm-return TEntity|null
     */
    public function findByLoginWithAuthIdentity(string $login): ?User
    {
        return $this
            ->select()
            ->where(['login' => $login])
            ->load('identity')
            ->fetchOne();
    }

    /**
     * @throws \Throwable
     */
    public function save(User $user): void
    {
        $this->entityWriter->write([$user]);
    }

    /**
     * @psalm-return TEntity|null
     */
    private function findBy(string $field, string $value): ?User
    {
        return $this->findOne([$field => $value]);
    }

    public function repoCount(): int
    {
        return $this->select()
            ->count();
    }
}
