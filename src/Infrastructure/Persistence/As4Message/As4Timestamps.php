<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use Cycle\Annotated\Annotation as Cycle;
use DateTimeImmutable;

#[Cycle\Embeddable]
class As4Timestamps
{
    #[Cycle\Column(type: 'datetime')]
    private DateTimeImmutable $createdAt;

    #[Cycle\Column(type: 'datetime')]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
