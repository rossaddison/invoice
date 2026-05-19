<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\GentorRelation\GentorRelation;
use PHPUnit\Framework\TestCase;

class GentorRelationEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $gr = new GentorRelation();
        $this->assertFalse($gr->hasIdentity());
    }

    public function testReqRelationIdThrowsWhenNotPersisted(): void
    {
        $gr = new GentorRelation();
        $this->expectException(\LogicException::class);
        $gr->reqRelationId();
    }

    public function testConstructorDefaults(): void
    {
        $gr = new GentorRelation();
        $this->assertSame('', $gr->getLowercaseName());
        $this->assertSame('', $gr->getCamelcaseName());
        $this->assertSame('', $gr->getViewFieldName());
        $this->assertNull($gr->reqGentorId());
        $this->assertNull($gr->getGentor());
    }

    public function testSetAndGetLowercaseName(): void
    {
        $gr = new GentorRelation();
        $gr->setLowercaseName('taxrate');
        $this->assertSame('taxrate', $gr->getLowercaseName());
    }

    public function testSetAndGetCamelcaseName(): void
    {
        $gr = new GentorRelation();
        $gr->setCamelcaseName('TaxRate');
        $this->assertSame('TaxRate', $gr->getCamelcaseName());
    }

    public function testSetAndGetViewFieldName(): void
    {
        $gr = new GentorRelation();
        $gr->setViewFieldName('tax_rate_id');
        $this->assertSame('tax_rate_id', $gr->getViewFieldName());
    }

    public function testSetAndGetGentorId(): void
    {
        $gr = new GentorRelation();
        $gr->setGentorId(3);
        $this->assertSame(3, $gr->reqGentorId());
    }
}
