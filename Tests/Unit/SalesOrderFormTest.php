<?php

declare(strict_types=1);

namespace Tests\Unit\SalesOrder;

use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\Group;
use App\Invoice\SalesOrder\SalesOrderForm;
use App\User\User;
use PHPUnit\Framework\TestCase;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use DateTimeImmutable;

/**
 * Unit tests for SalesOrderForm validation rules
 * Focuses on form validation logic and field constraints
 */
final class SalesOrderFormTest extends TestCase
{
    private ValidatorInterface $validator;
    private SalesOrder $salesOrder;

    protected function setUp(): void
    {
        $this->validator = new Validator();
        $this->salesOrder = $this->createMockSalesOrder();
    }

    /**
     * Test that longText fields (notes, payment_term) accept unlimited content
     * These fields should NOT have Length validation constraints
     */
    public function testLongTextFieldsAcceptUnlimitedContent(): void
    {
        // Create very long content (over 65535 characters)
        $longContent = str_repeat('This is a very long sales order content. ', 2000); // ~86,000 chars
        
        $form = $this->createFormWithData([
            'notes' => $longContent,
            'payment_term' => $longContent,
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        
        // Should be valid - no length constraints on longText fields
        $this->assertTrue($result->isValid(), 'LongText fields (notes, payment_term) should accept unlimited content');
    }

    /**
     * Test that text fields have appropriate length constraints
     */
    public function testTextFieldsHaveProperLengthConstraints(): void
    {
        // Test number field (max 100 chars)
        $form = $this->createFormWithData([
            'number' => str_repeat('1', 101), // 101 chars - should fail
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Number field should fail validation with 101 characters');
        
        // Test valid number field
        $form = $this->createFormWithData([
            'number' => str_repeat('1', 100), // 100 chars - should pass
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Number field should pass validation with 100 characters');
    }

    /**
     * Test client PO fields have 100 character limits
     */
    public function testClientPoFieldsMaxLength(): void
    {
        // Test client_po_number (max 100 chars)
        $form = $this->createFormWithData([
            'client_po_number' => str_repeat('P', 101), // 101 chars - should fail
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'client_po_number should fail with 101 characters');
        
        // Test valid client PO fields
        $form = $this->createFormWithData([
            'client_po_number' => str_repeat('P', 100), // 100 chars - should pass
            'client_po_line_number' => str_repeat('L', 100), // 100 chars - should pass
            'client_po_person' => str_repeat('N', 100), // 100 chars - should pass
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Client PO fields should pass with 100 characters');
    }

    /**
     * Test url_key field has 32 character limit
     */
    public function testUrlKeyMaxLength(): void
    {
        // Test with 33 characters (should fail)
        $form = $this->createFormWithData([
            'url_key' => str_repeat('a', 33), // 33 chars - should fail
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'url_key should fail with 33 characters');
        
        // Test with 32 characters (should pass)
        $form = $this->createFormWithData([
            'url_key' => str_repeat('a', 32), // 32 chars - should pass
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
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
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'password should fail with 91 characters');
        
        // Test with 90 characters (should pass)
        $form = $this->createFormWithData([
            'password' => str_repeat('p', 90), // 90 chars - should pass
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'password should pass with 90 characters');
    }

    /**
     * Test required fields validation
     */
    public function testRequiredFields(): void
    {
        // Test without required client_id
        $form = $this->createFormWithData([
            'group_id' => 1,
            'quote_id' => '1',
            'client_id' => null // missing required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required client_id');
        
        // Test without required group_id
        $form = $this->createFormWithData([
            'client_id' => 1,
            'quote_id' => '1',
            'group_id' => null // missing required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required group_id');
        
        // Test without required quote_id
        $form = $this->createFormWithData([
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => null // missing required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required quote_id');
        
        // Test with all required fields
        $form = $this->createFormWithData([
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Should pass validation with all required fields');
    }

    /**
     * Test numeric field validation constraints
     */
    public function testNumericFieldValidation(): void
    {
        // Test status_id within valid range (1-9)
        $form = $this->createFormWithData([
            'status_id' => 5, // Valid range
            'discount_amount' => 999.99, // Valid amount
            'discount_percent' => 25.5, // Valid percentage
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Numeric fields should pass with valid values');
        
        // Test status_id outside valid range
        $form = $this->createFormWithData([
            'status_id' => 10, // Outside valid range (1-9)
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'status_id should fail validation outside range 1-9');
        
        // Test invalid discount_percent (over 100)
        $form = $this->createFormWithData([
            'discount_percent' => 101.0, // Over 100%
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'discount_percent should fail validation over 100%');
    }

    /**
     * Test that empty values are allowed for optional fields with skipOnEmpty: true
     */
    public function testSkipOnEmptyFields(): void
    {
        $form = $this->createFormWithData([
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1',
            'number' => '', // Empty but should be allowed
            'client_po_number' => '', // Empty but should be allowed
            'client_po_line_number' => '', // Empty but should be allowed
            'client_po_person' => '', // Empty but should be allowed
            'url_key' => '', // Empty but should be allowed
            'password' => '', // Empty but should be allowed
            'notes' => '', // Empty longText field
            'payment_term' => '' // Empty longText field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Empty optional fields should pass validation');
    }

    /**
     * Test form initialization with SalesOrder entity
     */
    public function testFormInitializationFromEntity(): void
    {
        $form = new SalesOrderForm($this->salesOrder);
        
        $this->assertEquals('SO-001', $form->getNumber());
        $this->assertEquals('Test sales order notes', $form->getNotes());
        $this->assertEquals('Net 30 days', $form->getPaymentTerm());
        $this->assertEquals(1, $form->getClient_id());
        $this->assertEquals(1, $form->getGroup_id());
        $this->assertEquals('1', $form->getQuote_id());
        $this->assertEquals('PO12345', $form->getClient_po_number());
    }

    /**
     * Test that longText fields preserve special content exactly
     */
    public function testLongTextFieldsPreserveContent(): void
    {
        $specialContent = "Line 1\nLine 2\n\nLine 4 with special chars: @#$%^&*()";
        
        $form = $this->createFormWithData([
            'notes' => $specialContent,
            'payment_term' => $specialContent,
            'client_id' => 1,
            'group_id' => 1,
            'quote_id' => '1'
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'LongText fields should preserve special content');
        $this->assertEquals($specialContent, $form->getNotes(), 'Notes content should be preserved exactly');
        $this->assertEquals($specialContent, $form->getPaymentTerm(), 'Payment term content should be preserved exactly');
    }

    /**
     * Create a mock SalesOrder entity for testing
     */
    private function createMockSalesOrder(): SalesOrder
    {
        $salesOrder = $this->createMock(SalesOrder::class);
        $client = $this->createMock(Client::class);
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);
        $now = new DateTimeImmutable();
        
        $salesOrder->method('getNumber')->willReturn('SO-001');
        $salesOrder->method('getDate_created')->willReturn($now);
        $salesOrder->method('getQuote_id')->willReturn('1');
        $salesOrder->method('getInv_id')->willReturn('1');
        $salesOrder->method('getGroup_id')->willReturn('1');
        $salesOrder->method('getClient_id')->willReturn('1');
        $salesOrder->method('getClient_po_number')->willReturn('PO12345');
        $salesOrder->method('getClient_po_line_number')->willReturn('LN001');
        $salesOrder->method('getClient_po_person')->willReturn('John Doe');
        $salesOrder->method('getStatus_id')->willReturn(1);
        $salesOrder->method('getDiscount_amount')->willReturn(50.0);
        $salesOrder->method('getDiscount_percent')->willReturn(5.0);
        $salesOrder->method('getUrl_key')->willReturn('test-so-key');
        $salesOrder->method('getPassword')->willReturn('so-password');
        $salesOrder->method('getNotes')->willReturn('Test sales order notes');
        $salesOrder->method('getPaymentTerm')->willReturn('Net 30 days');
        $salesOrder->method('getClient')->willReturn($client);
        $salesOrder->method('getGroup')->willReturn($group);
        $salesOrder->method('getUser')->willReturn($user);
        
        return $salesOrder;
    }

    /**
     * Create SalesOrderForm with custom data for testing
     */
    private function createFormWithData(array $data): SalesOrderForm
    {
        $salesOrder = $this->createMockSalesOrder();
        $form = new SalesOrderForm($salesOrder);
        
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