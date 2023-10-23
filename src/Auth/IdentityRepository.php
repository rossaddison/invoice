<?php

declare(strict_types=1);

namespace App\Auth;

use Cycle\ORM\Select;
use Throwable;
use App\Auth\Identity;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Identity
 * @extends Select\Repository<TEntity>
 */
final class IdentityRepository extends Select\Repository implements IdentityRepositoryInterface
{
    /**
     * 
     * @param EntityWriter $entityWriter
     * @param Select<TEntity> $select
     */
    public function __construct(private EntityWriter $entityWriter, Select $select)
    {
        parent::__construct($select);
    }
    
    /**
     * 
     * @param string $id
     * @return Identity|null
     */
    public function findIdentity(string $id): ?Identity
    {
        return $this->findOne(['user_id' => $id]);
    }

    /**
     * @throws Throwable
     */
    public function save(Identity $identity): void
    {
        $this->entityWriter->write([$identity]);
    }
}
