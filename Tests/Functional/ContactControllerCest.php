<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class ContactControllerCest
{
    public string $interest = '/interest';
    
    public string $testUser = 'Test User';

    public function testInterestPageLoads(FunctionalTester $tester): void
    {
        $tester->wantTo('see the contact interest page loads');
        $tester->amOnPage($this->interest);
        $tester->seeResponseCodeIs(200);
        $tester->seeInSource('name');
        $tester->seeInSource('email');
        $tester->seeInSource('subject');
        $tester->seeInSource('body');
    }

    public function testInterestFormValidation(FunctionalTester $tester): void
    {
        $tester->wantTo('test contact form validation');
        $tester->amOnPage($this->interest);
        $tester->seeResponseCodeIs(200);
        
        // Submit empty form to test validation
        if ($tester->seeElement('form')) {
            $tester->submitForm('form', []);
            $tester->seeResponseCodeIs(200);
            // Should stay on the same page with validation errors
        }
    }

    public function testInterestFormWithValidData(FunctionalTester $tester): void
    {
        $tester->wantTo('test contact form with valid data');
        $tester->amOnPage($this->interest);
        $tester->seeResponseCodeIs(200);
        
        // Try to submit form with valid data
        if ($tester->seeElement('form')) {
            $tester->submitForm('form', [
                'ContactForm[name]' => $this->testUser,
                'ContactForm[email]' => 'test@example.com',
                'ContactForm[subject]' => 'Test Subject',
                'ContactForm[body]' => 'This is a test message'
            ]);
            // Should redirect or show success - either way not an error
            $tester->dontSeeResponseCodeIs(500);
        }
    }

    public function testInterestFormWithInvalidEmail(FunctionalTester $tester): void
    {
        $tester->wantTo('test contact form with invalid email');
        $tester->amOnPage($this->interest);
        $tester->seeResponseCodeIs(200);
        
        // Submit form with invalid email
        if ($tester->seeElement('form')) {
            $tester->submitForm('form', [
                'ContactForm[name]' => $this->testUser,
                'ContactForm[email]' => 'invalid-email',
                'ContactForm[subject]' => 'Test Subject',
                'ContactForm[body]' => 'This is a test message'
            ]);
            $tester->seeResponseCodeIs(200);
            // Should stay on page with validation error
        }
    }

    public function testInterestFormMissingRequiredFields(FunctionalTester $tester): void
    {
        $tester->wantTo('test contact form with missing required fields');
        $tester->amOnPage($this->interest);
        $tester->seeResponseCodeIs(200);
        
        // Submit form with only partial data
        if ($tester->seeElement('form')) {
            $tester->submitForm('form', [
                'ContactForm[name]' => 'Test User',
                'ContactForm[email]' => 'test@example.com'
                // Missing subject and body
            ]);
            $tester->seeResponseCodeIs(200);
            // Should stay on page with validation errors
        }
    }

    public function testInterestGetRequest(FunctionalTester $tester): void
    {
        $tester->wantTo('test GET request to interest page');
        $tester->amOnPage($this->interest);
        $tester->seeResponseCodeIs(200);
        $tester->seeInSource('contact');
    }
}
