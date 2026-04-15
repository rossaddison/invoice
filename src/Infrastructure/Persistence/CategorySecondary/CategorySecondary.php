<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CategorySecondary;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary as CP;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: CSR::class)]
class CategorySecondary
{
    #[BelongsTo(target: CP::class, nullable: true, fkAction: 'NO ACTION')]
    private ?CP $category_primary = null;

    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $category_primary_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCategoryPrimaryId(): ?int
    {
        return $this->category_primary_id;
    }

    public function setCategoryPrimaryId(int $category_primary_id): void
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
    public function getCategoryPrimary(): ?CP
    {
        return $this->category_primary;
    }

    public function setCategoryPrimary(?CP $categoryPrimary): void
    {
        $this->category_primary = $categoryPrimary;
    }
}
