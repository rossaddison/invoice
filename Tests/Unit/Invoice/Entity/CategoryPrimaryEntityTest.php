<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\CategoryPrimary;
use PHPUnit\Framework\TestCase;

class CategoryPrimaryEntityTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $this->assertNull($categoryPrimary->getId());
        $this->assertSame('', $categoryPrimary->getName());
    }

    public function testConstructorWithName(): void
    {
        $categoryPrimary = new CategoryPrimary('Electronics');
        
        $this->assertNull($categoryPrimary->getId());
        $this->assertSame('Electronics', $categoryPrimary->getName());
    }

    public function testConstructorWithEmptyName(): void
    {
        $categoryPrimary = new CategoryPrimary('');
        
        $this->assertNull($categoryPrimary->getId());
        $this->assertSame('', $categoryPrimary->getName());
    }

    public function testGetIdReturnsNull(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $this->assertNull($categoryPrimary->getId());
    }

    public function testNameSetterAndGetter(): void
    {
        $categoryPrimary = new CategoryPrimary();
        $categoryPrimary->setName('Furniture');
        
        $this->assertSame('Furniture', $categoryPrimary->getName());
    }

    public function testSetEmptyName(): void
    {
        $categoryPrimary = new CategoryPrimary('Initial Name');
        $categoryPrimary->setName('');
        
        $this->assertSame('', $categoryPrimary->getName());
    }

    public function testCommonProductCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $commonCategories = [
            'Electronics',
            'Clothing',
            'Home & Garden',
            'Sports & Outdoors',
            'Books',
            'Beauty & Personal Care',
            'Health & Wellness',
            'Automotive',
            'Toys & Games',
            'Food & Beverages',
            'Office Supplies',
            'Pet Supplies',
            'Music & Movies',
            'Jewelry & Accessories'
        ];
        
        foreach ($commonCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testBusinessCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $businessCategories = [
            'Professional Services',
            'Consulting',
            'Software & Technology',
            'Marketing & Advertising',
            'Legal Services',
            'Financial Services',
            'Real Estate',
            'Healthcare Services',
            'Education & Training',
            'Manufacturing',
            'Construction',
            'Transportation',
            'Hospitality',
            'Retail Trade'
        ];
        
        foreach ($businessCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testIndustrySpecificCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        // Technology industry
        $categoryPrimary->setName('Software Development');
        $this->assertSame('Software Development', $categoryPrimary->getName());
        
        // Healthcare industry
        $categoryPrimary->setName('Medical Equipment');
        $this->assertSame('Medical Equipment', $categoryPrimary->getName());
        
        // Manufacturing industry
        $categoryPrimary->setName('Industrial Machinery');
        $this->assertSame('Industrial Machinery', $categoryPrimary->getName());
        
        // Education industry
        $categoryPrimary->setName('Educational Materials');
        $this->assertSame('Educational Materials', $categoryPrimary->getName());
        
        // Automotive industry
        $categoryPrimary->setName('Vehicle Parts & Accessories');
        $this->assertSame('Vehicle Parts & Accessories', $categoryPrimary->getName());
    }

    public function testServiceCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $serviceCategories = [
            'IT Support',
            'Web Development',
            'Graphic Design',
            'Content Writing',
            'Digital Marketing',
            'SEO Services',
            'Cloud Services',
            'Data Analytics',
            'Cybersecurity',
            'System Integration',
            'Project Management',
            'Quality Assurance',
            'User Experience Design',
            'Technical Documentation'
        ];
        
        foreach ($serviceCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testRetailCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $retailCategories = [
            'Apparel & Fashion',
            'Home Improvement',
            'Kitchen & Dining',
            'Electronics & Gadgets',
            'Sporting Goods',
            'Baby & Kids',
            'Garden & Outdoor',
            'Arts & Crafts',
            'Musical Instruments',
            'Collectibles & Antiques',
            'Gift Items',
            'Seasonal Products',
            'Travel Accessories',
            'Fitness Equipment'
        ];
        
        foreach ($retailCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testLongCategoryNames(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $longName = 'Professional Enterprise Software Development and Implementation Services for Large-Scale Organizations';
        $categoryPrimary->setName($longName);
        
        $this->assertSame($longName, $categoryPrimary->getName());
    }

    public function testSpecialCharactersInNames(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $specialNames = [
            'Health & Wellness',
            'Arts & Crafts',
            'Food & Beverages',
            'Toys & Games',
            'Books & Media',
            'Home & Garden',
            'Sports & Recreation',
            'Beauty & Personal Care',
            'Office & School Supplies',
            'Pet & Animal Care'
        ];
        
        foreach ($specialNames as $name) {
            $categoryPrimary->setName($name);
            $this->assertSame($name, $categoryPrimary->getName());
        }
    }

    public function testCategoryNamesWithNumbers(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $numberedNames = [
            'Category 1',
            'Type 2 Products',
            'Level 3 Services',
            'Grade A Materials',
            'Class B Equipment',
            'Tier 1 Support',
            'Version 2.0 Software',
            '24/7 Services',
            '365-Day Support',
            'Top 10 Products'
        ];
        
        foreach ($numberedNames as $name) {
            $categoryPrimary->setName($name);
            $this->assertSame($name, $categoryPrimary->getName());
        }
    }

    public function testCategoryNamesWithPunctuation(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $punctuationNames = [
            'High-Performance Computing',
            'State-of-the-Art Technology',
            'Real-Time Processing',
            'End-to-End Solutions',
            'Plug-and-Play Devices',
            'Do-It-Yourself Kits',
            'Ready-to-Use Products',
            'All-in-One Systems',
            'Point-of-Sale Equipment',
            'Multi-Purpose Tools'
        ];
        
        foreach ($punctuationNames as $name) {
            $categoryPrimary->setName($name);
            $this->assertSame($name, $categoryPrimary->getName());
        }
    }

    public function testUnicodeInNames(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $unicodeNames = [
            '电子产品 (Electronics)',
            'Électronique',
            'Möbel & Einrichtung',
            'Ropa y Accesorios',
            'Спорт и отдых',
            'Παιχνίδια και Παιδικά',
            'コンピュータ機器',
            'الإلكترونيات',
            'Zdrowie i Uroda',
            'Kráska a zdraví'
        ];
        
        foreach ($unicodeNames as $name) {
            $categoryPrimary->setName($name);
            $this->assertSame($name, $categoryPrimary->getName());
        }
    }

    public function testConstructorAndSetterConsistency(): void
    {
        $categoryName = 'Test Category';
        
        // Create via constructor
        $category1 = new CategoryPrimary($categoryName);
        
        // Create via setter
        $category2 = new CategoryPrimary();
        $category2->setName($categoryName);
        
        $this->assertSame($category1->getName(), $category2->getName());
        $this->assertSame($categoryName, $category1->getName());
        $this->assertSame($categoryName, $category2->getName());
    }

    public function testNullIdBehavior(): void
    {
        $categoryPrimary = new CategoryPrimary('Test');
        
        // ID should remain null after construction and name changes
        $this->assertNull($categoryPrimary->getId());
        
        $categoryPrimary->setName('Updated Name');
        $this->assertNull($categoryPrimary->getId());
    }

    public function testMethodReturnTypes(): void
    {
        $categoryPrimary = new CategoryPrimary('Electronics');
        
        $this->assertNull($categoryPrimary->getId());
        $this->assertIsString($categoryPrimary->getName());
    }

    public function testCategoryNameOverwriting(): void
    {
        $categoryPrimary = new CategoryPrimary('Initial Category');
        
        $this->assertSame('Initial Category', $categoryPrimary->getName());
        
        $categoryPrimary->setName('Updated Category');
        $this->assertSame('Updated Category', $categoryPrimary->getName());
        
        $categoryPrimary->setName('Final Category');
        $this->assertSame('Final Category', $categoryPrimary->getName());
    }

    public function testEcommerceCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $ecommerceCategories = [
            'Digital Downloads',
            'Subscription Services',
            'Gift Cards & Vouchers',
            'Marketplace Products',
            'Wholesale Items',
            'Clearance & Sale',
            'New Arrivals',
            'Best Sellers',
            'Premium Products',
            'Eco-Friendly Items',
            'Handmade Products',
            'Imported Goods',
            'Local Products',
            'Seasonal Collections'
        ];
        
        foreach ($ecommerceCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testB2BCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $b2bCategories = [
            'Enterprise Software',
            'Industrial Equipment',
            'Office Furniture',
            'Professional Tools',
            'Safety Equipment',
            'Laboratory Supplies',
            'Networking Hardware',
            'Server Components',
            'Security Systems',
            'Communication Devices',
            'Testing Equipment',
            'Manufacturing Tools',
            'Quality Control',
            'Compliance Services'
        ];
        
        foreach ($b2bCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testCreativeIndustryCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $creativeCategories = [
            'Graphic Design Services',
            'Video Production',
            'Photography',
            'Web Design',
            'Branding & Identity',
            'Print Design',
            'Digital Art',
            'Animation Services',
            'Content Creation',
            'Social Media Design',
            'Marketing Materials',
            'Packaging Design',
            'UI/UX Design',
            'Creative Consulting'
        ];
        
        foreach ($creativeCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testTechnologyCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $techCategories = [
            'Cloud Computing',
            'Artificial Intelligence',
            'Machine Learning',
            'Data Science',
            'Blockchain Technology',
            'IoT Solutions',
            'Mobile Development',
            'Web Applications',
            'API Development',
            'DevOps Services',
            'Cybersecurity Solutions',
            'Database Management',
            'System Architecture',
            'Technical Support'
        ];
        
        foreach ($techCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testHealthcareCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $healthcareCategories = [
            'Medical Devices',
            'Pharmaceuticals',
            'Healthcare Software',
            'Diagnostic Equipment',
            'Therapeutic Services',
            'Medical Supplies',
            'Health Monitoring',
            'Rehabilitation Equipment',
            'Laboratory Services',
            'Telemedicine',
            'Health Analytics',
            'Clinical Research',
            'Medical Training',
            'Healthcare Consulting'
        ];
        
        foreach ($healthcareCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testEdgeCaseNames(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        // Single character
        $categoryPrimary->setName('A');
        $this->assertSame('A', $categoryPrimary->getName());
        
        // Numbers only
        $categoryPrimary->setName('12345');
        $this->assertSame('12345', $categoryPrimary->getName());
        
        // Special characters only
        $categoryPrimary->setName('***');
        $this->assertSame('***', $categoryPrimary->getName());
        
        // Mixed alphanumeric with symbols
        $categoryPrimary->setName('Cat-2024_v1.0');
        $this->assertSame('Cat-2024_v1.0', $categoryPrimary->getName());
    }

    public function testWhitespaceHandling(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        // Leading/trailing spaces
        $categoryPrimary->setName(' Electronics ');
        $this->assertSame(' Electronics ', $categoryPrimary->getName());
        
        // Multiple spaces
        $categoryPrimary->setName('Home    Appliances');
        $this->assertSame('Home    Appliances', $categoryPrimary->getName());
        
        // Tab characters
        $categoryPrimary->setName("Office\tSupplies");
        $this->assertSame("Office\tSupplies", $categoryPrimary->getName());
        
        // Newline characters
        $categoryPrimary->setName("Multi\nLine\nCategory");
        $this->assertSame("Multi\nLine\nCategory", $categoryPrimary->getName());
    }

    public function testHierarchicalCategoryNames(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $hierarchicalNames = [
            'Electronics > Computers',
            'Home & Garden > Furniture',
            'Clothing > Men\'s Apparel',
            'Sports > Outdoor Equipment',
            'Books > Technical Manuals',
            'Health > Fitness Equipment',
            'Automotive > Car Parts',
            'Tools > Power Tools',
            'Kitchen > Small Appliances',
            'Office > Computer Accessories'
        ];
        
        foreach ($hierarchicalNames as $name) {
            $categoryPrimary->setName($name);
            $this->assertSame($name, $categoryPrimary->getName());
        }
    }

    public function testCategoryNamesWithBrands(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $brandNames = [
            'Apple Products',
            'Microsoft Solutions',
            'Google Services',
            'Amazon Products',
            'Samsung Electronics',
            'Dell Computers',
            'HP Equipment',
            'Adobe Software',
            'Oracle Solutions',
            'IBM Services'
        ];
        
        foreach ($brandNames as $name) {
            $categoryPrimary->setName($name);
            $this->assertSame($name, $categoryPrimary->getName());
        }
    }

    public function testSeasonalCategories(): void
    {
        $categoryPrimary = new CategoryPrimary();
        
        $seasonalCategories = [
            'Spring Collection',
            'Summer Essentials',
            'Fall Fashion',
            'Winter Gear',
            'Holiday Specials',
            'Back to School',
            'Valentine\'s Day',
            'Easter Products',
            'Halloween Items',
            'Christmas Gifts',
            'New Year Promotions',
            'Black Friday Deals'
        ];
        
        foreach ($seasonalCategories as $category) {
            $categoryPrimary->setName($category);
            $this->assertSame($category, $categoryPrimary->getName());
        }
    }

    public function testConstructorParameterHandling(): void
    {
        // Explicit null (though not typically done)
        $category1 = new CategoryPrimary(null);
        $this->assertNull($category1->getName());
        
        // Empty string
        $category2 = new CategoryPrimary('');
        $this->assertSame('', $category2->getName());
        
        // Normal string
        $category3 = new CategoryPrimary('Normal Category');
        $this->assertSame('Normal Category', $category3->getName());
    }

    public function testStateConsistency(): void
    {
        $categoryPrimary = new CategoryPrimary('Initial Category');
        
        // Verify initial state
        $this->assertSame('Initial Category', $categoryPrimary->getName());
        $this->assertNull($categoryPrimary->getId());
        
        // Change name
        $categoryPrimary->setName('Updated Category');
        $this->assertSame('Updated Category', $categoryPrimary->getName());
        $this->assertNull($categoryPrimary->getId());
        
        // Set empty name
        $categoryPrimary->setName('');
        $this->assertSame('', $categoryPrimary->getName());
        $this->assertNull($categoryPrimary->getId());
    }
}