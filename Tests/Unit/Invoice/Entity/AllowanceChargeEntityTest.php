<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\AllowanceCharge;
use App\Invoice\Entity\TaxRate;
use PHPUnit\Framework\TestCase;

class AllowanceChargeEntityTest extends TestCase
{
    public string $volumeDiscount = 'Volume discount';
    
    public string $earlyPaymentDiscount = 'Early payment discount';
    
    public string $serviceCharge = 'Service charge';
    
    public string $lineItemDiscount = 'Line item discount';
    
    public function testConstructorWithDefaults(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $this->assertSame('', $allowanceCharge->getId());
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('', $allowanceCharge->getReasonCode());
        $this->assertSame('', $allowanceCharge->getReason());
        $this->assertSame(0, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(0, $allowanceCharge->getAmount());
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
        $this->assertSame('', $allowanceCharge->getTaxRateId());
        $this->assertNull($allowanceCharge->getTaxRate());
    }

    public function testConstructorWithAllParameters(): void
    {
        $allowanceCharge = new AllowanceCharge(
            id: 1,
            identifier: true,
            level: 1,
            reason_code: 'DIS',
            reason: $this->volumeDiscount,
            multiplier_factor_numeric: 10,
            amount: 5000,
            base_amount: 50000,
            tax_rate_id: 100
        );
        
        $this->assertSame('1', $allowanceCharge->getId());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame(1, $allowanceCharge->getLevel());
        $this->assertSame('DIS', $allowanceCharge->getReasonCode());
        $this->assertSame($this->volumeDiscount, $allowanceCharge->getReason());
        $this->assertSame(10, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(5000, $allowanceCharge->getAmount());
        $this->assertSame(50000, $allowanceCharge->getBaseAmount());
        $this->assertSame('100', $allowanceCharge->getTaxRateId());
        $this->assertNull($allowanceCharge->getTaxRate());
    }

    public function testIdSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setId(50);
        
        $this->assertSame('50', $allowanceCharge->getId());
    }

    public function testIdentifierSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setIdentifier(true);
        $this->assertTrue($allowanceCharge->getIdentifier());
        
        $allowanceCharge->setIdentifier(false);
        $this->assertFalse($allowanceCharge->getIdentifier());
    }

    public function testLevelSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Overall level
        $allowanceCharge->setLevel(0);
        $this->assertSame(0, $allowanceCharge->getLevel());
        
