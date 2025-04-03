<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\CategorySecondary\CategorySecondaryRepository::class)]
class CategorySecondary
{
    #[BelongsTo(target: CategoryPrimary::class, nullable: true, fkAction: 'NO ACTION')]
    private ?CategoryPrimary $category_primary = null;
    
    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $category_primary_id = null,    
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
    ) {}
    
    public function getId(): int|null
    {
        return $this->id;
    }
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    public function getCategory_primary_id(): int|null
    {
        return $this->category_primary_id;
    }
    
    public function setCategory_primary_id(int $category_primary_id): void
    {
        $this->category_primary_id = $category_primary_id;
    }
    
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    // get relation CategoryPrimary
    public function getCategoryPrimary(): ?CategoryPrimary
    {
        return $this->category_primary;
    }  
}