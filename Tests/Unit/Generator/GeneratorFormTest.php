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
        
        $this->assertSame('test_route_prefix', $form->getRoutePrefix());
        $this->assertSame('test_route_suffix', $form->getRouteSuffix());
        $this->assertSame('TestCamelCase', $form->getCamelcaseCapitalName());
        $this->assertSame('test_singular', $form->getSmallSingularName());
        $this->assertSame('test_plural', $form->getSmallPluralName());
        $this->assertSame('Test\\Namespace', $form->getNamespacePath());
        $this->assertSame('test/layout/dir', $form->getControllerLayoutDir());
        $this->assertSame('test.layout.dir', $form->getControllerLayoutDirDotPath());
        $this->assertSame('test_table', $form->getPreEntityTable());
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
        
        $this->assertIsString($form->getRoutePrefix());
        $this->assertIsString($form->getRouteSuffix());
        $this->assertIsString($form->getCamelcaseCapitalName());
        $this->assertIsString($form->getSmallSingularName());
        $this->assertIsString($form->getSmallPluralName());
        $this->assertIsString($form->getNamespacePath());
        $this->assertIsString($form->getControllerLayoutDir());
        $this->assertIsString($form->getControllerLayoutDirDotPath());
        $this->assertIsString($form->getPreEntityTable());
        $this->assertIsString($form->getFormName());
    }

    public function testEmptyEntityValues(): void
    {
        $this->gentor->method('getRoutePrefix')->willReturn('');
        $this->gentor->method('getRouteSuffix')->willReturn('');
        $this->gentor->method('getCamelcaseCapitalName')->willReturn('');
        $this->gentor->method('getSmallSingularName')->willReturn('');
        $this->gentor->method('getSmallPluralName')->willReturn('');
        $this->gentor->method('getNamespacePath')->willReturn('');
        $this->gentor->method('getControllerLayoutDir')->willReturn('');
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn('');
        $this->gentor->method('getPreEntityTable')->willReturn('');
        $this->gentor->method('isFlashInclude')->willReturn(false);
        $this->gentor->method('isCreatedInclude')->willReturn(false);
        $this->gentor->method('isModifiedInclude')->willReturn(false);
        $this->gentor->method('isUpdatedInclude')->willReturn(false);
        $this->gentor->method('isDeletedInclude')->willReturn(false);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('', $form->getRoutePrefix());
        $this->assertSame('', $form->getRouteSuffix());
        $this->assertSame('', $form->getCamelcaseCapitalName());
        $this->assertSame('', $form->getSmallSingularName());
        $this->assertSame('', $form->getSmallPluralName());
        $this->assertSame('', $form->getNamespacePath());
        $this->assertSame('', $form->getControllerLayoutDir());
        $this->assertSame('', $form->getControllerLayoutDirDotPath());
        $this->assertSame('', $form->getPreEntityTable());
    }

    public function testLongStringValues(): void
    {
        $longString = str_repeat('Long', 50); // 200 characters
        
        $this->gentor->method('getRoutePrefix')->willReturn($longString);
        $this->gentor->method('getRouteSuffix')->willReturn($longString);
        $this->gentor->method('getCamelcaseCapitalName')->willReturn($longString);
        $this->gentor->method('getSmallSingularName')->willReturn($longString);
        $this->gentor->method('getSmallPluralName')->willReturn($longString);
        $this->gentor->method('getNamespacePath')->willReturn($longString);
        $this->gentor->method('getControllerLayoutDir')->willReturn($longString);
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn($longString);
        $this->gentor->method('getPreEntityTable')->willReturn($longString);
        $this->gentor->method('isFlashInclude')->willReturn(true);
        $this->gentor->method('isCreatedInclude')->willReturn(true);
        $this->gentor->method('isModifiedInclude')->willReturn(true);
        $this->gentor->method('isUpdatedInclude')->willReturn(true);
        $this->gentor->method('isDeletedInclude')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame($longString, $form->getRoutePrefix());
        $this->assertSame($longString, $form->getRouteSuffix());
        $this->assertSame($longString, $form->getCamelcaseCapitalName());
        $this->assertSame($longString, $form->getSmallSingularName());
        $this->assertSame($longString, $form->getSmallPluralName());
        $this->assertSame($longString, $form->getNamespacePath());
        $this->assertSame($longString, $form->getControllerLayoutDir());
        $this->assertSame($longString, $form->getControllerLayoutDirDotPath());
        $this->assertSame($longString, $form->getPreEntityTable());
    }

    public function testSpecialCharactersInStrings(): void
    {
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?`~"\'\\';
        
        $this->gentor->method('getRoutePrefix')->willReturn($specialChars);
        $this->gentor->method('getRouteSuffix')->willReturn($specialChars);
        $this->gentor->method('getCamelcaseCapitalName')->willReturn($specialChars);
        $this->gentor->method('getSmallSingularName')->willReturn($specialChars);
        $this->gentor->method('getSmallPluralName')->willReturn($specialChars);
        $this->gentor->method('getNamespacePath')->willReturn($specialChars);
        $this->gentor->method('getControllerLayoutDir')->willReturn($specialChars);
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn($specialChars);
        $this->gentor->method('getPreEntityTable')->willReturn($specialChars);
        $this->gentor->method('isFlashInclude')->willReturn(true);
        $this->gentor->method('isCreatedInclude')->willReturn(false);
        $this->gentor->method('isModifiedInclude')->willReturn(true);
        $this->gentor->method('isUpdatedInclude')->willReturn(false);
        $this->gentor->method('isDeletedInclude')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame($specialChars, $form->getRoutePrefix());
        $this->assertSame($specialChars, $form->getRouteSuffix());
        $this->assertSame($specialChars, $form->getCamelcaseCapitalName());
        $this->assertSame($specialChars, $form->getSmallSingularName());
        $this->assertSame($specialChars, $form->getSmallPluralName());
        $this->assertSame($specialChars, $form->getNamespacePath());
        $this->assertSame($specialChars, $form->getControllerLayoutDir());
        $this->assertSame($specialChars, $form->getControllerLayoutDirDotPath());
        $this->assertSame($specialChars, $form->getPreEntityTable());
    }

    public function testUnicodeCharactersInStrings(): void
    {
        $unicode = 'Hello 世界! 🌍 Héllö Wørld™€₹中文';
        
        $this->gentor->method('getRoutePrefix')->willReturn($unicode);
        $this->gentor->method('getRouteSuffix')->willReturn($unicode);
        $this->gentor->method('getCamelcaseCapitalName')->willReturn($unicode);
        $this->gentor->method('getSmallSingularName')->willReturn($unicode);
        $this->gentor->method('getSmallPluralName')->willReturn($unicode);
        $this->gentor->method('getNamespacePath')->willReturn($unicode);
        $this->gentor->method('getControllerLayoutDir')->willReturn($unicode);
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn($unicode);
        $this->gentor->method('getPreEntityTable')->willReturn($unicode);
        $this->gentor->method('isFlashInclude')->willReturn(false);
        $this->gentor->method('isCreatedInclude')->willReturn(false);
        $this->gentor->method('isModifiedInclude')->willReturn(false);
        $this->gentor->method('isUpdatedInclude')->willReturn(false);
        $this->gentor->method('isDeletedInclude')->willReturn(false);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame($unicode, $form->getRoutePrefix());
        $this->assertSame($unicode, $form->getRouteSuffix());
        $this->assertSame($unicode, $form->getCamelcaseCapitalName());
        $this->assertSame($unicode, $form->getSmallSingularName());
        $this->assertSame($unicode, $form->getSmallPluralName());
        $this->assertSame($unicode, $form->getNamespacePath());
        $this->assertSame($unicode, $form->getControllerLayoutDir());
        $this->assertSame($unicode, $form->getControllerLayoutDirDotPath());
        $this->assertSame($unicode, $form->getPreEntityTable());
    }

    public function testCommonGeneratorScenarios(): void
    {
        // Test typical invoice generator scenario
        $this->gentor->method('getRoutePrefix')->willReturn('invoice');
        $this->gentor->method('getRouteSuffix')->willReturn('invoice');
        $this->gentor->method('getCamelcaseCapitalName')->willReturn('Invoice');
        $this->gentor->method('getSmallSingularName')->willReturn('invoice');
        $this->gentor->method('getSmallPluralName')->willReturn('invoices');
        $this->gentor->method('getNamespacePath')->willReturn('App\\Invoice');
        $this->gentor->method('getControllerLayoutDir')->willReturn('invoice');
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn('invoice');
        $this->gentor->method('getPreEntityTable')->willReturn('inv_');
        $this->gentor->method('isFlashInclude')->willReturn(true);
        $this->gentor->method('isCreatedInclude')->willReturn(true);
        $this->gentor->method('isModifiedInclude')->willReturn(true);
        $this->gentor->method('isUpdatedInclude')->willReturn(true);
        $this->gentor->method('isDeletedInclude')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('invoice', $form->getRoutePrefix());
        $this->assertSame('invoice', $form->getRouteSuffix());
        $this->assertSame('Invoice', $form->getCamelcaseCapitalName());
        $this->assertSame('invoice', $form->getSmallSingularName());
        $this->assertSame('invoices', $form->getSmallPluralName());
        $this->assertSame('App\\Invoice', $form->getNamespacePath());
        $this->assertSame('invoice', $form->getControllerLayoutDir());
        $this->assertSame('invoice', $form->getControllerLayoutDirDotPath());
        $this->assertSame('inv_', $form->getPreEntityTable());
    }

    public function testQuoteGeneratorScenario(): void
    {
        // Test typical quote generator scenario
        $this->gentor->method('getRoutePrefix')->willReturn('quote');
        $this->gentor->method('getRouteSuffix')->willReturn('quote');
        $this->gentor->method('getCamelcaseCapitalName')->willReturn('Quote');
        $this->gentor->method('getSmallSingularName')->willReturn('quote');
        $this->gentor->method('getSmallPluralName')->willReturn('quotes');
        $this->gentor->method('getNamespacePath')->willReturn('App\\Quote');
        $this->gentor->method('getControllerLayoutDir')->willReturn('quote');
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn('quote');
        $this->gentor->method('getPreEntityTable')->willReturn('quote_');
        $this->gentor->method('isFlashInclude')->willReturn(false);
        $this->gentor->method('isCreatedInclude')->willReturn(true);
        $this->gentor->method('isModifiedInclude')->willReturn(false);
        $this->gentor->method('isUpdatedInclude')->willReturn(true);
        $this->gentor->method('isDeletedInclude')->willReturn(false);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('quote', $form->getRoutePrefix());
        $this->assertSame('quote', $form->getRouteSuffix());
        $this->assertSame('Quote', $form->getCamelcaseCapitalName());
        $this->assertSame('quote', $form->getSmallSingularName());
        $this->assertSame('quotes', $form->getSmallPluralName());
        $this->assertSame('App\\Quote', $form->getNamespacePath());
        $this->assertSame('quote', $form->getControllerLayoutDir());
        $this->assertSame('quote', $form->getControllerLayoutDirDotPath());
        $this->assertSame('quote_', $form->getPreEntityTable());
    }

    public function testGetterMethodsConsistency(): void
    {
        $this->setupMockGentor();
        
        $form = new GeneratorForm($this->gentor);
        
        // Test that getter methods are consistent (same result on multiple calls)
        $this->assertSame($form->getRoutePrefix(), $form->getRoutePrefix());
        $this->assertSame($form->getRouteSuffix(), $form->getRouteSuffix());
        $this->assertSame($form->getCamelcaseCapitalName(), $form->getCamelcaseCapitalName());
        $this->assertSame($form->getSmallSingularName(), $form->getSmallSingularName());
        $this->assertSame($form->getSmallPluralName(), $form->getSmallPluralName());
        $this->assertSame($form->getNamespacePath(), $form->getNamespacePath());
        $this->assertSame($form->getControllerLayoutDir(), $form->getControllerLayoutDir());
        $this->assertSame($form->getControllerLayoutDirDotPath(), $form->getControllerLayoutDirDotPath());
        $this->assertSame($form->getPreEntityTable(), $form->getPreEntityTable());
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
        $this->gentor->method('getRoutePrefix')->willReturn('deep/nested/route');
        $this->gentor->method('getRouteSuffix')->willReturn('entity');
        $this->gentor->method('getCamelcaseCapitalName')->willReturn('DeepNestedEntity');
        $this->gentor->method('getSmallSingularName')->willReturn('entity');
        $this->gentor->method('getSmallPluralName')->willReturn('entities');
        $this->gentor->method('getNamespacePath')->willReturn('App\\Deep\\Nested\\Entity');
        $this->gentor->method('getControllerLayoutDir')->willReturn('deep/nested/entity');
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn('deep.nested.entity');
        $this->gentor->method('getPreEntityTable')->willReturn('deep_nested_');
        $this->gentor->method('isFlashInclude')->willReturn(true);
        $this->gentor->method('isCreatedInclude')->willReturn(true);
        $this->gentor->method('isModifiedInclude')->willReturn(true);
        $this->gentor->method('isUpdatedInclude')->willReturn(true);
        $this->gentor->method('isDeletedInclude')->willReturn(true);
        
        $form = new GeneratorForm($this->gentor);
        
        $this->assertSame('deep/nested/route', $form->getRoutePrefix());
        $this->assertSame('entity', $form->getRouteSuffix());
        $this->assertSame('DeepNestedEntity', $form->getCamelcaseCapitalName());
        $this->assertSame('entity', $form->getSmallSingularName());
        $this->assertSame('entities', $form->getSmallPluralName());
        $this->assertSame('App\\Deep\\Nested\\Entity', $form->getNamespacePath());
        $this->assertSame('deep/nested/entity', $form->getControllerLayoutDir());
        $this->assertSame('deep.nested.entity', $form->getControllerLayoutDirDotPath());
        $this->assertSame('deep_nested_', $form->getPreEntityTable());
    }

    private function setupMockGentor(): void
    {
        $this->gentor->method('getRoutePrefix')->willReturn('test_route_prefix');
        $this->gentor->method('getRouteSuffix')->willReturn('test_route_suffix');
        $this->gentor->method('getCamelcaseCapitalName')->willReturn('TestCamelCase');
        $this->gentor->method('getSmallSingularName')->willReturn('test_singular');
        $this->gentor->method('getSmallPluralName')->willReturn('test_plural');
        $this->gentor->method('getNamespacePath')->willReturn('Test\\Namespace');
        $this->gentor->method('getControllerLayoutDir')->willReturn('test/layout/dir');
        $this->gentor->method('getControllerLayoutDirDotPath')->willReturn('test.layout.dir');
        $this->gentor->method('getPreEntityTable')->willReturn('test_table');
        $this->gentor->method('isFlashInclude')->willReturn(true);
        $this->gentor->method('isCreatedInclude')->willReturn(true);
        $this->gentor->method('isModifiedInclude')->willReturn(true);
        $this->gentor->method('isUpdatedInclude')->willReturn(true);
        $this->gentor->method('isDeletedInclude')->willReturn(true);
    }
}
