<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\CustomValue;
use PHPUnit\Framework\TestCase;

class CustomValueEntityTest extends TestCase
{
    public string $beg2024 = '2024-01-01';
    
    public function testConstructorWithDefaults(): void
    {
        $customValue = new CustomValue();
        
        $this->assertSame('', $customValue->getId());
        $this->assertNull($customValue->getCustom_field_id());
        $this->assertSame('', $customValue->getValue());
        $this->assertNull($customValue->getCustomField());
    }

    public function testConstructorWithAllParameters(): void
    {
        $customValue = new CustomValue(
            id: 1,
            custom_field_id: 100,
            value: 'Custom field value'
        );
        
        $this->assertSame('1', $customValue->getId());
        $this->assertSame(100, $customValue->getCustom_field_id());
        $this->assertSame('Custom field value', $customValue->getValue());
        $this->assertNull($customValue->getCustomField());
    }

    public function testConstructorWithPartialParameters(): void
    {
        $customValue = new CustomValue(
            id: 2,
            value: 'Partial value'
        );
        
        $this->assertSame('2', $customValue->getId());
        $this->assertNull($customValue->getCustom_field_id());
        $this->assertSame('Partial value', $customValue->getValue());
        $this->assertNull($customValue->getCustomField());
    }

    public function testIdSetterAndGetter(): void
    {
        $customValue = new CustomValue();
        $customValue->setId(50);
        
        $this->assertSame('50', $customValue->getId());
    }

    public function testCustomFieldIdSetterAndGetter(): void
    {
        $customValue = new CustomValue();
        $customValue->setCustom_field_id(200);
        
        $this->assertSame(200, $customValue->getCustom_field_id());
    }

    public function testValueSetterAndGetter(): void
    {
        $customValue = new CustomValue();
        $customValue->setValue('Test custom value');
        
        $this->assertSame('Test custom value', $customValue->getValue());
    }

    public function testCustomFieldRelationship(): void
    {
        $customValue = new CustomValue();
        
        // Initially null (no setter available, populated by ORM)
        $this->assertNull($customValue->getCustomField());
    }

    public function testIdTypeConversion(): void
    {
        $customValue = new CustomValue();
        $customValue->setId(999);
        
        $this->assertIsString($customValue->getId());
        $this->assertSame('999', $customValue->getId());
    }

    public function testZeroId(): void
    {
        $customValue = new CustomValue();
        $customValue->setId(0);
        
        $this->assertSame('0', $customValue->getId());
    }

    public function testNegativeId(): void
    {
        $customValue = new CustomValue();
        $customValue->setId(-1);
        
        $this->assertSame('-1', $customValue->getId());
    }

    public function testLargeId(): void
    {
        $customValue = new CustomValue();
        $largeId = PHP_INT_MAX;
        
        $customValue->setId($largeId);
        $this->assertSame((string)$largeId, $customValue->getId());
    }

    public function testZeroCustomFieldId(): void
    {
        $customValue = new CustomValue();
        $customValue->setCustom_field_id(0);
        
        $this->assertSame(0, $customValue->getCustom_field_id());
    }

    public function testNegativeCustomFieldId(): void
    {
        $customValue = new CustomValue();
        $customValue->setCustom_field_id(-1);
        
        $this->assertSame(-1, $customValue->getCustom_field_id());
    }

    public function testLargeCustomFieldId(): void
    {
        $customValue = new CustomValue();
        $largeId = PHP_INT_MAX;
        
        $customValue->setCustom_field_id($largeId);
        $this->assertSame($largeId, $customValue->getCustom_field_id());
    }

    public function testEmptyStringValue(): void
    {
        $customValue = new CustomValue();
        $customValue->setValue('');
        
        $this->assertSame('', $customValue->getValue());
    }

