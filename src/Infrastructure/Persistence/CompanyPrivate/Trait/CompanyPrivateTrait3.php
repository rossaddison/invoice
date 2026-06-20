<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CompanyPrivate\Trait;

use App\Infrastructure\Persistence\Company\Company;
use DateTime;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait CompanyPrivateTrait3
{

    //cycle
    public function getStartDate(): ?DateTimeImmutable
    {
        /** @var DateTimeImmutable|null $this->start_date */
        return $this->start_date;
    }

    public function setStartDate(?DateTime $start_date): void
    {
        $this->start_date = $start_date !== null ?
            DateTimeImmutable::createFromMutable($start_date) : null;
    }

    //cycle
    public function getEndDate(): ?DateTimeImmutable
    {
        /** @var DateTimeImmutable|null $this->end_date */
        return $this->end_date;
    }

    public function setEndDate(?DateTime $end_date): void
    {
        $this->end_date = $end_date !== null ?
            DateTimeImmutable::createFromMutable($end_date) : null;
    }
}
