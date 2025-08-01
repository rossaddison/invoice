<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Task\TaskRepository::class)]
class Task
{
    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    #[BelongsTo(target: Project::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Project $project = null;

    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $project_id = null, #[Column(type: 'text', nullable: true)]
        private ?string $name = '', #[Column(type: 'longText', nullable: false)]
        private string $description = '', #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $price = null, #[Column(type: 'date', nullable: true)]
        private mixed $finish_date = '', #[Column(type: 'int', nullable: false)]
        private ?int $status = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null) {}

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProject_id(): string
    {
        return (string) $this->project_id;
    }

    public function setProject_id(int $project_id): void
    {
        $this->project_id = $project_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getFinish_date(): string|DateTimeImmutable
    {
        /** @var DateTimeImmutable|string $this->finish_date */
        return $this->finish_date;
    }

    public function setFinish_date(?DateTime $finish_date): void
    {
        $this->finish_date = $finish_date;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getTax_rate_id(): string
    {
        return (string) $this->tax_rate_id;
    }

    public function setTax_rate_id(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function Is_overdue(): bool
    {
        return $this->finish_date < new DateTime(date('Y-m-d')) ? false : true;
    }
}
