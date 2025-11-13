<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodEntityTest extends TestCase
{
    public string $creditCard = 'Credit Card';
    
    public string $bankTransfer = 'Bank Transfer';
    
    public string $testMethod = 'Test Method';
    
    public function testConstructorWithDefaults(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $this->assertSame('', $paymentMethod->getId());
        $this->assertSame('', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testConstructorWithAllParameters(): void
    {
        $paymentMethod = new PaymentMethod(
            id: 1,
            name: $this->creditCard,
            active: true
        );
        
        $this->assertSame('1', $paymentMethod->getId());
        $this->assertSame($this->creditCard, $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testConstructorWithInactiveMethod(): void
    {
        $paymentMethod = new PaymentMethod(
            id: 2,
            name: 'Deprecated Method',
            active: false
        );
        
        $this->assertSame('2', $paymentMethod->getId());
        $this->assertSame('Deprecated Method', $paymentMethod->getName());
        $this->assertFalse($paymentMethod->getActive());
    }

    public function testIdSetterAndGetter(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setId(50);
        
        $this->assertSame('50', $paymentMethod->getId());
    }

    public function testNameSetterAndGetter(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setName($this->bankTransfer);
        
        $this->assertSame($this->bankTransfer, $paymentMethod->getName());
    }

    public function testActiveSetterAndGetter(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $paymentMethod->setActive(false);
        $this->assertFalse($paymentMethod->getActive());
        
        $paymentMethod->setActive(true);
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testIdTypeConversion(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setId(999);
        
        $this->assertIsString($paymentMethod->getId());
        $this->assertSame('999', $paymentMethod->getId());
    }

    public function testZeroId(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setId(0);
        
        $this->assertSame('0', $paymentMethod->getId());
    }

    public function testNegativeId(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setId(-1);
        
        $this->assertSame('-1', $paymentMethod->getId());
    }

    public function testLargeId(): void
    {
        $paymentMethod = new PaymentMethod();
        $largeId = PHP_INT_MAX;
        
        $paymentMethod->setId($largeId);
        $this->assertSame((string)$largeId, $paymentMethod->getId());
    }

    public function testEmptyStringName(): void
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setName('');
        
        $this->assertSame('', $paymentMethod->getName());
    }

    public function testCommonPaymentMethods(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $commonMethods = [
            $this->creditCard,
            'Debit Card',
            $this->bankTransfer,
            'PayPal',
            'Cash',
            'Check',
            'Money Order',
            'Wire Transfer',
            'ACH Transfer',
            'Digital Wallet',
            'Cryptocurrency',
            'Gift Card',
            'Store Credit'
        ];
        
        foreach ($commonMethods as $method) {
            $paymentMethod->setName($method);
            $this->assertSame($method, $paymentMethod->getName());
        }
    }

    public function testCreditCardVariations(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $creditCardTypes = [
            'Visa',
            'MasterCard',
            'American Express',
            'Discover',
            'Diners Club',
            'JCB',
            'UnionPay',
            'Maestro'
        ];
        
        foreach ($creditCardTypes as $cardType) {
            $paymentMethod->setName($cardType);
            $this->assertSame($cardType, $paymentMethod->getName());
        }
    }

    public function testDigitalPaymentMethods(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $digitalMethods = [
            'Apple Pay',
            'Google Pay',
            'Samsung Pay',
            'Amazon Pay',
            'Stripe',
            'Square',
            'Venmo',
            'Zelle',
            'Skrill',
            'Neteller'
        ];
        
        foreach ($digitalMethods as $method) {
            $paymentMethod->setName($method);
            $this->assertSame($method, $paymentMethod->getName());
        }
    }

    public function testInternationalPaymentMethods(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $internationalMethods = [
            'Alipay',
            'WeChat Pay',
            'SEPA',
            'SOFORT',
            'iDEAL',
            'Giropay',
            'Bancontact',
            'EPS',
            'Przelewy24',
            'Klarna'
        ];
        
        foreach ($internationalMethods as $method) {
            $paymentMethod->setName($method);
            $this->assertSame($method, $paymentMethod->getName());
        }
    }

    public function testCryptocurrencyMethods(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $cryptoMethods = [
            'Bitcoin',
            'Ethereum',
            'Litecoin',
            'Bitcoin Cash',
            'Ripple (XRP)',
            'Dogecoin',
            'USDC',
            'Tether (USDT)'
        ];
        
        foreach ($cryptoMethods as $method) {
            $paymentMethod->setName($method);
            $this->assertSame($method, $paymentMethod->getName());
        }
    }

    public function testLongPaymentMethodNames(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $longName = 'Corporate Multi-Currency Wire Transfer with International Exchange Processing';
        $paymentMethod->setName($longName);
        
        $this->assertSame($longName, $paymentMethod->getName());
    }

    public function testSpecialCharactersInNames(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $specialNames = [
            'Credit Card (Visa/MC)',
            'Bank Transfer - SWIFT',
            'PayPal & Credit Card',
            'Cash on Delivery (C.O.D.)',
            'Wire Transfer (USD $)',
            'Payment @ Store',
            'Method #1',
            'Pay*Pal Alternative'
        ];
        
        foreach ($specialNames as $name) {
            $paymentMethod->setName($name);
            $this->assertSame($name, $paymentMethod->getName());
        }
    }

    public function testUnicodeInNames(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $unicodeNames = [
            '微信支付 (WeChat Pay)',
            '支付宝 (Alipay)',
            'Überweisung',
            'Tarjeta de Crédito',
            'Kreditkarte',
            'Κάρτα Πίστης',
            'クレジットカード',
            'Carte de Crédit'
        ];
        
        foreach ($unicodeNames as $name) {
            $paymentMethod->setName($name);
            $this->assertSame($name, $paymentMethod->getName());
        }
    }

    public function testActiveStatusToggling(): void
    {
        $paymentMethod = new PaymentMethod();
        
        // Default is true
        $this->assertTrue($paymentMethod->getActive());
        
        // Set to false
        $paymentMethod->setActive(false);
        $this->assertFalse($paymentMethod->getActive());
        
        // Set back to true
        $paymentMethod->setActive(true);
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testCompletePaymentMethodSetup(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $paymentMethod->setId(1);
        $paymentMethod->setName('Credit Card Processing');
        $paymentMethod->setActive(true);
        
        $this->assertSame('1', $paymentMethod->getId());
        $this->assertSame('Credit Card Processing', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testInactivePaymentMethod(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $paymentMethod->setId(99);
        $paymentMethod->setName('Deprecated Payment Gateway');
        $paymentMethod->setActive(false);
        
        $this->assertSame('99', $paymentMethod->getId());
        $this->assertSame('Deprecated Payment Gateway', $paymentMethod->getName());
        $this->assertFalse($paymentMethod->getActive());
    }

    public function testMethodReturnTypes(): void
    {
        $paymentMethod = new PaymentMethod(
            id: 1,
            name: $this->testMethod,
            active: true
        );
        
        $this->assertIsString($paymentMethod->getId());
        $this->assertIsString($paymentMethod->getName());
        $this->assertIsBool($paymentMethod->getActive());
    }

    public function testBusinessScenarios(): void
    {
        $paymentMethod = new PaymentMethod();
        
        // E-commerce scenario
        $paymentMethod->setName('Online Credit Card');
        $paymentMethod->setActive(true);
        $this->assertSame('Online Credit Card', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
        
        // B2B scenario
        $paymentMethod->setName('Net 30 Terms');
        $paymentMethod->setActive(true);
        $this->assertSame('Net 30 Terms', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
        
        // Retail scenario
        $paymentMethod->setName('Point of Sale');
        $paymentMethod->setActive(true);
        $this->assertSame('Point of Sale', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
        
        // Legacy scenario
        $paymentMethod->setName('Old Payment Gateway');
        $paymentMethod->setActive(false);
        $this->assertSame('Old Payment Gateway', $paymentMethod->getName());
        $this->assertFalse($paymentMethod->getActive());
    }

    public function testEntityStateConsistency(): void
    {
        $paymentMethod = new PaymentMethod(
            id: 999,
            name: 'Initial Method',
            active: false
        );
        
        // Verify initial state
        $this->assertSame('999', $paymentMethod->getId());
        $this->assertSame('Initial Method', $paymentMethod->getName());
        $this->assertFalse($paymentMethod->getActive());
        
        // Modify all properties
        $paymentMethod->setId(111);
        $paymentMethod->setName('Modified Method');
        $paymentMethod->setActive(true);
        
        // Verify changes
        $this->assertSame('111', $paymentMethod->getId());
        $this->assertSame('Modified Method', $paymentMethod->getName());
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testPaymentMethodCategories(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $categories = [
            // Traditional
            'Cash Payment',
            'Check Payment',
            'Money Order',
            
            // Electronic
            'ACH Transfer',
            'Wire Transfer',
            'Electronic Check',
            
            // Card-based
            $this->creditCard,
            'Debit Card',
            'Prepaid Card',
            
            // Mobile
            'Mobile Payment',
            'QR Code Payment',
            'NFC Payment',
            
            // Alternative
            'Buy Now Pay Later',
            'Installment Payment',
            'Subscription Payment'
        ];
        
        foreach ($categories as $category) {
            $paymentMethod->setName($category);
            $this->assertSame($category, $paymentMethod->getName());
        }
    }

    public function testPaymentTerminology(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $terminology = [
            'Payment Gateway',
            'Payment Processor',
            'Merchant Account',
            'Payment Service Provider',
            'Acquiring Bank',
            'Issuing Bank',
            'Payment Network',
            'Card Association',
            'Payment Facilitator',
            'Third Party Processor'
        ];
        
        foreach ($terminology as $term) {
            $paymentMethod->setName($term);
            $this->assertSame($term, $paymentMethod->getName());
        }
    }

    public function testRegionalPaymentMethods(): void
    {
        $paymentMethod = new PaymentMethod();
        
        // European methods
        $paymentMethod->setName('SEPA Direct Debit');
        $this->assertSame('SEPA Direct Debit', $paymentMethod->getName());
        
        // Asian methods
        $paymentMethod->setName('UnionPay');
        $this->assertSame('UnionPay', $paymentMethod->getName());
        
        // Latin American methods
        $paymentMethod->setName('PIX (Brazil)');
        $this->assertSame('PIX (Brazil)', $paymentMethod->getName());
        
        // Middle Eastern methods
        $paymentMethod->setName('KNET (Kuwait)');
        $this->assertSame('KNET (Kuwait)', $paymentMethod->getName());
    }

    public function testIndustrySpecificMethods(): void
    {
        $paymentMethod = new PaymentMethod();
        
        // Healthcare
        $paymentMethod->setName('HSA Card');
        $this->assertSame('HSA Card', $paymentMethod->getName());
        
        // Government
        $paymentMethod->setName('Purchase Card (P-Card)');
        $this->assertSame('Purchase Card (P-Card)', $paymentMethod->getName());
        
        // Education
        $paymentMethod->setName('Student Account');
        $this->assertSame('Student Account', $paymentMethod->getName());
        
        // Travel
        $paymentMethod->setName('Travel Card');
        $this->assertSame('Travel Card', $paymentMethod->getName());
    }

    public function testStatusManagement(): void
    {
        $paymentMethod = new PaymentMethod();
        
        // Active method
        $paymentMethod->setName('Current Method');
        $paymentMethod->setActive(true);
        $this->assertTrue($paymentMethod->getActive());
        
        // Temporarily disabled
        $paymentMethod->setActive(false);
        $this->assertFalse($paymentMethod->getActive());
        
        // Re-enabled
        $paymentMethod->setActive(true);
        $this->assertTrue($paymentMethod->getActive());
    }

    public function testPaymentMethodDescriptiveNames(): void
    {
        $paymentMethod = new PaymentMethod();
        
        $descriptiveNames = [
            'Secure Credit Card Processing',
            'Real-time Bank Transfer',
            'Instant Digital Wallet Payment',
            'Contactless Card Payment',
            'Recurring Subscription Payment',
            'One-time Bank Draft',
            'Express Checkout',
            'Guest Payment (No Account)',
            'Saved Payment Method',
            'Multi-currency Payment'
        ];
        
        foreach ($descriptiveNames as $name) {
            $paymentMethod->setName($name);
            $this->assertSame($name, $paymentMethod->getName());
        }
    }

    public function testEdgeCaseNames(): void
    {
        $paymentMethod = new PaymentMethod();
        
        // Single character
        $paymentMethod->setName('X');
        $this->assertSame('X', $paymentMethod->getName());
        
        // Numbers only
        $paymentMethod->setName('123456');
        $this->assertSame('123456', $paymentMethod->getName());
        
        // Special characters only
        $paymentMethod->setName('***');
        $this->assertSame('***', $paymentMethod->getName());
        
        // Mixed content
        $paymentMethod->setName('Method-2024_v1.0');
        $this->assertSame('Method-2024_v1.0', $paymentMethod->getName());
    }

    public function testPaymentMethodWithSpaces(): void
    {
        $paymentMethod = new PaymentMethod();
        
        // Leading/trailing spaces
        $paymentMethod->setName(' Credit Card ');
        $this->assertSame(' Credit Card ', $paymentMethod->getName());
        
        // Multiple spaces
        $paymentMethod->setName('Bank    Transfer');
        $this->assertSame('Bank    Transfer', $paymentMethod->getName());
        
        // Tab characters
        $paymentMethod->setName("Payment\tMethod");
        $this->assertSame("Payment\tMethod", $paymentMethod->getName());
    }

    public function testConstructorParameterCombinations(): void
    {
        // Only ID
        $method1 = new PaymentMethod(id: 1);
        $this->assertSame('1', $method1->getId());
        $this->assertSame('', $method1->getName());
        $this->assertTrue($method1->getActive());
        
        // ID and name
        $method2 = new PaymentMethod(id: 2, name: 'Test');
        $this->assertSame('2', $method2->getId());
        $this->assertSame('Test', $method2->getName());
        $this->assertTrue($method2->getActive());
        
        // ID and active status
        $method3 = new PaymentMethod(id: 3, active: false);
        $this->assertSame('3', $method3->getId());
        $this->assertSame('', $method3->getName());
        $this->assertFalse($method3->getActive());
        
        // Name and active status
        $method4 = new PaymentMethod(name: $this->testMethod, active: false);
        $this->assertSame('', $method4->getId());
        $this->assertSame($this->testMethod, $method4->getName());
        $this->assertFalse($method4->getActive());
    }
}