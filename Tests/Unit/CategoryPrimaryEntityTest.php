<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\CategoryPrimary;
use Codeception\Test\Unit;

final class CategoryPrimaryEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $category = new CategoryPrimary();
        
        $this->assertNull($category->getId());
        $this->assertSame('', $category->getName());
    }

    public function testConstructorWithName(): void
    {
        $category = new CategoryPrimary('Electronics');
        
        $this->assertNull($category->getId());
        $this->assertSame('Electronics', $category->getName());
    }

    public function testIdGetter(): void
    {
        $category = new CategoryPrimary();
        
        $this->assertNull($category->getId());
    }

    public function testNameSetterAndGetter(): void
    {
        $category = new CategoryPrimary();
        $category->setName('Books');
        
        $this->assertSame('Books', $category->getName());
    }

    public function testPublicNameProperty(): void
    {
        $category = new CategoryPrimary('Test Category');
        
        // Test that name property is accessible as public
        $this->assertSame('Test Category', $category->name);
        
        // Test setting via public property
        $category->name = 'Updated Category';
        $this->assertSame('Updated Category', $category->getName());
    }

    public function testCommonCategoryTypes(): void
    {
        $electronics = new CategoryPrimary('Electronics');
        $this->assertSame('Electronics', $electronics->getName());

        $clothing = new CategoryPrimary('Clothing');
        $this->assertSame('Clothing', $clothing->getName());

        $books = new CategoryPrimary('Books');
        $this->assertSame('Books', $books->getName());

        $homeGarden = new CategoryPrimary('Home & Garden');
        $this->assertSame('Home & Garden', $homeGarden->getName());
    }

    public function testLongCategoryNames(): void
    {
        $longName = 'Very Long Category Name That Could Potentially Exceed Normal Database Limits And Still Be Valid';
        $category = new CategoryPrimary($longName);
        
        $this->assertSame($longName, $category->getName());
    }

    public function testSpecialCharactersInName(): void
    {
        $category = new CategoryPrimary('Electronics & Gadgets');
        
        $this->assertSame('Electronics & Gadgets', $category->getName());
    }

    public function testUnicodeInName(): void
    {
        $category = new CategoryPrimary('Électronique & 电子产品');
        
        $this->assertSame('Électronique & 电子产品', $category->getName());
    }

    public function testNullNameHandling(): void
    {
        $category = new CategoryPrimary(null);
        
        $this->assertNull($category->getName());
    }

    public function testEmptyNameHandling(): void
    {
        $category = new CategoryPrimary('');
        
        $this->assertSame('', $category->getName());
    }

    public function testNameSetterOverridesConstructor(): void
    {
        $category = new CategoryPrimary('Original Name');
        $this->assertSame('Original Name', $category->getName());
        
        $category->setName('Updated Name');
        $this->assertSame('Updated Name', $category->getName());
    }

    public function testCompleteCategorySetup(): void
    {
        $category = new CategoryPrimary();
        $category->setName('Complete Setup Category');
        
        $this->assertSame('Complete Setup Category', $category->getName());
        $this->assertNull($category->getId()); // ID remains null until persisted
    }

    public function testCategoryNamesWithNumbers(): void
    {
        $category = new CategoryPrimary('Category 123');
        
        $this->assertSame('Category 123', $category->getName());
    }

    public function testCategoryNamesWithSpecialFormats(): void
    {
        $category1 = new CategoryPrimary('A/B Testing');
        $this->assertSame('A/B Testing', $category1->getName());

        $category2 = new CategoryPrimary('Level-1 Category');
        $this->assertSame('Level-1 Category', $category2->getName());

        $category3 = new CategoryPrimary('Parent: Child Category');
        $this->assertSame('Parent: Child Category', $category3->getName());
    }

    public function testIdPropertyType(): void
    {
        $category = new CategoryPrimary();
        
        // Verify ID getter returns int|null type
        $this->assertNull($category->getId());
        $this->assertTrue(is_null($category->getId()) || is_int($category->getId()));
    }

    public function testNamePropertyNullability(): void
    {
        $category = new CategoryPrimary();
        
        // Test null assignment
        $category->setName('Test');
        $this->assertSame('Test', $category->getName());
        
        // Note: setter requires string parameter, so null testing done via constructor
        $nullCategory = new CategoryPrimary(null);
        $this->assertNull($nullCategory->getName());
    }
}
