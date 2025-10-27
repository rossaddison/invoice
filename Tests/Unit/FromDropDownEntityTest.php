<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\FromDropDown;
use Codeception\Test\Unit;

final class FromDropDownEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $fromDropDown = new FromDropDown();
        
        $this->assertSame('', $fromDropDown->getId());
        $this->assertSame('', $fromDropDown->getEmail());
        $this->assertFalse($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testConstructorWithAllParameters(): void
    {
        $fromDropDown = new FromDropDown(1, 'test@example.com', true, true);
        
        $this->assertSame('1', $fromDropDown->getId());
        $this->assertSame('test@example.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
    }

    public function testIdSetterAndGetter(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setId(42);
        
        $this->assertSame('42', $fromDropDown->getId());
    }

    public function testEmailSetterAndGetter(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setEmail('admin@company.com');
        
        $this->assertSame('admin@company.com', $fromDropDown->getEmail());
    }

    public function testIncludeSetterAndGetter(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setInclude(true);
        
        $this->assertTrue($fromDropDown->getInclude());
        
        $fromDropDown->setInclude(false);
        $this->assertFalse($fromDropDown->getInclude());
    }

    public function testDefaultEmailSetterAndGetter(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setDefault_email(true);
        
        $this->assertTrue($fromDropDown->getDefault_email());
        
        $fromDropDown->setDefault_email(false);
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testCommonEmailFormats(): void
    {
        $personal = new FromDropDown(1, 'john.doe@gmail.com', true, false);
        $this->assertSame('john.doe@gmail.com', $personal->getEmail());
        $this->assertTrue($personal->getInclude());
        $this->assertFalse($personal->getDefault_email());

        $business = new FromDropDown(2, 'admin@company.co.uk', true, true);
        $this->assertSame('admin@company.co.uk', $business->getEmail());
        $this->assertTrue($business->getInclude());
        $this->assertTrue($business->getDefault_email());

        $noreply = new FromDropDown(3, 'noreply@system.org', false, false);
        $this->assertSame('noreply@system.org', $noreply->getEmail());
        $this->assertFalse($noreply->getInclude());
        $this->assertFalse($noreply->getDefault_email());
    }

    public function testLongEmailAddresses(): void
    {
        $longEmail = 'very.long.email.address.with.many.dots@very.long.domain.name.example.com';
        $fromDropDown = new FromDropDown(1, $longEmail, true, false);
        
        $this->assertSame($longEmail, $fromDropDown->getEmail());
    }

    public function testSpecialCharactersInEmail(): void
    {
        $specialEmail = 'test+tag@sub-domain.example-site.com';
        $fromDropDown = new FromDropDown(1, $specialEmail, true, false);
        
        $this->assertSame($specialEmail, $fromDropDown->getEmail());
    }

    public function testUnicodeInEmail(): void
    {
        $unicodeEmail = 'tëst@éxample.côm';
        $fromDropDown = new FromDropDown(1, $unicodeEmail, true, false);
        
        $this->assertSame($unicodeEmail, $fromDropDown->getEmail());
    }

    public function testZeroAndLargeIds(): void
    {
        $zeroId = new FromDropDown(0, 'zero@test.com', false, false);
        $this->assertSame('0', $zeroId->getId());

        $largeId = new FromDropDown(999999, 'large@test.com', false, false);
        $this->assertSame('999999', $largeId->getId());
    }

    public function testChainedSetterCalls(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setId(100);
        $fromDropDown->setEmail('chained@test.com');
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame('100', $fromDropDown->getId());
        $this->assertSame('chained@test.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
    }

    public function testIdStringConversion(): void
    {
        $fromDropDown = new FromDropDown(123, 'test@example.com', false, false);
        
        // Verify ID getter returns string even though setter accepts int
        $this->assertIsString($fromDropDown->getId());
        $this->assertSame('123', $fromDropDown->getId());
    }

    public function testBooleanProperties(): void
    {
        $fromDropDown = new FromDropDown(1, 'test@example.com', true, false);
        
        // Verify boolean properties return actual booleans
        $this->assertIsBool($fromDropDown->getInclude());
        $this->assertIsBool($fromDropDown->getDefault_email());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testCompleteFromDropDownSetup(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setId(999);
        $fromDropDown->setEmail('complete@setup.com');
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame('999', $fromDropDown->getId());
        $this->assertSame('complete@setup.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
    }

    public function testEmptyEmailHandling(): void
    {
        $fromDropDown = new FromDropDown(1, '', false, false);
        
        $this->assertSame('', $fromDropDown->getEmail());
    }

    public function testBooleanToggling(): void
    {
        $fromDropDown = new FromDropDown(1, 'toggle@test.com', false, false);
        
        // Test include toggling
        $this->assertFalse($fromDropDown->getInclude());
        $fromDropDown->setInclude(true);
        $this->assertTrue($fromDropDown->getInclude());
        $fromDropDown->setInclude(false);
        $this->assertFalse($fromDropDown->getInclude());
        
        // Test default_email toggling
        $this->assertFalse($fromDropDown->getDefault_email());
        $fromDropDown->setDefault_email(true);
        $this->assertTrue($fromDropDown->getDefault_email());
        $fromDropDown->setDefault_email(false);
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testEmailConfigurationScenarios(): void
    {
        // Scenario 1: Default email that's included
        $defaultIncluded = new FromDropDown(1, 'default@company.com', true, true);
        $this->assertTrue($defaultIncluded->getInclude());
        $this->assertTrue($defaultIncluded->getDefault_email());
        
        // Scenario 2: Non-default email that's included
        $nonDefaultIncluded = new FromDropDown(2, 'support@company.com', true, false);
        $this->assertTrue($nonDefaultIncluded->getInclude());
        $this->assertFalse($nonDefaultIncluded->getDefault_email());
        
        // Scenario 3: Email that's not included
        $notIncluded = new FromDropDown(3, 'disabled@company.com', false, false);
        $this->assertFalse($notIncluded->getInclude());
        $this->assertFalse($notIncluded->getDefault_email());
    }
}