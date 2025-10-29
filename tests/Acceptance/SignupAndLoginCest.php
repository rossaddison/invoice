<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class SignupAndLoginCest
{
    public function __construct(
        private readonly string $emptyLogin = '',
        private readonly string $emptyPassword = '',
        // If failing try the actual login name here    
        private readonly string $liveAdminUserLogin = 'admin',
        // If failing try the actual login password here    
        private readonly string $liveAdminUserPassword = 'admin',
        private readonly string $firstUserLogin = 'firstUserLogin',
        private readonly string $firstUserWrongLogin = 'firstUserWrongLogin',
        private readonly string $firstUserPassword = 'firstUserPassword',
        private readonly string $firstUserWrongPassword = 'firstUserWrongPassword',
    ) {}

    public function testSignupPage(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the register page.');
        $I->amOnPage('/signup');

        $I->expectTo('see register page.');
        $I->see('Signup');
    }

    public function testRegisterSuccess(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the register page.');
        $I->amOnPage('/signup');

        $I->fillField('#signup-login', $this->firstUserLogin);
        $I->fillField('#signup-password', $this->firstUserPassword);
        $I->fillField('#signup-passwordverify', $this->firstUserPassword);

        $I->click('Submit', '#signupForm');

        $I->expectTo('see register success message.');

        /**
         * $I->see('Hello, everyone!');
         *
         * The user clicks on an email verification link which is sent
         * to 'userinv/signup'
         *
         * $I->see('This user is marked as inactive. Please contact the system administrator.');
         */
    }

    /**
     * Note: This function depends on the above function being successful
     *
     * @depends Tests\Acceptance\SignupAndLoginCest:testRegisterSuccess
     */
    public function testLoginUsernameSubmitFormSuccessData(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the log in page.');
        $I->amOnPage('/login');

        $I->fillField('#login-login', $this->liveAdminUserLogin);
        $I->fillField('#login-password', $this->liveAdminUserPassword);
        $I->checkOption('#login-rememberme');

        $I->click('Submit', '#loginForm');

        $I->expectTo('see invoice page.');
        $I->dontSeeLink('login');

        $I->amOnPage('/invoice');
    }

    public function testRegisterEmptyData(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the register page.');
        $I->amOnPage('/signup');

        $I->fillField('#signup-login', '');
        $I->fillField('#signup-password', '');
        $I->fillField('#signup-passwordverify', '');

        $I->click('Submit', '#signupForm');

        $I->expectTo('see registration register validation.');
        $I->see('Login cannot be blank.');

        $I->see('Email cannot be blank.');
        $I->see('Email is not a valid email address.');

        $I->see('Password cannot be blank.');

        $I->see('PasswordVerify cannot be blank.');
        $I->seeElement('button', ['name' => 'register-button']);
    }

    public function testRegisterUsernameExistData(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the register page.');
        $I->amOnPage('/signup');

        $I->fillField('#signup-login', $this->firstUserLogin);
        $I->fillField('#signup-password', $this->firstUserPassword);
        $I->fillField('#signup-passwordverify', $this->firstUserPassword);

        $I->click('Submit', '#signupForm');

        /**
         * $I->expectTo('see registration register validation.');
         *
         * Because the User has to click on a verification email link,
         * effectively in the tests the username entered in testRegisterSuccess will not pre-exist
         *
         * $I->see('A User with this login already exists');
         * $I->seeElement('button', ['name' => 'register-button']);
         */
    }

    public function testRegisterWrongPassword(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the register page.');
        $I->amOnPage('/signup');

        $I->fillField('#signup-login', $this->firstUserLogin);
        $I->fillField('#signup-password', $this->firstUserWrongPassword);
        $I->fillField('#signup-passwordverify', $this->firstUserPassword);

        $I->click('Submit', '#signupForm');

        $I->expectTo('see registration register validation.');
        $I->see('Passwords do not match');
    }

    public function testLoginPage(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the log in page.');
        $I->amOnPage('/login');

        $I->expectTo('see log in page.');
        $I->see('Login');
    }    

    public function testLoginEmptyDataTest(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the log in page.');
        $I->amOnPage('/login');

        $I->fillField('#login-login', $this->emptyLogin);
        $I->fillField('#login-password', $this->emptyPassword);

        $I->click('Submit', '#loginForm');

        $I->expectTo('see validations errors.');
        $I->see('Login cannot be blank.');
        $I->see('Password cannot be blank.');
        $I->seeElement('button', ['name' => 'login-button']);
    }

    public function testLoginSubmitFormWrongDataUsername(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the log in page.');
        $I->amOnPage('/login');

        $I->fillField('#login-login', $this->firstUserWrongLogin);
        $I->fillField('#login-password', $this->firstUserPassword);
        $I->checkOption('#login-rememberme');

        $I->click('Submit', '#loginForm');

        $I->expectTo('see validations errors.');
        $I->see('Invalid login or password');
        $I->seeElement('button', ['name' => 'login-button']);
    }

    public function testLoginSubmitFormWrongDataPassword(AcceptanceTester $I): void
    {
        $I->amGoingTo('go to the login page.');
        $I->amOnPage('/login');

        $I->fillField('#login-login', $this->firstUserLogin);
        $I->fillField('#login-password', $this->firstUserWrongPassword);
        $I->checkOption('#login-rememberme');

        $I->click('Submit', '#loginForm');

        $I->expectTo('see validations errors.');
        $I->see('Invalid login or password');
        $I->seeElement('button', ['name' => 'login-button']);
    }
}
