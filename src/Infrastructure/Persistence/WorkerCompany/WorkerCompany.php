<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorkerCompany;

use App\Invoice\WorkerCompany\WorkerCompanyRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\PrimaryKey;

/**
 * Registers a HomeCare worker under one of the limited businesses (self-employed).
 *
 * The runs are split between two limited businesses so that each worker can be
 * registered as self-employed. This is a pure link table: its primary key is the
 * combination of the two keys it joins, `worker.id` and `company.id`, with no id column
 * of its own. A row is exactly "this worker is registered under this business"; the same
 * pair can therefore exist only once.
 *
 * Both columns are plain scalar FKs, deliberately not Cycle `#[BelongsTo]` relations,
 * matching InvWorkerAllocation. No `#[Index]`: the composite primary key already indexes
 * (worker_id, company_id), and a separate index on a not-yet-created table has taken the
 * schema build down before (MergeIndexes ordering in rossaddison/yii-cycle-1).
 */
#[Entity(repository: WorkerCompanyRepository::class)]
#[Table(primary: new PrimaryKey(columns: ['worker_id', 'company_id']))]
class WorkerCompany
{
    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private int $worker_id,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $company_id,
    ) {
    }

    public function reqWorkerId(): int
    {
        return $this->worker_id;
    }

    public function reqCompanyId(): int
    {
        return $this->company_id;
    }
}
