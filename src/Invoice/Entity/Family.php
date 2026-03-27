<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\Family\FamilyRepository::class)]
class Family
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    public function __construct(
        #[Column(type: 'text', nullable: true)]
        public ?string $family_name = '',
        #[Column(type: 'text', nullable: true)]
        public ?string $family_commalist = '',
        #[Column(type: 'text', nullable: true)]
        public ?string $family_productprefix = '',
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $category_primary_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $category_secondary_id = null,
    ) {
    }

    public function getFamilyId(): ?int
    {
        return $this->id;
    }

    public function getFamilyName(): ?string
    {
        return $this->family_name;
    }

    public function setFamilyName(string $family_name): void
    {
        $this->family_name = $family_name;
    }
    
    public function getFamilyCommalist(): ?string
    {
        return $this->family_commalist;
    }

    public function setFamilyCommalist(string $family_commalist): void
    {
        $this->family_commalist = $family_commalist;
    }
    
    public function getFamilyProductprefix(): ?string
    {
        return $this->family_productprefix;
    }

    public function setFamilyProductprefix(string $family_productprefix): void
    {
        $this->family_productprefix = $family_productprefix;
    }

    public function getCategoryPrimaryId(): string
    {
        return (string) $this->category_primary_id;
    }

    public function setCategoryPrimaryId(int $category_primary_id): void
    {
        $this->category_primary_id = $category_primary_id;
    }

    public function getCategorySecondaryId(): string
    {
        return (string) $this->category_secondary_id;
    }

    public function setCategorySecondaryId(int $category_secondary_id): void
    {
        $this->category_secondary_id = $category_secondary_id;
    }
}
