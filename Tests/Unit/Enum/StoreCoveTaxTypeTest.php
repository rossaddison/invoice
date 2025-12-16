<?php

declare(strict_types=1);

namespace Tests\Unit\Enum;

use App\Invoice\Enum\StoreCoveTaxType;
use Codeception\Test\Unit;

class StoreCoveTaxTypeTest extends Unit
{
    public function testEnumCases(): void
    {
        $expectedCases = [
            'Standard' => 'standard',
            'ZeroRated' => 'zero_rated',
            'Exempt' => 'exempt',
            'ReverseCharge' => 'reverse_charge',
            'IntraCommunity' => 'intra_community',
            'Export' => 'export',
            'OutsideScope' => 'outside_scope',
            'Regulation33Exempt' => 'regulation33_exempt',
            'NonRegulation33Exempt' => 'nonregulation33_exempt',
            'DeemedSupply' => 'deemed_supply',
            'SRCAs' => 'srca_s',
            'SRCAc' => 'srca_c',
            'NotRegistered' => 'not_registered',
            'IGST' => 'igst',
            'CGST' => 'cgst',
            'SGST' => 'sgst',
            'CESS' => 'cess',
            'STATECESS' => 'state_cess',
            'SROVR' => 'srovr',
            'SROVRrs' => 'srovr_rs',
            'SROVRlvg' => 'srovr_lvg',
            'SRLVG' => 'srlvg',
        ];

        $cases = StoreCoveTaxType::cases();
        
        $this->assertCount(22, $cases);
        
        foreach ($expectedCases as $caseName => $expectedValue) {
            $enumCase = StoreCoveTaxType::{$caseName};
            $this->assertSame($expectedValue, $enumCase->value);
        }
    }

    public function testStandardCase(): void
    {
        $this->assertSame('standard', StoreCoveTaxType::Standard->value);
        $this->assertSame('Standard', StoreCoveTaxType::Standard->name);
    }

    public function testZeroRatedCase(): void
    {
        $this->assertSame('zero_rated', StoreCoveTaxType::ZeroRated->value);
        $this->assertSame('ZeroRated', StoreCoveTaxType::ZeroRated->name);
    }

    public function testExemptCase(): void
    {
        $this->assertSame('exempt', StoreCoveTaxType::Exempt->value);
        $this->assertSame('Exempt', StoreCoveTaxType::Exempt->name);
    }

    public function testReverseChargeCase(): void
    {
        $this->assertSame('reverse_charge', StoreCoveTaxType::ReverseCharge->value);
        $this->assertSame('ReverseCharge', StoreCoveTaxType::ReverseCharge->name);
    }

    public function testIntraCommunityCase(): void
    {
        $this->assertSame('intra_community', StoreCoveTaxType::IntraCommunity->value);
        $this->assertSame('IntraCommunity', StoreCoveTaxType::IntraCommunity->name);
    }

    public function testExportCase(): void
    {
        $this->assertSame('export', StoreCoveTaxType::Export->value);
        $this->assertSame('Export', StoreCoveTaxType::Export->name);
    }

    public function testOutsideScopeCase(): void
    {
        $this->assertSame('outside_scope', StoreCoveTaxType::OutsideScope->value);
        $this->assertSame('OutsideScope', StoreCoveTaxType::OutsideScope->name);
    }

    public function testRegulation33ExemptCase(): void
    {
        $this->assertSame('regulation33_exempt', StoreCoveTaxType::Regulation33Exempt->value);
        $this->assertSame('Regulation33Exempt', StoreCoveTaxType::Regulation33Exempt->name);
    }

    public function testNonRegulation33ExemptCase(): void
    {
        $this->assertSame('nonregulation33_exempt', StoreCoveTaxType::NonRegulation33Exempt->value);
        $this->assertSame('NonRegulation33Exempt', StoreCoveTaxType::NonRegulation33Exempt->name);
    }

    public function testDeemedSupplyCase(): void
    {
        $this->assertSame('deemed_supply', StoreCoveTaxType::DeemedSupply->value);
        $this->assertSame('DeemedSupply', StoreCoveTaxType::DeemedSupply->name);
    }

    public function testSRCAsCase(): void
    {
        $this->assertSame('srca_s', StoreCoveTaxType::SRCAs->value);
        $this->assertSame('SRCAs', StoreCoveTaxType::SRCAs->name);
    }

    public function testSRCAcCase(): void
    {
        $this->assertSame('srca_c', StoreCoveTaxType::SRCAc->value);
        $this->assertSame('SRCAc', StoreCoveTaxType::SRCAc->name);
    }

    public function testNotRegisteredCase(): void
    {
        $this->assertSame('not_registered', StoreCoveTaxType::NotRegistered->value);
        $this->assertSame('NotRegistered', StoreCoveTaxType::NotRegistered->name);
    }

