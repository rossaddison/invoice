<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use Codeception\Test\Unit;

class CategoryPrimaryEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $category = new CategoryPrimary();

        $this->assertFalse($category->isPersisted());
        $this->assertSame('', $category->getName());
    }

    public function testConstructorWithName(): void
    {
        $category = new CategoryPrimary('Electronics');

        $this->assertSame('Electronics', $category->getName());
    }

    public function testConstructorWithNullName(): void
    {
        $category = new CategoryPrimary(null);

        $this->assertNull($category->getName());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $category = new CategoryPrimary();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('CategoryPrimary has no ID (not persisted yet)');
        $category->reqId();
    }

    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $category = new CategoryPrimary();

        $this->assertFalse($category->isPersisted());
    }

    public function testNameSetterAndGetter(): void
    {
        $category = new CategoryPrimary();

        $category->setName('Food & Beverage');
        $this->assertSame('Food & Beverage', $category->getName());

        $category->setName('Electronics');
        $this->assertSame('Electronics', $category->getName());
    }

    public function testNameSetterOverridesConstructorValue(): void
    {
        $category = new CategoryPrimary('Original');

        $category->setName('Updated');
        $this->assertSame('Updated', $category->getName());
    }

    public function testEmptyStringName(): void
    {
        $category = new CategoryPrimary('');

        $this->assertSame('', $category->getName());
    }

    public function testSetNameWithEmptyString(): void
    {
        $category = new CategoryPrimary('Electronics');

        $category->setName('');
        $this->assertSame('', $category->getName());
    }

    public function testLongName(): void
    {
        $category = new CategoryPrimary();
        $longName = str_repeat('Category Name ', 100);

        $category->setName($longName);
        $this->assertSame($longName, $category->getName());
    }

    public function testSpecialCharactersInName(): void
    {
        $category = new CategoryPrimary();
        $specialName = 'Category: Electronics & Gadgets (2024) - 50% Off!';

        $category->setName($specialName);
        $this->assertSame($specialName, $category->getName());
    }

    public function testUnicodeCharactersInName(): void
    {
        $category = new CategoryPrimary();
        $unicodeName = 'Électronique & Ménager 世界中の製品 €100+';

        $category->setName($unicodeName);
        $this->assertSame($unicodeName, $category->getName());
    }

    public function testGetNameReturnTypeIsNullableString(): void
    {
        $category = new CategoryPrimary();
        $this->assertIsString($category->getName());

        $category = new CategoryPrimary(null);
        $this->assertNull($category->getName());
    }

    public function testMultipleNameUpdates(): void
    {
        $category = new CategoryPrimary();

        foreach (['Electronics', 'Food', 'Clothing', 'Books'] as $name) {
            $category->setName($name);
            $this->assertSame($name, $category->getName());
        }
    }

    public function testCompleteSetup(): void
    {
        $category = new CategoryPrimary('Initial');

        $this->assertFalse($category->isPersisted());
        $this->assertSame('Initial', $category->getName());

        $category->setName('Updated Category');
        $this->assertSame('Updated Category', $category->getName());

        $this->expectException(\LogicException::class);
        $category->reqId();
    }
}