        // InvoiceLine level
        $allowanceCharge->setLevel(1);
        $this->assertSame(1, $allowanceCharge->getLevel());
    }

    public function testReasonCodeSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setReasonCode('DIS');
        
        $this->assertSame('DIS', $allowanceCharge->getReasonCode());
    }

    public function testReasonSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setReason($this->earlyPaymentDiscount);
        
        $this->assertSame($this->earlyPaymentDiscount, $allowanceCharge->getReason());
    }

    public function testMultiplierFactorNumericSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setMultiplierFactorNumeric(15);
        
        $this->assertSame(15, $allowanceCharge->getMultiplierFactorNumeric());
    }

    public function testAmountSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setAmount(2500);
        
        $this->assertSame(2500, $allowanceCharge->getAmount());
    }

    public function testBaseAmountSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setBaseAmount(25000);
        
        $this->assertSame(25000, $allowanceCharge->getBaseAmount());
    }

    public function testTaxRateIdSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setTaxRateId(200);
        
        $this->assertSame('200', $allowanceCharge->getTaxRateId());
    }

    public function testTaxRateRelationshipSetterAndGetter(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $taxRate = $this->createMock(TaxRate::class);
        
        $allowanceCharge->setTaxrate($taxRate);
        $this->assertSame($taxRate, $allowanceCharge->getTaxRate());
        
        $allowanceCharge->setTaxrate(null);
        $this->assertNull($allowanceCharge->getTaxRate());
    }

    public function testIdTypeConversion(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setId(999);
        
        $this->assertIsString($allowanceCharge->getId());
        $this->assertSame('999', $allowanceCharge->getId());
    }

    public function testTaxRateIdTypeConversion(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $allowanceCharge->setTaxRateId(777);
        
        $this->assertIsString($allowanceCharge->getTaxRateId());
        $this->assertSame('777', $allowanceCharge->getTaxRateId());
    }

    public function testZeroValues(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setId(0);
        $allowanceCharge->setLevel(0);
        $allowanceCharge->setMultiplierFactorNumeric(0);
        $allowanceCharge->setAmount(0);
        $allowanceCharge->setBaseAmount(0);
        $allowanceCharge->setTaxRateId(0);
        
        $this->assertSame('0', $allowanceCharge->getId());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame(0, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(0, $allowanceCharge->getAmount());
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
        $this->assertSame('0', $allowanceCharge->getTaxRateId());
    }

    public function testNegativeValues(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setId(-1);
        $allowanceCharge->setLevel(-1);
        $allowanceCharge->setMultiplierFactorNumeric(-5);
        $allowanceCharge->setAmount(-1000);
        $allowanceCharge->setBaseAmount(-5000);
        $allowanceCharge->setTaxRateId(-10);
        
        $this->assertSame('-1', $allowanceCharge->getId());
        $this->assertSame(-1, $allowanceCharge->getLevel());
        $this->assertSame(-5, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(-1000, $allowanceCharge->getAmount());
        $this->assertSame(-5000, $allowanceCharge->getBaseAmount());
        $this->assertSame('-10', $allowanceCharge->getTaxRateId());
    }

    public function testLargeValues(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $largeValue = PHP_INT_MAX;
        
        $allowanceCharge->setId($largeValue);
        $allowanceCharge->setLevel($largeValue);
        $allowanceCharge->setMultiplierFactorNumeric($largeValue);
        $allowanceCharge->setAmount($largeValue);
        $allowanceCharge->setBaseAmount($largeValue);
        $allowanceCharge->setTaxRateId($largeValue);
        
        $this->assertSame((string)$largeValue, $allowanceCharge->getId());
        $this->assertSame($largeValue, $allowanceCharge->getLevel());
        $this->assertSame($largeValue, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame($largeValue, $allowanceCharge->getAmount());
        $this->assertSame($largeValue, $allowanceCharge->getBaseAmount());
        $this->assertSame((string)$largeValue, $allowanceCharge->getTaxRateId());
    }

    public function testEmptyStringFields(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $allowanceCharge->setReasonCode('');
        $allowanceCharge->setReason('');
        
        $this->assertSame('', $allowanceCharge->getReasonCode());
        $this->assertSame('', $allowanceCharge->getReason());
    }

    public function testCommonReasonCodes(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $reasonCodes = [
            'DIS' => 'Discount',
            'CHG' => 'Charge',
            'SUR' => 'Surcharge',
            'FEE' => 'Fee',
            'TAX' => 'Tax',
            'VAT' => 'VAT',
            'SER' => $this->serviceCharge,
            'DEL' => 'Delivery charge',
            'HAN' => 'Handling fee',
            'INS' => 'Insurance'
        ];
        
        foreach ($reasonCodes as $code => $description) {
            $allowanceCharge->setReasonCode($code);
            $allowanceCharge->setReason($description);
            
            $this->assertSame($code, $allowanceCharge->getReasonCode());
            $this->assertSame($description, $allowanceCharge->getReason());
        }
    }

    public function testAllowanceScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Volume discount scenario
        $allowanceCharge->setIdentifier(false); // Allowance
        $allowanceCharge->setLevel(0); // Overall
        $allowanceCharge->setReasonCode('95');
        $allowanceCharge->setReason($this->volumeDiscount);
        $allowanceCharge->setMultiplierFactorNumeric(10); // 10%
        $allowanceCharge->setBaseAmount(100000); // $1000.00
        $allowanceCharge->setAmount(10000); // $100.00 discount
        
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('95', $allowanceCharge->getReasonCode());
        $this->assertSame($this->volumeDiscount, $allowanceCharge->getReason());
        $this->assertSame(10, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(100000, $allowanceCharge->getBaseAmount());
        $this->assertSame(10000, $allowanceCharge->getAmount());
    }

    public function testChargeScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Shipping charge scenario
        $allowanceCharge->setIdentifier(true); // Charge
        $allowanceCharge->setLevel(0); // Overall
        $allowanceCharge->setReasonCode('FC');
        $allowanceCharge->setReason('Freight charge');
        $allowanceCharge->setMultiplierFactorNumeric(1); // Fixed amount
        $allowanceCharge->setBaseAmount(0); // No base calculation
        $allowanceCharge->setAmount(2500); // $25.00 shipping
        
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('FC', $allowanceCharge->getReasonCode());
        $this->assertSame('Freight charge', $allowanceCharge->getReason());
        $this->assertSame(1, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(0, $allowanceCharge->getBaseAmount());
        $this->assertSame(2500, $allowanceCharge->getAmount());
    }

    public function testLineItemAllowanceScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Line item discount scenario
        $allowanceCharge->setIdentifier(false); // Allowance
        $allowanceCharge->setLevel(1); // InvoiceLine
        $allowanceCharge->setReasonCode('100');
        $allowanceCharge->setReason($this->lineItemDiscount);
        $allowanceCharge->setMultiplierFactorNumeric(5); // 5%
        $allowanceCharge->setBaseAmount(50000); // $500.00
        $allowanceCharge->setAmount(2500); // $25.00 discount
        
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame(1, $allowanceCharge->getLevel());
        $this->assertSame('100', $allowanceCharge->getReasonCode());
        $this->assertSame($this->lineItemDiscount, $allowanceCharge->getReason());
        $this->assertSame(5, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(50000, $allowanceCharge->getBaseAmount());
        $this->assertSame(2500, $allowanceCharge->getAmount());
    }

    public function testPercentageCalculationScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $scenarios = [
            // 10% discount on $1000
            [10, 100000, 10000],
            // 5% discount on $500
            [5, 50000, 2500],
            // 15% discount on $200
            [15, 20000, 3000],
            // 25% discount on $800
            [25, 80000, 20000],
            // 2.5% service charge on $1200
            [250, 120000, 3000] // 2.5% as 250 basis points
        ];
        
        foreach ($scenarios as [$multiplier, $baseAmount, $expectedAmount]) {
            $allowanceCharge->setMultiplierFactorNumeric($multiplier);
            $allowanceCharge->setBaseAmount($baseAmount);
            $allowanceCharge->setAmount($expectedAmount);
            
            $this->assertSame($multiplier, $allowanceCharge->getMultiplierFactorNumeric());
            $this->assertSame($baseAmount, $allowanceCharge->getBaseAmount());
            $this->assertSame($expectedAmount, $allowanceCharge->getAmount());
        }
    }

    public function testFixedAmountScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $fixedScenarios = [
            ['Shipping fee', 2500],
            ['Handling charge', 500],
            ['Processing fee', 1000],
            ['Documentation fee', 750],
            ['Express delivery', 5000],
            ['Insurance premium', 1250],
            [$this->serviceCharge, 300],
            ['Administrative fee', 200]
        ];
        
        foreach ($fixedScenarios as [$reason, $amount]) {
            $allowanceCharge->setReason($reason);
            $allowanceCharge->setAmount($amount);
            $allowanceCharge->setMultiplierFactorNumeric(1); // Fixed amount
            $allowanceCharge->setBaseAmount(0); // No base calculation
            
            $this->assertSame($reason, $allowanceCharge->getReason());
            $this->assertSame($amount, $allowanceCharge->getAmount());
            $this->assertSame(1, $allowanceCharge->getMultiplierFactorNumeric());
            $this->assertSame(0, $allowanceCharge->getBaseAmount());
        }
    }

    public function testBusinessDiscountTypes(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $discountTypes = [
            $this->earlyPaymentDiscount,
            $this->volumeDiscount,
            'Loyalty discount', 
            'Seasonal discount',
            'New customer discount',
            'Bulk order discount',
            'Trade discount',
            'Cash discount',
            'Promotional discount',
            'Employee discount',
            'Senior citizen discount',
            'Student discount',
            'Military discount',
            'Member discount'
        ];
        
        foreach ($discountTypes as $discountType) {
            $allowanceCharge->setReason($discountType);
            $this->assertSame($discountType, $allowanceCharge->getReason());
        }
    }

    public function testBusinessChargeTypes(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $chargeTypes = [
            'Delivery charge',
            'Installation fee',
            'Setup fee',
            'Maintenance fee',
            'Support fee',
            'Warranty extension',
            'Express service',
            'Priority handling',
            'Special packaging',
            'Customization fee',
            'Configuration charge',
            'Training fee',
            'Consulting fee',
            'License fee'
        ];
        
        foreach ($chargeTypes as $chargeType) {
            $allowanceCharge->setReason($chargeType);
            $this->assertSame($chargeType, $allowanceCharge->getReason());
        }
    }

    public function testTaxRateRelationshipWorkflow(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $taxRate1 = $this->createMock(TaxRate::class);
        $taxRate2 = $this->createMock(TaxRate::class);
        
        // Initially null
        $this->assertNull($allowanceCharge->getTaxRate());
        
        // Set first tax rate
        $allowanceCharge->setTaxRateId(100);
        $allowanceCharge->setTaxrate($taxRate1);
        $this->assertSame('100', $allowanceCharge->getTaxRateId());
        $this->assertSame($taxRate1, $allowanceCharge->getTaxRate());
        
        // Replace with second tax rate
        $allowanceCharge->setTaxRateId(200);
        $allowanceCharge->setTaxrate($taxRate2);
        $this->assertSame('200', $allowanceCharge->getTaxRateId());
        $this->assertSame($taxRate2, $allowanceCharge->getTaxRate());
        
        // Set back to null
        $allowanceCharge->setTaxrate(null);
        $this->assertNull($allowanceCharge->getTaxRate());
    }

    public function testCompleteAllowanceChargeSetup(): void
    {
        $allowanceCharge = new AllowanceCharge();
        $taxRate = $this->createMock(TaxRate::class);
        
        $allowanceCharge->setId(1);
        $allowanceCharge->setIdentifier(false);
        $allowanceCharge->setLevel(0);
        $allowanceCharge->setReasonCode('DIS');
        $allowanceCharge->setReason($this->volumeDiscount);
        $allowanceCharge->setMultiplierFactorNumeric(10);
        $allowanceCharge->setAmount(5000);
        $allowanceCharge->setBaseAmount(50000);
        $allowanceCharge->setTaxRateId(100);
        $allowanceCharge->setTaxrate($taxRate);
        
        $this->assertSame('1', $allowanceCharge->getId());
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('DIS', $allowanceCharge->getReasonCode());
        $this->assertSame($this->volumeDiscount, $allowanceCharge->getReason());
        $this->assertSame(10, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(5000, $allowanceCharge->getAmount());
        $this->assertSame(50000, $allowanceCharge->getBaseAmount());
        $this->assertSame('100', $allowanceCharge->getTaxRateId());
        $this->assertSame($taxRate, $allowanceCharge->getTaxRate());
    }

    public function testMethodReturnTypes(): void
    {
        $allowanceCharge = new AllowanceCharge(
            id: 1,
            identifier: true,
            level: 1,
            reason_code: 'CHG',
            reason: $this->serviceCharge,
            multiplier_factor_numeric: 5,
            amount: 1000,
            base_amount: 20000,
            tax_rate_id: 50
        );
        
        $this->assertIsString($allowanceCharge->getId());
        $this->assertIsBool($allowanceCharge->getIdentifier());
        $this->assertIsInt($allowanceCharge->getLevel());
        $this->assertIsString($allowanceCharge->getReasonCode());
        $this->assertIsString($allowanceCharge->getReason());
        $this->assertIsInt($allowanceCharge->getMultiplierFactorNumeric());
        $this->assertIsInt($allowanceCharge->getAmount());
        $this->assertIsInt($allowanceCharge->getBaseAmount());
        $this->assertIsString($allowanceCharge->getTaxRateId());
        $this->assertNull($allowanceCharge->getTaxRate());
    }

    public function testSpecialCharactersInReasonFields(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $specialReasons = [
            'Discount & Allowance',
            'Fee (Administrative)',
            'Charge - Processing',
            'Tax @ 10%',
            'Surcharge #1',
            'Service * Premium',
            'Handling / Shipping',
            'Special + Extra',
            'Discount: Volume',
            'Fee; Service'
        ];
        
        foreach ($specialReasons as $reason) {
            $allowanceCharge->setReason($reason);
            $this->assertSame($reason, $allowanceCharge->getReason());
        }
    }

    public function testUnicodeInReasonFields(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $unicodeReasons = [
            '折扣 (Discount)',
            'Réduction de prix',
            'Descuento especial',
            'Скидка за объём',
            'Έκπτωση όγκου',
            'ボリューム割引',
            'خصم الكمية',
            'Zniżka ilościowa',
            'Množstevní sleva',
            'Mennyiségi kedvezmény'
        ];
        
        foreach ($unicodeReasons as $reason) {
            $allowanceCharge->setReason($reason);
            $this->assertSame($reason, $allowanceCharge->getReason());
        }
    }

    public function testLongReasonDescriptions(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $longReason = 'Comprehensive volume discount applied to qualifying orders exceeding minimum purchase thresholds, available to preferred customers with established credit terms and payment history, subject to additional terms and conditions as outlined in the customer agreement and applicable pricing schedules.';
        
        $allowanceCharge->setReason($longReason);
        $this->assertSame($longReason, $allowanceCharge->getReason());
    }

    public function testReasonCodeFormats(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        $codeFormats = [
            // Standard 3-character codes
            'DIS', 'CHG', 'SUR', 'FEE', 'TAX', 'VAT',
            // Numeric codes
            '95', '100', '102', '103',
            // Mixed formats
            'FC1', 'D15', 'C22', 'S05',
            // Single characters
            'A', 'B', 'C', 'D'
        ];
        
        foreach ($codeFormats as $code) {
            $allowanceCharge->setReasonCode($code);
            $this->assertSame($code, $allowanceCharge->getReasonCode());
        }
    }

    public function testEntityStateConsistency(): void
    {
        $allowanceCharge = new AllowanceCharge(
            id: 999,
            identifier: true,
            level: 1,
            reason_code: 'TEST',
            reason: 'Test reason',
            multiplier_factor_numeric: 50,
            amount: 1000,
            base_amount: 2000,
            tax_rate_id: 888
        );
        
        // Verify initial state
        $this->assertSame('999', $allowanceCharge->getId());
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame(1, $allowanceCharge->getLevel());
        $this->assertSame('TEST', $allowanceCharge->getReasonCode());
        $this->assertSame('Test reason', $allowanceCharge->getReason());
        $this->assertSame(50, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(1000, $allowanceCharge->getAmount());
        $this->assertSame(2000, $allowanceCharge->getBaseAmount());
        $this->assertSame('888', $allowanceCharge->getTaxRateId());
        
        // Modify all properties
        $allowanceCharge->setId(111);
        $allowanceCharge->setIdentifier(false);
        $allowanceCharge->setLevel(0);
        $allowanceCharge->setReasonCode('MOD');
        $allowanceCharge->setReason('Modified reason');
        $allowanceCharge->setMultiplierFactorNumeric(25);
        $allowanceCharge->setAmount(500);
        $allowanceCharge->setBaseAmount(1000);
        $allowanceCharge->setTaxRateId(222);
        
        // Verify changes
        $this->assertSame('111', $allowanceCharge->getId());
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('MOD', $allowanceCharge->getReasonCode());
        $this->assertSame('Modified reason', $allowanceCharge->getReason());
        $this->assertSame(25, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(500, $allowanceCharge->getAmount());
        $this->assertSame(1000, $allowanceCharge->getBaseAmount());
        $this->assertSame('222', $allowanceCharge->getTaxRateId());
    }

    public function testAdvancedCalculationScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Complex percentage calculation (7.5% on $2000)
        $allowanceCharge->setMultiplierFactorNumeric(750); // 7.5% as basis points
        $allowanceCharge->setBaseAmount(200000); // $2000.00
        $allowanceCharge->setAmount(15000); // $150.00
        
        $this->assertSame(750, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(200000, $allowanceCharge->getBaseAmount());
        $this->assertSame(15000, $allowanceCharge->getAmount());
        
        // Tiered discount calculation
        $allowanceCharge->setMultiplierFactorNumeric(1250); // 12.5%
        $allowanceCharge->setBaseAmount(80000); // $800.00
        $allowanceCharge->setAmount(10000); // $100.00
        
        $this->assertSame(1250, $allowanceCharge->getMultiplierFactorNumeric());
        $this->assertSame(80000, $allowanceCharge->getBaseAmount());
        $this->assertSame(10000, $allowanceCharge->getAmount());
    }

    public function testInvoiceLevelVsLineLevel(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Invoice level allowance
        $allowanceCharge->setLevel(0);
        $allowanceCharge->setReason('Invoice level discount');
        $this->assertSame(0, $allowanceCharge->getLevel());
        $this->assertSame('Invoice level discount', $allowanceCharge->getReason());
        
        // Line level allowance
        $allowanceCharge->setLevel(1);
        $allowanceCharge->setReason($this->lineItemDiscount);
        $this->assertSame(1, $allowanceCharge->getLevel());
        $this->assertSame($this->lineItemDiscount, $allowanceCharge->getReason());
    }

    public function testConstructorParameterCombinations(): void
    {
        // Only ID
        $charge1 = new AllowanceCharge(id: 1);
        $this->assertSame('1', $charge1->getId());
        $this->assertFalse($charge1->getIdentifier());
        $this->assertSame(0, $charge1->getLevel());
        
        // ID and identifier
        $charge2 = new AllowanceCharge(id: 2, identifier: true);
        $this->assertSame('2', $charge2->getId());
        $this->assertTrue($charge2->getIdentifier());
        
        // Essential fields
        $charge3 = new AllowanceCharge(
            id: 3,
            identifier: false,
            reason_code: 'DIS',
            reason: 'Discount'
        );
        $this->assertSame('3', $charge3->getId());
        $this->assertFalse($charge3->getIdentifier());
        $this->assertSame('DIS', $charge3->getReasonCode());
        $this->assertSame('Discount', $charge3->getReason());
    }

    public function testRealWorldDiscountScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Early bird discount - 15% off for orders placed 30 days in advance
        $allowanceCharge->setIdentifier(false);
        $allowanceCharge->setReasonCode('95');
        $allowanceCharge->setReason('Early bird discount - 15% off for advance orders');
        $allowanceCharge->setMultiplierFactorNumeric(15);
        $allowanceCharge->setBaseAmount(100000);
        $allowanceCharge->setAmount(15000);
        
        $this->assertFalse($allowanceCharge->getIdentifier());
        $this->assertSame('95', $allowanceCharge->getReasonCode());
        $this->assertSame('Early bird discount - 15% off for advance orders', $allowanceCharge->getReason());
        
        // Quantity break discount - Tiered pricing for bulk orders
        $allowanceCharge->setIdentifier(false);
        $allowanceCharge->setReasonCode('100');
        $allowanceCharge->setReason('Quantity break discount - Orders over 100 units');
        $allowanceCharge->setMultiplierFactorNumeric(8);
        $allowanceCharge->setBaseAmount(250000);
        $allowanceCharge->setAmount(20000);
        
        $this->assertSame('100', $allowanceCharge->getReasonCode());
        $this->assertSame('Quantity break discount - Orders over 100 units', $allowanceCharge->getReason());
    }

    public function testRealWorldChargeScenarios(): void
    {
        $allowanceCharge = new AllowanceCharge();
        
        // Expedited shipping with insurance
        $allowanceCharge->setIdentifier(true);
        $allowanceCharge->setReasonCode('FC');
        $allowanceCharge->setReason('Expedited shipping with insurance coverage');
        $allowanceCharge->setMultiplierFactorNumeric(1);
        $allowanceCharge->setBaseAmount(0);
        $allowanceCharge->setAmount(7500);
        
        $this->assertTrue($allowanceCharge->getIdentifier());
        $this->assertSame('FC', $allowanceCharge->getReasonCode());
        $this->assertSame('Expedited shipping with insurance coverage', $allowanceCharge->getReason());
        
        // Installation and configuration service
        $allowanceCharge->setIdentifier(true);
        $allowanceCharge->setReasonCode('SAA');
        $allowanceCharge->setReason('Professional installation and configuration service');
        $allowanceCharge->setMultiplierFactorNumeric(1);
        $allowanceCharge->setBaseAmount(0);
        $allowanceCharge->setAmount(25000);
        
        $this->assertSame('SAA', $allowanceCharge->getReasonCode());
        $this->assertSame('Professional installation and configuration service', $allowanceCharge->getReason());
    }
}