<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class SalesOrderItemControllerCest
{
    /**
     * Unauthenticated GET to the edit route.
     * The auth middleware issues a 302 before the controller runs;
     * PhpBrowser follows it automatically, so the edit form is never reached.
     */
    public function testEditUnauthenticatedGetRedirectsAwayFromEditPage(
        FunctionalTester $tester
    ): void {
        $tester->wantTo('verify unauthenticated GET is redirected away from the edit page');

        $tester->amOnPage('/invoice/salesorderitem/edit/1');

        $tester->dontSeeCurrentUrlEquals('/invoice/salesorderitem/edit/1');
        $tester->dontSeeInSource('peppol_po_itemid');
    }

    /**
     * Auth fires before item lookup: a non-existent item ID must also redirect
     * away, not return 404. This confirms the controller body is never reached
     * for unauthenticated requests regardless of whether the item exists.
     */
    public function testEditUnauthenticatedNonExistentItemRedirectsNotNotFound(
        FunctionalTester $tester
    ): void {
        $tester->wantTo('verify auth redirect occurs before item lookup for non-existent item');

        $tester->amOnPage('/invoice/salesorderitem/edit/99999999');

        $tester->dontSeeCurrentUrlEquals('/invoice/salesorderitem/edit/99999999');
        $tester->dontSeeResponseCodeIs(404);
    }
}