    public function testIGSTCase(): void
    {
        $this->assertSame('igst', StoreCoveTaxType::IGST->value);
        $this->assertSame('IGST', StoreCoveTaxType::IGST->name);
    }

    public function testCGSTCase(): void
    {
        $this->assertSame('cgst', StoreCoveTaxType::CGST->value);
        $this->assertSame('CGST', StoreCoveTaxType::CGST->name);
    }

    public function testSGSTCase(): void
    {
        $this->assertSame('sgst', StoreCoveTaxType::SGST->value);
        $this->assertSame('SGST', StoreCoveTaxType::SGST->name);
    }

    public function testCESSCase(): void
    {
        $this->assertSame('cess', StoreCoveTaxType::CESS->value);
        $this->assertSame('CESS', StoreCoveTaxType::CESS->name);
    }

    public function testSTATECESSCase(): void
    {
        $this->assertSame('state_cess', StoreCoveTaxType::STATECESS->value);
        $this->assertSame('STATECESS', StoreCoveTaxType::STATECESS->name);
    }

    public function testSROVRCase(): void
    {
        $this->assertSame('srovr', StoreCoveTaxType::SROVR->value);
        $this->assertSame('SROVR', StoreCoveTaxType::SROVR->name);
    }

    public function testSROVRrsCase(): void
    {
        $this->assertSame('srovr_rs', StoreCoveTaxType::SROVRrs->value);
        $this->assertSame('SROVRrs', StoreCoveTaxType::SROVRrs->name);
    }

    public function testSROVRlvgCase(): void
    {
        $this->assertSame('srovr_lvg', StoreCoveTaxType::SROVRlvg->value);
        $this->assertSame('SROVRlvg', StoreCoveTaxType::SROVRlvg->name);
    }

    public function testSRLVGCase(): void
    {
        $this->assertSame('srlvg', StoreCoveTaxType::SRLVG->value);
        $this->assertSame('SRLVG', StoreCoveTaxType::SRLVG->name);
    }

    public function testFromValue(): void
    {
        $this->assertSame(StoreCoveTaxType::Standard, StoreCoveTaxType::from('standard'));
        $this->assertSame(StoreCoveTaxType::ZeroRated, StoreCoveTaxType::from('zero_rated'));
        $this->assertSame(StoreCoveTaxType::Exempt, StoreCoveTaxType::from('exempt'));
    }

    public function testTryFromValue(): void
    {
        $this->assertSame(StoreCoveTaxType::Standard, StoreCoveTaxType::tryFrom('standard'));
        $this->assertSame(StoreCoveTaxType::ZeroRated, StoreCoveTaxType::tryFrom('zero_rated'));
        $this->assertNull(StoreCoveTaxType::tryFrom('invalid_value'));
    }

    public function testFromInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        StoreCoveTaxType::from('invalid_value');
    }

    public function testAllCasesHaveUniqueValues(): void
    {
        $cases = StoreCoveTaxType::cases();
        $values = array_map(fn($case) => $case->value, $cases);
        
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function testAllCasesHaveUniqueNames(): void
    {
        $cases = StoreCoveTaxType::cases();
        $names = array_map(fn($case) => $case->name, $cases);
        
        $this->assertSame(count($names), count(array_unique($names)));
    }

    public function testAllValuesAreSnakeCase(): void
    {
        $cases = StoreCoveTaxType::cases();
        
        foreach ($cases as $case) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $case->value);
        }
    }

    public function testEnumIsStringBacked(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, StoreCoveTaxType::Standard);
        $this->assertIsString(StoreCoveTaxType::Standard->value);
    }

    public function testCasesCount(): void
    {
        $this->assertCount(22, StoreCoveTaxType::cases());
    }

    public function testSerializationCompatibility(): void
    {
        $json = json_encode([
            'standard' => StoreCoveTaxType::Standard->value,
            'zero_rated' => StoreCoveTaxType::ZeroRated->value,
            'exempt' => StoreCoveTaxType::Exempt->value,
        ]);
        
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertSame('standard', $decoded['standard']);
        $this->assertSame('zero_rated', $decoded['zero_rated']);
        $this->assertSame('exempt', $decoded['exempt']);
    }

    public function testStringRepresentation(): void
    {
        $this->assertSame('standard', (string) StoreCoveTaxType::Standard->value);
        $this->assertSame('zero_rated', (string) StoreCoveTaxType::ZeroRated->value);
    }

    public function testComparisonOperations(): void
    {
        $standard1 = StoreCoveTaxType::Standard;
        $standard2 = StoreCoveTaxType::Standard;
        $zeroRated = StoreCoveTaxType::ZeroRated;
        
        $this->assertTrue($standard1 === $standard2);
        $this->assertFalse($standard1 === $zeroRated);
        $this->assertTrue($standard1->value === $standard2->value);
        $this->assertFalse($standard1->value === $zeroRated->value);
    }
}
