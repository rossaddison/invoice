<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;
use Cycle\ORM\Entity\Behavior;

#[Entity(repository: \App\Invoice\ProductClient\ProductClientRepository::class)]
#[Behavior\CreatedAt(field: 'created_at', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updated_at', column: 'updated_at')]
class ProductClient
{
    #[BelongsTo(target: Product::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Product $product = null;

    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $created_at;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $updated_at;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $product_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null
    ) {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function setProductId(?int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function setClientId(?int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }
    
    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->created_at = $createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }
    
    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updated_at = $updatedAt;
    }
}