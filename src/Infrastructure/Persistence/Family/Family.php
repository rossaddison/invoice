<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Family;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\Family\FamilyRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity(repository: FamilyRepository::class)]
#[Index(columns: ['street_sort_order'])]
class Family
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'text', nullable: true)]
        private ?string $family_name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $family_commalist = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $family_productprefix = '',
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $category_primary_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $category_secondary_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $street_sort_order = null,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Family');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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

    public function setFamilyProductprefix(
        string $family_productprefix
    ): void {
        $this->family_productprefix = $family_productprefix;
    }

    public function getCategoryPrimaryId(): ?int
    {
        return $this->category_primary_id;
    }

    public function reqCategoryPrimaryId(): int
    {
        return $this->requireId($this->category_primary_id, 'Family category_primary_id');
    }

    public function setCategoryPrimaryId(int $category_primary_id): void
    {
        $this->category_primary_id = $category_primary_id;
    }

    public function getCategorySecondaryId(): ?int
    {
        return $this->category_secondary_id;
    }

    public function reqCategorySecondaryId(): int
    {
        return $this->requireId($this->category_secondary_id, 'Family category_secondary_id');
    }

    public function setCategorySecondaryId(
        int $category_secondary_id
    ): void {
        $this->category_secondary_id = $category_secondary_id;
    }

    public function getStreetSortOrder(): ?int
    {
        return $this->street_sort_order;
    }

    public function setStreetSortOrder(int $street_sort_order): void
    {
        $this->street_sort_order = $street_sort_order;
    }
}
