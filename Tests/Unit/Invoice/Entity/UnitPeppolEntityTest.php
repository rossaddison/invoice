<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Unit;
use App\Invoice\Entity\UnitPeppol;
use PHPUnit\Framework\TestCase;

class UnitPeppolEntityTest extends TestCase
{
    public string $unitOfMass = 'Unit of mass';
    
    public string $unitOfTimePeriod = 'Unit of time period';
    
    public function testConstructorWithDefaults(): void
    {
        $unitPeppol = new UnitPeppol();
        
        $this->assertSame('', $unitPeppol->getId());
        $this->assertSame('', $unitPeppol->getUnit_id());
        $this->assertSame('', $unitPeppol->getCode());
        $this->assertSame('', $unitPeppol->getName());
        $this->assertSame('', $unitPeppol->getDescription());
        $this->assertNull($unitPeppol->getUnit());
    }

    public function testConstructorWithAllParameters(): void
    {
        $unitPeppol = new UnitPeppol(
            id: 1,
            unit_id: 100,
            code: 'KGM',
            name: 'Kilogram',
            description: 'Unit of mass equal to one thousand grams'
        );
        
        $this->assertSame('1', $unitPeppol->getId());
        $this->assertSame('100', $unitPeppol->getUnit_id());
        $this->assertSame('KGM', $unitPeppol->getCode());
        $this->assertSame('Kilogram', $unitPeppol->getName());
        $this->assertSame('Unit of mass equal to one thousand grams', $unitPeppol->getDescription());
    }

    public function testIdSetterAndGetter(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setId(50);
        
        $this->assertSame('50', $unitPeppol->getId());
    }

    public function testUnitIdSetterAndGetter(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setUnit_id(200);
        
        $this->assertSame('200', $unitPeppol->getUnit_id());
    }

    public function testCodeSetterAndGetter(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setCode('MTR');
        
        $this->assertSame('MTR', $unitPeppol->getCode());
    }

    public function testNameSetterAndGetter(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setName('Metre');
        
        $this->assertSame('Metre', $unitPeppol->getName());
    }

    public function testDescriptionSetterAndGetter(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setDescription('Unit of length in the metric system');
        
        $this->assertSame('Unit of length in the metric system', $unitPeppol->getDescription());
    }

    public function testUnitRelationshipSetterAndGetter(): void
    {
        $unitPeppol = new UnitPeppol();
        $unit = $this->createMock(Unit::class);
        
        $unitPeppol->setUnit($unit);
        $this->assertSame($unit, $unitPeppol->getUnit());
        
        $unitPeppol->setUnit(null);
        $this->assertNull($unitPeppol->getUnit());
    }

    public function testIdTypeConversion(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setId(999);
        
        $this->assertIsString($unitPeppol->getId());
        $this->assertSame('999', $unitPeppol->getId());
    }

    public function testUnitIdTypeConversion(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setUnit_id(777);
        
        $this->assertIsString($unitPeppol->getUnit_id());
        $this->assertSame('777', $unitPeppol->getUnit_id());
    }

    public function testZeroIds(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setId(0);
        $unitPeppol->setUnit_id(0);
        
        $this->assertSame('0', $unitPeppol->getId());
        $this->assertSame('0', $unitPeppol->getUnit_id());
    }

    public function testNegativeIds(): void
    {
        $unitPeppol = new UnitPeppol();
        $unitPeppol->setId(-1);
        $unitPeppol->setUnit_id(-5);
        
        $this->assertSame('-1', $unitPeppol->getId());
        $this->assertSame('-5', $unitPeppol->getUnit_id());
    }

    public function testLargeIds(): void
    {
        $unitPeppol = new UnitPeppol();
        $largeId = PHP_INT_MAX;
        
        $unitPeppol->setId($largeId);
        $unitPeppol->setUnit_id($largeId - 1);
        
        $this->assertSame((string)$largeId, $unitPeppol->getId());
        $this->assertSame((string)($largeId - 1), $unitPeppol->getUnit_id());
    }

