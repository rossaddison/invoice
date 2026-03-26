<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Invoice\Entity\Gentor;
use Codeception\Test\Unit;

class GentorEntityTest extends Unit
{
    public string $invoiceViews = 'invoice/views';
    
    public function testConstructorWithDefaults(): void
    {
        $gentor = new Gentor();
        
        $this->assertSame('', $gentor->getRoutePrefix());
        $this->assertSame('', $gentor->getRouteSuffix());
        $this->assertSame('', $gentor->getCamelcaseCapitalName());
        $this->assertSame('', $gentor->getSmallSingularName());
        $this->assertSame('', $gentor->getSmallPluralName());
        $this->assertSame('', $gentor->getNamespacePath());
        $this->assertSame('dirname(dirname(__DIR__)', $gentor->getControllerLayoutDir());
        $this->assertSame('@invoice/layout/main.php', $gentor->getControllerLayoutDirDotPath());
        $this->assertSame('', $gentor->getPreEntityTable());
        $this->assertFalse($gentor->isCreatedInclude());
        $this->assertFalse($gentor->isUpdatedInclude());
        $this->assertFalse($gentor->isModifiedInclude());
        $this->assertFalse($gentor->isDeletedInclude());
        $this->assertFalse($gentor->isFlashInclude());
    }

    public function testConstructorWithAllParameters(): void
    {
        $gentor = new Gentor(
            'test_prefix',
            'test_suffix',
            'TestCapital',
            'test_singular',
            'test_plural',
            'Test\\Namespace',
            'test/layout',
            'test.layout.path',
            'test_table_',
            true,
            true,
            true,
            true,
            true
        );
        
        $this->assertSame('test_prefix', $gentor->getRoutePrefix());
        $this->assertSame('test_suffix', $gentor->getRouteSuffix());
        $this->assertSame('TestCapital', $gentor->getCamelcaseCapitalName());
        $this->assertSame('test_singular', $gentor->getSmallSingularName());
        $this->assertSame('test_plural', $gentor->getSmallPluralName());
        $this->assertSame('Test\\Namespace', $gentor->getNamespacePath());
        $this->assertSame('test/layout', $gentor->getControllerLayoutDir());
        $this->assertSame('test.layout.path', $gentor->getControllerLayoutDirDotPath());
        $this->assertSame('test_table_', $gentor->getPreEntityTable());
        $this->assertTrue($gentor->isCreatedInclude());
        $this->assertTrue($gentor->isUpdatedInclude());
        $this->assertTrue($gentor->isModifiedInclude());
        $this->assertTrue($gentor->isDeletedInclude());
        $this->assertTrue($gentor->isFlashInclude());
    }

    public function testGentorIdGetter(): void
    {
        $gentor = new Gentor();
        
        // ID should be null initially, converted to empty string
        $this->assertSame('', $gentor->getGentorId());
    }

    public function testRoutePrefixSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRoutePrefix('invoice');
        $this->assertSame('invoice', $gentor->getRoutePrefix());
        
