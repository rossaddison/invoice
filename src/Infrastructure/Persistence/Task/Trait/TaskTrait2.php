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
trait TaskTrait2
{

    public function setFinishDate(?DateTime $finish_date): void
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

    public function reqTaxRateId(): int
    {
        return $this->requireId($this->tax_rate_id, 'TaxRate');
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function isOverdue(): bool
    {
        return $this->finish_date < new DateTime(date('Y-m-d')) ? false : true;
    }
}
