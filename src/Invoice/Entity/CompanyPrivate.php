<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\ORM\Entity\Behavior;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\CompanyPrivate\CompanyPrivateRepository::class)]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class CompanyPrivate
{
    #[BelongsTo(target: Company::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Company $company = null;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_created;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_modified;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $company_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $vat_id = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $tax_code = '',
        #[Column(type: 'string(34)', nullable: true)]
        private ?string $iban = '',
        #[Column(type: 'string(14)', nullable: true)]
        private ?string $gln = '',
        #[Column(type: 'string(7)', nullable: true)]
        private ?string $rcc = '',
        #[Column(type: 'string(150)', nullable: true)]
        private ?string $logo_filename = '',
        #[Column(type: 'int', nullable: false, default: 80)]
        private ?int $logo_width = null,
        #[Column(type: 'int', nullable: false, default: 40)]
        private ?int $logo_height = null,
        #[Column(type: 'int', nullable: false, default: 10)]
        private ?int $logo_margin = null,
        #[Column(type: 'date', nullable: true)]
        private mixed $start_date = null,
        #[Column(type: 'date', nullable: true)]
        private mixed $end_date = null,
    ) {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
    }

    public function isActiveToday(): bool
    {
        $today = new \DateTimeImmutable('today');
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if ($startDate === null || $endDate === null) {
            return false;
        }

        return $today >= $startDate && $today <= $endDate;
    }

    //get relation $company
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    //set relation $company
    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCompanyId(): string
    {
        return (string) $this->company_id;
    }

    public function setCompanyId(int $company_id): void
    {
        $this->company_id = $company_id;
    }

    public function getVatId(): string
    {
        return (string) $this->vat_id;
    }

    public function setVatId(string $vat_id): void
    {
        $this->vat_id = $vat_id;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
    }

    public function setTaxCode(string $tax_code): void
    {
        $this->tax_code = $tax_code;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    public function getGln(): ?string
    {
        return $this->gln;
    }

    public function setGln(string $gln): void
    {
        $this->gln = $gln;
    }

    public function getRcc(): ?string
    {
        return $this->rcc;
    }

    public function setRcc(string $rcc): void
    {
        $this->rcc = $rcc;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logo_filename;
    }

    public function setLogoFilename(string $logo_filename): void
    {
        $this->logo_filename = $logo_filename;
    }

    public function getLogoWidth(): ?int
    {
        return $this->logo_width;
    }

    public function setLogoWidth(int $logo_width): void
    {
        $this->logo_width = $logo_width;
    }

    public function getLogoHeight(): ?int
    {
        return $this->logo_height;
    }

    public function setLogoHeight(int $logo_height): void
    {
        $this->logo_height = $logo_height;
    }

    public function getLogoMargin(): ?int
    {
        return $this->logo_margin;
    }

    public function setLogoMargin(int $logo_margin): void
    {
        $this->logo_margin = $logo_margin;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    //cycle
    public function getStartDate(): ?DateTimeImmutable
    {
        /** @var DateTimeImmutable|null $this->start_date */
        return $this->start_date;
    }

    public function setStartDate(?DateTime $start_date): void
    {
        $this->start_date = $start_date;
    }

    //cycle
    public function getEndDate(): ?DateTimeImmutable
    {
        /** @var DateTimeImmutable|null $this->end_date */
        return $this->end_date;
    }

    public function setEndDate(?DateTime $end_date): void
    {
        $this->end_date = $end_date;
    }
    
    public function isNewRecord(): bool
    {
        return $this->getId() === null;
    }
}
