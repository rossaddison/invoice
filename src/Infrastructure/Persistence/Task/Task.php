<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Task;

use App\Infrastructure\Persistence\{
    TaxRate\TaxRate, Project\Project, Trait\RequireId};
use App\Invoice\Task\TaskRepository as TR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;
use App\Infrastructure\Persistence\Task\Trait\TaskTrait1;
use App\Infrastructure\Persistence\Task\Trait\TaskTrait2;

#[Entity(repository: TR::class)]
class Task
{
    use RequireId;
    use TaskTrait1;
    use TaskTrait2;
 
    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    #[BelongsTo(target: Project::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Project $project = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $project_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'longText', nullable: false)]
        private string $description = '',
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $price = null,
        #[Column(type: 'date', nullable: true)]
        private mixed $finish_date = '',
        #[Column(type: 'int', nullable: false)]
        private ?int $status = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null,
    ) {
    }
}
