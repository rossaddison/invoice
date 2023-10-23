<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;
use App\Invoice\Entity\Product;

#[Entity(repository: \App\Invoice\ProductImage\ProductImageRepository::class)]
class ProductImage {

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Product::class, nullable: false, fkAction: "NO ACTION")]
    private ?Product $product = null;

    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $product_id = null;

    #[Column(type: 'longText)', nullable: false)]
    private string $file_name_original = '';

    #[Column(type: 'longText)', nullable: false)]
    private string $file_name_new = '';

    #[Column(type: 'datetime)', nullable: false)]
    private DateTimeImmutable $uploaded_date;

    #[Column(type: 'longText)', nullable: false)]
    private string $description = '';

    public function __construct(
            int $id = null,
            int $product_id = null,
            string $file_name_original = '',
            string $file_name_new = '',
            string $description = '',
    ) {
        $this->id = $id;
        $this->product_id = $product_id;
        $this->file_name_original = $file_name_original;
        $this->file_name_new = $file_name_new;
        $this->description = $description;
        $this->uploaded_date = new \DateTimeImmutable();
    }

    //get relation $product
    public function getProduct(): ?Product {
        return $this->product;
    }

    //set relation $product
    public function setProduct(?Product $product): void {
        $this->product = $product;
    }

    public function getId(): string {
        return (string) $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getProduct_id(): string {
        return (string) $this->product_id;
    }

    public function setProduct_id(int $product_id): void {
        $this->product_id = $product_id;
    }

    public function getFile_name_original(): string {
        return $this->file_name_original;
    }

    public function setFile_name_original(string $file_name_original): void {
        $this->file_name_original = $file_name_original;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function getFile_name_new(): string {
        return $this->file_name_new;
    }

    public function setFile_name_new(string $file_name_new): void {
        $this->file_name_new = $file_name_new;
    }

    public function getUploaded_date(): DateTimeImmutable {
        /** @var DateTimeImmutable $this->uploaded_date */
        return $this->uploaded_date;
    }

    public function setUploaded_date(DateTimeImmutable $uploaded_date): void {
        $this->uploaded_date = $uploaded_date;
    }

    public function nullifyRelationOnChange(int $product_id): void {
        if ($this->product_id <> $product_id) {
            $this->product = null;
        }
    }

}
