<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductClient;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Entity\Product;
use App\Invoice\ProductClient\ProductClientRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity(repository: ProductClientRepository::class)]
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
        private ?int $client_id = null,
    ) {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'ProductClient has no ID (not persisted yet)'
            );
        }

        return $this->id;
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

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeImmutable $updated_at): void
    {
        $this->updated_at = $updated_at;
    }
}
