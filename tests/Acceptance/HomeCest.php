<?php

declare(strict_types=1);


namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class HomeCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->see('Home');
    }

    public function testhomePage(AcceptanceTester $I)
    {
        $I->expectTo('see home page.');
        $I->see('Home');
        $I->see('About');
        $I->see('Accreditations');
        $I->see('Gallery');
        $I->see('Team');
        $I->see('Pricing');
        $I->see('Testimonial');
        $I->see('Privacy Policy');
        $I->see('Terms of Service');
        $I->see('Contact Us');
        $I->see('Login');
        $I->see('Signup');
    }
}
