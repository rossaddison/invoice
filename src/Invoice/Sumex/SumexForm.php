<?php

declare(strict_types=1);

namespace App\Invoice\Sumex;

use App\Invoice\Entity\Sumex;
use DateTimeImmutable;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SumexForm extends FormModel
{
    private ?int $invoice = null;

    #[Required]
    private ?int $reason = null;

    #[Required]
    private ?string $diagnosis = '';

    #[Required]
    private ?string $observations = '';

    private readonly mixed $treatmentstart;

    private readonly mixed $treatmentend;

    private readonly mixed $casedate;

    #[Required]
    private ?string $casenumber = '';

    public function __construct(Sumex $sumex)
    {
        $this->invoice        = $sumex->getInvoice();
        $this->reason         = $sumex->getReason();
        $this->diagnosis      = $sumex->getDiagnosis();
        $this->observations   = $sumex->getObservations();
        $this->treatmentstart = $sumex->getTreatmentstart();
        $this->treatmentend   = $sumex->getTreatmentend();
        $this->casedate       = $sumex->getCasedate();
        $this->casenumber     = $sumex->getCasenumber();
    }

    public function getInvoice(): ?int
    {
        return $this->invoice;
    }

    public function getReason(): ?int
    {
        return $this->reason;
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function getTreatmentstart(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->treatmentstart
         */
        return $this->treatmentstart;
    }

    public function getTreatmentend(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->treatmentend
         */
        return $this->treatmentend;
    }

    public function getCasedate(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->casedate
         */
        return $this->casedate;
    }

    public function getCasenumber(): ?string
    {
        return $this->casenumber;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
