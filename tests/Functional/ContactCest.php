<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

final class ContactCest
{
    public function _before(FunctionalTester $functionalTester)
    {
        $functionalTester->amOnPage('/contact');
    }

    public function openContactPage(FunctionalTester $functionalTester)
    {
        $functionalTester->wantTo('ensure that contact page works');
        $functionalTester->seeElement('section', ['id' => 'Contact']);
    }
}
