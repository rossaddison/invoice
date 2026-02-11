<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\Client;
use App\Invoice\Inv\InvForm;
use PHPUnit\Framework\TestCase;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use DateTimeImmutable;

/**
 * Unit tests for InvForm validation rules
 * Focuses on form validation logic and field constraints
 */
final class InvFormTest extends TestCase
{
    private ValidatorInterface $validator;
    private Inv $inv;

    protected function setUp(): void
    {
        $this->validator = new Validator();
        $this->inv = $this->createMockInv();
    }

    /**
     * Test that longText fields (terms, note) accept unlimited content
     * These fields should NOT have Length validation constraints
     */
    public function testLongTextFieldsAcceptUnlimitedContent(): void
    {
        // Create very long content (over 65535 characters)
        $longContent = str_repeat('This is a very long text content. ', 2000); // ~70,000 chars
        
        // Set longText fields to very long content
        $form = $this->createFormWithData([
            'terms' => $longContent,
            'note' => $longContent,
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        
        // Should be valid - no length constraints on longText fields
        $this->assertTrue($result->isValid(), 'LongText fields should accept unlimited content');
    }

    /**
     * Test that text fields have appropriate length constraints
     */
    public function testTextFieldsHaveProperLengthConstraints(): void
    {
        // Test number field (max 100 chars)
        $form = $this->createFormWithData([
            'number' => str_repeat('1', 101), // 101 chars - should fail
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Number field should fail validation with 101 characters');
        
        // Test valid number field
        $form = $this->createFormWithData([
            'number' => str_repeat('1', 100), // 100 chars - should pass
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Number field should pass validation with 100 characters');
    }

    /**
     * Test stand_in_code field has 3 character limit
     */
    public function testStandInCodeMaxLength(): void
    {
        // Test with 4 characters (should fail)
        $form = $this->createFormWithData([
            'stand_in_code' => 'ABCD', // 4 chars - should fail
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'stand_in_code should fail with 4 characters');
        
        // Test with 3 characters (should pass)
        $form = $this->createFormWithData([
            'stand_in_code' => 'ABC', // 3 chars - should pass
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'stand_in_code should pass with 3 characters');
    }

    /**
     * Test url_key field has 32 character limit
     */
    public function testUrlKeyMaxLength(): void
    {
        // Test with 33 characters (should fail)
        $form = $this->createFormWithData([
            'url_key' => str_repeat('a', 33), // 33 chars - should fail
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'url_key should fail with 33 characters');
        
        // Test with 32 characters (should pass)
        $form = $this->createFormWithData([
            'url_key' => str_repeat('a', 32), // 32 chars - should pass
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'url_key should pass with 32 characters');
    }

    /**
     * Test password field has 90 character limit
     */
    public function testPasswordMaxLength(): void
    {
        // Test with 91 characters (should fail)
        $form = $this->createFormWithData([
            'password' => str_repeat('p', 91), // 91 chars - should fail
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'password should fail with 91 characters');
        
        // Test with 90 characters (should pass)
        $form = $this->createFormWithData([
            'password' => str_repeat('p', 90), // 90 chars - should pass
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'password should pass with 90 characters');
    }

    /**
     * Test document_description field has 32 character limit
     */
    public function testDocumentDescriptionMaxLength(): void
    {
        // Test with 33 characters (should fail)
        $form = $this->createFormWithData([
            'document_description' => str_repeat('d', 33), // 33 chars - should fail
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'document_description should fail with 33 characters');
        
        // Test with 32 characters (should pass)
        $form = $this->createFormWithData([
            'document_description' => str_repeat('d', 32), // 32 chars - should pass
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'document_description should pass with 32 characters');
    }

    /**
     * Test required fields validation
     */
    public function testRequiredFields(): void
    {
        // Test without required client_id
        $form = $this->createFormWithData([
            'group_id' => '1',
            'client_id' => '' // empty required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required client_id');
        
        // Test without required group_id
        $form = $this->createFormWithData([
            'client_id' => '1',
            'group_id' => '' // empty required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required group_id');
        
        // Test with both required fields
        $form = $this->createFormWithData([
            'client_id' => '1',
            'group_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Should pass validation with all required fields');
    }

    /**
     * Test that empty values are allowed for optional fields with skipOnEmpty: true
     */
    public function testSkipOnEmptyFields(): void
    {
        $form = $this->createFormWithData([
            'client_id' => '1',
            'group_id' => '1',
            'number' => '', // Empty but should be allowed
            'stand_in_code' => '', // Empty but should be allowed
            'url_key' => '', // Empty but should be allowed
            'password' => '', // Empty but should be allowed
            'document_description' => '', // Empty but should be allowed
            'terms' => '', // Empty longText field
            'note' => '' // Empty longText field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Empty optional fields should pass validation');
    }

    /**
     * Test form initialization with Inv entity
     */
    public function testFormInitializationFromEntity(): void
    {
        $form = new InvForm($this->inv);
        
        $this->assertEquals('1', $form->getId());
        $this->assertEquals('INV-001', $form->getNumber());
        $this->assertEquals('Test terms', $form->getTerms());
        $this->assertEquals('Test note', $form->getNote());
        $this->assertEquals('1', $form->getClient_id());
        $this->assertEquals('1', $form->getGroup_id());
    }

    /**
     * Create a mock Inv entity for testing
     */
    private function createMockInv(): Inv
    {
        $inv = $this->createMock(Inv::class);
        $client = $this->createMock(Client::class);
        $now = new DateTimeImmutable();
        
        $inv->method('getId')->willReturn('1');
        $inv->method('getNumber')->willReturn('INV-001');
        $inv->method('getTerms')->willReturn('Test terms');
        $inv->method('getNote')->willReturn('Test note');
        $inv->method('getClient_id')->willReturn('1');
        $inv->method('getGroup_id')->willReturn('1');
        $inv->method('getStatus_id')->willReturn(1);
        $inv->method('getDate_created')->willReturn($now);
        $inv->method('getDate_modified')->willReturn($now);
        $inv->method('getDate_supplied')->willReturn($now);
        $inv->method('getDate_tax_point')->willReturn($now);
        $inv->method('getDate_due')->willReturn($now);
        $inv->method('getTime_created')->willReturn($now);
        $inv->method('getStand_in_code')->willReturn('ABC');
        $inv->method('getQuote_id')->willReturn('1');
        $inv->method('getSo_id')->willReturn('1');
        $inv->method('getCreditinvoice_parent_id')->willReturn('0');
        $inv->method('getDelivery_id')->willReturn('0');
        $inv->method('getDelivery_location_id')->willReturn('0');
        $inv->method('getPostal_address_id')->willReturn('0');
        $inv->method('getContract_id')->willReturn('0');
        $inv->method('getDiscount_amount')->willReturn(0.00);
        $inv->method('getUrl_key')->willReturn('test-key');
        $inv->method('getPassword')->willReturn('password');
        $inv->method('getPayment_method')->willReturn(0);
        $inv->method('getDocumentDescription')->willReturn('Test description');
        $inv->method('getIs_read_only')->willReturn(false);
        $inv->method('getClient')->willReturn($client);
        
        return $inv;
    }

    /**
     * Create InvForm with custom data for testing
     */
    private function createFormWithData(array $data): InvForm
    {
        $inv = $this->createMockInv();
        $form = new InvForm($inv);
        
        // Use reflection to set properties for testing
        $reflection = new \ReflectionClass($form);
        
        foreach ($data as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue($form, $value);
            }
        }
        
        return $form;
    }
}
