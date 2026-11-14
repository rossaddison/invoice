<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareAlias\HomeCareAlias;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of HomeCareAlias
 * @extends Select\Repository<TEntity>
 */
final class HomeCareAliasRepository extends Select\Repository implements HomeCareAliasRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|HomeCareAlias|null $alias
     * @throws Throwable
     */
    #[\Override]
    public function save(array|HomeCareAlias|null $alias): void
    {
        $this->entityWriter->write([$alias]);
    }

    #[\Override]
    public function repoLookupquery(string $kind, string $alias): ?HomeCareAlias
    {
        return $this->select()
            ->where(['kind' => $kind, 'alias' => $alias])
            ->fetchOne() ?: null;
    }
}
