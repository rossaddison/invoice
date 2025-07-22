<?php

declare(strict_types=1);

namespace App\Invoice\Sumex;

use App\Invoice\Entity\Sumex;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

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
        $this->invoice = $sumex->getInvoice();
        $this->reason = $sumex->getReason();
        $this->diagnosis = $sumex->getDiagnosis();
        $this->observations = $sumex->getObservations();
        $this->treatmentstart = $sumex->getTreatmentstart();
        $this->treatmentend = $sumex->getTreatmentend();
        $this->casedate = $sumex->getCasedate();
        $this->casenumber = $sumex->getCasenumber();
    }

    public function getInvoice(): int|null
    {
        return $this->invoice;
    }

    public function getReason(): int|null
    {
        return $this->reason;
    }

    public function getDiagnosis(): string|null
    {
        return $this->diagnosis;
    }

    public function getObservations(): string|null
    {
        return $this->observations;
    }

    public function getTreatmentstart(): string|null|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->treatmentstart
         */
        return $this->treatmentstart;
    }

    public function getTreatmentend(): string|null|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->treatmentend
         */
        return $this->treatmentend;
    }

    public function getCasedate(): string|null|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->casedate
         */
        return $this->casedate;
    }

    public function getCasenumber(): string|null
    {
        return $this->casenumber;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
