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
        $this->company_id = (int) $company_private->getCompany_id();
        $this->company_public_name = $company_private->getCompany()?->getName();
        $this->vat_id = $company_private->getVat_id();
        $this->tax_code = $company_private->getTax_code();
        $this->iban = $company_private->getIban();
        $this->gln = $company_private->getGln();
        $this->rcc = $company_private->getRcc();
        $this->logo_filename = $company_private->getLogo_filename();
        $this->logo_width = (string) $company_private->getLogo_width();
        $this->logo_height = (string) $company_private->getLogo_height();
        $this->logo_margin = (string) $company_private->getLogo_margin();
        $this->start_date = $company_private->getStart_date();
        $this->end_date = $company_private->getEnd_date();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getCompany_id(): int|null
    {
        return $this->company_id;
    }

    public function getVat_id(): string|null
    {
        return $this->vat_id;
    }

    public function getTax_code(): string|null
    {
        return $this->tax_code;
    }

    public function getIban(): string|null
    {
        return $this->iban;
    }

    public function getGln(): string|null
    {
        return $this->gln;
    }

    public function getLogo_filename(): string|null
    {
        return $this->logo_filename;
    }

    public function getLogo_width(): string|null
    {
        return $this->logo_width;
    }

    public function getLogo_height(): string|null
    {
        return $this->logo_height;
    }

    public function getLogo_margin(): string|null
    {
        return $this->logo_margin;
    }

    public function getRcc(): string|null
    {
        return $this->rcc;
    }

    public function getStart_date(): string|null|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->start_date
         */
        return $this->start_date;
    }

    public function getEnd_date(): string|null|DateTimeImmutable
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
