<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Profile\ProfileRepository::class)]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class Profile
{
    #[BelongsTo(target: Company::class, nullable: false)]
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
        #[Column(type: 'tinyInteger(11)', default: 0)]
        private ?int $current = 0,
        #[Column(type: 'text', nullable: true)]
        private ?string $mobile = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $email = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $description = '',
    ) {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCompany_id(): string
    {
        return (string) $this->company_id;
    }

    public function setCompany_id(int $company_id): void
    {
        $this->company_id = $company_id;
    }

    public function getCurrent(): int|null
    {
        return $this->current;
    }

    public function setCurrent(int $current): void
    {
        $this->current = $current;
    }

    public function getMobile(): string|null
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDate_created(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDate_modified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function isNewRecord(): bool
    {
        return $this->getId() === null;
    }
}
