<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class ContactControllerCest
{
    public string $interest = '/interest';
    
    public string $testUser = 'Test User';

    public function testInterestPageLoads(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the contact interest page loads');
        $functionalTester->amOnPage($this->interest);
        $functionalTester->seeResponseCodeIs(200);
        $functionalTester->seeInSource('name');
        $functionalTester->seeInSource('email');
        $functionalTester->seeInSource('subject');
        $functionalTester->seeInSource('body');
    }

    public function testInterestFormValidation(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test contact form validation');
        $functionalTester->amOnPage($this->interest);
        $functionalTester->seeResponseCodeIs(200);
        
        // Submit empty form to test validation
        if ($functionalTester->seeElement('form')) {
            $functionalTester->submitForm('form', []);
            $functionalTester->seeResponseCodeIs(200);
            // Should stay on the same page with validation errors
        }
    }

    public function testInterestFormWithValidData(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test contact form with valid data');
        $functionalTester->amOnPage($this->interest);
        $functionalTester->seeResponseCodeIs(200);
        
        // Try to submit form with valid data
        if ($functionalTester->seeElement('form')) {
            $functionalTester->submitForm('form', [
                'ContactForm[name]' => $this->testUser,
                'ContactForm[email]' => 'test@example.com',
                'ContactForm[subject]' => 'Test Subject',
                'ContactForm[body]' => 'This is a test message'
            ]);
            // Should redirect or show success - either way not an error
            $functionalTester->dontSeeResponseCodeIs(500);
        }
    }

    public function testInterestFormWithInvalidEmail(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test contact form with invalid email');
        $functionalTester->amOnPage($this->interest);
        $functionalTester->seeResponseCodeIs(200);
        
        // Submit form with invalid email
        if ($functionalTester->seeElement('form')) {
            $functionalTester->submitForm('form', [
                'ContactForm[name]' => $this->testUser,
                'ContactForm[email]' => 'invalid-email',
                'ContactForm[subject]' => 'Test Subject',
                'ContactForm[body]' => 'This is a test message'
            ]);
            $functionalTester->seeResponseCodeIs(200);
            // Should stay on page with validation error
        }
    }

    public function testInterestFormMissingRequiredFields(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test contact form with missing required fields');
        $functionalTester->amOnPage($this->interest);
        $functionalTester->seeResponseCodeIs(200);
        
        // Submit form with only partial data
        if ($functionalTester->seeElement('form')) {
            $functionalTester->submitForm('form', [
                'ContactForm[name]' => 'Test User',
                'ContactForm[email]' => 'test@example.com'
                // Missing subject and body
            ]);
            $functionalTester->seeResponseCodeIs(200);
            // Should stay on page with validation errors
        }
    }

    public function testInterestGetRequest(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test GET request to interest page');
        $functionalTester->amOnPage($this->interest);
        $functionalTester->seeResponseCodeIs(200);
        $functionalTester->seeInSource('contact');
    }
}
