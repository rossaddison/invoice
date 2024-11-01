<?php

declare(strict_types=1);

namespace App\Auth;

use Cycle\ORM\Select;
use Throwable;
use App\Auth\Token;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Token
 * @extends Select\Repository<TEntity>
 */
final class TokenRepository extends Select\Repository implements IdentityWithTokenRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(private EntityWriter $entityWriter, Select $select)
    {
        parent::__construct($select);
    }

    /**
     * @param string $token
     * @param string $type
     * @return Identity|null
     */
    public function findIdentityByToken(string $token, string $type = null): ?Identity
    {
        $tokenRecord =  $this->findOne(['token' => $token, 'type' => $type]);
        return null !== $tokenRecord ? $tokenRecord->getIdentity() : null;
    }

    /**
     * @param string $identityId
     * @param string $type
     * @return Token|null
     */
    public function findTokenByIdentityIdAndType(string $identityId, string $type = null): ?Token
    {
        $tokenRecord = $this->findOne(['identity_id' => $identityId, 'type' => $type]);
        return null !== $tokenRecord ? $tokenRecord : null;
    }

    /**
     *
     * @param Token $token
     * @return void
     */
    public function save(Token $token): void
    {
        $this->entityWriter->write([$token]);
    }
}
