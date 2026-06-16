<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use Cycle\Annotated\Annotation as Cycle;
use DateTime;

#[Cycle\Embeddable]
class As4Timestamps
{
    #[Cycle\Column(type: 'datetime')]
    private DateTime $createdAt;

    #[Cycle\Column(type: 'datetime')]
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }

    public function touch(): void
    {
        $this->updatedAt = new DateTime();
    }
}
