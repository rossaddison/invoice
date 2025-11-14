s<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class SiteControllerCest
{
    public function testIndexPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the home page loads');
        $functionalTester->amOnPage('/');
        $functionalTester->seeResponseCodeIs(200);
        $functionalTester->seeInSource('Home');
    }

    public function testAboutPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the about page loads');
        $functionalTester->amOnPage('/about');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testAccreditationsPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the accreditations page loads');
        $functionalTester->amOnPage('/accreditations');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testGalleryPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the gallery page loads');
        $functionalTester->amOnPage('/gallery');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testTeamPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the team page loads');
        $functionalTester->amOnPage('/team');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testPricingPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the pricing page loads');
        $functionalTester->amOnPage('/pricing');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testPrivacyPolicyPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the privacy policy page loads');
        $functionalTester->amOnPage('/privacypolicy');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testTermsOfServicePage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the terms of service page loads');
        $functionalTester->amOnPage('/termsofservice');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testTestimonialPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the testimonial page loads');
        $functionalTester->amOnPage('/testimonial');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testContactPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the contact page loads');
        $functionalTester->amOnPage('/contact');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testOauth2AuthErrorPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the oauth2 auth error page loads');
        $functionalTester->amOnPage('/oauth2autherror/testmessage');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testOauth2CallbackUnauthorisedPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the oauth2 callback unauthorised page loads');
        $functionalTester->amOnPage('/oauth2callbackresultunauthorised');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testUserCancelledOauth2Page(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the user cancelled oauth2 page loads');
        $functionalTester->amOnPage('/usercancelledoauth2');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testAdminMustMakeActivePage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the admin must make active page loads');
        $functionalTester->amOnPage('/adminmustmakeactive');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testForgotAlertPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the forgot alert page loads');
        $functionalTester->amOnPage('/forgotalert');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testForgotEmailFailedPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the forgot email failed page loads');
        $functionalTester->amOnPage('/forgotemailfailed');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testForgotUserNotFoundPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the forgot user not found page loads');
        $functionalTester->amOnPage('/forgotusernotfound');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordErrorPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the one time password error page loads');
        $functionalTester->amOnPage('/onetimepassworderror');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordFailurePage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the one time password failure page loads');
        $functionalTester->amOnPage('/onetimepasswordfailure');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordSuccessPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the one time password success page loads');
        $functionalTester->amOnPage('/onetimepasswordsuccess');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testResetPasswordFailedPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the reset password failed page loads');
        $functionalTester->amOnPage('/resetpasswordfailed');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testResetPasswordSuccessPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the reset password success page loads');
        $functionalTester->amOnPage('/resetpasswordsuccess');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testSignupFailedPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the signup failed page loads');
        $functionalTester->amOnPage('/signupfailed');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testSignupSuccessPage(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the signup success page loads');
        $functionalTester->amOnPage('/signupsuccess');
        $functionalTester->seeResponseCodeIs(200);
    }
}
