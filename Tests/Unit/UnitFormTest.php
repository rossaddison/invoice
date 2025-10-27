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
        $this->assertEquals(1, $this->form->getUnit_id());
        $this->assertEquals('Piece', $this->form->getUnit_name());
        $this->assertEquals('Pieces', $this->form->getUnit_name_plrl());
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
        
        $this->assertNull($emptyForm->getUnit_id());
        $this->assertEquals('', $emptyForm->getUnit_name());
        $this->assertEquals('', $emptyForm->getUnit_name_plrl());
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
        
        $this->assertEquals(99, $longForm->getUnit_id());
        $this->assertEquals($longName, $longForm->getUnit_name());
        $this->assertEquals($longPlural, $longForm->getUnit_name_plrl());
        $this->assertEquals(50, strlen($longForm->getUnit_name()));
        $this->assertEquals(50, strlen($longForm->getUnit_name_plrl()));
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
            
            $this->assertEquals($singular, $form->getUnit_name());
            $this->assertEquals($plural, $form->getUnit_name_plrl());
            $this->assertEquals($index + 10, $form->getUnit_id());
        }
    }

    public function testAllGetterMethods(): void
    {
        // Test that all getter methods return expected types
        $this->assertIsInt($this->form->getUnit_id());
        $this->assertIsString($this->form->getUnit_name());
        $this->assertIsString($this->form->getUnit_name_plrl());
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
        
        $this->assertNull($nullIdForm->getUnit_id());
        $this->assertEquals('Test Unit', $nullIdForm->getUnit_name());
        $this->assertEquals('Test Units', $nullIdForm->getUnit_name_plrl());
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
        
        $this->assertEquals('Sheep', $sameNameForm->getUnit_name());
        $this->assertEquals('Sheep', $sameNameForm->getUnit_name_plrl());
        $this->assertEquals(42, $sameNameForm->getUnit_id());
    }

    public function testSpecialCharactersInNames(): void
    {
        $specialUnit = new Unit(
            id: 100,
            unit_name: 'M²',
            unit_name_plrl: 'M²'
        );
        $specialForm = new UnitForm($specialUnit);
        
        $this->assertEquals('M²', $specialForm->getUnit_name());
        $this->assertEquals('M²', $specialForm->getUnit_name_plrl());
        $this->assertEquals(100, $specialForm->getUnit_id());
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