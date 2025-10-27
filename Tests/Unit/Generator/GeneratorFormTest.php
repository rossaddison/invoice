<?php

declare(strict_types=1);

namespace Tests\Unit\Generator;

use App\Invoice\Entity\Gentor;
use App\Invoice\Generator\GeneratorForm;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

class GeneratorFormTest extends Unit
{
    private MockObject $gentor;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->gentor = $this->createMock(Gentor::class);
    }

    public function testFormInitializationFromEntity(): void
    {
        $this->setupMockGentor();
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('test_route_prefix', $form->getRoute_prefix());
        $this->assertSame('test_route_suffix', $form->getRoute_suffix());
        $this->assertSame('TestCamelCase', $form->getCamelcase_capital_name());
        $this->assertSame('test_singular', $form->getSmall_singular_name());
        $this->assertSame('test_plural', $form->getSmall_plural_name());
        $this->assertSame('Test\\Namespace', $form->getNamespace_path());
        $this->assertSame('test/layout/dir', $form->getController_layout_dir());
        $this->assertSame('test.layout.dir', $form->getController_layout_dir_dot_path());
        $this->assertSame('test_table', $form->getPre_entity_table());
    }

    public function testGetFormName(): void
    {
        $this->setupMockGentor();
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('', $form->getFormName());
    }

    public function testAllStringGettersReturnString(): void
    {
        $this->setupMockGentor();
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertIsString($form->getRoute_prefix());
        $this->assertIsString($form->getRoute_suffix());
        $this->assertIsString($form->getCamelcase_capital_name());
        $this->assertIsString($form->getSmall_singular_name());
        $this->assertIsString($form->getSmall_plural_name());
        $this->assertIsString($form->getNamespace_path());
        $this->assertIsString($form->getController_layout_dir());
        $this->assertIsString($form->getController_layout_dir_dot_path());
        $this->assertIsString($form->getPre_entity_table());
        $this->assertIsString($form->getFormName());
    }

    public function testEmptyEntityValues(): void
    {
        $this->gentor->method('getRoute_prefix')->willReturn('');
        $this->gentor->method('getRoute_suffix')->willReturn('');
        $this->gentor->method('getCamelcase_capital_name')->willReturn('');
        $this->gentor->method('getSmall_singular_name')->willReturn('');
        $this->gentor->method('getSmall_plural_name')->willReturn('');
        $this->gentor->method('getNamespace_path')->willReturn('');
        $this->gentor->method('getController_layout_dir')->willReturn('');
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn('');
        $this->gentor->method('getPre_entity_table')->willReturn('');
        $this->gentor->method('isFlash_include')->willReturn(false);
        $this->gentor->method('isCreated_include')->willReturn(false);
        $this->gentor->method('isModified_include')->willReturn(false);
        $this->gentor->method('isUpdated_include')->willReturn(false);
        $this->gentor->method('isDeleted_include')->willReturn(false);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('', $form->getRoute_prefix());
        $this->assertSame('', $form->getRoute_suffix());
        $this->assertSame('', $form->getCamelcase_capital_name());
        $this->assertSame('', $form->getSmall_singular_name());
        $this->assertSame('', $form->getSmall_plural_name());
        $this->assertSame('', $form->getNamespace_path());
        $this->assertSame('', $form->getController_layout_dir());
        $this->assertSame('', $form->getController_layout_dir_dot_path());
        $this->assertSame('', $form->getPre_entity_table());
    }

    public function testLongStringValues(): void
    {
        $longString = str_repeat('Long', 50); // 200 characters
        
        $this->gentor->method('getRoute_prefix')->willReturn($longString);
        $this->gentor->method('getRoute_suffix')->willReturn($longString);
        $this->gentor->method('getCamelcase_capital_name')->willReturn($longString);
        $this->gentor->method('getSmall_singular_name')->willReturn($longString);
        $this->gentor->method('getSmall_plural_name')->willReturn($longString);
        $this->gentor->method('getNamespace_path')->willReturn($longString);
        $this->gentor->method('getController_layout_dir')->willReturn($longString);
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn($longString);
        $this->gentor->method('getPre_entity_table')->willReturn($longString);
        $this->gentor->method('isFlash_include')->willReturn(true);
        $this->gentor->method('isCreated_include')->willReturn(true);
        $this->gentor->method('isModified_include')->willReturn(true);
        $this->gentor->method('isUpdated_include')->willReturn(true);
        $this->gentor->method('isDeleted_include')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame($longString, $form->getRoute_prefix());
        $this->assertSame($longString, $form->getRoute_suffix());
        $this->assertSame($longString, $form->getCamelcase_capital_name());
        $this->assertSame($longString, $form->getSmall_singular_name());
        $this->assertSame($longString, $form->getSmall_plural_name());
        $this->assertSame($longString, $form->getNamespace_path());
        $this->assertSame($longString, $form->getController_layout_dir());
        $this->assertSame($longString, $form->getController_layout_dir_dot_path());
        $this->assertSame($longString, $form->getPre_entity_table());
    }

    public function testSpecialCharactersInStrings(): void
    {
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?`~"\'\\';
        
        $this->gentor->method('getRoute_prefix')->willReturn($specialChars);
        $this->gentor->method('getRoute_suffix')->willReturn($specialChars);
        $this->gentor->method('getCamelcase_capital_name')->willReturn($specialChars);
        $this->gentor->method('getSmall_singular_name')->willReturn($specialChars);
        $this->gentor->method('getSmall_plural_name')->willReturn($specialChars);
        $this->gentor->method('getNamespace_path')->willReturn($specialChars);
        $this->gentor->method('getController_layout_dir')->willReturn($specialChars);
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn($specialChars);
        $this->gentor->method('getPre_entity_table')->willReturn($specialChars);
        $this->gentor->method('isFlash_include')->willReturn(true);
        $this->gentor->method('isCreated_include')->willReturn(false);
        $this->gentor->method('isModified_include')->willReturn(true);
        $this->gentor->method('isUpdated_include')->willReturn(false);
        $this->gentor->method('isDeleted_include')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame($specialChars, $form->getRoute_prefix());
        $this->assertSame($specialChars, $form->getRoute_suffix());
        $this->assertSame($specialChars, $form->getCamelcase_capital_name());
        $this->assertSame($specialChars, $form->getSmall_singular_name());
        $this->assertSame($specialChars, $form->getSmall_plural_name());
        $this->assertSame($specialChars, $form->getNamespace_path());
        $this->assertSame($specialChars, $form->getController_layout_dir());
        $this->assertSame($specialChars, $form->getController_layout_dir_dot_path());
        $this->assertSame($specialChars, $form->getPre_entity_table());
    }

    public function testUnicodeCharactersInStrings(): void
    {
        $unicode = 'Hello 世界! 🌍 Héllö Wørld™€₹中文';
        
        $this->gentor->method('getRoute_prefix')->willReturn($unicode);
        $this->gentor->method('getRoute_suffix')->willReturn($unicode);
        $this->gentor->method('getCamelcase_capital_name')->willReturn($unicode);
        $this->gentor->method('getSmall_singular_name')->willReturn($unicode);
        $this->gentor->method('getSmall_plural_name')->willReturn($unicode);
        $this->gentor->method('getNamespace_path')->willReturn($unicode);
        $this->gentor->method('getController_layout_dir')->willReturn($unicode);
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn($unicode);
        $this->gentor->method('getPre_entity_table')->willReturn($unicode);
        $this->gentor->method('isFlash_include')->willReturn(false);
        $this->gentor->method('isCreated_include')->willReturn(false);
        $this->gentor->method('isModified_include')->willReturn(false);
        $this->gentor->method('isUpdated_include')->willReturn(false);
        $this->gentor->method('isDeleted_include')->willReturn(false);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame($unicode, $form->getRoute_prefix());
        $this->assertSame($unicode, $form->getRoute_suffix());
        $this->assertSame($unicode, $form->getCamelcase_capital_name());
        $this->assertSame($unicode, $form->getSmall_singular_name());
        $this->assertSame($unicode, $form->getSmall_plural_name());
        $this->assertSame($unicode, $form->getNamespace_path());
        $this->assertSame($unicode, $form->getController_layout_dir());
        $this->assertSame($unicode, $form->getController_layout_dir_dot_path());
        $this->assertSame($unicode, $form->getPre_entity_table());
    }

    public function testCommonGeneratorScenarios(): void
    {
        // Test typical invoice generator scenario
        $this->gentor->method('getRoute_prefix')->willReturn('invoice');
        $this->gentor->method('getRoute_suffix')->willReturn('invoice');
        $this->gentor->method('getCamelcase_capital_name')->willReturn('Invoice');
        $this->gentor->method('getSmall_singular_name')->willReturn('invoice');
        $this->gentor->method('getSmall_plural_name')->willReturn('invoices');
        $this->gentor->method('getNamespace_path')->willReturn('App\\Invoice');
        $this->gentor->method('getController_layout_dir')->willReturn('invoice');
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn('invoice');
        $this->gentor->method('getPre_entity_table')->willReturn('inv_');
        $this->gentor->method('isFlash_include')->willReturn(true);
        $this->gentor->method('isCreated_include')->willReturn(true);
        $this->gentor->method('isModified_include')->willReturn(true);
        $this->gentor->method('isUpdated_include')->willReturn(true);
        $this->gentor->method('isDeleted_include')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('invoice', $form->getRoute_prefix());
        $this->assertSame('invoice', $form->getRoute_suffix());
        $this->assertSame('Invoice', $form->getCamelcase_capital_name());
        $this->assertSame('invoice', $form->getSmall_singular_name());
        $this->assertSame('invoices', $form->getSmall_plural_name());
        $this->assertSame('App\\Invoice', $form->getNamespace_path());
        $this->assertSame('invoice', $form->getController_layout_dir());
        $this->assertSame('invoice', $form->getController_layout_dir_dot_path());
        $this->assertSame('inv_', $form->getPre_entity_table());
    }

    public function testQuoteGeneratorScenario(): void
    {
        // Test typical quote generator scenario
        $this->gentor->method('getRoute_prefix')->willReturn('quote');
        $this->gentor->method('getRoute_suffix')->willReturn('quote');
        $this->gentor->method('getCamelcase_capital_name')->willReturn('Quote');
        $this->gentor->method('getSmall_singular_name')->willReturn('quote');
        $this->gentor->method('getSmall_plural_name')->willReturn('quotes');
        $this->gentor->method('getNamespace_path')->willReturn('App\\Quote');
        $this->gentor->method('getController_layout_dir')->willReturn('quote');
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn('quote');
        $this->gentor->method('getPre_entity_table')->willReturn('quote_');
        $this->gentor->method('isFlash_include')->willReturn(false);
        $this->gentor->method('isCreated_include')->willReturn(true);
        $this->gentor->method('isModified_include')->willReturn(false);
        $this->gentor->method('isUpdated_include')->willReturn(true);
        $this->gentor->method('isDeleted_include')->willReturn(false);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('quote', $form->getRoute_prefix());
        $this->assertSame('quote', $form->getRoute_suffix());
        $this->assertSame('Quote', $form->getCamelcase_capital_name());
        $this->assertSame('quote', $form->getSmall_singular_name());
        $this->assertSame('quotes', $form->getSmall_plural_name());
        $this->assertSame('App\\Quote', $form->getNamespace_path());
        $this->assertSame('quote', $form->getController_layout_dir());
        $this->assertSame('quote', $form->getController_layout_dir_dot_path());
        $this->assertSame('quote_', $form->getPre_entity_table());
    }

    public function testGetterMethodsConsistency(): void
    {
        $this->setupMockGentor();
        
        $form = new GeneratorForm($this->gentor);
        
        // Test that getter methods are consistent (same result on multiple calls)
        $this->assertSame($form->getRoute_prefix(), $form->getRoute_prefix());
        $this->assertSame($form->getRoute_suffix(), $form->getRoute_suffix());
        $this->assertSame($form->getCamelcase_capital_name(), $form->getCamelcase_capital_name());
        $this->assertSame($form->getSmall_singular_name(), $form->getSmall_singular_name());
        $this->assertSame($form->getSmall_plural_name(), $form->getSmall_plural_name());
        $this->assertSame($form->getNamespace_path(), $form->getNamespace_path());
        $this->assertSame($form->getController_layout_dir(), $form->getController_layout_dir());
        $this->assertSame($form->getController_layout_dir_dot_path(), $form->getController_layout_dir_dot_path());
        $this->assertSame($form->getPre_entity_table(), $form->getPre_entity_table());
        $this->assertSame($form->getFormName(), $form->getFormName());
    }

    public function testFormNameAlwaysEmpty(): void
    {
        $this->setupMockGentor();
        
        $form = new GeneratorForm($this->gentor);
        
        // Form name should always be empty regardless of entity values
        $this->assertSame('', $form->getFormName());
        $this->assertEmpty($form->getFormName());
    }

    public function testComplexNamespaceScenarios(): void
    {
        // Test deep namespace
        $this->gentor->method('getRoute_prefix')->willReturn('deep/nested/route');
        $this->gentor->method('getRoute_suffix')->willReturn('entity');
        $this->gentor->method('getCamelcase_capital_name')->willReturn('DeepNestedEntity');
        $this->gentor->method('getSmall_singular_name')->willReturn('entity');
        $this->gentor->method('getSmall_plural_name')->willReturn('entities');
        $this->gentor->method('getNamespace_path')->willReturn('App\\Deep\\Nested\\Entity');
        $this->gentor->method('getController_layout_dir')->willReturn('deep/nested/entity');
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn('deep.nested.entity');
        $this->gentor->method('getPre_entity_table')->willReturn('deep_nested_');
        $this->gentor->method('isFlash_include')->willReturn(true);
        $this->gentor->method('isCreated_include')->willReturn(true);
        $this->gentor->method('isModified_include')->willReturn(true);
        $this->gentor->method('isUpdated_include')->willReturn(true);
        $this->gentor->method('isDeleted_include')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('deep/nested/route', $form->getRoute_prefix());
        $this->assertSame('entity', $form->getRoute_suffix());
        $this->assertSame('DeepNestedEntity', $form->getCamelcase_capital_name());
        $this->assertSame('entity', $form->getSmall_singular_name());
        $this->assertSame('entities', $form->getSmall_plural_name());
        $this->assertSame('App\\Deep\\Nested\\Entity', $form->getNamespace_path());
        $this->assertSame('deep/nested/entity', $form->getController_layout_dir());
        $this->assertSame('deep.nested.entity', $form->getController_layout_dir_dot_path());
        $this->assertSame('deep_nested_', $form->getPre_entity_table());
    }

    private function setupMockGentor(): void
    {
        $this->gentor->method('getRoute_prefix')->willReturn('test_route_prefix');
        $this->gentor->method('getRoute_suffix')->willReturn('test_route_suffix');
        $this->gentor->method('getCamelcase_capital_name')->willReturn('TestCamelCase');
        $this->gentor->method('getSmall_singular_name')->willReturn('test_singular');
        $this->gentor->method('getSmall_plural_name')->willReturn('test_plural');
        $this->gentor->method('getNamespace_path')->willReturn('Test\\Namespace');
        $this->gentor->method('getController_layout_dir')->willReturn('test/layout/dir');
        $this->gentor->method('getController_layout_dir_dot_path')->willReturn('test.layout.dir');
        $this->gentor->method('getPre_entity_table')->willReturn('test_table');
        $this->gentor->method('isFlash_include')->willReturn(true);
        $this->gentor->method('isCreated_include')->willReturn(true);
        $this->gentor->method('isModified_include')->willReturn(true);
        $this->gentor->method('isUpdated_include')->willReturn(true);
        $this->gentor->method('isDeleted_include')->willReturn(true);
    }
}