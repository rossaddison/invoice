<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Invoice\Entity\GentorRelation;
use App\Invoice\Entity\Gentor;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

class GentorRelationEntityTest extends Unit
{
    private MockObject $gentor;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->gentor = $this->createMock(Gentor::class);
    }

    public function testConstructorWithDefaults(): void
    {
        $gentorRelation = new GentorRelation();
        
        $this->assertSame('', $gentorRelation->getRelationId());
        $this->assertSame('', $gentorRelation->getLowercaseName());
        $this->assertSame('', $gentorRelation->getCamelcaseName());
        $this->assertSame('', $gentorRelation->getViewFieldName());
        $this->assertNull($gentorRelation->getGentorId());
        $this->assertNull($gentorRelation->getGentor());
    }

    public function testConstructorWithAllParameters(): void
    {
        $gentorRelation = new GentorRelation(
            'client',           // lowercasename
            'Client',           // camelcasename
            'client_name',      // view_field_name
            5                   // gentor_id
        );
        
        $this->assertSame('', $gentorRelation->getRelationId()); // ID is set by database
        $this->assertSame('client', $gentorRelation->getLowercaseName());
        $this->assertSame('Client', $gentorRelation->getCamelcaseName());
        $this->assertSame('client_name', $gentorRelation->getViewFieldName());
        $this->assertSame(5, $gentorRelation->getGentorId());
        $this->assertNull($gentorRelation->getGentor()); // Relationship is set by ORM
    }

    public function testConstructorWithNullValues(): void
    {
        $gentorRelation = new GentorRelation(null, null, null, null);
        
        $this->assertSame('', $gentorRelation->getRelationId());
        $this->assertNull($gentorRelation->getLowercaseName());
        $this->assertNull($gentorRelation->getCamelcaseName());
        $this->assertNull($gentorRelation->getViewFieldName());
        $this->assertNull($gentorRelation->getGentorId());
        $this->assertNull($gentorRelation->getGentor());
    }

    public function testLowercaseNameSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setLowercaseName('invoice');
        $this->assertSame('invoice', $gentorRelation->getLowercaseName());
        
        $gentorRelation->setLowercaseName('quote');
        $this->assertSame('quote', $gentorRelation->getLowercaseName());
    }

    public function testCamelcaseNameSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setCamelcaseName('Invoice');
        $this->assertSame('Invoice', $gentorRelation->getCamelcaseName());
        
        $gentorRelation->setCamelcaseName('SalesOrder');
        $this->assertSame('SalesOrder', $gentorRelation->getCamelcaseName());
    }

    public function testViewFieldNameSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setViewFieldName('invoice_number');
        $this->assertSame('invoice_number', $gentorRelation->getViewFieldName());
        
        $gentorRelation->setViewFieldName('client_display_name');
        $this->assertSame('client_display_name', $gentorRelation->getViewFieldName());
    }

    public function testGentorIdSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setGentorId(10);
        $this->assertSame(10, $gentorRelation->getGentorId());
        
        $gentorRelation->setGentorId(25);
        $this->assertSame(25, $gentorRelation->getGentorId());
    }

    public function testGetGentor(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Initially null (relationship set by ORM)
        $this->assertNull($gentorRelation->getGentor());
    }

    public function testRelationIdReturnsString(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Should return string representation of ID (empty string when null)
        $this->assertIsString($gentorRelation->getRelationId());
        $this->assertSame('', $gentorRelation->getRelationId());
    }

    public function testCommonEntityNames(): void
    {
        $entityNames = [
            ['lowercase' => 'client', 'camelcase' => 'Client'],
            ['lowercase' => 'invoice', 'camelcase' => 'Invoice'],
            ['lowercase' => 'quote', 'camelcase' => 'Quote'],
            ['lowercase' => 'salesorder', 'camelcase' => 'SalesOrder'],
            ['lowercase' => 'product', 'camelcase' => 'Product'],
            ['lowercase' => 'taxrate', 'camelcase' => 'TaxRate'],
            ['lowercase' => 'paymentmethod', 'camelcase' => 'PaymentMethod'],
            ['lowercase' => 'company', 'camelcase' => 'Company'],
        ];
        
        foreach ($entityNames as $names) {
            $gentorRelation = new GentorRelation();
            $gentorRelation->setLowercaseName($names['lowercase']);
            $gentorRelation->setCamelcaseName($names['camelcase']);
            
            $this->assertSame($names['lowercase'], $gentorRelation->getLowercaseName());
            $this->assertSame($names['camelcase'], $gentorRelation->getCamelcaseName());
        }
    }

    public function testCommonViewFieldNames(): void
    {
        $viewFields = [
            'client_name',
            'invoice_number',
            'quote_reference',
            'product_title',
            'company_name',
            'tax_rate_percent',
            'payment_method_name',
            'invoice_total_amount',
            'client_display_name',
            'full_address_text',
        ];
        
        $gentorRelation = new GentorRelation();
        
        foreach ($viewFields as $field) {
            $gentorRelation->setViewFieldName($field);
            $this->assertSame($field, $gentorRelation->getViewFieldName());
        }
    }

    public function testLongEntityNames(): void
    {
        $gentorRelation = new GentorRelation();
        
        $longLowercase = 'very_long_entity_name_with_multiple_words_and_underscores';
        $longCamelcase = 'VeryLongEntityNameWithMultipleWordsAndUnderscores';
        
        $gentorRelation->setLowercaseName($longLowercase);
        $gentorRelation->setCamelcaseName($longCamelcase);
        
        $this->assertSame($longLowercase, $gentorRelation->getLowercaseName());
        $this->assertSame($longCamelcase, $gentorRelation->getCamelcaseName());
    }

    public function testSpecialCharactersInNames(): void
    {
        $gentorRelation = new GentorRelation();
        
        $specialLowercase = 'entity_name-with-dashes_and_numbers123';
        $specialCamelcase = 'EntityName-WithDashesAndNumbers123';
        $specialViewField = 'field_name_with-special.characters_123';
        
        $gentorRelation->setLowercaseName($specialLowercase);
        $gentorRelation->setCamelcaseName($specialCamelcase);
        $gentorRelation->setViewFieldName($specialViewField);
        
        $this->assertSame($specialLowercase, $gentorRelation->getLowercaseName());
        $this->assertSame($specialCamelcase, $gentorRelation->getCamelcaseName());
        $this->assertSame($specialViewField, $gentorRelation->getViewFieldName());
    }

    public function testZeroAndLargeGentorIds(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Zero ID
        $gentorRelation->setGentorId(0);
        $this->assertSame(0, $gentorRelation->getGentorId());
        
        // Large ID
        $gentorRelation->setGentorId(999999999);
        $this->assertSame(999999999, $gentorRelation->getGentorId());
    }

    public function testEmptyStringHandling(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setLowercaseName('');
        $gentorRelation->setCamelcaseName('');
        $gentorRelation->setViewFieldName('');
        
        $this->assertSame('', $gentorRelation->getLowercaseName());
        $this->assertSame('', $gentorRelation->getCamelcaseName());
        $this->assertSame('', $gentorRelation->getViewFieldName());
    }

    public function testCompleteGentorRelationSetup(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Setup a complete relation for Client entity
        $gentorRelation->setLowercaseName('client');
        $gentorRelation->setCamelcaseName('Client');
        $gentorRelation->setViewFieldName('client_display_name');
        $gentorRelation->setGentorId(1);
        
        $this->assertSame('client', $gentorRelation->getLowercaseName());
        $this->assertSame('Client', $gentorRelation->getCamelcaseName());
        $this->assertSame('client_display_name', $gentorRelation->getViewFieldName());
        $this->assertSame(1, $gentorRelation->getGentorId());
        $this->assertNull($gentorRelation->getGentor()); // Relationship set by ORM
        $this->assertSame('', $gentorRelation->getRelationId()); // ID set by database
    }

    public function testEntityRelationScenarios(): void
    {
        $scenarios = [
            [
                'lowercase' => 'invoice',
                'camelcase' => 'Invoice',
                'view_field' => 'invoice_number',
                'gentor_id' => 1
            ],
            [
                'lowercase' => 'client',
                'camelcase' => 'Client',
                'view_field' => 'client_name',
                'gentor_id' => 2
            ],
            [
                'lowercase' => 'product',
                'camelcase' => 'Product',
                'view_field' => 'product_title',
                'gentor_id' => 3
            ],
            [
                'lowercase' => 'taxrate',
                'camelcase' => 'TaxRate',
                'view_field' => 'tax_rate_name',
                'gentor_id' => 4
            ]
        ];
        
        foreach ($scenarios as $scenario) {
            $gentorRelation = new GentorRelation(
                $scenario['lowercase'],
                $scenario['camelcase'],
                $scenario['view_field'],
                $scenario['gentor_id']
            );
            
            $this->assertSame($scenario['lowercase'], $gentorRelation->getLowercaseName());
            $this->assertSame($scenario['camelcase'], $gentorRelation->getCamelcaseName());
            $this->assertSame($scenario['view_field'], $gentorRelation->getViewFieldName());
            $this->assertSame($scenario['gentor_id'], $gentorRelation->getGentorId());
        }
    }

    public function testGetterMethodsConsistency(): void
    {
        $gentorRelation = new GentorRelation('test', 'Test', 'test_field', 100);
        
        // Multiple calls should return same values
        $this->assertSame($gentorRelation->getLowercaseName(), $gentorRelation->getLowercaseName());
        $this->assertSame($gentorRelation->getCamelcaseName(), $gentorRelation->getCamelcaseName());
        $this->assertSame($gentorRelation->getViewFieldName(), $gentorRelation->getViewFieldName());
        $this->assertSame($gentorRelation->getGentorId(), $gentorRelation->getGentorId());
        $this->assertSame($gentorRelation->getGentor(), $gentorRelation->getGentor());
        $this->assertSame($gentorRelation->getRelationId(), $gentorRelation->getRelationId());
    }

    public function testCamelCaseVsLowercaseConsistency(): void
    {
        $gentorRelation = new GentorRelation();
        
        $testCases = [
            ['lowercase' => 'client', 'camelcase' => 'Client'],
            ['lowercase' => 'salesorder', 'camelcase' => 'SalesOrder'],
            ['lowercase' => 'paymentmethod', 'camelcase' => 'PaymentMethod'],
            ['lowercase' => 'taxrate', 'camelcase' => 'TaxRate'],
            ['lowercase' => 'customfield', 'camelcase' => 'CustomField'],
        ];
        
        foreach ($testCases as $case) {
            $gentorRelation->setLowercaseName($case['lowercase']);
            $gentorRelation->setCamelcaseName($case['camelcase']);
            
            $this->assertSame($case['lowercase'], $gentorRelation->getLowercaseName());
            $this->assertSame($case['camelcase'], $gentorRelation->getCamelcaseName());
            
            // Verify they represent the same entity in different cases
            $this->assertSame(
                strtolower($case['camelcase']),
                $case['lowercase']
            );
        }
    }

    public function testFieldNameConventions(): void
    {
        $gentorRelation = new GentorRelation();
        
        $fieldNamePatterns = [
            'simple_field',
            'field_with_multiple_words',
            'field_name_123',
            'very_long_field_name_with_many_words_and_numbers_456',
            'id',
            'created_at',
            'updated_at',
            'display_name',
            'full_address',
        ];
        
        foreach ($fieldNamePatterns as $pattern) {
            $gentorRelation->setViewFieldName($pattern);
            $this->assertSame($pattern, $gentorRelation->getViewFieldName());
        }
    }

    public function testRelationshipProperties(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Test that Gentor relationship starts as null
        $this->assertNull($gentorRelation->getGentor());
        
        // Test that gentor_id can be set independently
        $gentorRelation->setGentorId(42);
        $this->assertSame(42, $gentorRelation->getGentorId());
        $this->assertNull($gentorRelation->getGentor()); // Still null until set by ORM
    }

    public function testPropertyTypes(): void
    {
        $gentorRelation = new GentorRelation('test', 'Test', 'test_field', 123);
        
        // Test return types
        $this->assertIsString($gentorRelation->getRelationId());
        $this->assertIsString($gentorRelation->getLowercaseName());
        $this->assertIsString($gentorRelation->getCamelcaseName());
        $this->assertIsString($gentorRelation->getViewFieldName());
        $this->assertIsInt($gentorRelation->getGentorId());
        $this->assertNull($gentorRelation->getGentor());
    }

    public function testNegativeGentorIds(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Test negative ID (though probably not used in practice)
        $gentorRelation->setGentorId(-1);
        $this->assertSame(-1, $gentorRelation->getGentorId());
        
        $gentorRelation->setGentorId(-999);
        $this->assertSame(-999, $gentorRelation->getGentorId());
    }

    public function testEntityRelationWorkflow(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Initial state
        $this->assertSame('', $gentorRelation->getRelationId());
        $this->assertSame('', $gentorRelation->getLowercaseName());
        $this->assertSame('', $gentorRelation->getCamelcaseName());
        $this->assertSame('', $gentorRelation->getViewFieldName());
        $this->assertNull($gentorRelation->getGentorId());
        $this->assertNull($gentorRelation->getGentor());
        
        // Setup relation
        $gentorRelation->setLowercaseName('invoice');
        $gentorRelation->setCamelcaseName('Invoice');
        $gentorRelation->setViewFieldName('invoice_display');
        $gentorRelation->setGentorId(5);
        
        // Verify setup
        $this->assertSame('invoice', $gentorRelation->getLowercaseName());
        $this->assertSame('Invoice', $gentorRelation->getCamelcaseName());
        $this->assertSame('invoice_display', $gentorRelation->getViewFieldName());
        $this->assertSame(5, $gentorRelation->getGentorId());
        
        // Modify relation
        $gentorRelation->setLowercaseName('quote');
        $gentorRelation->setCamelcaseName('Quote');
        $gentorRelation->setViewFieldName('quote_display');
        $gentorRelation->setGentorId(10);
        
        // Verify modification
        $this->assertSame('quote', $gentorRelation->getLowercaseName());
        $this->assertSame('Quote', $gentorRelation->getCamelcaseName());
        $this->assertSame('quote_display', $gentorRelation->getViewFieldName());
        $this->assertSame(10, $gentorRelation->getGentorId());
    }

    public function testConstructorParameterOrder(): void
    {
        // Test that parameters are correctly assigned in constructor
        $gentorRelation = new GentorRelation('param1', 'param2', 'param3', 42);
        
        $this->assertSame('param1', $gentorRelation->getLowercaseName());
        $this->assertSame('param2', $gentorRelation->getCamelcaseName());
        $this->assertSame('param3', $gentorRelation->getViewFieldName());
        $this->assertSame(42, $gentorRelation->getGentorId());
    }

    public function testNullPropertyHandling(): void
    {
        // Create with null values
        $gentorRelation = new GentorRelation(null, null, null, null);
        
        $this->assertNull($gentorRelation->getLowercaseName());
        $this->assertNull($gentorRelation->getCamelcaseName());
        $this->assertNull($gentorRelation->getViewFieldName());
        $this->assertNull($gentorRelation->getGentorId());
        
        // Set non-null values
        $gentorRelation->setLowercaseName('test');
        $gentorRelation->setCamelcaseName('Test');
        $gentorRelation->setViewFieldName('test_field');
        $gentorRelation->setGentorId(1);
        
        $this->assertSame('test', $gentorRelation->getLowercaseName());
        $this->assertSame('Test', $gentorRelation->getCamelcaseName());
        $this->assertSame('test_field', $gentorRelation->getViewFieldName());
        $this->assertSame(1, $gentorRelation->getGentorId());
    }
}
