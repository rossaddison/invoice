<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Invoice\Entity\CategorySecondary;
use App\Invoice\Entity\CategoryPrimary;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

class CategorySecondaryEntityTest extends Unit
{
    private MockObject $categoryPrimary;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryPrimary = $this->createMock(CategoryPrimary::class);
    }

    public function testConstructorWithDefaults(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $this->assertNull($categorySecondary->getId());
        $this->assertNull($categorySecondary->getCategory_primary_id());
        $this->assertSame('', $categorySecondary->getName());
        $this->assertNull($categorySecondary->getCategoryPrimary());
    }

    public function testConstructorWithAllParameters(): void
    {
        $categorySecondary = new CategorySecondary(1, 2, 'Electronics Accessories');
        
        $this->assertSame(1, $categorySecondary->getId());
        $this->assertSame(2, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Electronics Accessories', $categorySecondary->getName());
        $this->assertNull($categorySecondary->getCategoryPrimary());
    }

    public function testConstructorWithNullValues(): void
    {
        $categorySecondary = new CategorySecondary(null, null, null);
        
        $this->assertNull($categorySecondary->getId());
        $this->assertNull($categorySecondary->getCategory_primary_id());
        $this->assertNull($categorySecondary->getName());
        $this->assertNull($categorySecondary->getCategoryPrimary());
    }

    public function testIdSetterAndGetter(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $categorySecondary->setId(5);
        $this->assertSame(5, $categorySecondary->getId());
        
        $categorySecondary->setId(100);
        $this->assertSame(100, $categorySecondary->getId());
    }

    public function testCategoryPrimaryIdSetterAndGetter(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $categorySecondary->setCategory_primary_id(3);
        $this->assertSame(3, $categorySecondary->getCategory_primary_id());
        
        $categorySecondary->setCategory_primary_id(25);
        $this->assertSame(25, $categorySecondary->getCategory_primary_id());
    }

    public function testNameSetterAndGetter(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $categorySecondary->setName('Smartphones');
        $this->assertSame('Smartphones', $categorySecondary->getName());
        
        $categorySecondary->setName('Laptops & Computers');
        $this->assertSame('Laptops & Computers', $categorySecondary->getName());
    }

    public function testGetCategoryPrimary(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Initially null
        $this->assertNull($categorySecondary->getCategoryPrimary());
        
        // Note: There's no setter for CategoryPrimary in the entity class
        // It's likely managed by the ORM relationship
    }

    public function testCommonSecondaryCategories(): void
    {
        $categories = [
            'Mobile Phones',
            'Desktop Computers',
            'Gaming Consoles',
            'Home Audio',
            'Kitchen Appliances',
            'Outdoor Furniture',
            'Men\'s Clothing',
            'Women\'s Shoes',
            'Children\'s Toys',
            'Sports Equipment'
        ];
        
        $categorySecondary = new CategorySecondary();
        
        foreach ($categories as $category) {
            $categorySecondary->setName($category);
            $this->assertSame($category, $categorySecondary->getName());
        }
    }

    public function testLongCategoryNames(): void
    {
        $categorySecondary = new CategorySecondary();
        $longName = 'Very Long Category Name That Describes Specific Product Categories In Great Detail';
        
        $categorySecondary->setName($longName);
        $this->assertSame($longName, $categorySecondary->getName());
    }

    public function testSpecialCharactersInCategoryName(): void
    {
        $categorySecondary = new CategorySecondary();
        $specialName = 'Category & Sub-category: (Special) Items - 50% Off!';
        
        $categorySecondary->setName($specialName);
        $this->assertSame($specialName, $categorySecondary->getName());
    }

    public function testUnicodeInCategoryName(): void
    {
        $categorySecondary = new CategorySecondary();
        $unicodeName = 'Électronique & Gadgets 电子产品 तकनीकी उपकरण';
        
        $categorySecondary->setName($unicodeName);
        $this->assertSame($unicodeName, $categorySecondary->getName());
    }

    public function testZeroAndLargeIds(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Zero ID
        $categorySecondary->setId(0);
        $this->assertSame(0, $categorySecondary->getId());
        
        // Large ID
        $categorySecondary->setId(999999999);
        $this->assertSame(999999999, $categorySecondary->getId());
        
        // Zero category primary ID
        $categorySecondary->setCategory_primary_id(0);
        $this->assertSame(0, $categorySecondary->getCategory_primary_id());
        
        // Large category primary ID
        $categorySecondary->setCategory_primary_id(888888888);
        $this->assertSame(888888888, $categorySecondary->getCategory_primary_id());
    }

    public function testNullNameHandling(): void
    {
        // Constructor accepts null
        $categorySecondary = new CategorySecondary(1, 2, null);
        $this->assertNull($categorySecondary->getName());
    }

    public function testEmptyNameHandling(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $categorySecondary->setName('');
        $this->assertSame('', $categorySecondary->getName());
    }

    public function testCompleteCategorySecondarySetup(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Setup a complete secondary category
        $categorySecondary->setId(10);
        $categorySecondary->setCategory_primary_id(1); // Electronics primary category
        $categorySecondary->setName('Smartphones & Tablets');
        
        $this->assertSame(10, $categorySecondary->getId());
        $this->assertSame(1, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Smartphones & Tablets', $categorySecondary->getName());
        $this->assertNull($categorySecondary->getCategoryPrimary()); // Relationship would be set by ORM
    }

    public function testCategoryHierarchyScenarios(): void
    {
        $scenarios = [
            ['parent_id' => 1, 'name' => 'Desktop Computers'], // Electronics -> Desktop Computers
            ['parent_id' => 2, 'name' => 'Formal Wear'],      // Clothing -> Formal Wear
            ['parent_id' => 3, 'name' => 'Living Room'],      // Furniture -> Living Room
            ['parent_id' => 4, 'name' => 'Power Tools'],      // Tools -> Power Tools
            ['parent_id' => 5, 'name' => 'Fiction Books'],    // Books -> Fiction Books
        ];
        
        foreach ($scenarios as $index => $scenario) {
            $categorySecondary = new CategorySecondary($index + 1, $scenario['parent_id'], $scenario['name']);
            
            $this->assertSame($index + 1, $categorySecondary->getId());
            $this->assertSame($scenario['parent_id'], $categorySecondary->getCategory_primary_id());
            $this->assertSame($scenario['name'], $categorySecondary->getName());
        }
    }

    public function testNameWithNumbers(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $nameWithNumbers = 'iPhone 15 Series & iPhone 14 Models';
        $categorySecondary->setName($nameWithNumbers);
        $this->assertSame($nameWithNumbers, $categorySecondary->getName());
        
        $nameWithVersions = 'Software v2.0 & v3.1 Applications';
        $categorySecondary->setName($nameWithVersions);
        $this->assertSame($nameWithVersions, $categorySecondary->getName());
    }

    public function testCategoryNamesWithSpecialFormats(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $formats = [
            'Category A > Sub-category B',
            'Main/Sub/Sub-sub Categories',
            'Category #1: Premium Items',
            'Group [Advanced] - Professional',
            'Type {Standard} (Basic Features)',
        ];
        
        foreach ($formats as $format) {
            $categorySecondary->setName($format);
            $this->assertSame($format, $categorySecondary->getName());
        }
    }

    public function testIdPropertyType(): void
    {
        $categorySecondary = new CategorySecondary(123);
        
        $this->assertIsInt($categorySecondary->getId());
        $this->assertSame(123, $categorySecondary->getId());
    }

    public function testCategoryPrimaryIdPropertyType(): void
    {
        $categorySecondary = new CategorySecondary(1, 456);
        
        $this->assertIsInt($categorySecondary->getCategory_primary_id());
        $this->assertSame(456, $categorySecondary->getCategory_primary_id());
    }

    public function testNamePropertyNullability(): void
    {
        $categorySecondary = new CategorySecondary(1, 2, null);
        
        $this->assertNull($categorySecondary->getName());
        
        // Set to string
        $categorySecondary->setName('Test Category');
        $this->assertIsString($categorySecondary->getName());
        $this->assertSame('Test Category', $categorySecondary->getName());
    }

    public function testGetterMethodsConsistency(): void
    {
        $categorySecondary = new CategorySecondary(5, 10, 'Test Secondary Category');
        
        // Multiple calls should return same values
        $this->assertSame($categorySecondary->getId(), $categorySecondary->getId());
        $this->assertSame($categorySecondary->getCategory_primary_id(), $categorySecondary->getCategory_primary_id());
        $this->assertSame($categorySecondary->getName(), $categorySecondary->getName());
        $this->assertSame($categorySecondary->getCategoryPrimary(), $categorySecondary->getCategoryPrimary());
    }

    public function testCategoryRelationshipStructure(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Set up parent relationship
        $categorySecondary->setCategory_primary_id(1);
        $this->assertSame(1, $categorySecondary->getCategory_primary_id());
        
        // CategoryPrimary relationship is null until set by ORM
        $this->assertNull($categorySecondary->getCategoryPrimary());
    }

    public function testCommonEcommerceCategories(): void
    {
        $ecommerceCategories = [
            ['parent' => 1, 'name' => 'Smartphones'],
            ['parent' => 1, 'name' => 'Laptops'],
            ['parent' => 2, 'name' => 'T-Shirts'],
            ['parent' => 2, 'name' => 'Jeans'],
            ['parent' => 3, 'name' => 'Sofas'],
            ['parent' => 3, 'name' => 'Tables'],
            ['parent' => 4, 'name' => 'Vitamins'],
            ['parent' => 4, 'name' => 'Supplements'],
        ];
        
        foreach ($ecommerceCategories as $index => $category) {
            $categorySecondary = new CategorySecondary($index + 1, $category['parent'], $category['name']);
            
            $this->assertSame($index + 1, $categorySecondary->getId());
            $this->assertSame($category['parent'], $categorySecondary->getCategory_primary_id());
            $this->assertSame($category['name'], $categorySecondary->getName());
        }
    }

    public function testCategoryWithVeryLongDescription(): void
    {
        $categorySecondary = new CategorySecondary();
        $veryLongName = str_repeat('Very Long Secondary Category Name With Extended Description For Products That Fall Under This Specific Classification ', 5);
        
        $categorySecondary->setName($veryLongName);
        $this->assertSame($veryLongName, $categorySecondary->getName());
        $this->assertGreaterThan(500, strlen($categorySecondary->getName()));
    }

    public function testCategoryModificationWorkflow(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Initial setup
        $categorySecondary->setId(1);
        $categorySecondary->setCategory_primary_id(5);
        $categorySecondary->setName('Initial Category');
        
        $this->assertSame(1, $categorySecondary->getId());
        $this->assertSame(5, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Initial Category', $categorySecondary->getName());
        
        // Modification
        $categorySecondary->setCategory_primary_id(10);
        $categorySecondary->setName('Updated Category Name');
        
        $this->assertSame(1, $categorySecondary->getId()); // ID unchanged
        $this->assertSame(10, $categorySecondary->getCategory_primary_id()); // Parent changed
        $this->assertSame('Updated Category Name', $categorySecondary->getName()); // Name changed
    }

    public function testNegativeIds(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Note: The entity accepts int, so negative values are technically possible
        // though they might not be used in practice
        $categorySecondary->setId(-1);
        $this->assertSame(-1, $categorySecondary->getId());
        
        $categorySecondary->setCategory_primary_id(-5);
        $this->assertSame(-5, $categorySecondary->getCategory_primary_id());
    }

    public function testEntityStateAfterConstruction(): void
    {
        // Test various constructor states
        $entity1 = new CategorySecondary();
        $this->assertNull($entity1->getId());
        $this->assertNull($entity1->getCategory_primary_id());
        $this->assertSame('', $entity1->getName());
        
        $entity2 = new CategorySecondary(1);
        $this->assertSame(1, $entity2->getId());
        $this->assertNull($entity2->getCategory_primary_id());
        $this->assertSame('', $entity2->getName());
        
        $entity3 = new CategorySecondary(1, 2);
        $this->assertSame(1, $entity3->getId());
        $this->assertSame(2, $entity3->getCategory_primary_id());
        $this->assertSame('', $entity3->getName());
        
        $entity4 = new CategorySecondary(1, 2, 'Full Category');
        $this->assertSame(1, $entity4->getId());
        $this->assertSame(2, $entity4->getCategory_primary_id());
        $this->assertSame('Full Category', $entity4->getName());
    }
}
