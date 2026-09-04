<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of PeppolMessage
 * @extends Select\Repository<TEntity>
 */
final class PeppolMessageRepository extends Select\Repository implements PeppolMessageRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
    ) {
        parent::__construct($select);
    }

    /** @psalm-return EntityReader */
    public function findAllPreloaded(): EntityReader
    {
        return $this->prepareDataReader($this->select());
    }

    /**
     * Same filterCombined(array $queryParams) shape as
     * ProductRepository::filterCombined() — one Select, one andWhere per
     * non-empty query param, straight from $request->getQueryParams().
     * Powers the Peppol Messages screen's search fields.
     *
     * @param array<array-key, mixed> $queryParams
     * @psalm-return EntityReader
     */
    public function filterCombined(array $queryParams): EntityReader
    {
        $query = $this->select();
        if (!empty($queryParams['status'])) {
            $query = $query->andWhere(['status' => trim((string) $queryParams['status'])]);
        }
        if (!empty($queryParams['message_id'])) {
            $query = $query->andWhere(
                'message_id',
                'like',
                '%' . trim((string) $queryParams['message_id']) . '%'
            );
        }
        if (!empty($queryParams['recipient_id'])) {
            $query = $query->andWhere(
                'recipient_id',
                'like',
                '%' . trim((string) $queryParams['recipient_id']) . '%'
            );
        }
        if (!empty($queryParams['inv_id'])) {
            $query = $query->andWhere(['inv_id' => (int) $queryParams['inv_id']]);
        }
        return $this->prepareDataReader($query);
    }

    /**
     * @throws Throwable
     */
    #[\Override]
    public function save(PeppolMessage $message): void
    {
        $this->entityWriter->write([$message]);
    }

    /**
     * @throws Throwable
     */
    public function delete(PeppolMessage $message): void
    {
        $this->entityWriter->delete([$message]);
    }

    public function repoFind(int $id): ?PeppolMessage
    {
        return $this->select()->where(['id' => $id])->fetchOne() ?: null;
    }

    /**
     * Most recent message in the given status, by id (auto-increment, so
     * this is also most-recent-by-time without needing a second orderBy).
     * Used by the public Peppol Access Point status page to derive a real
     * "last confirmed send" date from actual message history, instead of
     * a synthetic sandbox ping the way gateway-status pings a payment
     * gateway's own sandbox API — Storecove/Oxalis don't have an
     * equivalent side-effect-free health check to call.
     */
    public function mostRecentByStatus(string $status): ?PeppolMessage
    {
        return $this->select()
            ->where(['status' => $status])
            ->orderBy('id', 'DESC')
            ->fetchOne() ?: null;
    }

    /** @psalm-return EntityReader */
    public function repoInvMessages(int $inv_id): EntityReader
    {
        return $this->prepareDataReader(
            $this->select()->where(['inv_id' => $inv_id])
        );
    }

    /**
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    #[\Override]
    public function repoByStatus(string $status): EntityReader
    {
        return $this->prepareDataReader(
            $this->select()->where(['status' => $status])
        );
    }

    #[\Override]
    public function repoByMessageId(string $message_id): ?PeppolMessage
    {
        return $this->select()->where(['message_id' => $message_id])->fetchOne() ?: null;
    }

    public function repoCount(int $inv_id): int
    {
        return $this->select()->where(['inv_id' => $inv_id])->count();
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])->withOrder(['id' => 'desc'])
        );
    }
}
