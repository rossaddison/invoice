<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\CategoryPrimary;
use App\Invoice\Entity\CategorySecondary;
use PHPUnit\Framework\TestCase;

class CategorySecondaryEntityTest extends TestCase
{
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
        $categorySecondary = new CategorySecondary(
            id: 1,
            category_primary_id: 100,
            name: 'Laptops'
        );
        
        $this->assertSame(1, $categorySecondary->getId());
        $this->assertSame(100, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Laptops', $categorySecondary->getName());
        $this->assertNull($categorySecondary->getCategoryPrimary());
    }

    public function testConstructorWithPartialParameters(): void
    {
        $categorySecondary = new CategorySecondary(
            id: 2,
            name: 'Smartphones'
        );
        
        $this->assertSame(2, $categorySecondary->getId());
        $this->assertNull($categorySecondary->getCategory_primary_id());
        $this->assertSame('Smartphones', $categorySecondary->getName());
        $this->assertNull($categorySecondary->getCategoryPrimary());
    }

    public function testIdSetterAndGetter(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setId(50);
        
        $this->assertSame(50, $categorySecondary->getId());
    }

    public function testCategoryPrimaryIdSetterAndGetter(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setCategory_primary_id(200);
        
        $this->assertSame(200, $categorySecondary->getCategory_primary_id());
    }

    public function testNameSetterAndGetter(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setName('Desktop Computers');
        
        $this->assertSame('Desktop Computers', $categorySecondary->getName());
    }

    public function testCategoryPrimaryRelationship(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Initially null
        $this->assertNull($categorySecondary->getCategoryPrimary());
        
        // The setter is not publicly available, so we can only test the getter
        // The relationship would be populated by the ORM
    }

    public function testZeroId(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setId(0);
        
        $this->assertSame(0, $categorySecondary->getId());
    }

    public function testNegativeId(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setId(-1);
        
        $this->assertSame(-1, $categorySecondary->getId());
    }

    public function testLargeId(): void
    {
        $categorySecondary = new CategorySecondary();
        $largeId = PHP_INT_MAX;
        
        $categorySecondary->setId($largeId);
        $this->assertSame($largeId, $categorySecondary->getId());
    }

    public function testZeroCategoryPrimaryId(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setCategory_primary_id(0);
        
        $this->assertSame(0, $categorySecondary->getCategory_primary_id());
    }

    public function testNegativeCategoryPrimaryId(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setCategory_primary_id(-1);
        
        $this->assertSame(-1, $categorySecondary->getCategory_primary_id());
    }

    public function testLargeCategoryPrimaryId(): void
    {
        $categorySecondary = new CategorySecondary();
        $largeId = PHP_INT_MAX;
        
        $categorySecondary->setCategory_primary_id($largeId);
        $this->assertSame($largeId, $categorySecondary->getCategory_primary_id());
    }

    public function testEmptyStringName(): void
    {
        $categorySecondary = new CategorySecondary();
        $categorySecondary->setName('');
        
        $this->assertSame('', $categorySecondary->getName());
    }

    public function testElectronicsSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $electronicsSubcategories = [
            'Laptops & Notebooks',
            'Desktop Computers',
            'Tablets',
            'Smartphones',
            'Computer Monitors',
            'Keyboards & Mice',
            'Speakers & Headphones',
            'Cameras & Photography',
            'Gaming Consoles',
            'Smart Home Devices',
            'Wearable Technology',
            'Audio Equipment',
            'Video Equipment',
            'Computer Components'
        ];
        
