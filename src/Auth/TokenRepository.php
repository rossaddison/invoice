<?php

declare(strict_types=1);

namespace App\Auth;

use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\Token\Token;
use Cycle\ORM\Select;
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
    public function __construct(private readonly EntityWriter $entityWriter, Select $select)
    {
        parent::__construct($select);
    }

    /**
     * @param string $token
     * @param string|null $type
     * @return Identity|null
     */
    #[\Override]
    public function findIdentityByToken(string $token, ?string $type = null): ?Identity
    {
        $tokenRecord = $this->findOne(['token' => $token, 'type' => $type]);
        return null !== $tokenRecord ? $tokenRecord->getIdentity() : null;
    }

    /**
     * @param string $token
     * @param string|null $type
     * @return Token|null
     */
    public function findTokenByTokenAndType(string $token, ?string $type = null): ?Token
    {
        $tokenRecord = $this->findOne(['token' => $token, 'type' => $type]);
        return $tokenRecord ?? null;
    }

    /**
     * Token-only lookup, no type filter — used where the caller doesn't
     * know the exact type value up front (e.g. WebshopOrderLoginController,
     * whose token type embeds a variable invoice id it needs to *read back*
     * from the found row, not supply as a search filter). `token` values
     * are `Yiisoft\Security\Random::string(32)`-generated, so a collision
     * across different token types is not a practical concern.
     *
     * @param string $token
     * @return Token|null
     */
    public function findTokenByToken(string $token): ?Token
    {
        return $this->findOne(['token' => $token]);
    }

    /**
     * @param int $identityId
     * @param string|null $type
     * @return Token|null
     */
    public function findTokenByIdentityIdAndType(int $identityId, ?string $type = null): ?Token
    {
        $tokenRecord = $this->findOne(['identity_id' => $identityId, 'type' => $type]);
        return $tokenRecord ?? null;
    }

    /**
     * @param Token $token
     */
    public function save(Token $token): void
    {
        $this->entityWriter->write([$token]);
    }
}
