<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\ItemLookup;
use PHPUnit\Framework\TestCase;

class ItemLookupEntityTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $itemLookup = new ItemLookup();
        
        $this->assertSame('', $itemLookup->getId());
        $this->assertSame('', $itemLookup->getName());
        $this->assertSame('', $itemLookup->getDescription());
        $this->assertNull($itemLookup->getPrice());
    }

    public function testConstructorWithAllParameters(): void
    {
        $itemLookup = new ItemLookup(
            id: 1,
            name: 'Premium Widget',
            description: 'High-quality widget for professional use',
            price: 99.99
        );
        
        $this->assertSame('1', $itemLookup->getId());
        $this->assertSame('Premium Widget', $itemLookup->getName());
        $this->assertSame('High-quality widget for professional use', $itemLookup->getDescription());
        $this->assertSame(99.99, $itemLookup->getPrice());
    }

    public function testConstructorWithPartialParameters(): void
    {
        $itemLookup = new ItemLookup(
            id: 2,
            name: 'Basic Item',
            description: 'Simple item description'
        );
        
        $this->assertSame('2', $itemLookup->getId());
        $this->assertSame('Basic Item', $itemLookup->getName());
        $this->assertSame('Simple item description', $itemLookup->getDescription());
        $this->assertNull($itemLookup->getPrice());
    }

    public function testIdSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(50);
        
        $this->assertSame('50', $itemLookup->getId());
    }

    public function testNameSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setName('Test Product');
        
        $this->assertSame('Test Product', $itemLookup->getName());
    }

    public function testDescriptionSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setDescription('Detailed product description');
        
        $this->assertSame('Detailed product description', $itemLookup->getDescription());
    }

    public function testPriceSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(25.50);
        
        $this->assertSame(25.50, $itemLookup->getPrice());
    }

    public function testIdTypeConversion(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(999);
        
        $this->assertIsString($itemLookup->getId());
        $this->assertSame('999', $itemLookup->getId());
    }

    public function testZeroId(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(0);
        
        $this->assertSame('0', $itemLookup->getId());
    }

    public function testNegativeId(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(-1);
        
        $this->assertSame('-1', $itemLookup->getId());
    }

    public function testLargeId(): void
    {
        $itemLookup = new ItemLookup();
        $largeId = PHP_INT_MAX;
        
        $itemLookup->setId($largeId);
        $this->assertSame((string)$largeId, $itemLookup->getId());
    }

    public function testZeroPrice(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(0.0);
        
        $this->assertSame(0.0, $itemLookup->getPrice());
    }

    public function testNegativePrice(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(-10.50);
        
        $this->assertSame(-10.50, $itemLookup->getPrice());
    }

    public function testHighPrecisionPrice(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(123.456789);
        
        $this->assertSame(123.456789, $itemLookup->getPrice());
    }

    public function testVeryLargePrice(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(999999.99);
        
        $this->assertSame(999999.99, $itemLookup->getPrice());
    }

    public function testVerySmallPrice(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(0.01);
        
        $this->assertSame(0.01, $itemLookup->getPrice());
    }

    public function testEmptyStringFields(): void
    {
        $itemLookup = new ItemLookup();
        
        $itemLookup->setName('');
        $itemLookup->setDescription('');
        
        $this->assertSame('', $itemLookup->getName());
        $this->assertSame('', $itemLookup->getDescription());
    }

    public function testCommonProductNames(): void
    {
        $itemLookup = new ItemLookup();
        
        $productNames = [
            'Laptop Computer',
            'Wireless Mouse',
            'USB Cable',
            'Bluetooth Headphones',
            'External Hard Drive',
            'Smartphone Case',
            'Power Bank',
            'Monitor Stand',
            'Keyboard Cover',
            'Webcam',
            'Microphone',
            'Speaker System',
            'Graphics Card',
            'Memory Module'
        ];
        
        foreach ($productNames as $name) {
            $itemLookup->setName($name);
            $this->assertSame($name, $itemLookup->getName());
        }
    }

    public function testServiceNames(): void
    {
        $itemLookup = new ItemLookup();
        
        $serviceNames = [
            'Consulting Services',
            'Technical Support',
            'Software Development',
            'Web Design',
            'Data Analysis',
            'Project Management',
            'Quality Assurance',
            'System Integration',
            'Training Services',
            'Maintenance Contract',
            'Cloud Services',
            'Security Audit',
            'Performance Optimization',
            'Custom Development'
        ];
        
        foreach ($serviceNames as $name) {
            $itemLookup->setName($name);
            $this->assertSame($name, $itemLookup->getName());
        }
    }

    public function testDetailedDescriptions(): void
    {
        $itemLookup = new ItemLookup();
        
        $descriptions = [
            'High-performance laptop computer with Intel Core i7 processor, 16GB RAM, and 512GB SSD storage.',
            'Ergonomic wireless mouse with precision optical sensor and long-lasting battery life.',
            'Professional-grade USB-C cable with fast charging and data transfer capabilities.',
            'Noise-cancelling Bluetooth headphones with premium sound quality and comfortable design.',
            'Portable external hard drive with 2TB storage capacity and USB 3.0 connectivity.',
            'Durable smartphone protective case with shock-absorbing material and precise cutouts.',
            'High-capacity power bank with multiple charging ports and LED battery indicator.',
            'Adjustable monitor stand with cable management and ergonomic viewing angles.',
            'Silicone keyboard cover providing protection from dust, spills, and wear.',
            'Full HD webcam with auto-focus and built-in microphone for clear video calls.'
        ];
        
        foreach ($descriptions as $description) {
            $itemLookup->setDescription($description);
            $this->assertSame($description, $itemLookup->getDescription());
        }
    }

    public function testTechnicalDescriptions(): void
    {
        $itemLookup = new ItemLookup();
        
        $technicalDescriptions = [
            'API Gateway service providing secure and scalable access to microservices architecture.',
            'Machine learning model training platform with GPU acceleration and distributed computing.',
            'Enterprise-grade database management system with high availability and automatic backup.',
            'Real-time analytics dashboard with customizable visualizations and data export features.',
            'Cloud-based content delivery network with global edge locations and SSL encryption.',
            'Automated testing framework supporting unit, integration, and end-to-end testing.',
            'Identity and access management solution with multi-factor authentication and role-based permissions.',
            'Container orchestration platform with auto-scaling and service mesh integration.',
            'Business intelligence tool with drag-and-drop report builder and scheduled delivery.',
            'DevOps pipeline automation with continuous integration and deployment capabilities.'
        ];
        
        foreach ($technicalDescriptions as $description) {
            $itemLookup->setDescription($description);
            $this->assertSame($description, $itemLookup->getDescription());
        }
    }

    public function testVariousPriceRanges(): void
    {
        $itemLookup = new ItemLookup();
        
        $priceRanges = [
            ['Budget Item', 9.99],
            ['Standard Product', 49.99],
            ['Premium Service', 199.99],
            ['Enterprise Solution', 999.99],
            ['Luxury Product', 2499.99],
            ['Professional Service', 150.00],
            ['Basic Package', 29.95],
            ['Advanced Package', 89.95],
            ['Ultimate Package', 299.95],
            ['Custom Solution', 5000.00]
        ];
        
        foreach ($priceRanges as [$name, $price]) {
            $itemLookup->setName($name);
            $itemLookup->setPrice($price);
            
            $this->assertSame($name, $itemLookup->getName());
            $this->assertSame($price, $itemLookup->getPrice());
        }
    }

    public function testProductCategories(): void
    {
        $itemLookup = new ItemLookup();
        
        $products = [
            ['Electronics', 'Consumer electronics and gadgets for everyday use', 299.99],
            ['Software', 'Professional software licenses and subscriptions', 99.99],
            ['Hardware', 'Computer hardware components and peripherals', 149.99],
            ['Services', 'Professional consulting and support services', 75.00],
            ['Training', 'Educational courses and certification programs', 199.99],
            ['Support', 'Technical support and maintenance contracts', 49.99],
            ['Accessories', 'Additional accessories and add-on products', 24.99],
            ['Supplies', 'Office supplies and consumable materials', 19.99],
            ['Equipment', 'Specialized equipment and tools', 499.99],
            ['Maintenance', 'Regular maintenance and service packages', 89.99]
        ];
        
        foreach ($products as [$name, $description, $price]) {
            $itemLookup->setName($name);
            $itemLookup->setDescription($description);
            $itemLookup->setPrice($price);
            
            $this->assertSame($name, $itemLookup->getName());
            $this->assertSame($description, $itemLookup->getDescription());
            $this->assertSame($price, $itemLookup->getPrice());
        }
    }

    public function testSpecialCharactersInNames(): void
    {
        $itemLookup = new ItemLookup();
        
        $specialNames = [
            'Product & Service Bundle',
            'High-Performance Computing',
            'All-in-One Solution',
            'Plug & Play Device',
            'Ready-to-Use Package',
            'State-of-the-Art Technology',
            'Next-Generation Platform',
            'Real-Time Processing',
            'End-to-End Solution',
            'Multi-Platform Support'
        ];
        
        foreach ($specialNames as $name) {
            $itemLookup->setName($name);
            $this->assertSame($name, $itemLookup->getName());
        }
    }

    public function testUnicodeInFields(): void
    {
        $itemLookup = new ItemLookup();
        
        // Chinese product
        $itemLookup->setName('高端笔记本电脑');
        $itemLookup->setDescription('专业级笔记本电脑，配备最新处理器和大容量内存');
        $this->assertSame('高端笔记本电脑', $itemLookup->getName());
        $this->assertSame('专业级笔记本电脑，配备最新处理器和大容量内存', $itemLookup->getDescription());
        
        // German product
        $itemLookup->setName('Hochleistungscomputer');
        $itemLookup->setDescription('Professioneller Computer für anspruchsvolle Anwendungen');
        $this->assertSame('Hochleistungscomputer', $itemLookup->getName());
        $this->assertSame('Professioneller Computer für anspruchsvolle Anwendungen', $itemLookup->getDescription());
        
        // French product
        $itemLookup->setName('Ordinateur Professionnel');
        $itemLookup->setDescription('Ordinateur haute performance pour usage professionnel');
        $this->assertSame('Ordinateur Professionnel', $itemLookup->getName());
        $this->assertSame('Ordinateur haute performance pour usage professionnel', $itemLookup->getDescription());
    }

    public function testLongDescriptions(): void
    {
        $itemLookup = new ItemLookup();
        
        $longDescription = 'This is an extremely comprehensive and detailed product description that covers multiple aspects including technical specifications, features, benefits, use cases, compatibility information, warranty details, support options, and additional services. The description provides complete information for customers to make informed purchasing decisions while highlighting all the key selling points and unique advantages of this particular product offering in the marketplace.';
        
        $itemLookup->setDescription($longDescription);
        $this->assertSame($longDescription, $itemLookup->getDescription());
    }

    public function testCompleteItemSetup(): void
    {
        $itemLookup = new ItemLookup();
        
        $itemLookup->setId(1);
        $itemLookup->setName('Professional Laptop');
        $itemLookup->setDescription('High-performance laptop for business use');
        $itemLookup->setPrice(1299.99);
        
        $this->assertSame('1', $itemLookup->getId());
        $this->assertSame('Professional Laptop', $itemLookup->getName());
        $this->assertSame('High-performance laptop for business use', $itemLookup->getDescription());
        $this->assertSame(1299.99, $itemLookup->getPrice());
    }

    public function testMethodReturnTypes(): void
    {
        $itemLookup = new ItemLookup(
            id: 1,
            name: 'Test Item',
            description: 'Test description',
            price: 99.99
        );
        
        $this->assertIsString($itemLookup->getId());
        $this->assertIsString($itemLookup->getName());
        $this->assertIsString($itemLookup->getDescription());
        $this->assertIsFloat($itemLookup->getPrice());
    }

    public function testPriceFloatPrecision(): void
    {
        $itemLookup = new ItemLookup();
        
        // Test various float precisions
        $prices = [
            1.0,
            1.5,
            1.99,
            1.999,
            1.9999,
            1.12345,
            123.456789,
            0.001,
            0.0001
        ];
        
        foreach ($prices as $price) {
            $itemLookup->setPrice($price);
            $this->assertSame($price, $itemLookup->getPrice());
        }
    }

    public function testBusinessScenarios(): void
    {
        $itemLookup = new ItemLookup();
        
        // E-commerce scenario
        $itemLookup->setName('Wireless Bluetooth Earbuds');
        $itemLookup->setDescription('Premium wireless earbuds with noise cancellation and long battery life');
        $itemLookup->setPrice(149.99);
        
        $this->assertSame('Wireless Bluetooth Earbuds', $itemLookup->getName());
        $this->assertSame('Premium wireless earbuds with noise cancellation and long battery life', $itemLookup->getDescription());
        $this->assertSame(149.99, $itemLookup->getPrice());
        
        // B2B scenario
        $itemLookup->setName('Enterprise Software License');
        $itemLookup->setDescription('Annual subscription for enterprise project management software');
        $itemLookup->setPrice(2400.00);
        
        $this->assertSame('Enterprise Software License', $itemLookup->getName());
        $this->assertSame('Annual subscription for enterprise project management software', $itemLookup->getDescription());
        $this->assertSame(2400.00, $itemLookup->getPrice());
        
        // Service scenario
        $itemLookup->setName('IT Consulting Services');
        $itemLookup->setDescription('Professional IT consulting services for system architecture and implementation');
        $itemLookup->setPrice(150.00);
        
        $this->assertSame('IT Consulting Services', $itemLookup->getName());
        $this->assertSame('Professional IT consulting services for system architecture and implementation', $itemLookup->getDescription());
        $this->assertSame(150.00, $itemLookup->getPrice());
    }

    public function testEntityStateConsistency(): void
    {
        $itemLookup = new ItemLookup(
            id: 999,
            name: 'Initial Item',
            description: 'Initial description',
            price: 99.99
        );
        
        // Verify initial state
        $this->assertSame('999', $itemLookup->getId());
        $this->assertSame('Initial Item', $itemLookup->getName());
        $this->assertSame('Initial description', $itemLookup->getDescription());
        $this->assertSame(99.99, $itemLookup->getPrice());
        
        // Modify all properties
        $itemLookup->setId(111);
        $itemLookup->setName('Modified Item');
        $itemLookup->setDescription('Modified description');
        $itemLookup->setPrice(199.99);
        
        // Verify changes
        $this->assertSame('111', $itemLookup->getId());
        $this->assertSame('Modified Item', $itemLookup->getName());
        $this->assertSame('Modified description', $itemLookup->getDescription());
        $this->assertSame(199.99, $itemLookup->getPrice());
    }

    public function testProductVariations(): void
    {
        $itemLookup = new ItemLookup();
        
        $variations = [
            ['Small Widget', 'Compact version of our popular widget', 19.99],
            ['Medium Widget', 'Standard size widget for general use', 39.99],
            ['Large Widget', 'Extended widget for heavy-duty applications', 59.99],
            ['Premium Widget', 'Top-tier widget with advanced features', 99.99],
            ['Basic Service', 'Essential service package', 29.99],
            ['Standard Service', 'Full-featured service package', 49.99],
            ['Premium Service', 'Complete service with priority support', 79.99],
            ['Enterprise Service', 'Comprehensive enterprise solution', 199.99]
        ];
        
        foreach ($variations as [$name, $description, $price]) {
            $itemLookup->setName($name);
            $itemLookup->setDescription($description);
            $itemLookup->setPrice($price);
            
            $this->assertSame($name, $itemLookup->getName());
            $this->assertSame($description, $itemLookup->getDescription());
            $this->assertSame($price, $itemLookup->getPrice());
        }
    }

    public function testSubscriptionPricing(): void
    {
        $itemLookup = new ItemLookup();
        
        $subscriptions = [
            ['Monthly Plan', 'Monthly subscription with basic features', 9.99],
            ['Quarterly Plan', 'Three-month subscription with standard features', 27.99],
            ['Annual Plan', 'Yearly subscription with premium features', 99.99],
            ['Lifetime Plan', 'One-time payment for lifetime access', 299.99]
        ];
        
        foreach ($subscriptions as [$name, $description, $price]) {
            $itemLookup->setName($name);
            $itemLookup->setDescription($description);
            $itemLookup->setPrice($price);
            
            $this->assertSame($name, $itemLookup->getName());
            $this->assertSame($description, $itemLookup->getDescription());
            $this->assertSame($price, $itemLookup->getPrice());
        }
    }

    public function testDescriptionWithHtmlTags(): void
    {
        $itemLookup = new ItemLookup();
        $htmlDescription = '<p>Product with <strong>HTML</strong> tags and <em>formatting</em>. Includes <a href="#">links</a> and <ul><li>lists</li></ul>.</p>';
        $itemLookup->setDescription($htmlDescription);
        
        $this->assertSame($htmlDescription, $itemLookup->getDescription());
    }

    public function testDescriptionWithLineBreaks(): void
    {
        $itemLookup = new ItemLookup();
        $multilineDescription = "Product Description:\n\n• Feature 1: High performance\n• Feature 2: Easy to use\n• Feature 3: Reliable\n\nTechnical Specifications:\n- Weight: 2kg\n- Dimensions: 30x20x10cm\n- Color: Black";
        $itemLookup->setDescription($multilineDescription);
        
        $this->assertSame($multilineDescription, $itemLookup->getDescription());
    }

    public function testNamesWithNumbers(): void
    {
        $itemLookup = new ItemLookup();
        
        $numberedNames = [
            'Product v2.0',
            'Model X1',
            'Version 3.5',
            'Series 100',
            'Type A1',
            'Generation 4',
            'Edition 2024',
            'Release 1.5',
            'Build 123',
            'Revision 5'
        ];
        
        foreach ($numberedNames as $name) {
            $itemLookup->setName($name);
            $this->assertSame($name, $itemLookup->getName());
        }
    }

    public function testEdgeCaseNames(): void
    {
        $itemLookup = new ItemLookup();
        
        // Single character
        $itemLookup->setName('X');
        $this->assertSame('X', $itemLookup->getName());
        
        // Numbers only
        $itemLookup->setName('123456');
        $this->assertSame('123456', $itemLookup->getName());
        
        // Special characters only
        $itemLookup->setName('***');
        $this->assertSame('***', $itemLookup->getName());
        
        // Mixed content
        $itemLookup->setName('Item-2024_v1.0');
        $this->assertSame('Item-2024_v1.0', $itemLookup->getName());
    }

    public function testWhitespaceInFields(): void
    {
        $itemLookup = new ItemLookup();
        
        // Leading/trailing spaces in name
        $itemLookup->setName(' Product Name ');
        $this->assertSame(' Product Name ', $itemLookup->getName());
        
        // Multiple spaces in description
        $itemLookup->setDescription('Description    with    multiple    spaces');
        $this->assertSame('Description    with    multiple    spaces', $itemLookup->getDescription());
        
        // Tab characters
        $itemLookup->setName("Product\tName");
        $this->assertSame("Product\tName", $itemLookup->getName());
    }

    public function testConstructorParameterCombinations(): void
    {
        // Only ID
        $item1 = new ItemLookup(id: 1);
        $this->assertSame('1', $item1->getId());
        $this->assertSame('', $item1->getName());
        $this->assertSame('', $item1->getDescription());
        $this->assertNull($item1->getPrice());
        
        // ID and name
        $item2 = new ItemLookup(id: 2, name: 'Test Item');
        $this->assertSame('2', $item2->getId());
        $this->assertSame('Test Item', $item2->getName());
        $this->assertSame('', $item2->getDescription());
        $this->assertNull($item2->getPrice());
        
        // All except price
        $item3 = new ItemLookup(id: 3, name: 'Test', description: 'Description');
        $this->assertSame('3', $item3->getId());
        $this->assertSame('Test', $item3->getName());
        $this->assertSame('Description', $item3->getDescription());
        $this->assertNull($item3->getPrice());
    }
}