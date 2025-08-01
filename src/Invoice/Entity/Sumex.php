<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Sumex\SumexRepository::class)]
class Sumex
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(#[Column(type: 'integer(11)', nullable: true)]
        private ?int $invoice = null, #[Column(type: 'integer(11)', nullable: false, default: 0)]
        private ?int $reason = null, #[Column(type: 'string(500)', nullable: false)]
        private string $diagnosis = '', #[Column(type: 'string(500)', nullable: false)]
        private string $observations = '', #[Column(type: 'string(35)', nullable: true)]
        private ?string $casenumber = '', #[Column(type: 'date', nullable: true)]
        private mixed $treatmentstart = '', #[Column(type: 'date', nullable: true)]
        private mixed $treatmentend = '', #[Column(type: 'date', nullable: true)]
        private mixed $casedate = '') {}

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getInvoice(): int|null
    {
        return $this->invoice;
    }

    public function setInvoice(int $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getReason(): int|null
    {
        return $this->reason;
    }

    public function setReason(int $reason): void
    {
        $this->reason = $reason;
    }

    public function getDiagnosis(): string
    {
        return $this->diagnosis;
    }

    public function setDiagnosis(string $diagnosis): void
    {
        $this->diagnosis = $diagnosis;
    }

    public function getObservations(): string
    {
        return $this->observations;
    }

    public function setObservations(string $observations): void
    {
        $this->observations = $observations;
    }

    public function getTreatmentstart(): DateTimeImmutable|string|null
    {
        /** @var DateTimeImmutable|string|null $this->treatmentstart */
        return $this->treatmentstart;
    }

    public function setTreatmentstart(DateTime $treatmentstart): void
    {
        $this->treatmentstart = $treatmentstart;
    }

    public function getTreatmentend(): DateTimeImmutable|string|null
    {
        /** @var DateTimeImmutable|string|null $this->treatmentend */
        return $this->treatmentend;
    }

    public function setTreatmentend(DateTime $treatmentend): void
    {
        $this->treatmentend = $treatmentend;
    }

    public function getCasedate(): DateTimeImmutable|string|null
    {
        /** @var DateTimeImmutable|string|null $this->casedate */
        return $this->casedate;
    }

    public function setCasedate(DateTime $casedate): void
    {
        $this->casedate = $casedate;
    }

    public function getCasenumber(): ?string
    {
        return $this->casenumber;
    }

    public function setCasenumber(string $casenumber): void
    {
        $this->casenumber = $casenumber;
    }
}
