<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contact\ContactForm;
use Codeception\Test\Unit;

class ContactFormTest extends Unit
{
    private ContactForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new ContactForm();
    }

    public function testFormName(): void
    {
        $this->assertEquals('ContactForm', $this->form->getFormName());
    }

    public function testPropertyLabels(): void
    {
        $labels = $this->form->getPropertyLabels();
        $this->assertIsArray($labels);
        $this->assertEquals('Name', $labels['name']);
        $this->assertEquals('Email', $labels['email']);
        $this->assertEquals('Subject', $labels['subject']);
        $this->assertEquals('Body', $labels['body']);
    }

    public function testValidationRules(): void
    {
        $rules = $this->form->getRules();
        $this->assertIsArray($rules);
        
        // All fields should be required
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('subject', $rules);
        $this->assertArrayHasKey('body', $rules);
        
        // Email field should have Email validator
        $emailRules = $rules['email'];
        $this->assertCount(2, $emailRules); // Required + Email
    }

    public function testFormStructure(): void
    {
        // Test that the form has the expected structure
        $this->assertInstanceOf(ContactForm::class, $this->form);
        $this->assertEquals('ContactForm', $this->form->getFormName());
    }

    public function testRequiredFieldsStructure(): void
    {
        $rules = $this->form->getRules();
        
        // Check that all required fields have rules
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('subject', $rules);
        $this->assertArrayHasKey('body', $rules);
        
        // Verify the structure of validation rules
        $this->assertIsArray($rules['name']);
        $this->assertIsArray($rules['email']);
        $this->assertIsArray($rules['subject']);
        $this->assertIsArray($rules['body']);
    }

    public function testEmailFieldHasEmailValidator(): void
    {
        $rules = $this->form->getRules();
        $emailRules = $rules['email'];
        
        // Email field should have at least 2 validators (Required + Email)
        $this->assertGreaterThanOrEqual(2, count($emailRules));
        
        // Check that Email validator is present
        $hasEmailValidator = false;
        foreach ($emailRules as $rule) {
            if ($rule instanceof \Yiisoft\Validator\Rule\Email) {
                $hasEmailValidator = true;
                break;
            }
        }
        $this->assertTrue($hasEmailValidator);
    }

    public function testAllFieldsHaveRequiredValidator(): void
    {
        $rules = $this->form->getRules();
        $requiredFields = ['name', 'email', 'subject', 'body'];
        
        foreach ($requiredFields as $field) {
            $fieldRules = $rules[$field];
            $hasRequiredValidator = false;
            
            foreach ($fieldRules as $rule) {
                if ($rule instanceof \Yiisoft\Validator\Rule\Required) {
                    $hasRequiredValidator = true;
                    break;
                }
            }
            
            $this->assertTrue($hasRequiredValidator, "Field '$field' should have Required validator");
        }
    }

    public function testFormLabelsMatchFields(): void
    {
        $labels = $this->form->getPropertyLabels();
        $rules = $this->form->getRules();
        
        // All fields with rules should have labels
        foreach (array_keys($rules) as $field) {
            $this->assertArrayHasKey($field, $labels, "Field '$field' should have a label");
            $this->assertNotEmpty($labels[$field], "Label for field '$field' should not be empty");
        }
    }

    public function testFormHasPropertyTranslator(): void
    {
        $translator = $this->form->getPropertyTranslator();
        $this->assertNotNull($translator);
        $this->assertInstanceOf(\Yiisoft\Validator\PropertyTranslatorInterface::class, $translator);
    }

    public function testPropertyTranslator(): void
    {
        $translator = $this->form->getPropertyTranslator();
        $this->assertNotNull($translator);
    }
}
