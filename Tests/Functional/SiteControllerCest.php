<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class SiteControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        // Setup before each test if needed
    }

    public function testIndexPage(FunctionalTester $I): void
    {
        $I->wantTo('see the home page loads');
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
        $I->seeInSource('Home');
    }

    public function testAboutPage(FunctionalTester $I): void
    {
        $I->wantTo('see the about page loads');
        $I->amOnPage('/about');
        $I->seeResponseCodeIs(200);
    }

    public function testAccreditationsPage(FunctionalTester $I): void
    {
        $I->wantTo('see the accreditations page loads');
        $I->amOnPage('/accreditations');
        $I->seeResponseCodeIs(200);
    }

    public function testGalleryPage(FunctionalTester $I): void
    {
        $I->wantTo('see the gallery page loads');
        $I->amOnPage('/gallery');
        $I->seeResponseCodeIs(200);
    }

    public function testTeamPage(FunctionalTester $I): void
    {
        $I->wantTo('see the team page loads');
        $I->amOnPage('/team');
        $I->seeResponseCodeIs(200);
    }

    public function testPricingPage(FunctionalTester $I): void
    {
        $I->wantTo('see the pricing page loads');
        $I->amOnPage('/pricing');
        $I->seeResponseCodeIs(200);
    }

    public function testPrivacyPolicyPage(FunctionalTester $I): void
    {
        $I->wantTo('see the privacy policy page loads');
        $I->amOnPage('/privacypolicy');
        $I->seeResponseCodeIs(200);
    }

    public function testTermsOfServicePage(FunctionalTester $I): void
    {
        $I->wantTo('see the terms of service page loads');
        $I->amOnPage('/termsofservice');
        $I->seeResponseCodeIs(200);
    }

    public function testTestimonialPage(FunctionalTester $I): void
    {
        $I->wantTo('see the testimonial page loads');
        $I->amOnPage('/testimonial');
        $I->seeResponseCodeIs(200);
    }

    public function testContactPage(FunctionalTester $I): void
    {
        $I->wantTo('see the contact page loads');
        $I->amOnPage('/contact');
        $I->seeResponseCodeIs(200);
    }

    public function testOauth2AuthErrorPage(FunctionalTester $I): void
    {
        $I->wantTo('see the oauth2 auth error page loads');
        $I->amOnPage('/oauth2autherror/testmessage');
        $I->seeResponseCodeIs(200);
    }

    public function testOauth2CallbackUnauthorisedPage(FunctionalTester $I): void
    {
        $I->wantTo('see the oauth2 callback unauthorised page loads');
        $I->amOnPage('/oauth2callbackresultunauthorised');
        $I->seeResponseCodeIs(200);
    }

    public function testUserCancelledOauth2Page(FunctionalTester $I): void
    {
        $I->wantTo('see the user cancelled oauth2 page loads');
        $I->amOnPage('/usercancelledoauth2');
        $I->seeResponseCodeIs(200);
    }

    public function testAdminMustMakeActivePage(FunctionalTester $I): void
    {
        $I->wantTo('see the admin must make active page loads');
        $I->amOnPage('/adminmustmakeactive');
        $I->seeResponseCodeIs(200);
    }

    public function testForgotAlertPage(FunctionalTester $I): void
    {
        $I->wantTo('see the forgot alert page loads');
        $I->amOnPage('/forgotalert');
        $I->seeResponseCodeIs(200);
    }

    public function testForgotEmailFailedPage(FunctionalTester $I): void
    {
        $I->wantTo('see the forgot email failed page loads');
        $I->amOnPage('/forgotemailfailed');
        $I->seeResponseCodeIs(200);
    }

    public function testForgotUserNotFoundPage(FunctionalTester $I): void
    {
        $I->wantTo('see the forgot user not found page loads');
        $I->amOnPage('/forgotusernotfound');
        $I->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordErrorPage(FunctionalTester $I): void
    {
        $I->wantTo('see the one time password error page loads');
        $I->amOnPage('/onetimepassworderror');
        $I->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordFailurePage(FunctionalTester $I): void
    {
        $I->wantTo('see the one time password failure page loads');
        $I->amOnPage('/onetimepasswordfailure');
        $I->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordSuccessPage(FunctionalTester $I): void
    {
        $I->wantTo('see the one time password success page loads');
        $I->amOnPage('/onetimepasswordsuccess');
        $I->seeResponseCodeIs(200);
    }

    public function testResetPasswordFailedPage(FunctionalTester $I): void
    {
        $I->wantTo('see the reset password failed page loads');
        $I->amOnPage('/resetpasswordfailed');
        $I->seeResponseCodeIs(200);
    }

    public function testResetPasswordSuccessPage(FunctionalTester $I): void
    {
        $I->wantTo('see the reset password success page loads');
        $I->amOnPage('/resetpasswordsuccess');
        $I->seeResponseCodeIs(200);
    }

    public function testSignupFailedPage(FunctionalTester $I): void
    {
        $I->wantTo('see the signup failed page loads');
        $I->amOnPage('/signupfailed');
        $I->seeResponseCodeIs(200);
    }

    public function testSignupSuccessPage(FunctionalTester $I): void
    {
        $I->wantTo('see the signup success page loads');
        $I->amOnPage('/signupsuccess');
        $I->seeResponseCodeIs(200);
    }
}