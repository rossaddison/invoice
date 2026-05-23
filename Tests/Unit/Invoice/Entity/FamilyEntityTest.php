<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Family\Family;
use PHPUnit\Framework\TestCase;

class FamilyEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $f = new Family();
        $this->assertFalse($f->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $f = new Family();
        $this->expectException(\LogicException::class);
        $f->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $f = new Family();
        $f->setId(1);
        $this->assertTrue($f->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $f = new Family();
        $f->setId(7);
        $this->assertSame(7, $f->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $f = new Family();
        $this->assertSame('', $f->getFamilyName());
        $this->assertSame('', $f->getFamilyCommalist());
        $this->assertSame('', $f->getFamilyProductprefix());
        $this->assertNull($f->getStreetSortOrder());
    }

    public function testSetAndGetFamilyName(): void
    {
        $f = new Family();
        $f->setFamilyName('Electronics');
        $this->assertSame('Electronics', $f->getFamilyName());
    }

    public function testSetAndGetFamilyCommalist(): void
    {
        $f = new Family();
        $f->setFamilyCommalist('item1,item2,item3');
        $this->assertSame('item1,item2,item3', $f->getFamilyCommalist());
    }

    public function testSetAndGetFamilyProductprefix(): void
    {
        $f = new Family();
        $f->setFamilyProductprefix('ELEC-');
        $this->assertSame('ELEC-', $f->getFamilyProductprefix());
    }

    public function testSetAndGetStreetSortOrder(): void
    {
        $f = new Family();
        $f->setStreetSortOrder(3);
        $this->assertSame(3, $f->getStreetSortOrder());
    }

    public function testReqCategoryPrimaryIdThrowsWhenNull(): void
    {
        $f = new Family();
        $this->expectException(\LogicException::class);
        $f->reqCategoryPrimaryId();
    }

    public function testSetAndReqCategoryPrimaryId(): void
    {
        $f = new Family();
        $f->setCategoryPrimaryId(2);
        $this->assertSame(2, $f->reqCategoryPrimaryId());
    }

    public function testReqCategorySecondaryIdThrowsWhenNull(): void
    {
        $f = new Family();
        $this->expectException(\LogicException::class);
        $f->reqCategorySecondaryId();
    }

    public function testSetAndReqCategorySecondaryId(): void
    {
        $f = new Family();
        $f->setCategorySecondaryId(5);
        $this->assertSame(5, $f->reqCategorySecondaryId());
    }

    public function testConstructorDefaultsForCategoryIds(): void
    {
        $f = new Family();
        $this->assertNull($f->getCategoryPrimaryId());
        $this->assertNull($f->getCategorySecondaryId());
    }

    public function testSetAndGetCategoryPrimaryId(): void
    {
        $f = new Family();
        $f->setCategoryPrimaryId(2);
        $this->assertSame(2, $f->getCategoryPrimaryId());
    }

    public function testSetAndGetCategorySecondaryId(): void
    {
        $f = new Family();
        $f->setCategorySecondaryId(5);
        $this->assertSame(5, $f->getCategorySecondaryId());
    }

    public function testReqIdExceptionMessage(): void
    {
        $f = new Family();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Family not persisted');
        $f->reqId();
    }

    public function testReqCategoryPrimaryIdExceptionMessage(): void
    {
        $f = new Family();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Family category_primary_id not persisted');
        $f->reqCategoryPrimaryId();
    }

    public function testReqCategorySecondaryIdExceptionMessage(): void
    {
        $f = new Family();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Family category_secondary_id not persisted');
        $f->reqCategorySecondaryId();
    }

    public function testHasIdentityTrueForZeroId(): void
    {
        $f = new Family();
        $f->setId(0);
        $this->assertTrue($f->hasIdentity());
        $this->assertSame(0, $f->reqId());
    }
}
