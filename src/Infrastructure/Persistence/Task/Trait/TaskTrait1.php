<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Task\Trait;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Project\Project;
use App\Invoice\Task\TaskRepository as TR;
use DateTime;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait TaskTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Task');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxRate(?TaxRate $tax_rate): void
    {
        $this->tax_rate = $tax_rate;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): void
    {
        $this->project = $project;
    }

    public function reqProjectId(): int
    {
        return $this->requireId($this->project_id, 'Project');
    }

    public function setProjectId(int $project_id): void
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

    public function getFinishDate(): string|DateTimeImmutable
    {
        /** @var DateTimeImmutable|string $this->finish_date */
        return $this->finish_date;
    }
}