    public function testEmptyStringFields(): void
    {
        $unitPeppol = new UnitPeppol();
        
        $unitPeppol->setCode('');
        $unitPeppol->setName('');
        $unitPeppol->setDescription('');
        
        $this->assertSame('', $unitPeppol->getCode());
        $this->assertSame('', $unitPeppol->getName());
        $this->assertSame('', $unitPeppol->getDescription());
    }

    public function testCommonUnitCodes(): void
    {
        $unitPeppol = new UnitPeppol();
        
        $commonUnits = [
            ['KGM', 'Kilogram', $this->unitOfMass],
            ['MTR', 'Metre', 'Unit of length'],
            ['LTR', 'Litre', 'Unit of volume'],
            ['PCE', 'Piece', 'Unit of quantity'],
            ['HUR', 'Hour', 'Unit of time'],
            ['DAY', 'Day', $this->unitOfTimePeriod],
            ['MON', 'Month', $this->unitOfTimePeriod],
            ['YEA', 'Year', $this->unitOfTimePeriod],
            ['TON', 'Tonne', $this->unitOfMass],
            ['GRM', 'Gram', $this->unitOfMass],
        ];
        
        foreach ($commonUnits as [$code, $name, $description]) {
            $unitPeppol->setCode($code);
            $unitPeppol->setName($name);
            $unitPeppol->setDescription($description);
            
            $this->assertSame($code, $unitPeppol->getCode());
            $this->assertSame($name, $unitPeppol->getName());
            $this->assertSame($description, $unitPeppol->getDescription());
        }
    }

    public function testMeasurementUnits(): void
    {
        $unitPeppol = new UnitPeppol();
        
        // Length units
        $unitPeppol->setCode('MMT');
        $unitPeppol->setName('Millimetre');
        $unitPeppol->setDescription('Unit of length equal to one thousandth of a metre');
        
        $this->assertSame('MMT', $unitPeppol->getCode());
        $this->assertSame('Millimetre', $unitPeppol->getName());
        $this->assertSame('Unit of length equal to one thousandth of a metre', $unitPeppol->getDescription());
        
        // Area units
        $unitPeppol->setCode('MTK');
        $unitPeppol->setName('Square metre');
        $unitPeppol->setDescription('Unit of area in the metric system');
        
        $this->assertSame('MTK', $unitPeppol->getCode());
        $this->assertSame('Square metre', $unitPeppol->getName());
        $this->assertSame('Unit of area in the metric system', $unitPeppol->getDescription());
        
        // Volume units
        $unitPeppol->setCode('MTQ');
        $unitPeppol->setName('Cubic metre');
        $unitPeppol->setDescription('Unit of volume in the metric system');
        
        $this->assertSame('MTQ', $unitPeppol->getCode());
        $this->assertSame('Cubic metre', $unitPeppol->getName());
        $this->assertSame('Unit of volume in the metric system', $unitPeppol->getDescription());
    }

    public function testSpecializedUnits(): void
    {
        $unitPeppol = new UnitPeppol();
        
        // Energy units
        $unitPeppol->setCode('JOU');
        $unitPeppol->setName('Joule');
        $unitPeppol->setDescription('Unit of energy in the International System of Units');
        
        $this->assertSame('JOU', $unitPeppol->getCode());
        $this->assertSame('Joule', $unitPeppol->getName());
        $this->assertSame('Unit of energy in the International System of Units', $unitPeppol->getDescription());
        
        // Power units
        $unitPeppol->setCode('WTT');
        $unitPeppol->setName('Watt');
        $unitPeppol->setDescription('Unit of power in the International System of Units');
        
        $this->assertSame('WTT', $unitPeppol->getCode());
        $this->assertSame('Watt', $unitPeppol->getName());
        $this->assertSame('Unit of power in the International System of Units', $unitPeppol->getDescription());
        
        // Temperature units
        $unitPeppol->setCode('CEL');
        $unitPeppol->setName('Degree Celsius');
        $unitPeppol->setDescription('Unit of temperature in the metric system');
        
        $this->assertSame('CEL', $unitPeppol->getCode());
        $this->assertSame('Degree Celsius', $unitPeppol->getName());
        $this->assertSame('Unit of temperature in the metric system', $unitPeppol->getDescription());
    }

