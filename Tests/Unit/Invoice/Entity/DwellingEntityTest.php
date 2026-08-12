<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Dwelling\Dwelling;
use PHPUnit\Framework\TestCase;

class DwellingEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $d = new Dwelling();
        $this->assertFalse($d->hasIdentity());
        $this->assertFalse($d->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $d = new Dwelling();
        $this->expectException(\LogicException::class);
        $d->reqId();
    }

    public function testReqIdExceptionMessage(): void
    {
        $d = new Dwelling();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Dwelling not persisted');
        $d->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $d = new Dwelling();
        $d->setId(1);
        $this->assertTrue($d->hasIdentity());
        $this->assertTrue($d->isPersisted());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $d = new Dwelling();
        $d->setId(7);
        $this->assertSame(7, $d->reqId());
    }

    public function testHasIdentityTrueForZeroId(): void
    {
        $d = new Dwelling();
        $d->setId(0);
        $this->assertTrue($d->hasIdentity());
        $this->assertSame(0, $d->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $d = new Dwelling();
        $this->assertNull($d->getFamilyId());
        $this->assertSame(0, $d->getHouseNumberNumeric());
        $this->assertNull($d->getHouseNumberSuffix());
        $this->assertNull($d->getFlatUnit());
        $this->assertSame('', $d->getPostcode());
        $this->assertNull($d->getLatitude());
        $this->assertNull($d->getLongitude());
        $this->assertNull($d->getSource());
    }

    public function testFamilyIdNullableAtSchemaLevelButReqFamilyIdThrowsUnset(): void
    {
        $d = new Dwelling();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Family not persisted');
        $d->reqFamilyId();
    }

    public function testSetAndGetFamilyId(): void
    {
        $d = new Dwelling();
        $d->setFamilyId(3);
        $this->assertSame(3, $d->getFamilyId());
        $this->assertSame(3, $d->reqFamilyId());
    }

    public function testSetAndGetHouseNumberNumeric(): void
    {
        $d = new Dwelling();
        $d->setHouseNumberNumeric(12);
        $this->assertSame(12, $d->getHouseNumberNumeric());
    }

    public function testSetAndGetHouseNumberSuffix(): void
    {
        $d = new Dwelling();
        $d->setHouseNumberSuffix('A');
        $this->assertSame('A', $d->getHouseNumberSuffix());
    }

    public function testSetHouseNumberSuffixNull(): void
    {
        $d = new Dwelling();
        $d->setHouseNumberSuffix('A');
        $d->setHouseNumberSuffix(null);
        $this->assertNull($d->getHouseNumberSuffix());
    }

    public function testGetHouseNumberDisplayWithoutSuffix(): void
    {
        $d = new Dwelling();
        $d->setHouseNumberNumeric(12);
        $this->assertSame('12', $d->getHouseNumberDisplay());
    }

    public function testGetHouseNumberDisplayWithSuffix(): void
    {
        $d = new Dwelling();
        $d->setHouseNumberNumeric(12);
        $d->setHouseNumberSuffix('A');
        $this->assertSame('12A', $d->getHouseNumberDisplay());
    }

    public function testSetAndGetFlatUnit(): void
    {
        $d = new Dwelling();
        $d->setFlatUnit('Flat 2');
        $this->assertSame('Flat 2', $d->getFlatUnit());
    }

    public function testSetPostcodeNormalizesToUppercaseNoSpace(): void
    {
        $d = new Dwelling();
        $d->setPostcode('sw1a 1aa');
        $this->assertSame('SW1A1AA', $d->getPostcode());
    }

    public function testSetPostcodeAlreadyNormalized(): void
    {
        $d = new Dwelling();
        $d->setPostcode('EC1A1BB');
        $this->assertSame('EC1A1BB', $d->getPostcode());
    }

    public function testSetAndGetLatitudeLongitude(): void
    {
        $d = new Dwelling();
        $d->setLatitude(51.5074);
        $d->setLongitude(-0.1278);
        $this->assertSame(51.5074, $d->getLatitude());
        $this->assertSame(-0.1278, $d->getLongitude());
    }

    public function testSetAndGetSource(): void
    {
        $d = new Dwelling();
        $d->setSource('commalist');
        $this->assertSame('commalist', $d->getSource());
    }
}
