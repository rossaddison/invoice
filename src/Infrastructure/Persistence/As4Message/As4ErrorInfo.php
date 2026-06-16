<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use Cycle\Annotated\Annotation as Cycle;

#[Cycle\Embeddable]
class As4ErrorInfo
{
    #[Cycle\Column(type: 'string', nullable: true)]
    private ?string $errorCode = null;

    #[Cycle\Column(type: 'text', nullable: true)]
    private ?string $errorDescription = null;

    public function getErrorCode(): ?string { return $this->errorCode; }
    public function getErrorDescription(): ?string { return $this->errorDescription; }

    public function set(string $errorCode, string $errorDescription): void
    {
        $this->errorCode        = $errorCode;
        $this->errorDescription = $errorDescription;
    }
}
