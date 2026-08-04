<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of GatewayStatus
 * @extends Select\Repository<TEntity>
 */
final class GatewayStatusRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|GatewayStatus|null $status
     * @throws Throwable
     */
    public function save(array|GatewayStatus|null $status): void
    {
        $this->entityWriter->write([$status]);
    }

    public function findByGatewayKeyquery(string $gatewayKey): ?GatewayStatus
    {
        return $this->select()
            ->where(['gateway_key' => $gatewayKey])
            ->fetchOne() ?: null;
    }

    /**
     * @return list<GatewayStatus>
     */
    public function findAllquery(): array
    {
        return $this->select()
            ->orderBy('name', 'ASC')
            ->fetchAll();
    }
}
