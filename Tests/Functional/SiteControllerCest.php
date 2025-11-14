s<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class SiteControllerCest
{
    public function testIndexPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the home page loads');
        $tester->amOnPage('/');
        $tester->seeResponseCodeIs(200);
        $tester->seeInSource('Home');
    }

    public function testAboutPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the about page loads');
        $tester->amOnPage('/about');
        $tester->seeResponseCodeIs(200);
    }

    public function testAccreditationsPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the accreditations page loads');
        $tester->amOnPage('/accreditations');
        $tester->seeResponseCodeIs(200);
    }

    public function testGalleryPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the gallery page loads');
        $tester->amOnPage('/gallery');
        $tester->seeResponseCodeIs(200);
    }

    public function testTeamPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the team page loads');
        $tester->amOnPage('/team');
        $tester->seeResponseCodeIs(200);
    }

    public function testPricingPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the pricing page loads');
        $tester->amOnPage('/pricing');
        $tester->seeResponseCodeIs(200);
    }

    public function testPrivacyPolicyPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the privacy policy page loads');
        $tester->amOnPage('/privacypolicy');
        $tester->seeResponseCodeIs(200);
    }

    public function testTermsOfServicePage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the terms of service page loads');
        $tester->amOnPage('/termsofservice');
        $tester->seeResponseCodeIs(200);
    }

    public function testTestimonialPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the testimonial page loads');
        $tester->amOnPage('/testimonial');
        $tester->seeResponseCodeIs(200);
    }

    public function testContactPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the contact page loads');
        $tester->amOnPage('/contact');
        $tester->seeResponseCodeIs(200);
    }

    public function testOauth2AuthErrorPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the oauth2 auth error page loads');
        $tester->amOnPage('/oauth2autherror/testmessage');
        $tester->seeResponseCodeIs(200);
    }

    public function testOauth2CallbackUnauthorisedPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the oauth2 callback unauthorised page loads');
        $tester->amOnPage('/oauth2callbackresultunauthorised');
        $tester->seeResponseCodeIs(200);
    }

    public function testUserCancelledOauth2Page(FunctionalTester $tester): void
    {
        $tester->wantTo('see the user cancelled oauth2 page loads');
        $tester->amOnPage('/usercancelledoauth2');
        $tester->seeResponseCodeIs(200);
    }

    public function testAdminMustMakeActivePage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the admin must make active page loads');
        $tester->amOnPage('/adminmustmakeactive');
        $tester->seeResponseCodeIs(200);
    }

    public function testForgotAlertPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the forgot alert page loads');
        $tester->amOnPage('/forgotalert');
        $tester->seeResponseCodeIs(200);
    }

    public function testForgotEmailFailedPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the forgot email failed page loads');
        $tester->amOnPage('/forgotemailfailed');
        $tester->seeResponseCodeIs(200);
    }

    public function testForgotUserNotFoundPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the forgot user not found page loads');
        $tester->amOnPage('/forgotusernotfound');
        $tester->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordErrorPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the one time password error page loads');
        $tester->amOnPage('/onetimepassworderror');
        $tester->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordFailurePage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the one time password failure page loads');
        $tester->amOnPage('/onetimepasswordfailure');
        $tester->seeResponseCodeIs(200);
    }

    public function testOneTimePasswordSuccessPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the one time password success page loads');
        $tester->amOnPage('/onetimepasswordsuccess');
        $tester->seeResponseCodeIs(200);
    }

    public function testResetPasswordFailedPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the reset password failed page loads');
        $tester->amOnPage('/resetpasswordfailed');
        $tester->seeResponseCodeIs(200);
    }

    public function testResetPasswordSuccessPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the reset password success page loads');
        $tester->amOnPage('/resetpasswordsuccess');
        $tester->seeResponseCodeIs(200);
    }

    public function testSignupFailedPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the signup failed page loads');
        $tester->amOnPage('/signupfailed');
        $tester->seeResponseCodeIs(200);
    }

    public function testSignupSuccessPage(FunctionalTester $tester): void
    {
        $tester->wantTo('see the signup success page loads');
        $tester->amOnPage('/signupsuccess');
        $tester->seeResponseCodeIs(200);
    }
}
