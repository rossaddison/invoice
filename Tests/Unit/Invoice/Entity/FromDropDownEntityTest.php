<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\FromDropDown;
use PHPUnit\Framework\TestCase;

class FromDropDownEntityTest extends TestCase
{
    public string $userExampleCom = 'user@example.com';
    
    public string $testExampleCom = 'test@example.com';
    
    public string $adminCompanyCom = 'admin@company.com';
    
    public string $supportCompanyCom = 'support@company.com';
    
    public string $infoCompanyCom = 'info@company.com';
    
    public string $customerServiceCom = 'customerservice@company.com';
    
    public string $primaryCompanyCom = 'primary@company.com';
    
    public string $ceoCompanyCom = 'ceo@company.com';
    
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
        $fromDropDown = new FromDropDown(
            id: 1,
            email: 'user@example.com',
            include: true,
            default_email: true
        );
        
        $this->assertSame('1', $fromDropDown->getId());
        $this->assertSame('user@example.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
    }

    public function testConstructorWithPartialParameters(): void
    {
        $fromDropDown = new FromDropDown(
            id: 2,
            email: $this->testExampleCom
        );
        
        $this->assertSame('2', $fromDropDown->getId());
        $this->assertSame($this->testExampleCom, $fromDropDown->getEmail());
        $this->assertFalse($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testIdSetterAndGetter(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setId(50);
        
        $this->assertSame('50', $fromDropDown->getId());
    }

    public function testEmailSetterAndGetter(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setEmail($this->adminCompanyCom);
        
        $this->assertSame($this->adminCompanyCom, $fromDropDown->getEmail());
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

    public function testIdTypeConversion(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setId(999);
        
        $this->assertIsString($fromDropDown->getId());
        $this->assertSame('999', $fromDropDown->getId());
    }

    public function testZeroId(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setId(0);
        
        $this->assertSame('0', $fromDropDown->getId());
    }

    public function testNegativeId(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setId(-1);
        
        $this->assertSame('-1', $fromDropDown->getId());
    }

    public function testLargeId(): void
    {
        $fromDropDown = new FromDropDown();
        $largeId = PHP_INT_MAX;
        
        $fromDropDown->setId($largeId);
        $this->assertSame((string)$largeId, $fromDropDown->getId());
    }

    public function testEmptyEmail(): void
    {
        $fromDropDown = new FromDropDown();
        $fromDropDown->setEmail('');
        
        $this->assertSame('', $fromDropDown->getEmail());
    }

    public function testValidEmailAddresses(): void
    {
        $fromDropDown = new FromDropDown();
        
        $validEmails = [
            'user@example.com',
            'admin@company.co.uk',
            'support@subdomain.example.org',
            'info@123-company.com',
            'contact@company-name.net',
            'sales@example.info',
            'service@my-company.biz',
            'hello@startup.io',
            'team@organization.gov',
            'help@nonprofit.org'
        ];
        
        foreach ($validEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testBusinessEmailAddresses(): void
    {
        $fromDropDown = new FromDropDown();
        
        $businessEmails = [
            'sales@company.com',
            $this->supportCompanyCom,
            $this->infoCompanyCom,
            $this->adminCompanyCom,
            'billing@company.com',
            'accounts@company.com',
            'hr@company.com',
            'marketing@company.com',
            'legal@company.com',
            'it@company.com',
            'finance@company.com',
            'operations@company.com',
            $this->customerServiceCom,
            'procurement@company.com'
        ];
        
        foreach ($businessEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testPersonalEmailAddresses(): void
    {
        $fromDropDown = new FromDropDown();
        
        $personalEmails = [
            'john.doe@gmail.com',
            'jane.smith@yahoo.com',
            'bob.wilson@outlook.com',
            'alice.johnson@hotmail.com',
            'david.brown@icloud.com',
            'sarah.davis@protonmail.com',
            'mike.jones@aol.com',
            'lisa.miller@mail.com',
            'tom.anderson@yandex.com',
            'amy.taylor@zoho.com'
        ];
        
        foreach ($personalEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testInternationalEmailAddresses(): void
    {
        $fromDropDown = new FromDropDown();
        
        $internationalEmails = [
            'contact@company.co.uk',
            'info@empresa.es',
            'support@société.fr',
            'admin@firma.de',
            'service@azienda.it',
            'hello@bedrijf.nl',
            'team@företag.se',
            'help@virksomhed.dk',
            'info@selskap.no',
            'contact@公司.cn'
        ];
        
        foreach ($internationalEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testSpecialCharacterEmails(): void
    {
        $fromDropDown = new FromDropDown();
        
        $specialEmails = [
            'user+tag@example.com',
            'user.name@example.com',
            'user_name@example.com',
            'user-name@example.com',
            'user123@example.com',
            '123user@example.com',
            'a@example.com',
            'very.long.username@very.long.domain.com',
            'test.email+tag+sorting@example.com',
            'user@sub.domain.example.com'
        ];
        
        foreach ($specialEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testBooleanFlagCombinations(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Both false (default)
        $fromDropDown->setInclude(false);
        $fromDropDown->setDefault_email(false);
        $this->assertFalse($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
        
        // Include true, default false
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(false);
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
        
        // Include false, default true
        $fromDropDown->setInclude(false);
        $fromDropDown->setDefault_email(true);
        $this->assertFalse($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
        
        // Both true
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
    }

    public function testDefaultEmailScenarios(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Primary company email as default
        $fromDropDown->setEmail($this->infoCompanyCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame($this->infoCompanyCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
        
        // Secondary email not default
        $fromDropDown->setEmail($this->supportCompanyCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame($this->supportCompanyCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testIncludeExcludeScenarios(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Active email included in dropdown
        $fromDropDown->setEmail('active@company.com');
        $fromDropDown->setInclude(true);
        
        $this->assertSame('active@company.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        
        // Inactive email excluded from dropdown
        $fromDropDown->setEmail('inactive@company.com');
        $fromDropDown->setInclude(false);
        
        $this->assertSame('inactive@company.com', $fromDropDown->getEmail());
        $this->assertFalse($fromDropDown->getInclude());
    }

    public function testCompleteFromDropDownSetup(): void
    {
        $fromDropDown = new FromDropDown();
        
        $fromDropDown->setId(1);
        $fromDropDown->setEmail($this->primaryCompanyCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame('1', $fromDropDown->getId());
        $this->assertSame($this->primaryCompanyCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
    }

    public function testMethodReturnTypes(): void
    {
        $fromDropDown = new FromDropDown(
            id: 1,
            email: $this->testExampleCom,
            include: true,
            default_email: false
        );
        
        $this->assertIsString($fromDropDown->getId());
        $this->assertIsString($fromDropDown->getEmail());
        $this->assertIsBool($fromDropDown->getInclude());
        $this->assertIsBool($fromDropDown->getDefault_email());
    }

    public function testEntityStateConsistency(): void
    {
        $fromDropDown = new FromDropDown(
            id: 999,
            email: 'initial@example.com',
            include: true,
            default_email: false
        );
        
        // Verify initial state
        $this->assertSame('999', $fromDropDown->getId());
        $this->assertSame('initial@example.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
        
        // Modify all properties
        $fromDropDown->setId(111);
        $fromDropDown->setEmail('modified@example.com');
        $fromDropDown->setInclude(false);
        $fromDropDown->setDefault_email(true);
        
        // Verify changes
        $this->assertSame('111', $fromDropDown->getId());
        $this->assertSame('modified@example.com', $fromDropDown->getEmail());
        $this->assertFalse($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
    }

    public function testDepartmentEmails(): void
    {
        $fromDropDown = new FromDropDown();
        
        $departmentEmails = [
            ['sales@company.com', true, false],
            ['marketing@company.com', true, false],
            [$this->supportCompanyCom, true, false],
            [$this->infoCompanyCom, true, true], // Default
            [$this->adminCompanyCom, true, false],
            ['hr@company.com', false, false], // Not included
            ['legal@company.com', false, false], // Not included
            ['finance@company.com', true, false],
            ['operations@company.com', true, false],
            ['it@company.com', false, false] // Not included
        ];
        
        foreach ($departmentEmails as [$email, $include, $default]) {
            $fromDropDown->setEmail($email);
            $fromDropDown->setInclude($include);
            $fromDropDown->setDefault_email($default);
            
            $this->assertSame($email, $fromDropDown->getEmail());
            $this->assertSame($include, $fromDropDown->getInclude());
            $this->assertSame($default, $fromDropDown->getDefault_email());
        }
    }

    public function testEmailWithDifferentDomains(): void
    {
        $fromDropDown = new FromDropDown();
        
        $domainEmails = [
            'contact@startup.com',
            'info@enterprise.net',
            'support@service.org',
            'admin@government.gov',
            'team@nonprofit.org',
            'hello@tech.io',
            'sales@business.co',
            'service@local.us',
            'help@international.eu',
            'info@mobile.app'
        ];
        
        foreach ($domainEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testRoleBasedEmails(): void
    {
        $fromDropDown = new FromDropDown();
        
        $roleEmails = [
            $this->ceoCompanyCom,
            'cto@company.com',
            'cfo@company.com',
            'vp-sales@company.com',
            'director@company.com',
            'manager@company.com',
            'coordinator@company.com',
            'specialist@company.com',
            'assistant@company.com',
            'intern@company.com'
        ];
        
        foreach ($roleEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testEmailConfiguration(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Configure primary email
        $fromDropDown->setId(1);
        $fromDropDown->setEmail($this->primaryCompanyCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame('1', $fromDropDown->getId());
        $this->assertSame($this->primaryCompanyCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
        
        // Reconfigure as secondary email
        $fromDropDown->setEmail('secondary@company.com');
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame('secondary@company.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testLongEmailAddresses(): void
    {
        $fromDropDown = new FromDropDown();
        
        $longEmails = [
            'very.long.email.address.with.many.dots@very.long.domain.name.example.com',
            'extremely.long.username.with.many.characters@subdomain.company.organization.com',
            'user.with.very.long.name.and.surname@company.with.long.name.co.uk',
            'department.team.role.location.project@enterprise.corporation.international.org'
        ];
        
        foreach ($longEmails as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testBusinessScenarios(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Customer service email - included, not default
        $fromDropDown->setEmail($this->customerServiceCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame($this->customerServiceCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
        
        // Main company email - included and default
        $fromDropDown->setEmail($this->infoCompanyCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame($this->infoCompanyCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
        
        // Internal email - not included
        $fromDropDown->setEmail('internal@company.com');
        $fromDropDown->setInclude(false);
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame('internal@company.com', $fromDropDown->getEmail());
        $this->assertFalse($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testEmailValidationPatterns(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Note: The entity doesn't validate email format, it just stores the string
        $emailPatterns = [
            // Standard formats
            'user@domain.com',
            'user.name@domain.com',
            'user+tag@domain.com',
            'user_name@domain.com',
            'user-name@domain.com',
            
            // Different TLDs
            'user@domain.org',
            'user@domain.net',
            'user@domain.edu',
            'user@domain.gov',
            
            // Subdomains
            'user@mail.domain.com',
            'user@subdomain.domain.co.uk',
            
            // International
            'user@domain.co.uk',
            'user@domain.com.au',
            'user@domain.de'
        ];
        
        foreach ($emailPatterns as $email) {
            $fromDropDown->setEmail($email);
            $this->assertSame($email, $fromDropDown->getEmail());
        }
    }

    public function testConstructorParameterCombinations(): void
    {
        // Only ID
        $dropdown1 = new FromDropDown(id: 1);
        $this->assertSame('1', $dropdown1->getId());
        $this->assertSame('', $dropdown1->getEmail());
        $this->assertFalse($dropdown1->getInclude());
        $this->assertFalse($dropdown1->getDefault_email());
        
        // ID and email
        $dropdown2 = new FromDropDown(id: 2, email: $this->testExampleCom);
        $this->assertSame('2', $dropdown2->getId());
        $this->assertSame($this->testExampleCom, $dropdown2->getEmail());
        $this->assertFalse($dropdown2->getInclude());
        $this->assertFalse($dropdown2->getDefault_email());
        
        // ID, email, and include
        $dropdown3 = new FromDropDown(id: 3, email: $this->testExampleCom, include: true);
        $this->assertSame('3', $dropdown3->getId());
        $this->assertSame($this->testExampleCom, $dropdown3->getEmail());
        $this->assertTrue($dropdown3->getInclude());
        $this->assertFalse($dropdown3->getDefault_email());
        
        // Email and flags only
        $dropdown4 = new FromDropDown(email: $this->testExampleCom, include: true, default_email: true);
        $this->assertSame('', $dropdown4->getId());
        $this->assertSame($this->testExampleCom, $dropdown4->getEmail());
        $this->assertTrue($dropdown4->getInclude());
        $this->assertTrue($dropdown4->getDefault_email());
    }

    public function testDropdownManagement(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Add to dropdown
        $fromDropDown->setEmail('new@company.com');
        $fromDropDown->setInclude(true);
        $this->assertTrue($fromDropDown->getInclude());
        
        // Remove from dropdown
        $fromDropDown->setInclude(false);
        $this->assertFalse($fromDropDown->getInclude());
        
        // Set as default
        $fromDropDown->setDefault_email(true);
        $this->assertTrue($fromDropDown->getDefault_email());
        
        // Unset as default
        $fromDropDown->setDefault_email(false);
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testCompanyEmailHierarchy(): void
    {
        $fromDropDown = new FromDropDown();
        
        // CEO email - included but not default (for privacy)
        $fromDropDown->setEmail($this->ceoCompanyCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame($this->ceoCompanyCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
        
        // General info email - included and default
        $fromDropDown->setEmail($this->infoCompanyCom);
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame($this->infoCompanyCom, $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
        
        // No-reply email - not included
        $fromDropDown->setEmail('noreply@company.com');
        $fromDropDown->setInclude(false);
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame('noreply@company.com', $fromDropDown->getEmail());
        $this->assertFalse($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }

    public function testRealWorldEmailConfiguration(): void
    {
        $fromDropDown = new FromDropDown();
        
        // Configure main contact email
        $fromDropDown->setId(1);
        $fromDropDown->setEmail('contact@acme-corp.com');
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(true);
        
        $this->assertSame('1', $fromDropDown->getId());
        $this->assertSame('contact@acme-corp.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertTrue($fromDropDown->getDefault_email());
        
        // Configure sales team email
        $fromDropDown->setId(2);
        $fromDropDown->setEmail('sales-team@acme-corp.com');
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame('2', $fromDropDown->getId());
        $this->assertSame('sales-team@acme-corp.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
        
        // Configure support email
        $fromDropDown->setId(3);
        $fromDropDown->setEmail('support@acme-corp.com');
        $fromDropDown->setInclude(true);
        $fromDropDown->setDefault_email(false);
        
        $this->assertSame('3', $fromDropDown->getId());
        $this->assertSame('support@acme-corp.com', $fromDropDown->getEmail());
        $this->assertTrue($fromDropDown->getInclude());
        $this->assertFalse($fromDropDown->getDefault_email());
    }
}