<?php

declare(strict_types=1);

namespace App\Api;

use App\Infrastructure\Persistence\ApiClient\ApiClient;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of ApiClient
 * @extends Select\Repository<TEntity>
 */
final class ApiClientRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @throws Throwable
     */
    public function save(ApiClient $client): void
    {
        $this->entityWriter->write([$client]);
    }

    /**
     * Looks a caller up by the sha256 hash of the plaintext key it sent in
     * the `X-Api-Key` header — never the plaintext itself, which is never
     * stored. A disabled client (`enabled = false`) never matches, so
     * revoking a key is a single flag flip, not a rotation of every other
     * client's key.
     */
    public function findActiveByKeyHash(string $keyHash): ?ApiClient
    {
        /** @var ApiClient|null */
        return $this->select()
            ->where(['key_hash' => $keyHash, 'enabled' => true])
            ->fetchOne();
    }
}
