<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\FamilyCustom\FamilyCustom;
use PHPUnit\Framework\TestCase;

class FamilyCustomEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $fc = new FamilyCustom();
        $this->assertFalse($fc->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $fc = new FamilyCustom();
        $this->expectException(\LogicException::class);
        $fc->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $fc = new FamilyCustom();
        $fc->setId(3);
        $this->assertTrue($fc->hasIdentity());
        $this->assertSame(3, $fc->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $fc = new FamilyCustom();
        $fc->setId(1);
        $this->assertIsInt($fc->reqId());
    }

    public function testFamilyIdDoesNotAffectReqId(): void
    {
        $fc = new FamilyCustom();
        $fc->setFamilyId(5);
        $this->assertFalse($fc->hasIdentity());
    }

    public function testFamilyIdSetterDoesNotSetPrimaryId(): void
    {
        $fc = new FamilyCustom();
        $fc->setFamilyId(5);
        $this->expectException(\LogicException::class);
        $fc->reqId();
    }

    public function testCustomFieldIdThrowsWhenNotSet(): void
    {
        $fc = new FamilyCustom();
        $this->expectException(\LogicException::class);
        $fc->reqCustomFieldId();
    }

    public function testCustomFieldIdSetterAndGetter(): void
    {
        $fc = new FamilyCustom();
        $fc->setCustomFieldId(12);
        $this->assertSame(12, $fc->reqCustomFieldId());
    }

    public function testValueIsNullByDefault(): void
    {
        $fc = new FamilyCustom();
        $this->assertNull($fc->getValue());
    }

    public function testValueSetterAndGetter(): void
    {
        $fc = new FamilyCustom();
        $fc->setValue('custom value');
        $this->assertSame('custom value', $fc->getValue());
    }

    public function testFamilyRelationIsNullByDefault(): void
    {
        $fc = new FamilyCustom();
        $this->assertNull($fc->getFamily());
    }

    public function testFamilyRelationSetterAndGetter(): void
    {
        $fc = new FamilyCustom();
        $family = $this->createMock(Family::class);
        $fc->setFamily($family);
        $this->assertSame($family, $fc->getFamily());
        $fc->setFamily(null);
        $this->assertNull($fc->getFamily());
    }

    public function testCustomFieldRelationSetterAndGetter(): void
    {
        $fc = new FamilyCustom();
        $field = $this->createMock(CustomField::class);
        $fc->setCustomField($field);
        $this->assertSame($field, $fc->getCustomField());
        $fc->setCustomField(null);
        $this->assertNull($fc->getCustomField());
    }
}
