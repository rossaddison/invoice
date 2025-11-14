<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\Unit;
use Codeception\Test\Unit as BaseUnit;

class UnitEntityTest extends BaseUnit
{
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unit = new Unit();
    }

    public function testConstructorWithDefaults(): void
    {
        $defaultUnit = new Unit();
        
        $this->assertNull($defaultUnit->getUnit_id());
        $this->assertEquals('', $defaultUnit->getUnit_name());
        $this->assertEquals('', $defaultUnit->getUnit_name_plrl());
    }

    public function testConstructorWithAllParameters(): void
    {
        $unit = new Unit(
            id: 42,
            unit_name: 'Piece',
            unit_name_plrl: 'Pieces'
        );

        $this->assertEquals(42, $unit->getUnit_id());
        $this->assertEquals('Piece', $unit->getUnit_name());
        $this->assertEquals('Pieces', $unit->getUnit_name_plrl());
    }

    public function testUnitIdSetterAndGetter(): void
    {
        $this->assertNull($this->unit->getUnit_id());
        
        $this->unit->setUnit_id(1);
        $this->assertEquals(1, $this->unit->getUnit_id());
        
        $this->unit->setUnit_id(999);
        $this->assertEquals(999, $this->unit->getUnit_id());
        
        $this->unit->setUnit_id(0);
        $this->assertEquals(0, $this->unit->getUnit_id());
    }

    public function testUnitNameSetterAndGetter(): void
    {
        $this->assertEquals('', $this->unit->getUnit_name());
        
        $this->unit->setUnit_name('Hour');
        $this->assertEquals('Hour', $this->unit->getUnit_name());
        
        $this->unit->setUnit_name('Meter');
        $this->assertEquals('Meter', $this->unit->getUnit_name());
        
        $this->unit->setUnit_name('');
        $this->assertEquals('', $this->unit->getUnit_name());
    }

    public function testUnitNamePluralSetterAndGetter(): void
    {
        $this->assertEquals('', $this->unit->getUnit_name_plrl());
        
        $this->unit->setUnit_name_plrl('Hours');
        $this->assertEquals('Hours', $this->unit->getUnit_name_plrl());
        
        $this->unit->setUnit_name_plrl('Meters');
        $this->assertEquals('Meters', $this->unit->getUnit_name_plrl());
        
        $this->unit->setUnit_name_plrl('');
        $this->assertEquals('', $this->unit->getUnit_name_plrl());
    }

    public function testCommonUnitTypes(): void
    {
        $commonUnits = [
            ['Hour', 'Hours'],
            ['Day', 'Days'],
            ['Piece', 'Pieces'],
            ['Meter', 'Meters'],
            ['Kilogram', 'Kilograms'],
            ['Liter', 'Liters'],
            ['Box', 'Boxes'],
            ['Item', 'Items']
        ];

        foreach ($commonUnits as $index => [$singular, $plural]) {
            $unit = new Unit(
                id: $index + 1,
                unit_name: $singular,
                unit_name_plrl: $plural
            );

            $this->assertEquals($index + 1, $unit->getUnit_id());
            $this->assertEquals($singular, $unit->getUnit_name());
            $this->assertEquals($plural, $unit->getUnit_name_plrl());
        }
    }

    public function testLongUnitNames(): void
    {
        // Test with names at max length (50 characters)
        $longName = str_repeat('A', 50);
        $longPlural = str_repeat('B', 50);
        
        $this->unit->setUnit_name($longName);
        $this->unit->setUnit_name_plrl($longPlural);
        
        $this->assertEquals($longName, $this->unit->getUnit_name());
        $this->assertEquals($longPlural, $this->unit->getUnit_name_plrl());
        $this->assertEquals(50, strlen($this->unit->getUnit_name()));
        $this->assertEquals(50, strlen($this->unit->getUnit_name_plrl()));
    }

    public function testSpecialCharactersInNames(): void
    {
        // Test with special characters and symbols
        $specialUnits = [
            ['M²', 'M²'],
            ['M³', 'M³'],
            ['°C', '°C'],
            ['Ω', 'Ω'],
            ['µm', 'µm']
        ];

        foreach ($specialUnits as [$name, $plural]) {
            $this->unit->setUnit_name($name);
            $this->unit->setUnit_name_plrl($plural);
            
            $this->assertEquals($name, $this->unit->getUnit_name());
            $this->assertEquals($plural, $this->unit->getUnit_name_plrl());
        }
    }

    public function testSameNameSingularAndPlural(): void
    {
        // Some units have the same singular and plural forms
        $sameNameUnits = ['Sheep', 'Fish', 'Deer', 'Series', 'Species'];

        foreach ($sameNameUnits as $name) {
            $this->unit->setUnit_name($name);
            $this->unit->setUnit_name_plrl($name);
            
            $this->assertEquals($name, $this->unit->getUnit_name());
            $this->assertEquals($name, $this->unit->getUnit_name_plrl());
        }
    }

    public function testCompleteEntitySetup(): void
    {
        // Test setting up a complete unit entity
        $this->unit->setUnit_id(100);
        $this->unit->setUnit_name('Kilogram');
        $this->unit->setUnit_name_plrl('Kilograms');

        $this->assertEquals(100, $this->unit->getUnit_id());
        $this->assertEquals('Kilogram', $this->unit->getUnit_name());
        $this->assertEquals('Kilograms', $this->unit->getUnit_name_plrl());
    }

    public function testEntityWithNullId(): void
    {
        // Test entity behavior when ID is null (new entity)
        $newUnit = new Unit(
            id: null,
            unit_name: 'New Unit',
            unit_name_plrl: 'New Units'
        );

        $this->assertNull($newUnit->getUnit_id());
        $this->assertEquals('New Unit', $newUnit->getUnit_name());
        $this->assertEquals('New Units', $newUnit->getUnit_name_plrl());
    }

    public function testChainedSetterCalls(): void
    {
        // Test that setters work independently and can be chained
        $this->unit->setUnit_id(50);
        $this->unit->setUnit_name('Barrel');
        $this->unit->setUnit_name_plrl('Barrels');
        
        $this->assertEquals(50, $this->unit->getUnit_id());
        $this->assertEquals('Barrel', $this->unit->getUnit_name());
        $this->assertEquals('Barrels', $this->unit->getUnit_name_plrl());
    }

    public function testNumericAndAlphanumericNames(): void
    {
        // Test with numeric and alphanumeric unit names
        $this->unit->setUnit_name('Type1');
        $this->unit->setUnit_name_plrl('Type1s');
        
        $this->assertEquals('Type1', $this->unit->getUnit_name());
        $this->assertEquals('Type1s', $this->unit->getUnit_name_plrl());
        
        $this->unit->setUnit_name('Model A1');
        $this->unit->setUnit_name_plrl('Model A1s');
        
        $this->assertEquals('Model A1', $this->unit->getUnit_name());
        $this->assertEquals('Model A1s', $this->unit->getUnit_name_plrl());
    }

    public function testPublicIdProperty(): void
    {
        // Test the public id property directly
        $unit = new Unit();
        $this->assertNull($unit->id);
        
        $unit->id = 123;
        $this->assertEquals(123, $unit->id);
        $this->assertEquals(123, $unit->getUnit_id());
    }
}
