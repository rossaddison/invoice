<?php

declare(strict_types=1);

namespace App\Invoice\HomeCarePendingSignup;

use App\Infrastructure\Persistence\HomeCarePendingSignup\HomeCarePendingSignup;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of HomeCarePendingSignup
 * @extends Select\Repository<TEntity>
 */
final class HomeCarePendingSignupRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|HomeCarePendingSignup|null $pendingSignup
     * @throws Throwable
     */
    public function save(array|HomeCarePendingSignup|null $pendingSignup): void
    {
        $this->entityWriter->write([$pendingSignup]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|HomeCarePendingSignup|null $pendingSignup
     * @throws Throwable
     */
    public function delete(array|HomeCarePendingSignup|null $pendingSignup): void
    {
        $this->entityWriter->delete([$pendingSignup]);
    }

    /**
     * @return HomeCarePendingSignup|null
     *
     * @psalm-return TEntity|null
     */
    public function repoByUserIdquery(int $user_id): ?HomeCarePendingSignup
    {
        $query = $this
            ->select()
            ->where(['user_id' => $user_id]);
        return $query->fetchOne() ?: null;
    }
}
