<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

final class ContactCest
{
    public function _before(FunctionalTester $I)
    {
        $I->amOnPage('/contact');
    }

    public function openContactPage(FunctionalTester $I)
    {
        $I->wantTo('ensure that contact page works');
        $I->seeElement('section', ['id' => 'Contact']);
    }
}
