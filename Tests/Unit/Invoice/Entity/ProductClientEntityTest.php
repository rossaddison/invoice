<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProductClientEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $pc = new ProductClient();
        $this->assertFalse($pc->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $pc = new ProductClient();
        $this->expectException(\LogicException::class);
        $pc->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $pc = new ProductClient();
        $pc->setId(14);
        $this->assertTrue($pc->isPersisted());
        $this->assertSame(14, $pc->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $pc = new ProductClient();
        $pc->setId(1);
        $this->assertIsInt($pc->reqId());
    }

    public function testProductIdIsNullByDefault(): void
    {
        $pc = new ProductClient();
        $this->assertNull($pc->getProductId());
    }

    public function testProductIdSetterAndGetter(): void
    {
        $pc = new ProductClient();
        $pc->setProductId(7);
        $this->assertSame(7, $pc->getProductId());
    }

    public function testClientIdIsNullByDefault(): void
    {
        $pc = new ProductClient();
        $this->assertNull($pc->getClientId());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $pc = new ProductClient();
        $pc->setClientId(3);
        $this->assertSame(3, $pc->getClientId());
    }

    public function testConstructorSetsTimestamps(): void
    {
        $pc = new ProductClient();
        $this->assertInstanceOf(DateTimeImmutable::class, $pc->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $pc->getUpdatedAt());
    }

    public function testCreatedAtSetterAndGetter(): void
    {
        $pc = new ProductClient();
        $dt = new DateTimeImmutable('2024-01-15 10:30:00');
        $pc->setCreatedAt($dt);
        $this->assertSame($dt, $pc->getCreatedAt());
    }

    public function testUpdatedAtSetterAndGetter(): void
    {
        $pc = new ProductClient();
        $dt = new DateTimeImmutable('2024-06-20 14:00:00');
        $pc->setUpdatedAt($dt);
        $this->assertSame($dt, $pc->getUpdatedAt());
    }

    public function testProductRelationIsNullByDefault(): void
    {
        $pc = new ProductClient();
        $this->assertNull($pc->getProduct());
    }

    public function testProductRelationSetterAndGetter(): void
    {
        $pc = new ProductClient();
        $product = $this->createMock(Product::class);
        $pc->setProduct($product);
        $this->assertSame($product, $pc->getProduct());
        $pc->setProduct(null);
        $this->assertNull($pc->getProduct());
    }

    public function testClientRelationIsNullByDefault(): void
    {
        $pc = new ProductClient();
        $this->assertNull($pc->getClient());
    }

    public function testClientRelationSetterAndGetter(): void
    {
        $pc = new ProductClient();
        $client = $this->createMock(Client::class);
        $pc->setClient($client);
        $this->assertSame($client, $pc->getClient());
        $pc->setClient(null);
        $this->assertNull($pc->getClient());
    }
}
