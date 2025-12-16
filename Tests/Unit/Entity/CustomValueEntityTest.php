<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Invoice\Entity\CustomValue;
use App\Invoice\Entity\CustomField;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

class CustomValueEntityTest extends Unit
{
    private MockObject $customField;
    
    public string $updatedData = 'Updated data';
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->customField = $this->createMock(CustomField::class);
    }

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
        $customValue = new CustomValue(1, 5, 'Custom field value');
        
        $this->assertSame('1', $customValue->getId());
        $this->assertSame(5, $customValue->getCustom_field_id());
        $this->assertSame('Custom field value', $customValue->getValue());
        $this->assertNull($customValue->getCustomField()); // Relationship set by ORM
    }

    public function testConstructorWithNullId(): void
    {
        $customValue = new CustomValue(null, 10, 'Test value');
        
        $this->assertSame('', $customValue->getId());
        $this->assertSame(10, $customValue->getCustom_field_id());
        $this->assertSame('Test value', $customValue->getValue());
    }

    public function testIdSetterAndGetter(): void
    {
        $customValue = new CustomValue();
        
        $customValue->setId(123);
        $this->assertSame('123', $customValue->getId());
        
        $customValue->setId(456);
        $this->assertSame('456', $customValue->getId());
    }

    public function testCustomFieldIdSetterAndGetter(): void
    {
        $customValue = new CustomValue();
        
        $customValue->setCustom_field_id(10);
        $this->assertSame(10, $customValue->getCustom_field_id());
        
        $customValue->setCustom_field_id(25);
        $this->assertSame(25, $customValue->getCustom_field_id());
    }

    public function testValueSetterAndGetter(): void
    {
        $customValue = new CustomValue();
        
        $customValue->setValue('First value');
        $this->assertSame('First value', $customValue->getValue());
        
        $customValue->setValue('Updated value');
        $this->assertSame('Updated value', $customValue->getValue());
    }

    public function testGetCustomField(): void
    {
        $customValue = new CustomValue();
        
        // Initially null (relationship set by ORM)
        $this->assertNull($customValue->getCustomField());
    }

    public function testIdReturnsString(): void
    {
        $customValue = new CustomValue();
        
        // Test that ID is always returned as string
        $customValue->setId(0);
        $this->assertIsString($customValue->getId());
        $this->assertSame('0', $customValue->getId());
        
        $customValue->setId(999);
        $this->assertIsString($customValue->getId());
        $this->assertSame('999', $customValue->getId());
    }

    public function testCommonCustomFieldValues(): void
    {
        $values = [
            'John Doe',
            'john.doe@example.com',
            '+1 555-123-4567',
            '123 Main Street',
            'New York, NY 10001',
            'Company ABC Inc.',
            'Manager',
            'Additional notes here',
            '2023-12-31',
            '1000.50',
        ];
        
        $customValue = new CustomValue();
        
        foreach ($values as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testLongCustomValues(): void
    {
        $customValue = new CustomValue();
        $longValue = str_repeat('This is a very long custom field value with lots of content. ', 20);
        
        $customValue->setValue($longValue);
        $this->assertSame($longValue, $customValue->getValue());
        $this->assertGreaterThan(1000, strlen($customValue->getValue()));
    }

    public function testSpecialCharactersInValue(): void
    {
        $customValue = new CustomValue();
        $specialValue = 'Value with special chars: !@#$%^&*()_+-=[]{}|;:\'",.<>?/~`';
        
        $customValue->setValue($specialValue);
        $this->assertSame($specialValue, $customValue->getValue());
    }

    public function testUnicodeCharactersInValue(): void
    {
        $customValue = new CustomValue();
        $unicodeValue = 'Unicode: 世界 🌍 café naïve résumé Москва العربية';
        
        $customValue->setValue($unicodeValue);
        $this->assertSame($unicodeValue, $customValue->getValue());
    }

    public function testHtmlContentInValue(): void
    {
        $customValue = new CustomValue();
        $htmlValue = '<p>HTML content with <strong>bold</strong> and <em>italic</em> text</p>';
        
        $customValue->setValue($htmlValue);
        $this->assertSame($htmlValue, $customValue->getValue());
    }

    public function testJsonContentInValue(): void
    {
        $customValue = new CustomValue();
        $jsonValue = '{"name": "John", "age": 30, "city": "New York", "active": true}';
        
        $customValue->setValue($jsonValue);
        $this->assertSame($jsonValue, $customValue->getValue());
    }

    public function testZeroAndLargeIds(): void
    {
        $customValue = new CustomValue();
        
        // Zero ID
        $customValue->setId(0);
        $this->assertSame('0', $customValue->getId());
        
        // Large ID
        $customValue->setId(999999999);
        $this->assertSame('999999999', $customValue->getId());
        
        // Zero custom field ID
        $customValue->setCustom_field_id(0);
        $this->assertSame(0, $customValue->getCustom_field_id());
        
        // Large custom field ID
        $customValue->setCustom_field_id(888888888);
        $this->assertSame(888888888, $customValue->getCustom_field_id());
    }

    public function testEmptyValueHandling(): void
    {
        $customValue = new CustomValue();
        
        // Default is empty string
        $this->assertSame('', $customValue->getValue());
        
        // Set to empty string explicitly
        $customValue->setValue('');
        $this->assertSame('', $customValue->getValue());
    }

    public function testCompleteCustomValueSetup(): void
    {
        $customValue = new CustomValue();
        
        // Setup a complete custom value
        $customValue->setId(1);
        $customValue->setCustom_field_id(5);
        $customValue->setValue('Complete custom field value');
        
        $this->assertSame('1', $customValue->getId());
        $this->assertSame(5, $customValue->getCustom_field_id());
        $this->assertSame('Complete custom field value', $customValue->getValue());
        $this->assertNull($customValue->getCustomField()); // Relationship set by ORM
    }

    public function testCustomFieldTypes(): void
    {
        $fieldTypeValues = [
            'text' => 'Simple text value',
            'email' => 'user@example.com',
            'phone' => '+1-555-123-4567',
            'url' => 'https://example.com',
            'number' => '12345',
            'decimal' => '123.45',
            'date' => '2023-12-31',
            'datetime' => '2023-12-31 23:59:59',
            'textarea' => 'Multi-line\ntext\ncontent',
            'checkbox' => '1',
        ];
        
        foreach ($fieldTypeValues as $value) {
            $customValue = new CustomValue(null, 1, $value);
            
            $this->assertSame('', $customValue->getId());
            $this->assertSame(1, $customValue->getCustom_field_id());
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testGetterMethodsConsistency(): void
    {
        $customValue = new CustomValue(100, 200, 'Test consistency');
        
        // Multiple calls should return same values
        $this->assertSame($customValue->getId(), $customValue->getId());
        $this->assertSame($customValue->getCustom_field_id(), $customValue->getCustom_field_id());
        $this->assertSame($customValue->getValue(), $customValue->getValue());
        $this->assertSame($customValue->getCustomField(), $customValue->getCustomField());
    }

    public function testRelationshipStructure(): void
    {
        $customValue = new CustomValue();
        
        // Set up relationship reference
        $customValue->setCustom_field_id(10);
        $this->assertSame(10, $customValue->getCustom_field_id());
        
        // CustomField relationship is null until set by ORM
        $this->assertNull($customValue->getCustomField());
    }

    public function testCustomValueScenarios(): void
    {
        $scenarios = [
            ['field_id' => 1, 'value' => 'Client Name'],
            ['field_id' => 2, 'value' => 'client@email.com'],
            ['field_id' => 3, 'value' => '555-0123'],
            ['field_id' => 4, 'value' => '123 Business St'],
            ['field_id' => 5, 'value' => 'Special instructions'],
        ];
        
        foreach ($scenarios as $index => $scenario) {
            $customValue = new CustomValue($index + 1, $scenario['field_id'], $scenario['value']);
            
            $this->assertSame((string)($index + 1), $customValue->getId());
            $this->assertSame($scenario['field_id'], $customValue->getCustom_field_id());
            $this->assertSame($scenario['value'], $customValue->getValue());
        }
    }

    public function testMultilineTextValues(): void
    {
        $customValue = new CustomValue();
        $multilineValue = "Line 1\nLine 2\nLine 3\n\nLine 5 with spaces";
        
        $customValue->setValue($multilineValue);
        $this->assertSame($multilineValue, $customValue->getValue());
        $this->assertStringContainsString("\n", $customValue->getValue());
    }

    public function testNumericStringValues(): void
    {
        $customValue = new CustomValue();
        
        $numericValues = ['0', '123', '456.78', '-99', '1000000'];
        
        foreach ($numericValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
            $this->assertIsString($customValue->getValue());
        }
    }

    public function testBooleanStringValues(): void
    {
        $customValue = new CustomValue();
        
        $booleanValues = ['true', 'false', '1', '0', 'yes', 'no'];
        
        foreach ($booleanValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testWhitespaceInValues(): void
    {
        $customValue = new CustomValue();
        
        $whitespaceValues = [
            '  leading spaces',
            'trailing spaces  ',
            '  both sides  ',
            'middle   spaces',
            "\ttab character",
            "newline\ncharacter",
        ];
        
        foreach ($whitespaceValues as $value) {
            $customValue->setValue($value);
            $this->assertSame($value, $customValue->getValue());
        }
    }

    public function testCustomValueModification(): void
    {
        $customValue = new CustomValue();
        
        // Initial setup
        $customValue->setId(1);
        $customValue->setCustom_field_id(5);
        $customValue->setValue('Initial value');
        
        $this->assertSame('1', $customValue->getId());
        $this->assertSame(5, $customValue->getCustom_field_id());
        $this->assertSame('Initial value', $customValue->getValue());
        
        // Modification
        $customValue->setCustom_field_id(10);
        $customValue->setValue('Modified value');
        
        $this->assertSame('1', $customValue->getId()); // ID unchanged
        $this->assertSame(10, $customValue->getCustom_field_id()); // Field ID changed
        $this->assertSame('Modified value', $customValue->getValue()); // Value changed
    }

    public function testPropertyTypes(): void
    {
        $customValue = new CustomValue(123, 456, 'type test');
        
        // Test return types
        $this->assertIsString($customValue->getId());
        $this->assertIsInt($customValue->getCustom_field_id());
        $this->assertIsString($customValue->getValue());
        $this->assertNull($customValue->getCustomField());
    }

    public function testNegativeIds(): void
    {
        $customValue = new CustomValue();
        
        // Test negative ID (though probably not used in practice)
        $customValue->setId(-1);
        $this->assertSame('-1', $customValue->getId());
        
        $customValue->setCustom_field_id(-5);
        $this->assertSame(-5, $customValue->getCustom_field_id());
    }

    public function testEntityStateAfterConstruction(): void
    {
        // Test various constructor states
        $entity1 = new CustomValue();
        $this->assertSame('', $entity1->getId());
        $this->assertNull($entity1->getCustom_field_id());
        $this->assertSame('', $entity1->getValue());
        
        $entity2 = new CustomValue(1);
        $this->assertSame('1', $entity2->getId());
        $this->assertNull($entity2->getCustom_field_id());
        $this->assertSame('', $entity2->getValue());
        
        $entity3 = new CustomValue(1, 2);
        $this->assertSame('1', $entity3->getId());
        $this->assertSame(2, $entity3->getCustom_field_id());
        $this->assertSame('', $entity3->getValue());
        
        $entity4 = new CustomValue(1, 2, 'Full Value');
        $this->assertSame('1', $entity4->getId());
        $this->assertSame(2, $entity4->getCustom_field_id());
        $this->assertSame('Full Value', $entity4->getValue());
    }

    public function testCustomValueWorkflow(): void
    {
        $customValue = new CustomValue();
        
        // Step 1: Create with field reference
        $customValue->setCustom_field_id(1);
        $customValue->setValue('Initial data');
        
        $this->assertSame(1, $customValue->getCustom_field_id());
        $this->assertSame('Initial data', $customValue->getValue());
        
        // Step 2: Update value
        $customValue->setValue($this->updatedData);
        $this->assertSame($this->updatedData, $customValue->getValue());
        
        // Step 3: Change field reference
        $customValue->setCustom_field_id(2);
        $this->assertSame(2, $customValue->getCustom_field_id());
        $this->assertSame($this->updatedData, $customValue->getValue()); // Value unchanged
    }

    public function testVeryLongValues(): void
    {
        $customValue = new CustomValue();
        $veryLongValue = str_repeat('A', 10000); // 10KB string
        
        $customValue->setValue($veryLongValue);
        $this->assertSame($veryLongValue, $customValue->getValue());
        $this->assertSame(10000, strlen($customValue->getValue()));
    }

    public function testSpecialFormattedValues(): void
    {
        $customValue = new CustomValue();
        
        $specialFormats = [
            'XML: <?xml version="1.0"?><root><item>value</item></root>',
            'CSV: "Name","Age","City"\n"John",30,"NY"',
            'URL: https://example.com/path?param=value&other=123',
            'SQL: SELECT * FROM table WHERE id = 1',
            'Regex: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$/',
        ];
        
        foreach ($specialFormats as $format) {
            $customValue->setValue($format);
            $this->assertSame($format, $customValue->getValue());
        }
    }
}
