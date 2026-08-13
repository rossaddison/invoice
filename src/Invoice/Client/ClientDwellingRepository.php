<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use Cycle\ORM\Select;

/**
 * Client-Dwelling relationship query, split out of ClientRepository purely to keep that class under
 * SonarQube's php:S1448 method-count ceiling (20 methods) — the same reasoning already documented for
 * MolliePaymentController/PaystackPaymentController splitting out of PaymentInformationController. Not
 * Client's Cycle-ORM-designated repository (that's still ClientRepository, per Client's own
 * #[Entity(repository: ...)] attribute) — wired explicitly in config/common/di/cycle.php, following the
 * same explicit-Select pattern already used for CycleOrmAs4MessageRepository.
 *
 * @template TEntity of Client
 * @extends Select\Repository<TEntity>
 */
final class ClientDwellingRepository extends Select\Repository
{
    /**
     * Every dwelling_id currently claimed by a Client — the "already occupied" side of the worker
     * canvassing dropdown's unclaimed-Dwelling anti-join, composed at the service layer
     * ({@see \App\Invoice\Dwelling\DwellingService::repoUnclaimedDwellings()}).
     *
     * @return list<int>
     */
    public function repoClaimedDwellingIds(): array
    {
        $query = $this->select()
            ->where(['dwelling_id' => ['not' => null]]);
        $ids = [];
        /** @var Client $client */
        foreach ($query as $client) {
            $dwellingId = $client->getDwellingId();
            if ($dwellingId !== null) {
                $ids[] = $dwellingId;
            }
        }
        return $ids;
    }
}
