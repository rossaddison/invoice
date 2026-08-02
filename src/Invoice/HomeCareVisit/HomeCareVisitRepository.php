<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareVisit;

use App\Infrastructure\Persistence\HomeCareVisit\HomeCareVisit;
use Cycle\Database\Exception\StatementException\ConstrainException;
use Cycle\ORM\Select;
use DateTimeImmutable;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of HomeCareVisit
 * @extends Select\Repository<TEntity>
 */
final class HomeCareVisitRepository extends Select\Repository implements HomeCareVisitRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|HomeCareVisit|null $visit
     * @throws Throwable
     */
    #[\Override]
    public function save(array|HomeCareVisit|null $visit): void
    {
        $this->entityWriter->write([$visit]);
    }

    /**
     * @param array|HomeCareVisit|null $visit
     * @throws Throwable
     */
    #[\Override]
    public function delete(array|HomeCareVisit|null $visit): void
    {
        $this->entityWriter->delete([$visit]);
    }

    #[\Override]
    public function repoFindByClientAndDatequery(int $clientId, DateTimeImmutable $visitedAt): ?HomeCareVisit
    {
        return $this->select()
            ->where(['client_id' => $clientId, 'visited_at' => $visitedAt->format('Y-m-d')])
            ->fetchOne() ?: null;
    }

    #[\Override]
    public function tryCreatePendingVisit(int $clientId, DateTimeImmutable $visitedAt): ?HomeCareVisit
    {
        $visit = new HomeCareVisit($clientId, $visitedAt);
        try {
            $this->save($visit);
        } catch (ConstrainException) {
            // Lost the race to a concurrent request (or this is a repeat
            // same-day scan) — the unique (client_id, visited_at) index is
            // what actually enforces this, not application-level locking.
            return null;
        }
        return $visit;
    }

    #[\Override]
    public function repoLatestGeneratedVisitquery(int $clientId): ?HomeCareVisit
    {
        return $this->select()
            ->where(['client_id' => $clientId, 'outcome' => 'generated'])
            ->orderBy('visited_at', 'DESC')
            ->fetchOne() ?: null;
    }

    #[\Override]
    public function repoRecentquery(int $limit): iterable
    {
        return $this->select()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }
}
