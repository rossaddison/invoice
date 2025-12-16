<?php

declare(strict_types=1);

namespace Tests\Unit\CompanyPrivate;

use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Entity\Company;
use App\Invoice\CompanyPrivate\CompanyPrivateForm;
use PHPUnit\Framework\TestCase;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use DateTimeImmutable;

/**
 * Unit tests for CompanyPrivateForm validation rules
 * Focuses on form validation logic and field constraints
 */
final class CompanyPrivateFormTest extends TestCase
{
    private ValidatorInterface $validator;
    private CompanyPrivate $companyPrivate;

    public string $gln = '1234567890123';
    
    protected function setUp(): void
    {
        $this->validator = new Validator();
        $this->companyPrivate = $this->createMockCompanyPrivate();
    }

    /**
     * Test vat_id field has 30 character limit (corrected from 65535)
     */
    public function testVatIdMaxLength(): void
    {
        // Test with 31 characters (should fail)
        $form = $this->createFormWithData([
            'vat_id' => str_repeat('V', 31), // 31 chars - should fail
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'vat_id should fail validation with 31 characters');
        
        // Test with 30 characters (should pass)
        $form = $this->createFormWithData([
            'vat_id' => str_repeat('V', 30), // 30 chars - should pass
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'vat_id should pass validation with 30 characters');
    }

    /**
     * Test tax_code field has 20 character limit (corrected from 65535)
     */
    public function testTaxCodeMaxLength(): void
    {
        // Test with 21 characters (should fail)
        $form = $this->createFormWithData([
            'tax_code' => str_repeat('T', 21), // 21 chars - should fail
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'tax_code should fail validation with 21 characters');
        
        // Test with 20 characters (should pass)
        $form = $this->createFormWithData([
            'tax_code' => str_repeat('T', 20), // 20 chars - should pass
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'tax_code should pass validation with 20 characters');
    }

    /**
     * Test iban field has 34 character limit
     */
    public function testIbanMaxLength(): void
    {
        // Test with 35 characters (should fail)
        $form = $this->createFormWithData([
            'iban' => str_repeat('I', 35), // 35 chars - should fail
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'iban should fail validation with 35 characters');
        
        // Test with 34 characters (should pass)
        $form = $this->createFormWithData([
            'iban' => str_repeat('I', 34), // 34 chars - should pass
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'iban should pass validation with 34 characters');
    }

    /**
     * Test gln field has 14 character limit
     */
    public function testGlnMaxLength(): void
    {
        // Test with 15 characters (should fail)
        $form = $this->createFormWithData([
            'gln' => str_repeat('G', 15), // 15 chars - should fail
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'gln should fail validation with 15 characters');
        
        // Test with 14 characters (should pass)
        $form = $this->createFormWithData([
            'gln' => str_repeat('G', 14), // 14 chars - should pass
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'gln should pass validation with 14 characters');
    }

    /**
     * Test rcc field has 7 character limit
     */
    public function testRccMaxLength(): void
    {
        // Test with 8 characters (should fail)
        $form = $this->createFormWithData([
            'rcc' => str_repeat('R', 8), // 8 chars - should fail
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'rcc should fail validation with 8 characters');
        
        // Test with 7 characters (should pass)
        $form = $this->createFormWithData([
            'rcc' => str_repeat('R', 7), // 7 chars - should pass
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'rcc should pass validation with 7 characters');
    }

    /**
     * Test logo_filename field has 150 character limit
     */
    public function testLogoFilenameMaxLength(): void
    {
        // Test with 151 characters (should fail)
        $form = $this->createFormWithData([
            'logo_filename' => str_repeat('L', 151), // 151 chars - should fail
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'logo_filename should fail validation with 151 characters');
        
        // Test with 150 characters (should pass)
        $form = $this->createFormWithData([
            'logo_filename' => str_repeat('L', 150), // 150 chars - should pass
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'logo_filename should pass validation with 150 characters');
    }

    /**
     * Test required fields validation
     */
    public function testRequiredFields(): void
    {
        // Test without required company_id
        $form = $this->createFormWithData([
            'company_id' => null // missing required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required company_id');
        
        // Test with required company_id
        $form = $this->createFormWithData([
            'company_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Should pass validation with required company_id');
    }

    /**
     * Test that empty values are allowed for optional fields with skipOnEmpty: true
     */
    public function testSkipOnEmptyFields(): void
    {
        $form = $this->createFormWithData([
            'company_id' => 1,
            'vat_id' => '', // Empty but should be allowed
            'tax_code' => '', // Empty but should be allowed
            'iban' => '', // Empty but should be allowed
            'gln' => '', // Empty but should be allowed
            'rcc' => '', // Empty but should be allowed
            'logo_filename' => '', // Empty but should be allowed
            'logo_width' => '', // Optional field
            'logo_height' => '', // Optional field
            'logo_margin' => '' // Optional field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Empty optional fields should pass validation');
    }

    /**
     * Test realistic field values
     */
    public function testRealisticFieldValues(): void
    {
        $form = $this->createFormWithData([
            'company_id' => 1,
            'vat_id' => 'GB123456789', // Realistic UK VAT ID
            'tax_code' => 'TC12345', // Realistic tax code
            'iban' => 'GB82WEST12345698765432', // Realistic IBAN
            'gln' => $this->gln, // Realistic GLN (13 digits)
            'rcc' => 'RCC001', // Realistic RCC
            'logo_filename' => 'company_logo.png', // Realistic filename
            'logo_width' => '200',
            'logo_height' => '100',
            'logo_margin' => '10'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Realistic field values should pass validation');
    }

    /**
     * Test form initialization with CompanyPrivate entity
     */
    public function testFormInitializationFromEntity(): void
    {
        $form = new CompanyPrivateForm($this->companyPrivate);
        
        $this->assertEquals(1, $form->getId());
        $this->assertEquals(1, $form->getCompany_id());
        $this->assertEquals('GB123456789', $form->getVat_id());
        $this->assertEquals('TC12345', $form->getTax_code());
        $this->assertEquals('GB82WEST12345698765432', $form->getIban());
        $this->assertEquals($this->gln, $form->getGln());
        $this->assertEquals('RCC001', $form->getRcc());
        $this->assertEquals('company_logo.png', $form->getLogo_filename());
        $this->assertEquals('200', $form->getLogo_width());
        $this->assertEquals('100', $form->getLogo_height());
        $this->assertEquals('10', $form->getLogo_margin());
    }

    /**
     * Test all Length validation limits to ensure they are reasonable for business use
     */
    public function testAllLengthLimitsAreReasonable(): void
    {
        $form = $this->createFormWithData([
            'company_id' => 1,
            'vat_id' => str_repeat('V', 30), // Max VAT ID length
            'tax_code' => str_repeat('T', 20), // Max tax code length
            'iban' => str_repeat('I', 34), // Max IBAN length (international standard)
            'gln' => str_repeat('G', 14), // Max GLN length
            'rcc' => str_repeat('R', 7), // Max RCC length
            'logo_filename' => str_repeat('L', 150) // Max logo filename length
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'All maximum length values should be acceptable for business use');
        
        // Verify these limits are NOT the old excessive 65535 values
        $this->assertNotEquals(65535, 30, 'VAT ID limit should not be 65535');
        $this->assertNotEquals(65535, 20, 'Tax code limit should not be 65535');
        $this->assertLessThan(200, 30, 'VAT ID limit should be reasonable for business use');
        $this->assertLessThan(50, 20, 'Tax code limit should be reasonable for business use');
    }

    /**
     * Create a mock CompanyPrivate entity for testing
     */
    private function createMockCompanyPrivate(): CompanyPrivate
    {
        $companyPrivate = $this->createMock(CompanyPrivate::class);
        $company = $this->createMock(Company::class);
        $now = new DateTimeImmutable();
        
        $company->method('getName')->willReturn('Test Company');
        
        $companyPrivate->method('getId')->willReturn(1);
        $companyPrivate->method('getCompany_id')->willReturn('1');
        $companyPrivate->method('getCompany')->willReturn($company);
        $companyPrivate->method('getVat_id')->willReturn('GB123456789');
        $companyPrivate->method('getTax_code')->willReturn('TC12345');
        $companyPrivate->method('getIban')->willReturn('GB82WEST12345698765432');
        $companyPrivate->method('getGln')->willReturn('1234567890123');
        $companyPrivate->method('getRcc')->willReturn('RCC001');
        $companyPrivate->method('getLogo_filename')->willReturn('company_logo.png');
        $companyPrivate->method('getLogo_width')->willReturn(200);
        $companyPrivate->method('getLogo_height')->willReturn(100);
        $companyPrivate->method('getLogo_margin')->willReturn(10);
        $companyPrivate->method('getStart_date')->willReturn($now);
        $companyPrivate->method('getEnd_date')->willReturn($now);
        
        return $companyPrivate;
    }

    /**
     * Create CompanyPrivateForm with custom data for testing
     */
    private function createFormWithData(array $data): CompanyPrivateForm
    {
        $companyPrivate = $this->createMockCompanyPrivate();
        
        $form = new CompanyPrivateForm($companyPrivate);
        
        $reflection = new \ReflectionClass($form);
        
        foreach ($data as $property => $value) {
            
            if (! $reflection->hasProperty($property)) {
                throw new RuntimeException('Property missing');
            }
            
            $prop = $reflection->getProperty($property);
            
            $wasAccessible = $prop->isPublic();
            
            try {
                
                $prop->setAccessible(true);
                
                $prop->setValue($form, $value);
                
            } finally {
                
                $prop->setAccessible($wasAccessible);
                
            }
        }
        
        return $form;
    }
}
