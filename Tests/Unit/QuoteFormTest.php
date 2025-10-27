<?php

declare(strict_types=1);

namespace Tests\Unit\Quote;

use App\Invoice\Entity\Quote;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\Group;
use App\Invoice\Quote\QuoteForm;
use App\User\User;
use PHPUnit\Framework\TestCase;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use DateTimeImmutable;

/**
 * Unit tests for QuoteForm validation rules
 * Focuses on form validation logic and field constraints
 */
final class QuoteFormTest extends TestCase
{
    private ValidatorInterface $validator;
    private Quote $quote;

    protected function setUp(): void
    {
        $this->validator = new Validator();
        $this->quote = $this->createMockQuote();
    }

    /**
     * Test that notes field (longText) accepts unlimited content
     * This field should NOT have Length validation constraints
     */
    public function testNotesFieldAcceptsUnlimitedContent(): void
    {
        // Create very long content (over 65535 characters)
        $longContent = str_repeat('This is a very long quote notes content. ', 2000); // ~84,000 chars
        
        $form = $this->createFormWithData([
            'notes' => $longContent,
            'client_id' => 1,
            'group_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        
        // Should be valid - no length constraints on longText notes field
        $this->assertTrue($result->isValid(), 'Notes field (longText) should accept unlimited content');
    }

    /**
     * Test that all string fields in QuoteForm accept reasonable content
     * Since no Length validation is defined, all should pass
     */
    public function testStringFieldsAcceptContent(): void
    {
        $form = $this->createFormWithData([
            'number' => 'QUOTE-' . str_repeat('1', 100), // Long quote number
            'url_key' => str_repeat('a', 100), // Long URL key  
            'password' => str_repeat('p', 100), // Long password
            'notes' => str_repeat('Note content. ', 1000), // Very long notes
            'client_id' => 1,
            'group_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'All string fields should accept content without Length validation');
    }

    /**
     * Test required fields validation
     */
    public function testRequiredFields(): void
    {
        // Test without required client_id
        $form = $this->createFormWithData([
            'group_id' => 1,
            'client_id' => null // missing required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required client_id');
        
        // Test without required group_id
        $form = $this->createFormWithData([
            'client_id' => 1,
            'group_id' => null // missing required field
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertFalse($result->isValid(), 'Should fail validation without required group_id');
        
        // Test with both required fields
        $form = $this->createFormWithData([
            'client_id' => 1,
            'group_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Should pass validation with all required fields');
    }

    /**
     * Test that empty values are allowed for optional fields
     */
    public function testOptionalFieldsAllowEmpty(): void
    {
        $form = $this->createFormWithData([
            'client_id' => 1,
            'group_id' => 1,
            'number' => '', // Empty optional field
            'url_key' => '', // Empty optional field
            'password' => '', // Empty optional field
            'notes' => '', // Empty longText field
            'discount_amount' => 0.0,
            'discount_percent' => 0.0
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Empty optional fields should pass validation');
    }

    /**
     * Test numeric field validation
     */
    public function testNumericFields(): void
    {
        $form = $this->createFormWithData([
            'client_id' => 1,
            'group_id' => 1,
            'status_id' => 2,
            'discount_amount' => 123.45,
            'discount_percent' => 15.5,
            'delivery_location_id' => 5
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Numeric fields should accept valid numbers');
    }

    /**
     * Test form initialization with Quote entity
     */
    public function testFormInitializationFromEntity(): void
    {
        $form = new QuoteForm($this->quote);
        
        $this->assertEquals('QUOTE-001', $form->getNumber());
        $this->assertEquals('Test quote notes', $form->getNotes());
        $this->assertEquals(1, $form->getClient_id());
        $this->assertEquals(1, $form->getGroup_id());
        $this->assertEquals(1, $form->getStatus_id());
        $this->assertEquals(100.50, $form->getDiscount_amount());
        $this->assertEquals(10.0, $form->getDiscount_percent());
        $this->assertEquals('test-quote-key', $form->getUrl_key());
        $this->assertEquals('quote-password', $form->getPassword());
    }

    /**
     * Test getters return correct types
     */
    public function testGettersReturnCorrectTypes(): void
    {
        $form = new QuoteForm($this->quote);
        
        $this->assertIsString($form->getNumber());
        $this->assertIsString($form->getNotes());
        $this->assertIsInt($form->getClient_id());
        $this->assertIsInt($form->getGroup_id());
        $this->assertIsInt($form->getStatus_id());
        $this->assertIsFloat($form->getDiscount_amount());
        $this->assertIsFloat($form->getDiscount_percent());
        $this->assertIsString($form->getUrl_key());
        $this->assertIsString($form->getPassword());
    }

    /**
     * Test that notes field preserves content exactly
     */
    public function testNotesFieldPreservesContent(): void
    {
        $specialContent = "Line 1\nLine 2\n\nLine 4 with special chars: @#$%^&*()";
        
        $form = $this->createFormWithData([
            'notes' => $specialContent,
            'client_id' => 1,
            'group_id' => 1
        ]);
        
        $result = $this->validator->validate($form);
        $this->assertTrue($result->isValid(), 'Notes field should preserve special content');
        $this->assertEquals($specialContent, $form->getNotes(), 'Notes content should be preserved exactly');
    }

    /**
     * Create a mock Quote entity for testing
     */
    private function createMockQuote(): Quote
    {
        $quote = $this->createMock(Quote::class);
        $client = $this->createMock(Client::class);
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);
        $now = new DateTimeImmutable();
        
        $quote->method('getNumber')->willReturn('QUOTE-001');
        $quote->method('getDate_created')->willReturn($now);
        $quote->method('getInv_id')->willReturn('1');
        $quote->method('getSo_id')->willReturn('1');
        $quote->method('getGroup_id')->willReturn('1');
        $quote->method('getClient_id')->willReturn('1');
        $quote->method('getStatus_id')->willReturn(1);
        $quote->method('getDiscount_amount')->willReturn(100.50);
        $quote->method('getDiscount_percent')->willReturn(10.0);
        $quote->method('getUrl_key')->willReturn('test-quote-key');
        $quote->method('getPassword')->willReturn('quote-password');
        $quote->method('getNotes')->willReturn('Test quote notes');
        $quote->method('getDelivery_location_id')->willReturn('5');
        $quote->method('getClient')->willReturn($client);
        $quote->method('getGroup')->willReturn($group);
        $quote->method('getUser')->willReturn($user);
        
        return $quote;
    }

    /**
     * Create QuoteForm with custom data for testing
     */
    private function createFormWithData(array $data): QuoteForm
    {
        $quote = $this->createMockQuote();
        $form = new QuoteForm($quote);
        
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