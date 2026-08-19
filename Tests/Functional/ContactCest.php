<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

final class ContactCest
{
    public function _before(FunctionalTester $tester): void
    {
        $tester->amOnPage('/contact');
    }

    public function openContactPage(FunctionalTester $tester): void
    {
        $tester->wantTo('ensure that contact page works');
        $tester->seeElement('section', ['id' => 'Contact']);
    }
}
