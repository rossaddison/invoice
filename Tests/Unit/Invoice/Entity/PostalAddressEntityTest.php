<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\PostalAddress\PostalAddress;
use PHPUnit\Framework\TestCase;

class PostalAddressEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $pa = new PostalAddress();
        $this->assertFalse($pa->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $pa = new PostalAddress();
        $this->expectException(\LogicException::class);
        $pa->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $pa = new PostalAddress();
        $pa->setId(1);
        $this->assertTrue($pa->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $pa = new PostalAddress();
        $pa->setId(5);
        $this->assertSame(5, $pa->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $pa = new PostalAddress();
        $this->assertSame('', $pa->getStreetName());
        $this->assertSame('', $pa->getAdditionalStreetName());
        $this->assertSame('', $pa->getBuildingNumber());
        $this->assertSame('', $pa->getCityName());
        $this->assertSame('', $pa->getPostalzone());
        $this->assertSame('', $pa->getCountrysubentity());
        $this->assertSame('', $pa->getCountry());
    }

    public function testSetAndGetStreetName(): void
    {
        $pa = new PostalAddress();
        $pa->setStreetName('High Street');
        $this->assertSame('High Street', $pa->getStreetName());
    }

    public function testSetAndGetCityName(): void
    {
        $pa = new PostalAddress();
        $pa->setCityName('London');
        $this->assertSame('London', $pa->getCityName());
    }

    public function testSetAndGetPostalzone(): void
    {
        $pa = new PostalAddress();
        $pa->setPostalzone('EC1A 1BB');
        $this->assertSame('EC1A 1BB', $pa->getPostalzone());
    }

    public function testSetAndGetCountry(): void
    {
        $pa = new PostalAddress();
        $pa->setCountry('GB');
        $this->assertSame('GB', $pa->getCountry());
    }

    public function testGetFullAddressCombinesFields(): void
    {
        $pa = new PostalAddress();
        $pa->setStreetName('High Street');
        $pa->setBuildingNumber('10');
        $pa->setAdditionalStreetName('Flat 2');
        $pa->setPostalzone('EC1A 1BB');
        $full = $pa->getFullAddress();
        $this->assertStringContainsString('High Street', $full);
        $this->assertStringContainsString('10', $full);
        $this->assertStringContainsString('EC1A 1BB', $full);
    }

    public function testSetAndGetClientId(): void
    {
        $pa = new PostalAddress();
        $pa->setClientId(7);
        $this->assertSame(7, $pa->getClientId());
    }
}
