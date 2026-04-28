<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductCustom;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\ProductCustom\ProductCustomRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: ProductCustomRepository::class)]
class ProductCustom
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Product::class, nullable: false)]
    private ?Product $product = null;

    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $product_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $value = '',
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'ProductCustom');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->custom_field;
    }

    public function setCustomField(?CustomField $custom_field): void
    {
        $this->custom_field = $custom_field;
    }

    public function getProductId(): string
    {
        return (string) $this->product_id;
    }

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getCustomFieldId(): string
    {
        return (string) $this->custom_field_id;
    }

    public function setCustomFieldId(int $custom_field_id): void
    {
        $this->custom_field_id = $custom_field_id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
