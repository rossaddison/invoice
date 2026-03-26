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
        
        $this->assertNull($defaultUnit->getUnitId());
        $this->assertEquals('', $defaultUnit->getUnitName());
        $this->assertEquals('', $defaultUnit->getUnitNamePlrl());
    }

    public function testConstructorWithAllParameters(): void
    {
        $unit = new Unit(
            id: 42,
            unit_name: 'Piece',
            unit_name_plrl: 'Pieces'
        );

        $this->assertEquals(42, $unit->getUnitId());
        $this->assertEquals('Piece', $unit->getUnitName());
        $this->assertEquals('Pieces', $unit->getUnitNamePlrl());
    }

    public function testUnitIdSetterAndGetter(): void
    {
        $this->assertNull($this->unit->getUnitId());
        
        $this->unit->setUnitId(1);
        $this->assertEquals(1, $this->unit->getUnitId());
        
        $this->unit->setUnitId(999);
        $this->assertEquals(999, $this->unit->getUnitId());
        
        $this->unit->setUnitId(0);
        $this->assertEquals(0, $this->unit->getUnitId());
    }

    public function testUnitNameSetterAndGetter(): void
    {
        $this->assertEquals('', $this->unit->getUnitName());
        
        $this->unit->setUnitName('Hour');
        $this->assertEquals('Hour', $this->unit->getUnitName());
        
        $this->unit->setUnitName('Meter');
        $this->assertEquals('Meter', $this->unit->getUnitName());
        
        $this->unit->setUnitName('');
        $this->assertEquals('', $this->unit->getUnitName());
    }

    public function testUnitNamePluralSetterAndGetter(): void
    {
        $this->assertEquals('', $this->unit->getUnitNamePlrl());
        
        $this->unit->setUnitNamePlrl('Hours');
        $this->assertEquals('Hours', $this->unit->getUnitNamePlrl());
        
        $this->unit->setUnitNamePlrl('Meters');
        $this->assertEquals('Meters', $this->unit->getUnitNamePlrl());
        
        $this->unit->setUnitNamePlrl('');
        $this->assertEquals('', $this->unit->getUnitNamePlrl());
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

            $this->assertEquals($index + 1, $unit->getUnitId());
            $this->assertEquals($singular, $unit->getUnitName());
            $this->assertEquals($plural, $unit->getUnitNamePlrl());
        }
    }

    public function testLongUnitNames(): void
    {
        // Test with names at max length (50 characters)
        $longName = str_repeat('A', 50);
        $longPlural = str_repeat('B', 50);
        
        $this->unit->setUnitName($longName);
        $this->unit->setUnitNamePlrl($longPlural);
        
        $this->assertEquals($longName, $this->unit->getUnitName());
        $this->assertEquals($longPlural, $this->unit->getUnitNamePlrl());
        $this->assertEquals(50, strlen($this->unit->getUnitName()));
        $this->assertEquals(50, strlen($this->unit->getUnitNamePlrl()));
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
            $this->unit->setUnitName($name);
            $this->unit->setUnitNamePlrl($plural);
            
            $this->assertEquals($name, $this->unit->getUnitName());
            $this->assertEquals($plural, $this->unit->getUnitNamePlrl());
        }
    }

    public function testSameNameSingularAndPlural(): void
    {
        // Some units have the same singular and plural forms
        $sameNameUnits = ['Sheep', 'Fish', 'Deer', 'Series', 'Species'];

        foreach ($sameNameUnits as $name) {
            $this->unit->setUnitName($name);
            $this->unit->setUnitNamePlrl($name);
            
            $this->assertEquals($name, $this->unit->getUnitName());
            $this->assertEquals($name, $this->unit->getUnitNamePlrl());
        }
    }

    public function testCompleteEntitySetup(): void
    {
        // Test setting up a complete unit entity
        $this->unit->setUnitId(100);
        $this->unit->setUnitName('Kilogram');
        $this->unit->setUnitNamePlrl('Kilograms');

        $this->assertEquals(100, $this->unit->getUnitId());
        $this->assertEquals('Kilogram', $this->unit->getUnitName());
        $this->assertEquals('Kilograms', $this->unit->getUnitNamePlrl());
    }

    public function testEntityWithNullId(): void
    {
        // Test entity behavior when ID is null (new entity)
        $newUnit = new Unit(
            id: null,
            unit_name: 'New Unit',
            unit_name_plrl: 'New Units'
        );

        $this->assertNull($newUnit->getUnitId());
        $this->assertEquals('New Unit', $newUnit->getUnitName());
        $this->assertEquals('New Units', $newUnit->getUnitNamePlrl());
    }

    public function testChainedSetterCalls(): void
    {
        // Test that setters work independently and can be chained
        $this->unit->setUnitId(50);
        $this->unit->setUnitName('Barrel');
        $this->unit->setUnitNamePlrl('Barrels');
        
        $this->assertEquals(50, $this->unit->getUnitId());
        $this->assertEquals('Barrel', $this->unit->getUnitName());
        $this->assertEquals('Barrels', $this->unit->getUnitNamePlrl());
    }

    public function testNumericAndAlphanumericNames(): void
    {
        // Test with numeric and alphanumeric unit names
        $this->unit->setUnitName('Type1');
        $this->unit->setUnitNamePlrl('Type1s');
        
        $this->assertEquals('Type1', $this->unit->getUnitName());
        $this->assertEquals('Type1s', $this->unit->getUnitNamePlrl());
        
        $this->unit->setUnitName('Model A1');
        $this->unit->setUnitNamePlrl('Model A1s');
        
        $this->assertEquals('Model A1', $this->unit->getUnitName());
        $this->assertEquals('Model A1s', $this->unit->getUnitNamePlrl());
    }

    public function testPublicIdProperty(): void
    {
        // Test the public id property directly
        $unit = new Unit();
        $this->assertNull($unit->id);
        
        $unit->id = 123;
        $this->assertEquals(123, $unit->id);
        $this->assertEquals(123, $unit->getUnitId());
    }
}
