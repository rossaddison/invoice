<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\HomeCareAlias;

use App\Invoice\HomeCareSchedule\HomeCareAliasRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\PrimaryKey;

/**
 * A name as it is written in the schedule that means a known run or worker:
 * "Nor 2" is the run North 2. Remembered the first time a person confirms
 * the match in the schedule preview, so every later month maps by itself.
 *
 * The key is the pair (kind, alias), so a name has one meaning per kind. `alias`
 * is stored normalised (lower case, no punctuation, single spaces);
 * `target_id` is a category_secondary id for kind 'run' and a worker id for
 * kind 'worker'.
 */
#[Entity(repository: HomeCareAliasRepository::class)]
#[Table(primary: new PrimaryKey(columns: ['kind', 'alias']))]
class HomeCareAlias
{
    public const string KIND_RUN = 'run';
    public const string KIND_WORKER = 'worker';

    public function __construct(
        #[Column(type: 'string(10)', nullable: false)]
        private string $kind,
        #[Column(type: 'string(100)', nullable: false)]
        private string $alias,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $target_id,
    ) {
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getTargetId(): int
    {
        return $this->target_id;
    }

    public function pointTo(int $targetId): void
    {
        $this->target_id = $targetId;
    }
}
