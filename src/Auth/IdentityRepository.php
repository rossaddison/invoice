<?php

declare(strict_types=1);

namespace App\Auth;

use App\Infrastructure\Persistence\Identity\Identity;
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
     * $id is the identity's own primary key, i.e. Identity::getId() — the value
     * Yiisoft\User\CurrentUser::switchIdentity() stores in session at login and
     * passes back here on every subsequent request to restore it. Querying by
     * 'user_id' here (as this used to) doesn't match what getId() stores, so it
     * broke session restoration for any account where identity.id != user_id.
     *
     * @param string $id
     * @return Identity|null
     */
    #[\Override]
    public function findIdentity(string $id): ?Identity
    {
        return $this->findOne(['id' => $id]);
    }

    /**
     * @throws Throwable
     */
    public function save(Identity $identity): void
    {
        $this->entityWriter->write([$identity]);
    }
}