        $gentor->setRoutePrefix('quote');
        $this->assertSame('quote', $gentor->getRoutePrefix());
    }

    public function testRouteSuffixSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRouteSuffix('list');
        $this->assertSame('list', $gentor->getRouteSuffix());
        
        $gentor->setRouteSuffix('view');
        $this->assertSame('view', $gentor->getRouteSuffix());
    }

    public function testCamelcaseCapitalNameSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setCamelcaseCapitalName('InvoiceEntity');
        $this->assertSame('InvoiceEntity', $gentor->getCamelcaseCapitalName());
        
        $gentor->setCamelcaseCapitalName('QuoteEntity');
        $this->assertSame('QuoteEntity', $gentor->getCamelcaseCapitalName());
    }

    public function testSmallSingularNameSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setSmallSingularName('invoice');
        $this->assertSame('invoice', $gentor->getSmallSingularName());
        
        $gentor->setSmallSingularName('quote');
        $this->assertSame('quote', $gentor->getSmallSingularName());
    }

    public function testSmallPluralNameSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setSmallPluralName('invoices');
        $this->assertSame('invoices', $gentor->getSmallPluralName());
        
        $gentor->setSmallPluralName('quotes');
        $this->assertSame('quotes', $gentor->getSmallPluralName());
    }

    public function testNamespacePathSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setNamespacePath('App\\Invoice\\Entity');
        $this->assertSame('App\\Invoice\\Entity', $gentor->getNamespacePath());
        
        $gentor->setNamespacePath('App\\Quote\\Entity');
        $this->assertSame('App\\Quote\\Entity', $gentor->getNamespacePath());
    }

    public function testControllerLayoutDirSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setControllerLayoutDir($this->invoiceViews);
        $this->assertSame($this->invoiceViews, $gentor->getControllerLayoutDir());
        
        $gentor->setControllerLayoutDir('quote/views');
        $this->assertSame('quote/views', $gentor->getControllerLayoutDir());
    }

    public function testControllerLayoutDirDotPathSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setControllerLayoutDirDotPath('invoice.layout.main');
        $this->assertSame('invoice.layout.main', $gentor->getControllerLayoutDirDotPath());
        
        $gentor->setControllerLayoutDirDotPath('quote.layout.main');
        $this->assertSame('quote.layout.main', $gentor->getControllerLayoutDirDotPath());
    }

    public function testPreEntityTableSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setPreEntityTable('inv_');
        $this->assertSame('inv_', $gentor->getPreEntityTable());
        
        $gentor->setPreEntityTable('quote_');
        $this->assertSame('quote_', $gentor->getPreEntityTable());
    }

    public function testCreatedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setCreatedInclude(true);
        $this->assertTrue($gentor->isCreatedInclude());
        
        $gentor->setCreatedInclude(false);
        $this->assertFalse($gentor->isCreatedInclude());
    }

    public function testUpdatedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setUpdatedInclude(true);
        $this->assertTrue($gentor->isUpdatedInclude());
        
        $gentor->setUpdatedInclude(false);
        $this->assertFalse($gentor->isUpdatedInclude());
    }

    public function testModifiedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setModifiedInclude(true);
        $this->assertTrue($gentor->isModifiedInclude());
        
        $gentor->setModifiedInclude(false);
        $this->assertFalse($gentor->isModifiedInclude());
    }

    public function testDeletedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setDeletedInclude(true);
        $this->assertTrue($gentor->isDeletedInclude());
        
        $gentor->setDeletedInclude(false);
        $this->assertFalse($gentor->isDeletedInclude());
    }

    public function testFlashIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setFlashInclude(true);
        $this->assertTrue($gentor->isFlashInclude());
        
        $gentor->setFlashInclude(false);
        $this->assertFalse($gentor->isFlashInclude());
    }

    public function testMultipleSetterCalls(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRoutePrefix('test');
        $gentor->setRouteSuffix('entity');
        
        $this->assertSame('test', $gentor->getRoutePrefix());
        $this->assertSame('entity', $gentor->getRouteSuffix());
    }

    public function testAllBooleanProperties(): void
    {
        $gentor = new Gentor();
        
        // Test all combinations of boolean values
        $gentor->setCreatedInclude(true);
        $gentor->setUpdatedInclude(false);
        $gentor->setModifiedInclude(true);
        $gentor->setDeletedInclude(false);
        $gentor->setFlashInclude(true);
        
        $this->assertTrue($gentor->isCreatedInclude());
        $this->assertFalse($gentor->isUpdatedInclude());
        $this->assertTrue($gentor->isModifiedInclude());
        $this->assertFalse($gentor->isDeletedInclude());
        $this->assertTrue($gentor->isFlashInclude());
    }

    public function testEmptyStringValues(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRoutePrefix('');
        $gentor->setRouteSuffix('');
        $gentor->setCamelcaseCapitalName('');
        $gentor->setSmallSingularName('');
        $gentor->setSmallPluralName('');
        $gentor->setNamespacePath('');
        $gentor->setControllerLayoutDir('');
        $gentor->setControllerLayoutDirDotPath('');
        $gentor->setPreEntityTable('');
        
        $this->assertSame('', $gentor->getRoutePrefix());
        $this->assertSame('', $gentor->getRouteSuffix());
        $this->assertSame('', $gentor->getCamelcaseCapitalName());
        $this->assertSame('', $gentor->getSmallSingularName());
        $this->assertSame('', $gentor->getSmallPluralName());
        $this->assertSame('', $gentor->getNamespacePath());
        $this->assertSame('', $gentor->getControllerLayoutDir());
        $this->assertSame('', $gentor->getControllerLayoutDirDotPath());
        $this->assertSame('', $gentor->getPreEntityTable());
    }

    public function testLongStringValues(): void
    {
        $gentor = new Gentor();
        $longString = str_repeat('long', 25); // 100 characters
        
        $gentor->setNamespacePath($longString);
        $gentor->setControllerLayoutDir($longString);
        $gentor->setControllerLayoutDirDotPath($longString);
        
        $this->assertSame($longString, $gentor->getNamespacePath());
        $this->assertSame($longString, $gentor->getControllerLayoutDir());
        $this->assertSame($longString, $gentor->getControllerLayoutDirDotPath());
    }

    public function testSpecialCharactersInStrings(): void
    {
        $gentor = new Gentor();
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?`~"\'\\';
        
        $gentor->setRoutePrefix($specialChars);
        $gentor->setRouteSuffix($specialChars);
        $gentor->setCamelcaseCapitalName($specialChars);
        
        $this->assertSame($specialChars, $gentor->getRoutePrefix());
        $this->assertSame($specialChars, $gentor->getRouteSuffix());
        $this->assertSame($specialChars, $gentor->getCamelcaseCapitalName());
    }

    public function testUnicodeCharactersInStrings(): void
    {
        $gentor = new Gentor();
        $unicode = 'Hello 世界! 🌍 Héllö Wørld™€₹中文';
        
        $gentor->setSmallSingularName($unicode);
        $gentor->setSmallPluralName($unicode);
        $gentor->setNamespacePath($unicode);
        
        $this->assertSame($unicode, $gentor->getSmallSingularName());
        $this->assertSame($unicode, $gentor->getSmallPluralName());
        $this->assertSame($unicode, $gentor->getNamespacePath());
    }

    public function testCompleteEntitySetup(): void
    {
        $gentor = new Gentor();
        
        // Setup a complete invoice generator configuration
        $gentor->setRoutePrefix('invoice');
        $gentor->setRouteSuffix('invoice');
        $gentor->setCamelcaseCapitalName('Invoice');
        $gentor->setSmallSingularName('invoice');
        $gentor->setSmallPluralName('invoices');
        $gentor->setNamespacePath('App\\Invoice');
        $gentor->setControllerLayoutDir($this->invoiceViews);
        $gentor->setControllerLayoutDirDotPath('invoice.layout.main');
        $gentor->setPreEntityTable('inv_');
        $gentor->setCreatedInclude(true);
        $gentor->setUpdatedInclude(true);
        $gentor->setModifiedInclude(true);
        $gentor->setDeletedInclude(true);
        $gentor->setFlashInclude(true);
        
        $this->assertSame('invoice', $gentor->getRoutePrefix());
        $this->assertSame('invoice', $gentor->getRouteSuffix());
        $this->assertSame('Invoice', $gentor->getCamelcaseCapitalName());
        $this->assertSame('invoice', $gentor->getSmallSingularName());
        $this->assertSame('invoices', $gentor->getSmallPluralName());
        $this->assertSame('App\\Invoice', $gentor->getNamespacePath());
        $this->assertSame($this->invoiceViews, $gentor->getControllerLayoutDir());
        $this->assertSame('invoice.layout.main', $gentor->getControllerLayoutDirDotPath());
        $this->assertSame('inv_', $gentor->getPreEntityTable());
        $this->assertTrue($gentor->isCreatedInclude());
        $this->assertTrue($gentor->isUpdatedInclude());
        $this->assertTrue($gentor->isModifiedInclude());
        $this->assertTrue($gentor->isDeletedInclude());
        $this->assertTrue($gentor->isFlashInclude());
    }

    public function testGetterMethodsConsistency(): void
    {
        $gentor = new Gentor('test', 'entity', 'TestEntity', 'test', 'tests', 'Test\\Entity', 'test/views', 'test.views', 'test_', true, false, true, false, true);
        
        // Multiple calls should return same values
        $this->assertSame($gentor->getRoutePrefix(), $gentor->getRoutePrefix());
        $this->assertSame($gentor->getRouteSuffix(), $gentor->getRouteSuffix());
        $this->assertSame($gentor->getCamelcaseCapitalName(), $gentor->getCamelcaseCapitalName());
        $this->assertSame($gentor->getSmallSingularName(), $gentor->getSmallSingularName());
        $this->assertSame($gentor->getSmallPluralName(), $gentor->getSmallPluralName());
        $this->assertSame($gentor->getNamespacePath(), $gentor->getNamespacePath());
        $this->assertSame($gentor->getControllerLayoutDir(), $gentor->getControllerLayoutDir());
        $this->assertSame($gentor->getControllerLayoutDirDotPath(), $gentor->getControllerLayoutDirDotPath());
        $this->assertSame($gentor->getPreEntityTable(), $gentor->getPreEntityTable());
        $this->assertSame($gentor->isCreatedInclude(), $gentor->isCreatedInclude());
        $this->assertSame($gentor->isUpdatedInclude(), $gentor->isUpdatedInclude());
        $this->assertSame($gentor->isModifiedInclude(), $gentor->isModifiedInclude());
        $this->assertSame($gentor->isDeletedInclude(), $gentor->isDeletedInclude());
        $this->assertSame($gentor->isFlashInclude(), $gentor->isFlashInclude());
    }

    public function testDefaultLayoutValues(): void
    {
        $gentor = new Gentor();
        
        // Test default constructor values for layout properties
        $this->assertSame('dirname(dirname(__DIR__)', $gentor->getControllerLayoutDir());
        $this->assertSame('@invoice/layout/main.php', $gentor->getControllerLayoutDirDotPath());
    }

    public function testBooleanToggling(): void
    {
        $gentor = new Gentor();
        
        // Test toggling each boolean property
        $this->assertFalse($gentor->isCreatedInclude());
        $gentor->setCreatedInclude(!$gentor->isCreatedInclude());
        $this->assertTrue($gentor->isCreatedInclude());
        
        $this->assertFalse($gentor->isUpdatedInclude());
        $gentor->setUpdatedInclude(!$gentor->isUpdatedInclude());
        $this->assertTrue($gentor->isUpdatedInclude());
        
        $this->assertFalse($gentor->isModifiedInclude());
        $gentor->setModifiedInclude(!$gentor->isModifiedInclude());
        $this->assertTrue($gentor->isModifiedInclude());
        
        $this->assertFalse($gentor->isDeletedInclude());
        $gentor->setDeletedInclude(!$gentor->isDeletedInclude());
        $this->assertTrue($gentor->isDeletedInclude());
        
        $this->assertFalse($gentor->isFlashInclude());
        $gentor->setFlashInclude(!$gentor->isFlashInclude());
        $this->assertTrue($gentor->isFlashInclude());
    }
}
