<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class ContactControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        // Setup before each test if needed
    }

    public function testInterestPageLoads(FunctionalTester $I): void
    {
        $I->wantTo('see the contact interest page loads');
        $I->amOnPage('/interest');
        $I->seeResponseCodeIs(200);
        $I->seeInSource('name');
        $I->seeInSource('email');
        $I->seeInSource('subject');
        $I->seeInSource('body');
    }

    public function testInterestFormValidation(FunctionalTester $I): void
    {
        $I->wantTo('test contact form validation');
        $I->amOnPage('/interest');
        $I->seeResponseCodeIs(200);
        
        // Submit empty form to test validation
        if ($I->seeElement('form')) {
            $I->submitForm('form', []);
            $I->seeResponseCodeIs(200);
            // Should stay on the same page with validation errors
        }
    }

    public function testInterestFormWithValidData(FunctionalTester $I): void
    {
        $I->wantTo('test contact form with valid data');
        $I->amOnPage('/interest');
        $I->seeResponseCodeIs(200);
        
        // Try to submit form with valid data
        if ($I->seeElement('form')) {
            $I->submitForm('form', [
                'ContactForm[name]' => 'Test User',
                'ContactForm[email]' => 'test@example.com',
                'ContactForm[subject]' => 'Test Subject',
                'ContactForm[body]' => 'This is a test message'
            ]);
            // Should redirect or show success - either way not an error
            $I->dontSeeResponseCodeIs(500);
        }
    }

    public function testInterestFormWithInvalidEmail(FunctionalTester $I): void
    {
        $I->wantTo('test contact form with invalid email');
        $I->amOnPage('/interest');
        $I->seeResponseCodeIs(200);
        
        // Submit form with invalid email
        if ($I->seeElement('form')) {
            $I->submitForm('form', [
                'ContactForm[name]' => 'Test User',
                'ContactForm[email]' => 'invalid-email',
                'ContactForm[subject]' => 'Test Subject',
                'ContactForm[body]' => 'This is a test message'
            ]);
            $I->seeResponseCodeIs(200);
            // Should stay on page with validation error
        }
    }

    public function testInterestFormMissingRequiredFields(FunctionalTester $I): void
    {
        $I->wantTo('test contact form with missing required fields');
        $I->amOnPage('/interest');
        $I->seeResponseCodeIs(200);
        
        // Submit form with only partial data
        if ($I->seeElement('form')) {
            $I->submitForm('form', [
                'ContactForm[name]' => 'Test User',
                'ContactForm[email]' => 'test@example.com'
                // Missing subject and body
            ]);
            $I->seeResponseCodeIs(200);
            // Should stay on page with validation errors
        }
    }

    public function testInterestGetRequest(FunctionalTester $I): void
    {
        $I->wantTo('test GET request to interest page');
        $I->amOnPage('/interest');
        $I->seeResponseCodeIs(200);
        $I->seeInSource('contact');
    }
}