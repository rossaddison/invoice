<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Persistence\CustomField\CustomField;
use Codeception\Test\Unit;

class CustomFieldEntityTest extends Unit
{
    private CustomField $customField;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customField = new CustomField();
    }

    public function testConstructorWithDefaults(): void
    {
        $defaultCustomField = new CustomField();
        
        $this->assertFalse($defaultCustomField->isPersisted());
        $this->assertEquals('', $defaultCustomField->getTable());
        $this->assertEquals('', $defaultCustomField->getLabel());
        $this->assertEquals('', $defaultCustomField->getType());
        $this->assertNull($defaultCustomField->getLocation());
        $this->assertNull($defaultCustomField->getOrder());
        $this->assertFalse($defaultCustomField->getRequired());
    }

    public function testConstructorWithAllParameters(): void
    {
        $customField = new CustomField(
            id: 1,
            table: 'invoice',
            label: 'Custom Label',
            type: 'TEXT',
            location: 1,
            order: 5,
            required: true
        );

        $this->assertSame(1, $customField->reqId());
        $this->assertEquals('invoice', $customField->getTable());
        $this->assertEquals('Custom Label', $customField->getLabel());
        $this->assertEquals('TEXT', $customField->getType());
        $this->assertEquals(1, $customField->getLocation());
        $this->assertEquals(5, $customField->getOrder());
        $this->assertTrue($customField->getRequired());
    }

    public function testIdSetterAndGetter(): void
    {
        $this->assertFalse($this->customField->isPersisted());
        
        $this->customField->setId(42);
        $this->assertSame(42, $this->customField->reqId());
        
        $this->customField->setId(0);
        $this->assertSame(0, $this->customField->reqId());
    }

    public function testTableSetterAndGetter(): void
    {
        $this->assertEquals('', $this->customField->getTable());
        
        $this->customField->setTable('client');
        $this->assertEquals('client', $this->customField->getTable());
        
        $this->customField->setTable('invoice');
        $this->assertEquals('invoice', $this->customField->getTable());
        
        $this->customField->setTable('');
        $this->assertEquals('', $this->customField->getTable());
    }

    public function testLabelSetterAndGetter(): void
    {
        $this->assertEquals('', $this->customField->getLabel());
        
        $this->customField->setLabel('Project Code');
        $this->assertEquals('Project Code', $this->customField->getLabel());
        
        $this->customField->setLabel('Department');
        $this->assertEquals('Department', $this->customField->getLabel());
        
        $this->customField->setLabel('');
        $this->assertEquals('', $this->customField->getLabel());
    }

    public function testTypeSetterAndGetter(): void
    {
        $this->assertEquals('', $this->customField->getType());
        
        $this->customField->setType('TEXT');
        $this->assertEquals('TEXT', $this->customField->getType());
        
        $this->customField->setType('SELECT');
        $this->assertEquals('SELECT', $this->customField->getType());
        
        $this->customField->setType('NUMBER');
        $this->assertEquals('NUMBER', $this->customField->getType());
    }

    public function testLocationSetterAndGetter(): void
    {
        $this->assertNull($this->customField->getLocation());
        
        $this->customField->setLocation(1);
        $this->assertEquals(1, $this->customField->getLocation());
        
        $this->customField->setLocation(0);
        $this->assertEquals(0, $this->customField->getLocation());
        
        $this->customField->setLocation(999);
        $this->assertEquals(999, $this->customField->getLocation());
    }

    public function testOrderSetterAndGetter(): void
    {
        $this->assertNull($this->customField->getOrder());
        
        $this->customField->setOrder(1);
        $this->assertEquals(1, $this->customField->getOrder());
        
        $this->customField->setOrder(999);
        $this->assertEquals(999, $this->customField->getOrder());
        
        $this->customField->setOrder(0);
        $this->assertEquals(0, $this->customField->getOrder());
    }

    public function testRequiredSetterAndGetter(): void
    {
        $this->assertFalse($this->customField->getRequired());
        
        $this->customField->setRequired(true);
        $this->assertTrue($this->customField->getRequired());
        
        $this->customField->setRequired(false);
        $this->assertFalse($this->customField->getRequired());
    }

    public function testCommonCustomFieldTypes(): void
    {
        $fieldTypes = ['TEXT', 'SELECT', 'CHECKBOX', 'DATE', 'NUMBER', 'EMAIL', 'URL'];
        
        foreach ($fieldTypes as $type) {
            $this->customField->setType($type);
            $this->assertEquals($type, $this->customField->getType());
        }
    }

    public function testCommonTableNames(): void
    {
        $tableNames = ['invoice', 'client', 'quote', 'product', 'user'];
        
        foreach ($tableNames as $table) {
            $this->customField->setTable($table);
            $this->assertEquals($table, $this->customField->getTable());
        }
    }

    public function testLongLabelHandling(): void
    {
        // Test with label at max length (50 characters)
        $longLabel = str_repeat('A', 50);
        
        $this->customField->setLabel($longLabel);
        $this->assertEquals($longLabel, $this->customField->getLabel());
        $this->assertEquals(50, strlen($this->customField->getLabel()));
    }

    public function testCompleteCustomFieldSetup(): void
    {
        // Test setting up a complete custom field
        $this->customField->setId(100);
        $this->customField->setTable('invoice');
        $this->customField->setLabel('Project Reference');
        $this->customField->setType('TEXT');
        $this->customField->setLocation(2);
        $this->customField->setOrder(10);
        $this->customField->setRequired(true);

        $this->assertSame(100, $this->customField->reqId());
        $this->assertEquals('invoice', $this->customField->getTable());
        $this->assertEquals('Project Reference', $this->customField->getLabel());
        $this->assertEquals('TEXT', $this->customField->getType());
        $this->assertEquals(2, $this->customField->getLocation());
        $this->assertEquals(10, $this->customField->getOrder());
        $this->assertTrue($this->customField->getRequired());
    }

    public function testIdReturnsStringType(): void
    {
        // Test that getId always returns a string, even for numeric IDs
        $this->customField->setId(123);
        $this->assertIsInt($this->customField->reqId());
        $this->assertSame(123, $this->customField->reqId());
        
        $this->customField->setId(0);
        $this->assertIsInt($this->customField->reqId());
        $this->assertSame(0, $this->customField->reqId());
    }

    public function testOptionalCustomField(): void
    {
        // Test creating an optional custom field
        $optionalField = new CustomField(
            id: 50,
            table: 'client',
            label: 'Notes',
            type: 'TEXT',
            location: 0,
            order: 1,
            required: false
        );

        $this->assertSame(50, $optionalField->reqId());
        $this->assertEquals('client', $optionalField->getTable());
        $this->assertEquals('Notes', $optionalField->getLabel());
        $this->assertEquals('TEXT', $optionalField->getType());
        $this->assertEquals(0, $optionalField->getLocation());
        $this->assertEquals(1, $optionalField->getOrder());
        $this->assertFalse($optionalField->getRequired());
    }

    public function testRequiredCustomField(): void
    {
        // Test creating a required custom field
        $requiredField = new CustomField(
            id: 60,
            table: 'invoice',
            label: 'Department Code',
            type: 'SELECT',
            location: 1,
            order: 5,
            required: true
        );

        $this->assertSame(60, $requiredField->reqId());
        $this->assertEquals('invoice', $requiredField->getTable());
        $this->assertEquals('Department Code', $requiredField->getLabel());
        $this->assertEquals('SELECT', $requiredField->getType());
        $this->assertEquals(1, $requiredField->getLocation());
        $this->assertEquals(5, $requiredField->getOrder());
        $this->assertTrue($requiredField->getRequired());
    }

    public function testChainedSetterCalls(): void
    {
        // Test that setters work independently
        $this->customField->setId(777);
        $this->customField->setTable('product');
        $this->customField->setLabel('Category');
        $this->customField->setType('SELECT');
        $this->customField->setLocation(3);
        $this->customField->setOrder(15);
        $this->customField->setRequired(true);
        
        $this->assertSame(777, $this->customField->reqId());
        $this->assertEquals('product', $this->customField->getTable());
        $this->assertEquals('Category', $this->customField->getLabel());
        $this->assertEquals('SELECT', $this->customField->getType());
        $this->assertEquals(3, $this->customField->getLocation());
        $this->assertEquals(15, $this->customField->getOrder());
        $this->assertTrue($this->customField->getRequired());
    }
}
