<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\ProductProperty\ProductPropertyRepository::class)]
class ProductProperty
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[BelongsTo(target: Product::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Product $product = null;

    public function __construct(#[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_id = null, #[Column(type: 'text', nullable: true)]
        public ?string $name = '', #[Column(type: 'text', nullable: true)]
        public ?string $value = '') {}

    public function getProperty_id(): ?int
    {
        return $this->id;
    }

    public function getProduct_id(): string
    {
        return (string) $this->product_id;
    }

    public function setProduct_id(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): string|null
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function nullifyRelationOnChange(int $product_id): void
    {
        if ($this->product_id != $product_id) {
            $this->product = null;
        }
    }
}