        foreach ($electronicsSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testClothingSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $clothingSubcategories = [
            'Men\'s Clothing',
            'Women\'s Clothing',
            'Children\'s Clothing',
            'Shoes & Footwear',
            'Accessories',
            'Jewelry & Watches',
            'Bags & Luggage',
            'Sportswear',
            'Formal Wear',
            'Casual Wear',
            'Outdoor Clothing',
            'Undergarments',
            'Swimwear',
            'Winter Clothing'
        ];
        
        foreach ($clothingSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testHomeGardenSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $homeGardenSubcategories = [
            'Furniture',
            'Home Decor',
            'Kitchen & Dining',
            'Bedding & Bath',
            'Lighting',
            'Garden Tools',
            'Plants & Seeds',
            'Outdoor Furniture',
            'Home Improvement',
            'Appliances',
            'Storage & Organization',
            'Cleaning Supplies',
            'Security Systems',
            'HVAC & Air Quality'
        ];
        
        foreach ($homeGardenSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testSportsOutdoorSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $sportsSubcategories = [
            'Exercise & Fitness',
            'Team Sports',
            'Water Sports',
            'Winter Sports',
            'Cycling',
            'Running & Jogging',
            'Golf',
            'Tennis & Racquet Sports',
            'Outdoor Recreation',
            'Hunting & Fishing',
            'Camping & Hiking',
            'Sports Nutrition',
            'Athletic Footwear',
            'Sports Equipment'
        ];
        
        foreach ($sportsSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testAutomotiveSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $automotiveSubcategories = [
            'Car Parts & Accessories',
            'Motorcycle Parts',
            'Tools & Equipment',
            'Car Care & Detailing',
            'Tires & Wheels',
            'Interior Accessories',
            'Exterior Accessories',
            'Performance Parts',
            'Replacement Parts',
            'Car Electronics',
            'GPS & Navigation',
            'Car Audio & Video',
            'Safety & Security',
            'Fluids & Chemicals'
        ];
        
        foreach ($automotiveSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testBusinessServiceSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $businessSubcategories = [
            'Web Development',
            'Mobile App Development',
            'Graphic Design',
            'Digital Marketing',
            'SEO Services',
            'Content Writing',
            'Translation Services',
            'Legal Services',
            'Accounting Services',
            'Business Consulting',
            'Project Management',
            'Data Entry',
            'Virtual Assistant',
            'Technical Support'
        ];
        
        foreach ($businessSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testHealthBeautySubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $healthBeautySubcategories = [
            'Skincare',
            'Hair Care',
            'Makeup & Cosmetics',
            'Fragrances',
            'Personal Care',
            'Health Supplements',
            'Medical Supplies',
            'Fitness Equipment',
            'Massage & Relaxation',
            'Oral Care',
            'Vision Care',
            'First Aid',
            'Wellness Products',
            'Beauty Tools'
        ];
        
        foreach ($healthBeautySubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testBookMediaSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $bookMediaSubcategories = [
            'Fiction Books',
            'Non-Fiction Books',
            'Educational Books',
            'Children\'s Books',
            'eBooks',
            'Audiobooks',
            'Movies & TV Shows',
            'Music CDs',
            'Video Games',
            'Board Games',
            'Magazines',
            'Comics & Graphic Novels',
            'Software',
            'Digital Downloads'
        ];
        
        foreach ($bookMediaSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testCategoryHierarchySetup(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $categorySecondary->setId(1);
        $categorySecondary->setCategory_primary_id(100);
        $categorySecondary->setName('Desktop Computers');
        
        $this->assertSame(1, $categorySecondary->getId());
        $this->assertSame(100, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Desktop Computers', $categorySecondary->getName());
    }

    public function testMethodReturnTypes(): void
    {
        $categorySecondary = new CategorySecondary(
            id: 1,
            category_primary_id: 100,
            name: 'Test Category'
        );
        
        $this->assertIsInt($categorySecondary->getId());
        $this->assertIsInt($categorySecondary->getCategory_primary_id());
        $this->assertIsString($categorySecondary->getName());
        $this->assertNull($categorySecondary->getCategoryPrimary());
    }

    public function testSpecialCharactersInNames(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $specialNames = [
            'Men\'s Clothing',
            'Women\'s Accessories',
            'Children\'s Toys',
            'Home & Garden',
            'Arts & Crafts',
            'Health & Beauty',
            'Sports & Recreation',
            'Food & Beverages',
            'Books & Media',
            'Pets & Animals'
        ];
        
        foreach ($specialNames as $name) {
            $categorySecondary->setName($name);
            $this->assertSame($name, $categorySecondary->getName());
        }
    }

    public function testNamesWithNumbers(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $numberedNames = [
            'Category 1',
            'Subcategory 2.0',
            'Type A1',
            'Model X5',
            'Version 3.5',
            'Generation 4',
            'Series 100',
            'Class B2',
            'Level 5 Products',
            'Tier 1 Services'
        ];
        
        foreach ($numberedNames as $name) {
            $categorySecondary->setName($name);
            $this->assertSame($name, $categorySecondary->getName());
        }
    }

    public function testNamesWithPunctuation(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $punctuationNames = [
            'High-End Products',
            'All-in-One Solutions',
            'Ready-to-Use Items',
            'State-of-the-Art Technology',
            'Plug-and-Play Devices',
            'Do-It-Yourself Kits',
            'End-to-End Services',
            'Point-of-Sale Systems',
            'Real-Time Solutions',
            'Multi-Purpose Tools'
        ];
        
        foreach ($punctuationNames as $name) {
            $categorySecondary->setName($name);
            $this->assertSame($name, $categorySecondary->getName());
        }
    }

    public function testUnicodeInNames(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $unicodeNames = [
            '笔记本电脑 (Laptops)',
            'Électronique Grand Public',
            'Möbel & Einrichtung',
            'Ropa y Complementos',
            'Спортивные товары',
            'Υπολογιστές & Τεχνολογία',
            'コンピューター関連',
            'أجهزة إلكترونية',
            'Zdrowie i Uroda',
            'Domácí spotřebiče'
        ];
        
        foreach ($unicodeNames as $name) {
            $categorySecondary->setName($name);
            $this->assertSame($name, $categorySecondary->getName());
        }
    }

    public function testLongCategoryNames(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $longName = 'Professional Enterprise Software Development Tools and Integrated Development Environment Solutions for Large-Scale Organizations';
        $categorySecondary->setName($longName);
        
        $this->assertSame($longName, $categorySecondary->getName());
    }

    public function testConstructorAndSetterConsistency(): void
    {
        $categoryName = 'Gaming Accessories';
        $primaryId = 50;
        
        // Create via constructor
        $category1 = new CategorySecondary(
            id: 1,
            category_primary_id: $primaryId,
            name: $categoryName
        );
        
        // Create via setters
        $category2 = new CategorySecondary();
        $category2->setId(1);
        $category2->setCategory_primary_id($primaryId);
        $category2->setName($categoryName);
        
        $this->assertSame($category1->getId(), $category2->getId());
        $this->assertSame($category1->getCategory_primary_id(), $category2->getCategory_primary_id());
        $this->assertSame($category1->getName(), $category2->getName());
    }

    public function testEntityStateConsistency(): void
    {
        $categorySecondary = new CategorySecondary(
            id: 999,
            category_primary_id: 888,
            name: 'Initial Category'
        );
        
        // Verify initial state
        $this->assertSame(999, $categorySecondary->getId());
        $this->assertSame(888, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Initial Category', $categorySecondary->getName());
        
        // Modify all properties
        $categorySecondary->setId(111);
        $categorySecondary->setCategory_primary_id(222);
        $categorySecondary->setName('Modified Category');
        
        // Verify changes
        $this->assertSame(111, $categorySecondary->getId());
        $this->assertSame(222, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Modified Category', $categorySecondary->getName());
    }

    public function testTechnologySubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $techSubcategories = [
            'Cloud Computing',
            'Artificial Intelligence',
            'Machine Learning',
            'Data Analytics',
            'Cybersecurity',
            'Blockchain',
            'IoT Devices',
            'Mobile Development',
            'Web Development',
            'Database Management',
            'Network Infrastructure',
            'DevOps Tools',
            'API Services',
            'Software Testing'
        ];
        
        foreach ($techSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testEducationSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $educationSubcategories = [
            'Online Courses',
            'Certification Programs',
            'Technical Training',
            'Language Learning',
            'Professional Development',
            'Academic Subjects',
            'Skill Development',
            'Career Training',
            'Corporate Training',
            'Workshops & Seminars',
            'Tutoring Services',
            'Educational Software',
            'Learning Materials',
            'Exam Preparation'
        ];
        
        foreach ($educationSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testFinancialServiceSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $financialSubcategories = [
            'Investment Services',
            'Insurance Products',
            'Banking Services',
            'Loan Services',
            'Tax Preparation',
            'Financial Planning',
            'Accounting Services',
            'Payroll Services',
            'Credit Services',
            'Retirement Planning',
            'Estate Planning',
            'Risk Management',
            'Wealth Management',
            'Financial Software'
        ];
        
        foreach ($financialSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testFoodBeverageSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $foodBeverageSubcategories = [
            'Fresh Produce',
            'Dairy Products',
            'Meat & Poultry',
            'Seafood',
            'Bakery Items',
            'Beverages',
            'Snacks & Candy',
            'Frozen Foods',
            'Canned Goods',
            'Organic Products',
            'International Foods',
            'Baby Food',
            'Pet Food',
            'Cooking Ingredients'
        ];
        
        foreach ($foodBeverageSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testEdgeCaseNames(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Single character
        $categorySecondary->setName('A');
        $this->assertSame('A', $categorySecondary->getName());
        
        // Numbers only
        $categorySecondary->setName('12345');
        $this->assertSame('12345', $categorySecondary->getName());
        
        // Special characters only
        $categorySecondary->setName('***');
        $this->assertSame('***', $categorySecondary->getName());
        
        // Mixed content
        $categorySecondary->setName('Cat-2024_v1.0');
        $this->assertSame('Cat-2024_v1.0', $categorySecondary->getName());
    }

    public function testWhitespaceHandling(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Leading/trailing spaces
        $categorySecondary->setName(' Electronics ');
        $this->assertSame(' Electronics ', $categorySecondary->getName());
        
        // Multiple spaces
        $categorySecondary->setName('Home    Appliances');
        $this->assertSame('Home    Appliances', $categorySecondary->getName());
        
        // Tab characters
        $categorySecondary->setName("Office\tSupplies");
        $this->assertSame("Office\tSupplies", $categorySecondary->getName());
        
        // Newline characters
        $categorySecondary->setName("Multi\nLine\nCategory");
        $this->assertSame("Multi\nLine\nCategory", $categorySecondary->getName());
    }

    public function testHierarchicalRelationships(): void
    {
        $categorySecondary = new CategorySecondary();
        
        // Electronics -> Computers
        $categorySecondary->setCategory_primary_id(1);
        $categorySecondary->setName('Desktop Computers');
        $this->assertSame(1, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Desktop Computers', $categorySecondary->getName());
        
        // Clothing -> Men's Wear
        $categorySecondary->setCategory_primary_id(2);
        $categorySecondary->setName('Men\'s Shirts');
        $this->assertSame(2, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Men\'s Shirts', $categorySecondary->getName());
        
        // Home & Garden -> Furniture
        $categorySecondary->setCategory_primary_id(3);
        $categorySecondary->setName('Living Room Furniture');
        $this->assertSame(3, $categorySecondary->getCategory_primary_id());
        $this->assertSame('Living Room Furniture', $categorySecondary->getName());
    }

    public function testConstructorParameterCombinations(): void
    {
        // Only ID
        $category1 = new CategorySecondary(id: 1);
        $this->assertSame(1, $category1->getId());
        $this->assertNull($category1->getCategory_primary_id());
        $this->assertSame('', $category1->getName());
        
        // ID and primary ID
        $category2 = new CategorySecondary(id: 2, category_primary_id: 100);
        $this->assertSame(2, $category2->getId());
        $this->assertSame(100, $category2->getCategory_primary_id());
        $this->assertSame('', $category2->getName());
        
        // ID and name
        $category3 = new CategorySecondary(id: 3, name: 'Test Category');
        $this->assertSame(3, $category3->getId());
        $this->assertNull($category3->getCategory_primary_id());
        $this->assertSame('Test Category', $category3->getName());
        
        // Primary ID and name
        $category4 = new CategorySecondary(category_primary_id: 200, name: 'Another Test');
        $this->assertNull($category4->getId());
        $this->assertSame(200, $category4->getCategory_primary_id());
        $this->assertSame('Another Test', $category4->getName());
    }

    public function testRealWorldCategoryExamples(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $realWorldExamples = [
            // Electronics subcategories
            [1, 'Gaming Laptops'],
            [1, 'Business Laptops'],
            [1, 'Ultrabooks'],
            [1, ' 2-in-1 Laptops'],
            
            // Clothing subcategories
            [2, 'Casual Shirts'],
            [2, 'Formal Shirts'],
            [2, 'T-Shirts'],
            [2, 'Polo Shirts'],
            
            // Home & Garden subcategories
            [3, 'Sofas & Couches'],
            [3, 'Coffee Tables'],
            [3, 'TV Stands'],
            [3, 'Recliners']
        ];
        
        foreach ($realWorldExamples as [$primaryId, $name]) {
            $categorySecondary->setCategory_primary_id($primaryId);
            $categorySecondary->setName($name);
            
            $this->assertSame($primaryId, $categorySecondary->getCategory_primary_id());
            $this->assertSame($name, $categorySecondary->getName());
        }
    }

    public function testSeasonalSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $seasonalSubcategories = [
            'Spring Fashion',
            'Summer Essentials',
            'Fall Collections',
            'Winter Gear',
            'Holiday Decorations',
            'Back-to-School Items',
            'Valentine\'s Gifts',
            'Easter Products',
            'Halloween Costumes',
            'Christmas Gifts',
            'New Year Party Supplies',
            'Black Friday Specials'
        ];
        
        foreach ($seasonalSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }

    public function testBrandSpecificSubcategories(): void
    {
        $categorySecondary = new CategorySecondary();
        
        $brandSubcategories = [
            'Apple Products',
            'Samsung Electronics',
            'Dell Computers',
            'HP Devices',
            'Microsoft Software',
            'Google Services',
            'Amazon Products',
            'Adobe Solutions',
            'Oracle Systems',
            'IBM Services',
            'Intel Components',
            'NVIDIA Graphics',
            'AMD Processors',
            'Cisco Networking'
        ];
        
        foreach ($brandSubcategories as $subcategory) {
            $categorySecondary->setName($subcategory);
            $this->assertSame($subcategory, $categorySecondary->getName());
        }
    }
}