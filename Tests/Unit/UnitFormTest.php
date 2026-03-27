<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\Unit;
use App\Invoice\Unit\UnitForm;
use Codeception\Test\Unit as BaseUnit;

class UnitFormTest extends BaseUnit
{
    private Unit $unitEntity;
    private UnitForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a sample Unit entity
        $this->unitEntity = new Unit(
            id: 1,
            unit_name: 'Piece',
            unit_name_plrl: 'Pieces'
        );
        
        $this->form = new UnitForm($this->unitEntity);
    }

    public function testFormInitializationFromEntity(): void
    {
        $this->assertEquals(1, $this->form->getUnitId());
        $this->assertEquals('Piece', $this->form->getUnitName());
        $this->assertEquals('Pieces', $this->form->getUnitNamePlrl());
    }

    public function testGetFormName(): void
    {
        $this->assertEquals('', $this->form->getFormName());
    }

    public function testUnitWithEmptyValues(): void
    {
        $emptyUnit = new Unit(
            id: null,
            unit_name: '',
            unit_name_plrl: ''
        );
        $emptyForm = new UnitForm($emptyUnit);
        
        $this->assertNull($emptyForm->getUnitId());
        $this->assertEquals('', $emptyForm->getUnitName());
        $this->assertEquals('', $emptyForm->getUnitNamePlrl());
    }

    public function testUnitWithLongNames(): void
    {
        // Test with names at max length (50 characters)
        $longName = str_repeat('A', 50);
        $longPlural = str_repeat('B', 50);
        
        $longUnit = new Unit(
            id: 99,
            unit_name: $longName,
            unit_name_plrl: $longPlural
        );
        $longForm = new UnitForm($longUnit);
        
        $this->assertEquals(99, $longForm->getUnitId());
        $this->assertEquals($longName, $longForm->getUnitName());
        $this->assertEquals($longPlural, $longForm->getUnitNamePlrl());
        $this->assertEquals(50, strlen($longForm->getUnitName()));
        $this->assertEquals(50, strlen($longForm->getUnitNamePlrl()));
    }

    public function testCommonUnitTypes(): void
    {
        $commonUnits = [
            ['Hour', 'Hours'],
            ['Meter', 'Meters'],
            ['Kilogram', 'Kilograms'],
            ['Box', 'Boxes'],
            ['Item', 'Items']
        ];
        
        foreach ($commonUnits as $index => [$singular, $plural]) {
            $unit = new Unit(
                id: $index + 10,
                unit_name: $singular,
                unit_name_plrl: $plural
            );
            $form = new UnitForm($unit);
            
            $this->assertEquals($singular, $form->getUnitName());
            $this->assertEquals($plural, $form->getUnitNamePlrl());
            $this->assertEquals($index + 10, $form->getUnitId());
        }
    }

    public function testAllGetterMethods(): void
    {
        // Test that all getter methods return expected types
        $this->assertIsInt($this->form->getUnitId());
        $this->assertIsString($this->form->getUnitName());
        $this->assertIsString($this->form->getUnitNamePlrl());
        $this->assertIsString($this->form->getFormName());
    }

    public function testNullIdHandling(): void
    {
        $nullIdUnit = new Unit(
            id: null,
            unit_name: 'Test Unit',
            unit_name_plrl: 'Test Units'
        );
        $nullIdForm = new UnitForm($nullIdUnit);
        
        $this->assertNull($nullIdForm->getUnitId());
        $this->assertEquals('Test Unit', $nullIdForm->getUnitName());
        $this->assertEquals('Test Units', $nullIdForm->getUnitNamePlrl());
    }

    public function testSameNameSingularAndPlural(): void
    {
        // Some units might have the same singular and plural forms
        $sameNameUnit = new Unit(
            id: 42,
            unit_name: 'Sheep',
            unit_name_plrl: 'Sheep'
        );
        $sameNameForm = new UnitForm($sameNameUnit);
        
        $this->assertEquals('Sheep', $sameNameForm->getUnitName());
        $this->assertEquals('Sheep', $sameNameForm->getUnitNamePlrl());
        $this->assertEquals(42, $sameNameForm->getUnitId());
    }

    public function testSpecialCharactersInNames(): void
    {
        $specialUnit = new Unit(
            id: 100,
            unit_name: 'M²',
            unit_name_plrl: 'M²'
        );
        $specialForm = new UnitForm($specialUnit);
        
        $this->assertEquals('M²', $specialForm->getUnitName());
        $this->assertEquals('M²', $specialForm->getUnitNamePlrl());
        $this->assertEquals(100, $specialForm->getUnitId());
    }

    public function testFormNameIsConsistent(): void
    {
        // Test that getFormName always returns empty string
        $units = [
            new Unit(id: 1, unit_name: 'A', unit_name_plrl: 'As'),
            new Unit(id: 2, unit_name: 'B', unit_name_plrl: 'Bs'),
            new Unit(id: null, unit_name: '', unit_name_plrl: '')
        ];
        
        foreach ($units as $unit) {
            $form = new UnitForm($unit);
            $this->assertEquals('', $form->getFormName());
        }
    }
}
