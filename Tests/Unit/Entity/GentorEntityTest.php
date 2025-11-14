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
        
        $this->assertSame('', $gentor->getRoute_prefix());
        $this->assertSame('', $gentor->getRoute_suffix());
        $this->assertSame('', $gentor->getCamelcase_capital_name());
        $this->assertSame('', $gentor->getSmall_singular_name());
        $this->assertSame('', $gentor->getSmall_plural_name());
        $this->assertSame('', $gentor->getNamespace_path());
        $this->assertSame('dirname(dirname(__DIR__)', $gentor->getController_layout_dir());
        $this->assertSame('@invoice/layout/main.php', $gentor->getController_layout_dir_dot_path());
        $this->assertSame('', $gentor->getPre_entity_table());
        $this->assertFalse($gentor->isCreated_include());
        $this->assertFalse($gentor->isUpdated_include());
        $this->assertFalse($gentor->isModified_include());
        $this->assertFalse($gentor->isDeleted_include());
        $this->assertFalse($gentor->isFlash_include());
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
        
        $this->assertSame('test_prefix', $gentor->getRoute_prefix());
        $this->assertSame('test_suffix', $gentor->getRoute_suffix());
        $this->assertSame('TestCapital', $gentor->getCamelcase_capital_name());
        $this->assertSame('test_singular', $gentor->getSmall_singular_name());
        $this->assertSame('test_plural', $gentor->getSmall_plural_name());
        $this->assertSame('Test\\Namespace', $gentor->getNamespace_path());
        $this->assertSame('test/layout', $gentor->getController_layout_dir());
        $this->assertSame('test.layout.path', $gentor->getController_layout_dir_dot_path());
        $this->assertSame('test_table_', $gentor->getPre_entity_table());
        $this->assertTrue($gentor->isCreated_include());
        $this->assertTrue($gentor->isUpdated_include());
        $this->assertTrue($gentor->isModified_include());
        $this->assertTrue($gentor->isDeleted_include());
        $this->assertTrue($gentor->isFlash_include());
    }

    public function testGentorIdGetter(): void
    {
        $gentor = new Gentor();
        
        // ID should be null initially, converted to empty string
        $this->assertSame('', $gentor->getGentor_id());
    }

    public function testRoutePrefixSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRoute_prefix('invoice');
        $this->assertSame('invoice', $gentor->getRoute_prefix());
        
        $gentor->setRoute_prefix('quote');
        $this->assertSame('quote', $gentor->getRoute_prefix());
    }

    public function testRouteSuffixSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRoute_suffix('list');
        $this->assertSame('list', $gentor->getRoute_suffix());
        
        $gentor->setRoute_suffix('view');
        $this->assertSame('view', $gentor->getRoute_suffix());
    }

    public function testCamelcaseCapitalNameSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setCamelcase_capital_name('InvoiceEntity');
        $this->assertSame('InvoiceEntity', $gentor->getCamelcase_capital_name());
        
        $gentor->setCamelcase_capital_name('QuoteEntity');
        $this->assertSame('QuoteEntity', $gentor->getCamelcase_capital_name());
    }

    public function testSmallSingularNameSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setSmall_singular_name('invoice');
        $this->assertSame('invoice', $gentor->getSmall_singular_name());
        
        $gentor->setSmall_singular_name('quote');
        $this->assertSame('quote', $gentor->getSmall_singular_name());
    }

    public function testSmallPluralNameSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setSmall_plural_name('invoices');
        $this->assertSame('invoices', $gentor->getSmall_plural_name());
        
        $gentor->setSmall_plural_name('quotes');
        $this->assertSame('quotes', $gentor->getSmall_plural_name());
    }

    public function testNamespacePathSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setNamespace_path('App\\Invoice\\Entity');
        $this->assertSame('App\\Invoice\\Entity', $gentor->getNamespace_path());
        
        $gentor->setNamespace_path('App\\Quote\\Entity');
        $this->assertSame('App\\Quote\\Entity', $gentor->getNamespace_path());
    }

    public function testControllerLayoutDirSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setController_layout_dir($this->invoiceViews);
        $this->assertSame($this->invoiceViews, $gentor->getController_layout_dir());
        
        $gentor->setController_layout_dir('quote/views');
        $this->assertSame('quote/views', $gentor->getController_layout_dir());
    }

    public function testControllerLayoutDirDotPathSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setController_layout_dir_dot_path('invoice.layout.main');
        $this->assertSame('invoice.layout.main', $gentor->getController_layout_dir_dot_path());
        
        $gentor->setController_layout_dir_dot_path('quote.layout.main');
        $this->assertSame('quote.layout.main', $gentor->getController_layout_dir_dot_path());
    }

    public function testPreEntityTableSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setPre_entity_table('inv_');
        $this->assertSame('inv_', $gentor->getPre_entity_table());
        
        $gentor->setPre_entity_table('quote_');
        $this->assertSame('quote_', $gentor->getPre_entity_table());
    }

    public function testCreatedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setCreated_include(true);
        $this->assertTrue($gentor->isCreated_include());
        
        $gentor->setCreated_include(false);
        $this->assertFalse($gentor->isCreated_include());
    }

    public function testUpdatedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setUpdated_include(true);
        $this->assertTrue($gentor->isUpdated_include());
        
        $gentor->setUpdated_include(false);
        $this->assertFalse($gentor->isUpdated_include());
    }

    public function testModifiedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setModified_include(true);
        $this->assertTrue($gentor->isModified_include());
        
        $gentor->setModified_include(false);
        $this->assertFalse($gentor->isModified_include());
    }

    public function testDeletedIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setDeleted_include(true);
        $this->assertTrue($gentor->isDeleted_include());
        
        $gentor->setDeleted_include(false);
        $this->assertFalse($gentor->isDeleted_include());
    }

    public function testFlashIncludeSetterAndGetter(): void
    {
        $gentor = new Gentor();
        
        $gentor->setFlash_include(true);
        $this->assertTrue($gentor->isFlash_include());
        
        $gentor->setFlash_include(false);
        $this->assertFalse($gentor->isFlash_include());
    }

    public function testMultipleSetterCalls(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRoute_prefix('test');
        $gentor->setRoute_suffix('entity');
        
        $this->assertSame('test', $gentor->getRoute_prefix());
        $this->assertSame('entity', $gentor->getRoute_suffix());
    }

    public function testAllBooleanProperties(): void
    {
        $gentor = new Gentor();
        
        // Test all combinations of boolean values
        $gentor->setCreated_include(true);
        $gentor->setUpdated_include(false);
        $gentor->setModified_include(true);
        $gentor->setDeleted_include(false);
        $gentor->setFlash_include(true);
        
        $this->assertTrue($gentor->isCreated_include());
        $this->assertFalse($gentor->isUpdated_include());
        $this->assertTrue($gentor->isModified_include());
        $this->assertFalse($gentor->isDeleted_include());
        $this->assertTrue($gentor->isFlash_include());
    }

    public function testEmptyStringValues(): void
    {
        $gentor = new Gentor();
        
        $gentor->setRoute_prefix('');
        $gentor->setRoute_suffix('');
        $gentor->setCamelcase_capital_name('');
        $gentor->setSmall_singular_name('');
        $gentor->setSmall_plural_name('');
        $gentor->setNamespace_path('');
        $gentor->setController_layout_dir('');
        $gentor->setController_layout_dir_dot_path('');
        $gentor->setPre_entity_table('');
        
        $this->assertSame('', $gentor->getRoute_prefix());
        $this->assertSame('', $gentor->getRoute_suffix());
        $this->assertSame('', $gentor->getCamelcase_capital_name());
        $this->assertSame('', $gentor->getSmall_singular_name());
        $this->assertSame('', $gentor->getSmall_plural_name());
        $this->assertSame('', $gentor->getNamespace_path());
        $this->assertSame('', $gentor->getController_layout_dir());
        $this->assertSame('', $gentor->getController_layout_dir_dot_path());
        $this->assertSame('', $gentor->getPre_entity_table());
    }

    public function testLongStringValues(): void
    {
        $gentor = new Gentor();
        $longString = str_repeat('long', 25); // 100 characters
        
        $gentor->setNamespace_path($longString);
        $gentor->setController_layout_dir($longString);
        $gentor->setController_layout_dir_dot_path($longString);
        
        $this->assertSame($longString, $gentor->getNamespace_path());
        $this->assertSame($longString, $gentor->getController_layout_dir());
        $this->assertSame($longString, $gentor->getController_layout_dir_dot_path());
    }

    public function testSpecialCharactersInStrings(): void
    {
        $gentor = new Gentor();
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?`~"\'\\';
        
        $gentor->setRoute_prefix($specialChars);
        $gentor->setRoute_suffix($specialChars);
        $gentor->setCamelcase_capital_name($specialChars);
        
        $this->assertSame($specialChars, $gentor->getRoute_prefix());
        $this->assertSame($specialChars, $gentor->getRoute_suffix());
        $this->assertSame($specialChars, $gentor->getCamelcase_capital_name());
    }

    public function testUnicodeCharactersInStrings(): void
    {
        $gentor = new Gentor();
        $unicode = 'Hello 世界! 🌍 Héllö Wørld™€₹中文';
        
        $gentor->setSmall_singular_name($unicode);
        $gentor->setSmall_plural_name($unicode);
        $gentor->setNamespace_path($unicode);
        
        $this->assertSame($unicode, $gentor->getSmall_singular_name());
        $this->assertSame($unicode, $gentor->getSmall_plural_name());
        $this->assertSame($unicode, $gentor->getNamespace_path());
    }

    public function testCompleteEntitySetup(): void
    {
        $gentor = new Gentor();
        
        // Setup a complete invoice generator configuration
        $gentor->setRoute_prefix('invoice');
        $gentor->setRoute_suffix('invoice');
        $gentor->setCamelcase_capital_name('Invoice');
        $gentor->setSmall_singular_name('invoice');
        $gentor->setSmall_plural_name('invoices');
        $gentor->setNamespace_path('App\\Invoice');
        $gentor->setController_layout_dir($this->invoiceViews);
        $gentor->setController_layout_dir_dot_path('invoice.layout.main');
        $gentor->setPre_entity_table('inv_');
        $gentor->setCreated_include(true);
        $gentor->setUpdated_include(true);
        $gentor->setModified_include(true);
        $gentor->setDeleted_include(true);
        $gentor->setFlash_include(true);
        
        $this->assertSame('invoice', $gentor->getRoute_prefix());
        $this->assertSame('invoice', $gentor->getRoute_suffix());
        $this->assertSame('Invoice', $gentor->getCamelcase_capital_name());
        $this->assertSame('invoice', $gentor->getSmall_singular_name());
        $this->assertSame('invoices', $gentor->getSmall_plural_name());
        $this->assertSame('App\\Invoice', $gentor->getNamespace_path());
        $this->assertSame($this->invoiceViews, $gentor->getController_layout_dir());
        $this->assertSame('invoice.layout.main', $gentor->getController_layout_dir_dot_path());
        $this->assertSame('inv_', $gentor->getPre_entity_table());
        $this->assertTrue($gentor->isCreated_include());
        $this->assertTrue($gentor->isUpdated_include());
        $this->assertTrue($gentor->isModified_include());
        $this->assertTrue($gentor->isDeleted_include());
        $this->assertTrue($gentor->isFlash_include());
    }

    public function testGetterMethodsConsistency(): void
    {
        $gentor = new Gentor('test', 'entity', 'TestEntity', 'test', 'tests', 'Test\\Entity', 'test/views', 'test.views', 'test_', true, false, true, false, true);
        
        // Multiple calls should return same values
        $this->assertSame($gentor->getRoute_prefix(), $gentor->getRoute_prefix());
        $this->assertSame($gentor->getRoute_suffix(), $gentor->getRoute_suffix());
        $this->assertSame($gentor->getCamelcase_capital_name(), $gentor->getCamelcase_capital_name());
        $this->assertSame($gentor->getSmall_singular_name(), $gentor->getSmall_singular_name());
        $this->assertSame($gentor->getSmall_plural_name(), $gentor->getSmall_plural_name());
        $this->assertSame($gentor->getNamespace_path(), $gentor->getNamespace_path());
        $this->assertSame($gentor->getController_layout_dir(), $gentor->getController_layout_dir());
        $this->assertSame($gentor->getController_layout_dir_dot_path(), $gentor->getController_layout_dir_dot_path());
        $this->assertSame($gentor->getPre_entity_table(), $gentor->getPre_entity_table());
        $this->assertSame($gentor->isCreated_include(), $gentor->isCreated_include());
        $this->assertSame($gentor->isUpdated_include(), $gentor->isUpdated_include());
        $this->assertSame($gentor->isModified_include(), $gentor->isModified_include());
        $this->assertSame($gentor->isDeleted_include(), $gentor->isDeleted_include());
        $this->assertSame($gentor->isFlash_include(), $gentor->isFlash_include());
    }

    public function testDefaultLayoutValues(): void
    {
        $gentor = new Gentor();
        
        // Test default constructor values for layout properties
        $this->assertSame('dirname(dirname(__DIR__)', $gentor->getController_layout_dir());
        $this->assertSame('@invoice/layout/main.php', $gentor->getController_layout_dir_dot_path());
    }

    public function testBooleanToggling(): void
    {
        $gentor = new Gentor();
        
        // Test toggling each boolean property
        $this->assertFalse($gentor->isCreated_include());
        $gentor->setCreated_include(!$gentor->isCreated_include());
        $this->assertTrue($gentor->isCreated_include());
        
        $this->assertFalse($gentor->isUpdated_include());
        $gentor->setUpdated_include(!$gentor->isUpdated_include());
        $this->assertTrue($gentor->isUpdated_include());
        
        $this->assertFalse($gentor->isModified_include());
        $gentor->setModified_include(!$gentor->isModified_include());
        $this->assertTrue($gentor->isModified_include());
        
        $this->assertFalse($gentor->isDeleted_include());
        $gentor->setDeleted_include(!$gentor->isDeleted_include());
        $this->assertTrue($gentor->isDeleted_include());
        
        $this->assertFalse($gentor->isFlash_include());
        $gentor->setFlash_include(!$gentor->isFlash_include());
        $this->assertTrue($gentor->isFlash_include());
    }
}