    public function testTextValues(): void
    {
        $customValue = new CustomValue();
        
        $textValues = [
            'Simple text value',
            'Multi-line\ntext\nvalue',
            'Text with special chars: !@#$%^&*()',
            'Very long text value that spans multiple lines and contains various characters',
            'HTML content: <div>Hello World</div>',
            'JSON content: {"key": "value", "number": 123}',
            'XML content: <root><item>value</item></root>',
            'SQL query: SELECT * FROM table WHERE id = 1',
            'URL: https://example.com/path?param=value',
            'Code snippet: function test() { return true; }'
        ];
        
        foreach ($textValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testNumericStringValues(): void
    {
        $customValue = new CustomValue();
        
        $numericValues = [
            '0',
            '1',
            '123',
            '-456',
            '3.14159',
            '-2.718',
            '1.23e-4',
            '1000000',
            '0.001',
            '99.99'
        ];
        
        foreach ($numericValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testBooleanStringValues(): void
    {
        $customValue = new CustomValue();
        
        $booleanValues = [
            'true',
            'false',
            'TRUE',
            'FALSE',
            'yes',
            'no',
            'YES',
            'NO',
            '1',
            '0'
        ];
        
        foreach ($booleanValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testDateStringValues(): void
    {
        $customValue = new CustomValue();
        
        $dateValues = [
            $this->beg2024,
            '2024-12-31 23:59:59',
            '01/01/2024',
            '12-31-2024',
            'January 1, 2024',
            '2024-01-01T00:00:00Z',
            '2024-01-01T12:30:45+02:00',
            'Mon, 01 Jan 2024 00:00:00 GMT',
            '1704067200', // Unix timestamp
            'Q1 2024'
        ];
        
        foreach ($dateValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testContactInformationValues(): void
    {
        $customValue = new CustomValue();
        
        $contactValues = [
            'john.doe@example.com',
            '+1 (555) 123-4567',
            '(555) 987-6543',
            '123 Main Street, Anytown, ST 12345',
            'https://www.example.com',
            '@johndoe',
            'Skype: john.doe.skype',
            'LinkedIn: linkedin.com/in/johndoe',
            'Extension: 1234',
            'Mobile: +1-555-123-4567'
        ];
        
        foreach ($contactValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testBusinessValues(): void
    {
        $customValue = new CustomValue();
        
        $businessValues = [
            'ABC-123-XYZ',
            'TAX-ID: 12-3456789',
            'EIN: 12-3456789',
            'VAT: GB123456789',
            'DUNS: 123456789',
            'SIC: 1234',
            'NAICS: 123456',
            'Preferred Customer',
            'Net 30 Terms',
            'Volume Discount Eligible'
        ];
        
        foreach ($businessValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testTechnicalValues(): void
    {
        $customValue = new CustomValue();
        
        $technicalValues = [
            'API Key: sk_test_123456789',
            'Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9',
            'Hash: a1b2c3d4e5f6',
            'UUID: 550e8400-e29b-41d4-a716-446655440000',
            'Version: 2.1.3',
            'Build: 20240101.1',
            'Environment: production',
            'Region: us-east-1',
            'Instance: i-1234567890abcdef0',
            'Container: app-server-01'
        ];
        
        foreach ($technicalValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testSpecialCharactersInValues(): void
    {
        $customValue = new CustomValue();
        
        $specialValues = [
            'Value with spaces',
            'Value-with-hyphens',
            'Value_with_underscores',
            'Value.with.dots',
            'Value@with@symbols',
            'Value#with#hashes',
            'Value$with$dollars',
            'Value%with%percents',
            'Value&with&ampersands',
            'Value*with*asterisks'
        ];
        
        foreach ($specialValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testUnicodeInValues(): void
    {
        $customValue = new CustomValue();
        
        $unicodeValues = [
            '中文内容',
            'Contenu français',
            'Contenido español',
            'Русский контент',
            'Ελληνικό περιεχόμενο',
            '日本語コンテンツ',
            'محتوى عربي',
            'Treść polska',
            'Český obsah',
            'Magyar tartalom'
        ];
        
        foreach ($unicodeValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testFormattedTextValues(): void
    {
        $customValue = new CustomValue();
        
        // Markdown content
        $markdownValue = "# Header\n\n**Bold text** and *italic text*\n\n- List item 1\n- List item 2";
        $customValue->setValue($markdownValue);
        $this->assertSame($markdownValue, $customValue->getValue());
        
        // CSV content
        $csvValue = "Name,Age,City\nJohn,30,New York\nJane,25,Los Angeles";
        $customValue->setValue($csvValue);
        $this->assertSame($csvValue, $customValue->getValue());
        
        // Tab-separated content
        $tsvValue = "Column1\tColumn2\tColumn3\nValue1\tValue2\tValue3";
        $customValue->setValue($tsvValue);
        $this->assertSame($tsvValue, $customValue->getValue());
    }

    public function testLongValues(): void
    {
        $customValue = new CustomValue();
        
        $longValue = str_repeat('This is a very long text that will test the system\'s ability to handle large amounts of data in custom field values. ', 50);
        $customValue->setValue($longValue);
        
        $this->assertSame($longValue, $customValue->getValue());
        $this->assertTrue(strlen($customValue->getValue()) > 5000);
    }

    public function testCompleteCustomValueSetup(): void
    {
        $customValue = new CustomValue();
        
        $customValue->setId(1);
        $customValue->setCustom_field_id(100);
        $customValue->setValue('Complete custom field value setup');
        
        $this->assertSame('1', $customValue->getId());
        $this->assertSame(100, $customValue->getCustom_field_id());
        $this->assertSame('Complete custom field value setup', $customValue->getValue());
        $this->assertNull($customValue->getCustomField());
    }

    public function testMethodReturnTypes(): void
    {
        $customValue = new CustomValue(
            id: 1,
            custom_field_id: 100,
            value: 'Test value'
        );
        
        $this->assertIsString($customValue->getId());
        $this->assertIsInt($customValue->getCustom_field_id());
        $this->assertIsString($customValue->getValue());
        $this->assertNull($customValue->getCustomField());
    }

    public function testEntityStateConsistency(): void
    {
        $customValue = new CustomValue(
            id: 999,
            custom_field_id: 888,
            value: 'Initial value'
        );
        
        // Verify initial state
        $this->assertSame('999', $customValue->getId());
        $this->assertSame(888, $customValue->getCustom_field_id());
        $this->assertSame('Initial value', $customValue->getValue());
        
        // Modify all properties
        $customValue->setId(111);
        $customValue->setCustom_field_id(222);
        $customValue->setValue('Modified value');
        
        // Verify changes
        $this->assertSame('111', $customValue->getId());
        $this->assertSame(222, $customValue->getCustom_field_id());
        $this->assertSame('Modified value', $customValue->getValue());
    }

    public function testConfigurationValues(): void
    {
        $customValue = new CustomValue();
        
        $configValues = [
            'theme=dark',
            'language=en_US',
            'timezone=America/New_York',
            'currency=USD',
            'date_format=Y-m-d',
            'decimal_places=2',
            'thousand_separator=,',
            'decimal_separator=.',
            'tax_rate=8.25',
            'discount_rate=5.0'
        ];
        
        foreach ($configValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testMultilineValues(): void
    {
        $customValue = new CustomValue();
        
        $multilineValue = "Line 1: Description\nLine 2: Additional info\nLine 3: Notes\nLine 4: Comments";
        $customValue->setValue($multilineValue);
        $this->assertSame($multilineValue, $customValue->getValue());
        
        $jsonValue = "{\n  \"name\": \"John Doe\",\n  \"age\": 30,\n  \"city\": \"New York\"\n}";
        $customValue->setValue($jsonValue);
        $this->assertSame($jsonValue, $customValue->getValue());
    }

    public function testCustomFieldRelationshipScenarios(): void
    {
        $customValue = new CustomValue();
        
        // Different custom field types
        $fieldScenarios = [
            [1, 'Text field value'],
            [2, '42'],
            [3, 'true'],
            [4, $this->beg2024],
            [5, 'user@example.com'],
            [6, 'https://example.com'],
            [7, '{"key": "value"}'],
            [8, 'Option A'],
            [9, 'Multiple\nLine\nValue'],
            [10, 'Rich text with <strong>HTML</strong>']
        ];
        
        foreach ($fieldScenarios as [$fieldId, $value]) {
            $customValue->setCustom_field_id($fieldId);
            $customValue->setValue($value);
            
            $this->assertSame($fieldId, $customValue->getCustom_field_id());
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testWhitespaceHandling(): void
    {
        $customValue = new CustomValue();
        
        // Leading/trailing spaces
        $customValue->setValue(' Value with spaces ');
        $this->assertSame(' Value with spaces ', $customValue->getValue());
        
        // Multiple spaces
        $customValue->setValue('Value    with    multiple    spaces');
        $this->assertSame('Value    with    multiple    spaces', $customValue->getValue());
        
        // Tab characters
        $customValue->setValue("Value\twith\ttabs");
        $this->assertSame("Value\twith\ttabs", $customValue->getValue());
        
        // Mixed whitespace
        $customValue->setValue(" \t Value \t ");
        $this->assertSame(" \t Value \t ", $customValue->getValue());
    }

    public function testEdgeCaseValues(): void
    {
        $customValue = new CustomValue();
        
        // Single character
        $customValue->setValue('A');
        $this->assertSame('A', $customValue->getValue());
        
        // Only numbers
        $customValue->setValue('123456789');
        $this->assertSame('123456789', $customValue->getValue());
        
        // Only special characters
        $customValue->setValue('!@#$%^&*()');
        $this->assertSame('!@#$%^&*()', $customValue->getValue());
        
        // Mixed content with newlines
        $customValue->setValue("Mixed123!@#\nNew Line");
        $this->assertSame("Mixed123!@#\nNew Line", $customValue->getValue());
    }

    public function testDataTypeValues(): void
    {
        $customValue = new CustomValue();
        
        $dataTypeValues = [
            'String value',
            '12345',
            '123.45',
            'true',
            'false',
            'null',
            '[]',
            '{}',
            $this->beg2024,
            '12:30:45'
        ];
        
        foreach ($dataTypeValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testBusinessRuleValues(): void
    {
        $customValue = new CustomValue();
        
        $businessValues = [
            'Priority: High',
            'Status: Active',
            'Category: Premium',
            'Tier: Gold',
            'Level: Enterprise',
            'Type: Corporate',
            'Class: A',
            'Grade: Excellent',
            'Score: 95',
            'Rating: 5 stars'
        ];
        
        foreach ($businessValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testEncodedValues(): void
    {
        $customValue = new CustomValue();
        
        // Base64 encoded value
        $base64Value = base64_encode('Hello World');
        $customValue->setValue($base64Value);
        $this->assertSame($base64Value, $customValue->getValue());
        
        // URL encoded value
        $urlEncodedValue = urlencode('Hello World & More');
        $customValue->setValue($urlEncodedValue);
        $this->assertSame($urlEncodedValue, $customValue->getValue());
        
        // HTML encoded value
        $htmlEncodedValue = htmlentities('<div>Hello & Goodbye</div>');
        $customValue->setValue($htmlEncodedValue);
        $this->assertSame($htmlEncodedValue, $customValue->getValue());
    }

    public function testConstructorParameterCombinations(): void
    {
        // Only ID
        $value1 = new CustomValue(id: 1);
        $this->assertSame('1', $value1->getId());
        $this->assertNull($value1->getCustom_field_id());
        $this->assertSame('', $value1->getValue());
        
        // ID and field ID
        $value2 = new CustomValue(id: 2, custom_field_id: 100);
        $this->assertSame('2', $value2->getId());
        $this->assertSame(100, $value2->getCustom_field_id());
        $this->assertSame('', $value2->getValue());
        
        // ID and value
        $value3 = new CustomValue(id: 3, value: 'Test');
        $this->assertSame('3', $value3->getId());
        $this->assertNull($value3->getCustom_field_id());
        $this->assertSame('Test', $value3->getValue());
        
        // Field ID and value
        $value4 = new CustomValue(custom_field_id: 200, value: 'Another Test');
        $this->assertSame('', $value4->getId());
        $this->assertSame(200, $value4->getCustom_field_id());
        $this->assertSame('Another Test', $value4->getValue());
    }

    public function testValueTypes(): void
    {
        $customValue = new CustomValue();
        
        // Text field
        $customValue->setCustom_field_id(1);
        $customValue->setValue('Simple text content');
        $this->assertSame(1, $customValue->getCustom_field_id());
        $this->assertSame('Simple text content', $customValue->getValue());
        
        // Number field
        $customValue->setCustom_field_id(2);
        $customValue->setValue('42.75');
        $this->assertSame(2, $customValue->getCustom_field_id());
        $this->assertSame('42.75', $customValue->getValue());
        
        // Date field
        $customValue->setCustom_field_id(3);
        $customValue->setValue($this->beg2024);
        $this->assertSame(3, $customValue->getCustom_field_id());
        $this->assertSame($this->beg2024, $customValue->getValue());
        
        // Boolean field
        $customValue->setCustom_field_id(4);
        $customValue->setValue('true');
        $this->assertSame(4, $customValue->getCustom_field_id());
        $this->assertSame('true', $customValue->getValue());
    }

    public function testRealWorldScenarios(): void
    {
        $customValue = new CustomValue();
        
        // Customer preference field
        $customValue->setId(1);
        $customValue->setCustom_field_id(10);
        $customValue->setValue('Email notifications: Weekly digest');
        
        $this->assertSame('1', $customValue->getId());
        $this->assertSame(10, $customValue->getCustom_field_id());
        $this->assertSame('Email notifications: Weekly digest', $customValue->getValue());
        
        // Product specification field
        $customValue->setId(2);
        $customValue->setCustom_field_id(20);
        $customValue->setValue('Dimensions: 10" x 8" x 2", Weight: 1.5 lbs');
        
        $this->assertSame('2', $customValue->getId());
        $this->assertSame(20, $customValue->getCustom_field_id());
        $this->assertSame('Dimensions: 10" x 8" x 2", Weight: 1.5 lbs', $customValue->getValue());
        
        // Order notes field
        $customValue->setId(3);
        $customValue->setCustom_field_id(30);
        $customValue->setValue('Special delivery instructions: Leave at front door, ring doorbell twice');
        
        $this->assertSame('3', $customValue->getId());
        $this->assertSame(30, $customValue->getCustom_field_id());
        $this->assertSame('Special delivery instructions: Leave at front door, ring doorbell twice', $customValue->getValue());
    }
}