    public function testLongDescriptions(): void
    {
        $unitPeppol = new UnitPeppol();
        
        $longDescription = 'This is a very detailed description of a complex measurement unit that includes multiple aspects such as definition, usage context, conversion factors, industry applications, regulatory standards, and historical background information that exceeds typical length expectations.';
        
        $unitPeppol->setDescription($longDescription);
        $this->assertSame($longDescription, $unitPeppol->getDescription());
    }

    public function testSpecialCharactersInFields(): void
    {
        $unitPeppol = new UnitPeppol();
        
        $unitPeppol->setCode('M²');
        $unitPeppol->setName('Square Metre (m²)');
        $unitPeppol->setDescription('Unit with special chars: ² ³ ° µ α β γ');
        
        $this->assertSame('M²', $unitPeppol->getCode());
        $this->assertSame('Square Metre (m²)', $unitPeppol->getName());
        $this->assertSame('Unit with special chars: ² ³ ° µ α β γ', $unitPeppol->getDescription());
    }

    public function testUnicodeInFields(): void
    {
        $unitPeppol = new UnitPeppol();
        
        $unitPeppol->setCode('升');
        $unitPeppol->setName('升 (Chinese Litre)');
        $unitPeppol->setDescription('中文单位描述：升是体积单位');
        
        $this->assertSame('升', $unitPeppol->getCode());
        $this->assertSame('升 (Chinese Litre)', $unitPeppol->getName());
        $this->assertSame('中文单位描述：升是体积单位', $unitPeppol->getDescription());
    }

    public function testCompleteUnitPeppolSetup(): void
    {
        $unitPeppol = new UnitPeppol();
        $unit = $this->createMock(Unit::class);
        
        $unitPeppol->setId(1);
        $unitPeppol->setUnit_id(100);
        $unitPeppol->setUnit($unit);
        $unitPeppol->setCode('KGM');
        $unitPeppol->setName('Kilogram');
        $unitPeppol->setDescription('Complete setup: Unit of mass equal to 1000 grams');
        
        $this->assertSame('1', $unitPeppol->getId());
        $this->assertSame('100', $unitPeppol->getUnit_id());
        $this->assertSame($unit, $unitPeppol->getUnit());
        $this->assertSame('KGM', $unitPeppol->getCode());
        $this->assertSame('Kilogram', $unitPeppol->getName());
        $this->assertSame('Complete setup: Unit of mass equal to 1000 grams', $unitPeppol->getDescription());
    }

    public function testMethodReturnTypes(): void
    {
        $unitPeppol = new UnitPeppol(
            id: 1,
            unit_id: 100,
            code: 'MTR',
            name: 'Metre',
            description: 'Unit of length'
        );
        
        $this->assertIsString($unitPeppol->getId());
        $this->assertIsString($unitPeppol->getUnit_id());
        $this->assertIsString($unitPeppol->getCode());
        $this->assertIsString($unitPeppol->getName());
        $this->assertIsString($unitPeppol->getDescription());
        $this->assertNull($unitPeppol->getUnit());
    }

    public function testUnitRelationshipWorkflow(): void
    {
        $unitPeppol = new UnitPeppol();
        $unit1 = $this->createMock(Unit::class);
        $unit2 = $this->createMock(Unit::class);
        
        // Initially null
        $this->assertNull($unitPeppol->getUnit());
        
        // Set first unit
        $unitPeppol->setUnit_id(100);
        $unitPeppol->setUnit($unit1);
        $this->assertSame($unit1, $unitPeppol->getUnit());
        
        // Replace with second unit
        $unitPeppol->setUnit_id(200);
        $unitPeppol->setUnit($unit2);
        $this->assertSame($unit2, $unitPeppol->getUnit());
        
        // Set back to null
        $unitPeppol->setUnit(null);
        $this->assertNull($unitPeppol->getUnit());
    }

