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
        
        $this->assertSame('', $gentorRelation->getRelation_id());
        $this->assertSame('', $gentorRelation->getLowercase_name());
        $this->assertSame('', $gentorRelation->getCamelcase_name());
        $this->assertSame('', $gentorRelation->getView_field_name());
        $this->assertNull($gentorRelation->getGentor_id());
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
        
        $this->assertSame('', $gentorRelation->getRelation_id()); // ID is set by database
        $this->assertSame('client', $gentorRelation->getLowercase_name());
        $this->assertSame('Client', $gentorRelation->getCamelcase_name());
        $this->assertSame('client_name', $gentorRelation->getView_field_name());
        $this->assertSame(5, $gentorRelation->getGentor_id());
        $this->assertNull($gentorRelation->getGentor()); // Relationship is set by ORM
    }

    public function testConstructorWithNullValues(): void
    {
        $gentorRelation = new GentorRelation(null, null, null, null);
        
        $this->assertSame('', $gentorRelation->getRelation_id());
        $this->assertNull($gentorRelation->getLowercase_name());
        $this->assertNull($gentorRelation->getCamelcase_name());
        $this->assertNull($gentorRelation->getView_field_name());
        $this->assertNull($gentorRelation->getGentor_id());
        $this->assertNull($gentorRelation->getGentor());
    }

    public function testLowercaseNameSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setLowercase_name('invoice');
        $this->assertSame('invoice', $gentorRelation->getLowercase_name());
        
        $gentorRelation->setLowercase_name('quote');
        $this->assertSame('quote', $gentorRelation->getLowercase_name());
    }

    public function testCamelcaseNameSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setCamelcase_name('Invoice');
        $this->assertSame('Invoice', $gentorRelation->getCamelcase_name());
        
        $gentorRelation->setCamelcase_name('SalesOrder');
        $this->assertSame('SalesOrder', $gentorRelation->getCamelcase_name());
    }

    public function testViewFieldNameSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setView_field_name('invoice_number');
        $this->assertSame('invoice_number', $gentorRelation->getView_field_name());
        
        $gentorRelation->setView_field_name('client_display_name');
        $this->assertSame('client_display_name', $gentorRelation->getView_field_name());
    }

    public function testGentorIdSetterAndGetter(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setGentor_id(10);
        $this->assertSame(10, $gentorRelation->getGentor_id());
        
        $gentorRelation->setGentor_id(25);
        $this->assertSame(25, $gentorRelation->getGentor_id());
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
        $this->assertIsString($gentorRelation->getRelation_id());
        $this->assertSame('', $gentorRelation->getRelation_id());
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
            $gentorRelation->setLowercase_name($names['lowercase']);
            $gentorRelation->setCamelcase_name($names['camelcase']);
            
            $this->assertSame($names['lowercase'], $gentorRelation->getLowercase_name());
            $this->assertSame($names['camelcase'], $gentorRelation->getCamelcase_name());
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
            $gentorRelation->setView_field_name($field);
            $this->assertSame($field, $gentorRelation->getView_field_name());
        }
    }

    public function testLongEntityNames(): void
    {
        $gentorRelation = new GentorRelation();
        
        $longLowercase = 'very_long_entity_name_with_multiple_words_and_underscores';
        $longCamelcase = 'VeryLongEntityNameWithMultipleWordsAndUnderscores';
        
        $gentorRelation->setLowercase_name($longLowercase);
        $gentorRelation->setCamelcase_name($longCamelcase);
        
        $this->assertSame($longLowercase, $gentorRelation->getLowercase_name());
        $this->assertSame($longCamelcase, $gentorRelation->getCamelcase_name());
    }

    public function testSpecialCharactersInNames(): void
    {
        $gentorRelation = new GentorRelation();
        
        $specialLowercase = 'entity_name-with-dashes_and_numbers123';
        $specialCamelcase = 'EntityName-WithDashesAndNumbers123';
        $specialViewField = 'field_name_with-special.characters_123';
        
        $gentorRelation->setLowercase_name($specialLowercase);
        $gentorRelation->setCamelcase_name($specialCamelcase);
        $gentorRelation->setView_field_name($specialViewField);
        
        $this->assertSame($specialLowercase, $gentorRelation->getLowercase_name());
        $this->assertSame($specialCamelcase, $gentorRelation->getCamelcase_name());
        $this->assertSame($specialViewField, $gentorRelation->getView_field_name());
    }

    public function testZeroAndLargeGentorIds(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Zero ID
        $gentorRelation->setGentor_id(0);
        $this->assertSame(0, $gentorRelation->getGentor_id());
        
        // Large ID
        $gentorRelation->setGentor_id(999999999);
        $this->assertSame(999999999, $gentorRelation->getGentor_id());
    }

    public function testEmptyStringHandling(): void
    {
        $gentorRelation = new GentorRelation();
        
        $gentorRelation->setLowercase_name('');
        $gentorRelation->setCamelcase_name('');
        $gentorRelation->setView_field_name('');
        
        $this->assertSame('', $gentorRelation->getLowercase_name());
        $this->assertSame('', $gentorRelation->getCamelcase_name());
        $this->assertSame('', $gentorRelation->getView_field_name());
    }

    public function testCompleteGentorRelationSetup(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Setup a complete relation for Client entity
        $gentorRelation->setLowercase_name('client');
        $gentorRelation->setCamelcase_name('Client');
        $gentorRelation->setView_field_name('client_display_name');
        $gentorRelation->setGentor_id(1);
        
        $this->assertSame('client', $gentorRelation->getLowercase_name());
        $this->assertSame('Client', $gentorRelation->getCamelcase_name());
        $this->assertSame('client_display_name', $gentorRelation->getView_field_name());
        $this->assertSame(1, $gentorRelation->getGentor_id());
        $this->assertNull($gentorRelation->getGentor()); // Relationship set by ORM
        $this->assertSame('', $gentorRelation->getRelation_id()); // ID set by database
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
            
            $this->assertSame($scenario['lowercase'], $gentorRelation->getLowercase_name());
            $this->assertSame($scenario['camelcase'], $gentorRelation->getCamelcase_name());
            $this->assertSame($scenario['view_field'], $gentorRelation->getView_field_name());
            $this->assertSame($scenario['gentor_id'], $gentorRelation->getGentor_id());
        }
    }

    public function testGetterMethodsConsistency(): void
    {
        $gentorRelation = new GentorRelation('test', 'Test', 'test_field', 100);
        
        // Multiple calls should return same values
        $this->assertSame($gentorRelation->getLowercase_name(), $gentorRelation->getLowercase_name());
        $this->assertSame($gentorRelation->getCamelcase_name(), $gentorRelation->getCamelcase_name());
        $this->assertSame($gentorRelation->getView_field_name(), $gentorRelation->getView_field_name());
        $this->assertSame($gentorRelation->getGentor_id(), $gentorRelation->getGentor_id());
        $this->assertSame($gentorRelation->getGentor(), $gentorRelation->getGentor());
        $this->assertSame($gentorRelation->getRelation_id(), $gentorRelation->getRelation_id());
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
            $gentorRelation->setLowercase_name($case['lowercase']);
            $gentorRelation->setCamelcase_name($case['camelcase']);
            
            $this->assertSame($case['lowercase'], $gentorRelation->getLowercase_name());
            $this->assertSame($case['camelcase'], $gentorRelation->getCamelcase_name());
            
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
            $gentorRelation->setView_field_name($pattern);
            $this->assertSame($pattern, $gentorRelation->getView_field_name());
        }
    }

    public function testRelationshipProperties(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Test that Gentor relationship starts as null
        $this->assertNull($gentorRelation->getGentor());
        
        // Test that gentor_id can be set independently
        $gentorRelation->setGentor_id(42);
        $this->assertSame(42, $gentorRelation->getGentor_id());
        $this->assertNull($gentorRelation->getGentor()); // Still null until set by ORM
    }

    public function testPropertyTypes(): void
    {
        $gentorRelation = new GentorRelation('test', 'Test', 'test_field', 123);
        
        // Test return types
        $this->assertIsString($gentorRelation->getRelation_id());
        $this->assertIsString($gentorRelation->getLowercase_name());
        $this->assertIsString($gentorRelation->getCamelcase_name());
        $this->assertIsString($gentorRelation->getView_field_name());
        $this->assertIsInt($gentorRelation->getGentor_id());
        $this->assertNull($gentorRelation->getGentor());
    }

    public function testNegativeGentorIds(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Test negative ID (though probably not used in practice)
        $gentorRelation->setGentor_id(-1);
        $this->assertSame(-1, $gentorRelation->getGentor_id());
        
        $gentorRelation->setGentor_id(-999);
        $this->assertSame(-999, $gentorRelation->getGentor_id());
    }

    public function testEntityRelationWorkflow(): void
    {
        $gentorRelation = new GentorRelation();
        
        // Initial state
        $this->assertSame('', $gentorRelation->getRelation_id());
        $this->assertSame('', $gentorRelation->getLowercase_name());
        $this->assertSame('', $gentorRelation->getCamelcase_name());
        $this->assertSame('', $gentorRelation->getView_field_name());
        $this->assertNull($gentorRelation->getGentor_id());
        $this->assertNull($gentorRelation->getGentor());
        
        // Setup relation
        $gentorRelation->setLowercase_name('invoice');
        $gentorRelation->setCamelcase_name('Invoice');
        $gentorRelation->setView_field_name('invoice_display');
        $gentorRelation->setGentor_id(5);
        
        // Verify setup
        $this->assertSame('invoice', $gentorRelation->getLowercase_name());
        $this->assertSame('Invoice', $gentorRelation->getCamelcase_name());
        $this->assertSame('invoice_display', $gentorRelation->getView_field_name());
        $this->assertSame(5, $gentorRelation->getGentor_id());
        
        // Modify relation
        $gentorRelation->setLowercase_name('quote');
        $gentorRelation->setCamelcase_name('Quote');
        $gentorRelation->setView_field_name('quote_display');
        $gentorRelation->setGentor_id(10);
        
        // Verify modification
        $this->assertSame('quote', $gentorRelation->getLowercase_name());
        $this->assertSame('Quote', $gentorRelation->getCamelcase_name());
        $this->assertSame('quote_display', $gentorRelation->getView_field_name());
        $this->assertSame(10, $gentorRelation->getGentor_id());
    }

    public function testConstructorParameterOrder(): void
    {
        // Test that parameters are correctly assigned in constructor
        $gentorRelation = new GentorRelation('param1', 'param2', 'param3', 42);
        
        $this->assertSame('param1', $gentorRelation->getLowercase_name());
        $this->assertSame('param2', $gentorRelation->getCamelcase_name());
        $this->assertSame('param3', $gentorRelation->getView_field_name());
        $this->assertSame(42, $gentorRelation->getGentor_id());
    }

    public function testNullPropertyHandling(): void
    {
        // Create with null values
        $gentorRelation = new GentorRelation(null, null, null, null);
        
        $this->assertNull($gentorRelation->getLowercase_name());
        $this->assertNull($gentorRelation->getCamelcase_name());
        $this->assertNull($gentorRelation->getView_field_name());
        $this->assertNull($gentorRelation->getGentor_id());
        
        // Set non-null values
        $gentorRelation->setLowercase_name('test');
        $gentorRelation->setCamelcase_name('Test');
        $gentorRelation->setView_field_name('test_field');
        $gentorRelation->setGentor_id(1);
        
        $this->assertSame('test', $gentorRelation->getLowercase_name());
        $this->assertSame('Test', $gentorRelation->getCamelcase_name());
        $this->assertSame('test_field', $gentorRelation->getView_field_name());
        $this->assertSame(1, $gentorRelation->getGentor_id());
    }
}