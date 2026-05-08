<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PaymentPeppol;

use App\Infrastructure\Persistence\{
    Inv\Inv, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\PaymentPeppol\PaymentPeppolRepository::class)]
class PaymentPeppol
{
    use RequireId;
    
    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'timestamp', nullable: false)]
    private int $auto_reference;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $inv_id = null,
        #[Column(type: 'string(20)', nullable: false)]
        private string $provider = '',
    ) {
        // convert the current DateTimeImmutable to a timestamp
        $this->auto_reference =  new DateTimeImmutable()->getTimestamp();
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'PaymentPeppol');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAutoReference(): int
    {
        return $this->auto_reference;
    }

    public function setAutoReference(int $timestamp): void
    {
        $this->auto_reference = $timestamp;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }
}