    public function testEntityStateConsistency(): void
    {
        $unitPeppol = new UnitPeppol(
            id: 999,
            unit_id: 888,
            code: 'INIT',
            name: 'Initial Unit',
            description: 'Initial description'
        );
        
        // Verify initial state
        $this->assertSame('999', $unitPeppol->getId());
        $this->assertSame('888', $unitPeppol->getUnit_id());
        $this->assertSame('INIT', $unitPeppol->getCode());
        $this->assertSame('Initial Unit', $unitPeppol->getName());
        $this->assertSame('Initial description', $unitPeppol->getDescription());
        
        // Modify and verify changes
        $unitPeppol->setId(111);
        $unitPeppol->setUnit_id(222);
        $unitPeppol->setCode('MOD');
        $unitPeppol->setName('Modified Unit');
        $unitPeppol->setDescription('Modified description');
        
        $this->assertSame('111', $unitPeppol->getId());
        $this->assertSame('222', $unitPeppol->getUnit_id());
        $this->assertSame('MOD', $unitPeppol->getCode());
        $this->assertSame('Modified Unit', $unitPeppol->getName());
        $this->assertSame('Modified description', $unitPeppol->getDescription());
    }

    public function testInternationalUnits(): void
    {
        $unitPeppol = new UnitPeppol();
        
        // UK Imperial units
        $unitPeppol->setCode('FOT');
        $unitPeppol->setName('Foot');
        $unitPeppol->setDescription('Imperial unit of length equal to 12 inches');
        
        $this->assertSame('FOT', $unitPeppol->getCode());
        $this->assertSame('Foot', $unitPeppol->getName());
        $this->assertSame('Imperial unit of length equal to 12 inches', $unitPeppol->getDescription());
        
        // US units
        $unitPeppol->setCode('GLL');
        $unitPeppol->setName('Gallon (US)');
        $unitPeppol->setDescription('US liquid gallon, approximately 3.785 litres');
        
        $this->assertSame('GLL', $unitPeppol->getCode());
        $this->assertSame('Gallon (US)', $unitPeppol->getName());
        $this->assertSame('US liquid gallon, approximately 3.785 litres', $unitPeppol->getDescription());
        
        // Traditional units
        $unitPeppol->setCode('DZN');
        $unitPeppol->setName('Dozen');
        $unitPeppol->setDescription('Traditional counting unit equal to 12 pieces');
        
        $this->assertSame('DZN', $unitPeppol->getCode());
        $this->assertSame('Dozen', $unitPeppol->getName());
        $this->assertSame('Traditional counting unit equal to 12 pieces', $unitPeppol->getDescription());
    }

    public function testIndustrySpecificUnits(): void
    {
        $unitPeppol = new UnitPeppol();
        
        // Construction industry
        $unitPeppol->setCode('BG');
        $unitPeppol->setName('Bag');
        $unitPeppol->setDescription('Unit commonly used for cement and other building materials');
        
        $this->assertSame('BG', $unitPeppol->getCode());
        $this->assertSame('Bag', $unitPeppol->getName());
        $this->assertSame('Unit commonly used for cement and other building materials', $unitPeppol->getDescription());
        
        // IT industry
        $unitPeppol->setCode('E54');
        $unitPeppol->setName('Gigabyte');
        $unitPeppol->setDescription('Unit of digital information storage');
        
        $this->assertSame('E54', $unitPeppol->getCode());
        $this->assertSame('Gigabyte', $unitPeppol->getName());
        $this->assertSame('Unit of digital information storage', $unitPeppol->getDescription());
        
        // Energy industry
        $unitPeppol->setCode('KWH');
        $unitPeppol->setName('Kilowatt hour');
        $unitPeppol->setDescription('Unit of energy commonly used in electricity billing');
        
        $this->assertSame('KWH', $unitPeppol->getCode());
        $this->assertSame('Kilowatt hour', $unitPeppol->getName());
        $this->assertSame('Unit of energy commonly used in electricity billing', $unitPeppol->getDescription());
    }

