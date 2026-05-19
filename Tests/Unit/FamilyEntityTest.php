<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Persistence\Family\Family;
use PHPUnit\Framework\TestCase;

final class FamilyEntityTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $family = new Family();

        $this->assertFalse($family->hasIdentity());
        $this->assertSame('', $family->getFamilyName());
    }

    public function testConstructorWithAllParameters(): void
    {
        $family = new Family('Electronics', 'comma,list', 'prefix', 1, 2);

        $this->assertFalse($family->hasIdentity());
        $this->assertSame('Electronics', $family->getFamilyName());
        $this->assertSame(1, $family->reqCategoryPrimaryId());
        $this->assertSame(2, $family->reqCategorySecondaryId());
    }

    public function testFamilyIdGetter(): void
    {
        $family = new Family();

        $this->assertFalse($family->hasIdentity());
    }

    public function testFamilyNameSetterAndGetter(): void
    {
        $family = new Family();
        $family->setFamilyName('Books');

        $this->assertSame('Books', $family->getFamilyName());
    }

    public function testCategoryPrimaryIdSetterAndGetter(): void
    {
        $family = new Family();
        $family->setCategoryPrimaryId(5);

        $this->assertSame(5, $family->reqCategoryPrimaryId());
    }

    public function testCategorySecondaryIdSetterAndGetter(): void
    {
        $family = new Family();
        $family->setCategorySecondaryId(10);

        $this->assertSame(10, $family->reqCategorySecondaryId());
    }

    public function testCommonFamilyTypes(): void
    {
        $electronics = new Family('Electronics', '', '', 1, 10);
        $this->assertSame('Electronics', $electronics->getFamilyName());
        $this->assertSame(1, $electronics->reqCategoryPrimaryId());
        $this->assertSame(10, $electronics->reqCategorySecondaryId());

        $clothing = new Family('Clothing', '', '', 2, 20);
        $this->assertSame('Clothing', $clothing->getFamilyName());
        $this->assertSame(2, $clothing->reqCategoryPrimaryId());
        $this->assertSame(20, $clothing->reqCategorySecondaryId());
    }

    public function testLongFamilyNames(): void
    {
        $longName = 'Very Long Family Name That Could Potentially Exceed Normal Limits';
        $family = new Family($longName, '', '', 1, 2);

        $this->assertSame($longName, $family->getFamilyName());
    }

    public function testSpecialCharactersInFamilyName(): void
    {
        $family = new Family('Books & Magazines', '', '', 3, 30);

        $this->assertSame('Books & Magazines', $family->getFamilyName());
    }

    public function testCompleteFamilySetup(): void
    {
        $family = new Family('Home & Garden', '', '', 4, 40);
        $family->setFamilyName('Home Improvement');
        $family->setCategoryPrimaryId(5);
        $family->setCategorySecondaryId(50);

        $this->assertSame('Home Improvement', $family->getFamilyName());
        $this->assertSame(5, $family->reqCategoryPrimaryId());
        $this->assertSame(50, $family->reqCategorySecondaryId());
    }

    public function testPublicIdProperty(): void
    {
        $family = new Family();
        $this->assertFalse($family->hasIdentity());
    }

    public function testPublicFamilyNameProperty(): void
    {
        $family = new Family('Test Family');
        $this->assertSame('Test Family', $family->getFamilyName());
    }

    public function testZeroCategoryIds(): void
    {
        $family = new Family('Zero Categories', '', '', 0, 0);

        $this->assertSame(0, $family->reqCategoryPrimaryId());
        $this->assertSame(0, $family->reqCategorySecondaryId());
    }

    public function testLargeCategoryIds(): void
    {
        $family = new Family('Large IDs', '', '', 999999, 888888);

        $this->assertSame(999999, $family->reqCategoryPrimaryId());
        $this->assertSame(888888, $family->reqCategorySecondaryId());
    }

    public function testChainedSetterCalls(): void
    {
        $family = new Family();
        $family->setFamilyName('Chained');
        $family->setCategoryPrimaryId(100);
        $family->setCategorySecondaryId(200);

        $this->assertSame('Chained', $family->getFamilyName());
        $this->assertSame(100, $family->reqCategoryPrimaryId());
        $this->assertSame(200, $family->reqCategorySecondaryId());
    }

    public function testNullFamilyNameHandling(): void
    {
        $family = new Family(null, '', '', 1, 2);

        $this->assertNull($family->getFamilyName());
    }

    public function testUnicodeInFamilyName(): void
    {
        $family = new Family('Téchnology & Gadgéts 科技', '', '', 1, 2);

        $this->assertSame('Téchnology & Gadgéts 科技', $family->getFamilyName());
    }

    public function testCategoryIdIntType(): void
    {
        $family = new Family('Test', '', '', 123, 456);

        $this->assertIsInt($family->reqCategoryPrimaryId());
        $this->assertIsInt($family->reqCategorySecondaryId());
        $this->assertSame(123, $family->reqCategoryPrimaryId());
        $this->assertSame(456, $family->reqCategorySecondaryId());
    }
}
