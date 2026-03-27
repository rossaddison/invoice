<?php

declare(strict_types=1);

namespace App\Invoice\CompanyPrivate;

use App\Invoice\Entity\CompanyPrivate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Length;
use DateTimeImmutable;

final class CompanyPrivateForm extends FormModel
{
    private ?int $id = null;

    #[Required]
    private ?int $company_id = null;

    // use the cycle company relation to get the name of the company
    private ?string $company_public_name = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $vat_id = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $tax_code = '';
    #[Length(min: 0, max: 34, skipOnEmpty: true)]
    private ?string $iban = '';
    #[Length(min: 0, max: 14, skipOnEmpty: true)]
    private ?string $gln = '';
    #[Length(min: 0, max: 7, skipOnEmpty: true)]
    private ?string $rcc = '';

    #[Length(min: 0, max: 150, skipOnEmpty: true)]
    private ?string $logo_filename = '';

    private ?string $logo_width = '';
    private ?string $logo_height = '';
    private ?string $logo_margin = '';

    // start_date mixed to accomodate null|string|DateTimeImmutable
    private mixed $start_date = '';
    private mixed $end_date = '';

    public function __construct(CompanyPrivate $company_private)
    {
        $this->id = $company_private->getId();
        $this->company_id = (int) $company_private->getCompanyId();
        $this->company_public_name = $company_private->getCompany()?->getName();
        $this->vat_id = $company_private->getVatId();
        $this->tax_code = $company_private->getTaxCode();
        $this->iban = $company_private->getIban();
        $this->gln = $company_private->getGln();
        $this->rcc = $company_private->getRcc();
        $this->logo_filename = $company_private->getLogoFilename();
        $this->logo_width = (string) $company_private->getLogoWidth();
        $this->logo_height = (string) $company_private->getLogoHeight();
        $this->logo_margin = (string) $company_private->getLogoMargin();
        $this->start_date = $company_private->getStartDate();
        $this->end_date = $company_private->getEndDate();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id;
    }

    public function getVatId(): ?string
    {
        return $this->vat_id;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function getGln(): ?string
    {
        return $this->gln;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logo_filename;
    }

    public function getLogoWidth(): ?string
    {
        return $this->logo_width;
    }

    public function getLogoHeight(): ?string
    {
        return $this->logo_height;
    }

    public function getLogoMargin(): ?string
    {
        return $this->logo_margin;
    }

    public function getRcc(): ?string
    {
        return $this->rcc;
    }

    public function getStartDate(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->start_date
         */
        return $this->start_date;
    }

    public function getEndDate(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->end_date
         */
        return $this->end_date;
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
