<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\Family;
use Codeception\Test\Unit;

final class FamilyEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $family = new Family();
        
        $this->assertNull($family->getFamily_id());
        $this->assertSame('', $family->getFamily_name());
        $this->assertSame('', $family->getCategory_primary_id());
        $this->assertSame('', $family->getCategory_secondary_id());
    }

    public function testConstructorWithAllParameters(): void
    {
        $family = new Family('Electronics', 1, 2);
        
        $this->assertNull($family->getFamily_id());
        $this->assertSame('Electronics', $family->getFamily_name());
        $this->assertSame('1', $family->getCategory_primary_id());
        $this->assertSame('2', $family->getCategory_secondary_id());
    }

    public function testFamilyIdGetter(): void
    {
        $family = new Family();
        
        $this->assertNull($family->getFamily_id());
    }

    public function testFamilyNameSetterAndGetter(): void
    {
        $family = new Family();
        $family->setFamily_name('Books');
        
        $this->assertSame('Books', $family->getFamily_name());
    }

    public function testCategoryPrimaryIdSetterAndGetter(): void
    {
        $family = new Family();
        $family->setCategory_primary_id(5);
        
        $this->assertSame('5', $family->getCategory_primary_id());
    }

    public function testCategorySecondaryIdSetterAndGetter(): void
    {
        $family = new Family();
        $family->setCategory_secondary_id(10);
        
        $this->assertSame('10', $family->getCategory_secondary_id());
    }

    public function testCommonFamilyTypes(): void
    {
        $electronics = new Family('Electronics', 1, 10);
        $this->assertSame('Electronics', $electronics->getFamily_name());
        $this->assertSame('1', $electronics->getCategory_primary_id());
        $this->assertSame('10', $electronics->getCategory_secondary_id());

        $clothing = new Family('Clothing', 2, 20);
        $this->assertSame('Clothing', $clothing->getFamily_name());
        $this->assertSame('2', $clothing->getCategory_primary_id());
        $this->assertSame('20', $clothing->getCategory_secondary_id());
    }

    public function testLongFamilyNames(): void
    {
        $longName = 'Very Long Family Name That Could Potentially Exceed Normal Limits';
        $family = new Family($longName, 1, 2);
        
        $this->assertSame($longName, $family->getFamily_name());
    }

    public function testSpecialCharactersInFamilyName(): void
    {
        $family = new Family('Books & Magazines', 3, 30);
        
        $this->assertSame('Books & Magazines', $family->getFamily_name());
    }

    public function testCompleteFamilySetup(): void
    {
        $family = new Family('Home & Garden', 4, 40);
        $family->setFamily_name('Home Improvement');
        $family->setCategory_primary_id(5);
        $family->setCategory_secondary_id(50);
        
        $this->assertSame('Home Improvement', $family->getFamily_name());
        $this->assertSame('5', $family->getCategory_primary_id());
        $this->assertSame('50', $family->getCategory_secondary_id());
    }

    public function testPublicIdProperty(): void
    {
        $family = new Family();
        
        // Test that id property is accessible as public
        $this->assertNull($family->id);
    }

    public function testPublicFamilyNameProperty(): void
    {
        $family = new Family('Test Family');
        
        // Test that family_name property is accessible as public
        $this->assertSame('Test Family', $family->family_name);
    }

    public function testZeroCategoryIds(): void
    {
        $family = new Family('Zero Categories', 0, 0);
        
        $this->assertSame('0', $family->getCategory_primary_id());
        $this->assertSame('0', $family->getCategory_secondary_id());
    }

    public function testLargeCategoryIds(): void
    {
        $family = new Family('Large IDs', 999999, 888888);
        
        $this->assertSame('999999', $family->getCategory_primary_id());
        $this->assertSame('888888', $family->getCategory_secondary_id());
    }

    public function testChainedSetterCalls(): void
    {
        $family = new Family();
        $family->setFamily_name('Chained');
        $family->setCategory_primary_id(100);
        $family->setCategory_secondary_id(200);
        
        $this->assertSame('Chained', $family->getFamily_name());
        $this->assertSame('100', $family->getCategory_primary_id());
        $this->assertSame('200', $family->getCategory_secondary_id());
    }

    public function testNullFamilyNameHandling(): void
    {
        $family = new Family(null, 1, 2);
        
        $this->assertNull($family->getFamily_name());
    }

    public function testUnicodeInFamilyName(): void
    {
        $family = new Family('Téchnology & Gadgéts 科技', 1, 2);
        
        $this->assertSame('Téchnology & Gadgéts 科技', $family->getFamily_name());
    }

    public function testCategoryIdStringConversion(): void
    {
        $family = new Family('Test', 123, 456);
        
        // Verify getters return strings even though setters accept ints
        $this->assertIsString($family->getCategory_primary_id());
        $this->assertIsString($family->getCategory_secondary_id());
        $this->assertSame('123', $family->getCategory_primary_id());
        $this->assertSame('456', $family->getCategory_secondary_id());
    }
}