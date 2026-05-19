<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\ProductImage\ProductImage;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProductImageEntityTest extends TestCase
{
    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $pi = new ProductImage();
        $this->expectException(\LogicException::class);
        $pi->reqId();
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $pi = new ProductImage();
        $pi->setId(9);
        $this->assertSame(9, $pi->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $pi = new ProductImage();
        $this->assertSame('', $pi->getFileNameOriginal());
        $this->assertSame('', $pi->getFileNameNew());
        $this->assertSame('', $pi->getDescription());
        $this->assertNull($pi->getProduct());
        $this->assertInstanceOf(DateTimeImmutable::class, $pi->getUploadedDate());
    }

    public function testSetAndGetFileNameOriginal(): void
    {
        $pi = new ProductImage();
        $pi->setFileNameOriginal('product.jpg');
        $this->assertSame('product.jpg', $pi->getFileNameOriginal());
    }

    public function testSetAndGetFileNameNew(): void
    {
        $pi = new ProductImage();
        $pi->setFileNameNew('product_1234.jpg');
        $this->assertSame('product_1234.jpg', $pi->getFileNameNew());
    }

    public function testSetAndGetDescription(): void
    {
        $pi = new ProductImage();
        $pi->setDescription('Front view');
        $this->assertSame('Front view', $pi->getDescription());
    }

    public function testSetAndGetUploadedDate(): void
    {
        $pi = new ProductImage();
        $date = new DateTimeImmutable('2026-01-15');
        $pi->setUploadedDate($date);
        $this->assertSame($date, $pi->getUploadedDate());
    }

    public function testReqProductIdThrowsWhenNull(): void
    {
        $pi = new ProductImage();
        $this->expectException(\LogicException::class);
        $pi->reqProductId();
    }

    public function testSetAndReqProductId(): void
    {
        $pi = new ProductImage();
        $pi->setProductId(4);
        $this->assertSame(4, $pi->reqProductId());
    }
}
