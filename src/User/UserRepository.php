<?php

declare(strict_types=1);

namespace App\User;

use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of User
 * @extends Select\Repository<TEntity>
 */
final class UserRepository extends Select\Repository
{    
    /**
     * 
     * @param EntityWriter $entityWriter
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
                             'id' => 'desc',
                             'login' => 'desc'
                ])
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

    /**
     * 
     * @param array $scope
     * @param array $orderBy
     * @return EntityReader
     */
    public function findAllUsers(array $scope = [], array $orderBy = []): EntityReader
    {
        return new EntityReader($this
            ->select()
            ->where($scope)
            ->orderBy($orderBy));
    }

    /**
     * @param string $id
     *
     * @return User|null
     * @psalm-return TEntity|null
     */
    public function findById(string $id): User|null
    {
        return $this->findByPK($id);
    }

    /**
     * @param string $login
     *
     * @return User|null
     * @psalm-return TEntity|null
     */
    public function findByLogin(string $login): User|null
    {
        return $this->findBy('login', $login);
    }

    /**
     * @param string $login
     * @return User|null
     * @psalm-return TEntity|null
     */
    public function findByLoginWithAuthIdentity(string $login): User|null
    {
        return $this
            ->select()
            ->where(['login' => $login])
            ->load('identity')
            ->fetchOne();
    }

    /**
     * @throws Throwable
     */
    public function save(User $user): void
    {
        $this->entityWriter->write([$user]);
    }
    
    /**
     * @param string $field
     * @param string $value
     * @return User|null
     * @psalm-return TEntity|null
     */
    private function findBy(string $field, string $value): User|null
    {
        return $this->findOne([$field => $value]);
    }
}
