<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\PaymentPeppol\PaymentPeppolRepository::class)]
class PaymentPeppol
{
    #[BelongsTo(target:Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $inv_id = null;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'timestamp', nullable: false)]
    private int $auto_reference;

    #[Column(type: 'string(20)', nullable:false)]
    private string $provider = '';

    public function __construct(
        int $inv_id = null,
        string $provider = '',
    ) {
        $this->inv_id = $inv_id;
        // convert the current DateTimeImmutable to a timestamp
        $this->auto_reference = (new DateTimeImmutable())->getTimestamp();
        $this->provider = $provider;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function getId(): string
    {
        return (string)$this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAuto_reference(): int
    {
        return $this->auto_reference;
    }

    public function setAuto_reference(int $timestamp): void
    {
        $this->auto_reference = $timestamp;
    }

    public function getInv_id(): string
    {
        return (string)$this->inv_id;
    }

    public function setInv_id(int $inv_id): void
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