    public function testBusinessScenarios(): void
    {
        $unitPeppol = new UnitPeppol();
        
        // Manufacturing scenario
        $unitPeppol->setCode('KGM');
        $unitPeppol->setName('Kilogram');
        $unitPeppol->setDescription('Weight measurement for raw materials and finished products');
        
        $this->assertSame('KGM', $unitPeppol->getCode());
        $this->assertSame('Kilogram', $unitPeppol->getName());
        $this->assertSame('Weight measurement for raw materials and finished products', $unitPeppol->getDescription());
        
        // Service industry scenario
        $unitPeppol->setCode('HUR');
        $unitPeppol->setName('Hour');
        $unitPeppol->setDescription('Time-based billing unit for professional services');
        
        $this->assertSame('HUR', $unitPeppol->getCode());
        $this->assertSame('Hour', $unitPeppol->getName());
        $this->assertSame('Time-based billing unit for professional services', $unitPeppol->getDescription());
        
        // Retail scenario
        $unitPeppol->setCode('PCE');
        $unitPeppol->setName('Piece');
        $unitPeppol->setDescription('Individual item count for retail inventory and sales');
        
        $this->assertSame('PCE', $unitPeppol->getCode());
        $this->assertSame('Piece', $unitPeppol->getName());
        $this->assertSame('Individual item count for retail inventory and sales', $unitPeppol->getDescription());
    }

    public function testCodeFormatValidation(): void
    {
        $unitPeppol = new UnitPeppol();
        
        // Standard 3-character codes
        $validCodes = ['KGM', 'MTR', 'LTR', 'PCE', 'HUR', 'TON', 'GRM', 'MMT', 'MTK', 'MTQ'];
        
        foreach ($validCodes as $code) {
            $unitPeppol->setCode($code);
            $this->assertSame($code, $unitPeppol->getCode());
            $this->assertSame(3, strlen($unitPeppol->getCode()));
        }
        
        // Extended codes (can be longer for specific cases)
        $extendedCodes = ['E54', 'KWH', 'A73', 'B22'];
        
        foreach ($extendedCodes as $code) {
            $unitPeppol->setCode($code);
            $this->assertSame($code, $unitPeppol->getCode());
        }
    }

    public function testDescriptionWithHtmlTags(): void
    {
        $unitPeppol = new UnitPeppol();
        $htmlDescription = '<p>Unit description with <strong>HTML</strong> tags and <em>formatting</em></p>';
        $unitPeppol->setDescription($htmlDescription);
        
        $this->assertSame($htmlDescription, $unitPeppol->getDescription());
    }

    public function testDescriptionWithLineBreaks(): void
    {
        $unitPeppol = new UnitPeppol();
        $multilineDescription = "Line 1: Basic definition\nLine 2: Usage examples\nLine 3: Conversion notes\nLine 4: Additional information";
        $unitPeppol->setDescription($multilineDescription);
        
        $this->assertSame($multilineDescription, $unitPeppol->getDescription());
    }

    public function testNamesWithAbbreviations(): void
    {
        $unitPeppol = new UnitPeppol();
        
        $namesWithAbbrevs = [
            'Kilogram (kg)',
            'Metre (m)',
            'Litre (L or l)',
            'Square metre (m²)',
            'Cubic metre (m³)',
            'Kilometre per hour (km/h)',
            'Degrees Celsius (°C)',
            'Pounds per square inch (psi)',
        ];
        
        foreach ($namesWithAbbrevs as $name) {
            $unitPeppol->setName($name);
            $this->assertSame($name, $unitPeppol->getName());
        }
    }
}
