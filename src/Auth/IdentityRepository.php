<?php

declare(strict_types=1);

namespace App\Auth;

use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Identity
 * @extends Select\Repository<TEntity>
 */
final class IdentityRepository extends Select\Repository implements IdentityRepositoryInterface
{
    /**
     * @param EntityWriter $entityWriter
     * @param Select<TEntity> $select
     */
    public function __construct(private readonly EntityWriter $entityWriter, Select $select)
    {
        parent::__construct($select);
    }

    /**
     * @param string $id
     * @return Identity|null
     */
    #[\Override]
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
