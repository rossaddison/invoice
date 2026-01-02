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

    public function getFamily_id(): ?int
    {
        return $this->id;
    }

    public function getFamily_name(): ?string
    {
        return $this->family_name;
    }

    public function setFamily_name(string $family_name): void
    {
        $this->family_name = $family_name;
    }
    
    public function getFamily_commalist(): ?string
    {
        return $this->family_commalist;
    }

    public function setFamily_commalist(string $family_commalist): void
    {
        $this->family_commalist = $family_commalist;
    }
    
    public function getFamily_productprefix(): ?string
    {
        return $this->family_productprefix;
    }

    public function setFamily_productprefix(string $family_productprefix): void
    {
        $this->family_productprefix = $family_productprefix;
    }

    public function getCategory_primary_id(): string
    {
        return (string) $this->category_primary_id;
    }

    public function setCategory_primary_id(int $category_primary_id): void
    {
        $this->category_primary_id = $category_primary_id;
    }

    public function getCategory_secondary_id(): string
    {
        return (string) $this->category_secondary_id;
    }

    public function setCategory_secondary_id(int $category_secondary_id): void
    {
        $this->category_secondary_id = $category_secondary_id;
    }
}
