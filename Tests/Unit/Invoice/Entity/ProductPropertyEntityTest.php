<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\ProductProperty\ProductProperty;
use PHPUnit\Framework\TestCase;

class ProductPropertyEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $pp = new ProductProperty();
        $this->assertFalse($pp->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $pp = new ProductProperty();
        $this->expectException(\LogicException::class);
        $pp->reqId();
    }

    public function testConstructorDefaults(): void
    {
        $pp = new ProductProperty();
        $this->assertSame('', $pp->getName());
        $this->assertSame('', $pp->getValue());
        $this->assertNull($pp->getProduct());
    }

    public function testSetAndGetName(): void
    {
        $pp = new ProductProperty();
        $pp->setName('Colour');
        $this->assertSame('Colour', $pp->getName());
    }

    public function testSetAndGetValue(): void
    {
        $pp = new ProductProperty();
        $pp->setValue('Red');
        $this->assertSame('Red', $pp->getValue());
    }

    public function testReqProductIdThrowsWhenNull(): void
    {
        $pp = new ProductProperty();
        $this->expectException(\LogicException::class);
        $pp->reqProductId();
    }

    public function testSetAndReqProductId(): void
    {
        $pp = new ProductProperty();
        $pp->setProductId(3);
        $this->assertSame(3, $pp->reqProductId());
    }
}
