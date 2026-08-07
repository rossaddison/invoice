<?php

declare(strict_types=1);

namespace Tests\Functional;

use Codeception\Util\HttpCode;
use Tests\Support\FunctionalTester;

class SalesOrderItemControllerCest
{
    /**
     * Unauthenticated GET to the edit route.
     * AccessChecker::process() (src/Middleware/AccessChecker.php) returns a
     * straight 403 Forbidden on the same URL when the guest permission check
     * fails — it never redirects. PhpBrowser therefore stays on the
     * requested URL; the edit form itself is never rendered.
     */
    public function testEditUnauthenticatedGetIsForbidden(
        FunctionalTester $tester
    ): void {
        $tester->wantTo('verify unauthenticated GET to the edit page is refused with 403');

        $tester->amOnPage('/invoice/salesorderitem/edit/1');

        $tester->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $tester->dontSeeInSource('peppol_po_itemid');
    }

    /**
     * Auth fires before item lookup: a non-existent item ID must also get
     * 403, not 404. This confirms the controller body is never reached for
     * unauthenticated requests regardless of whether the item exists.
     */
    public function testEditUnauthenticatedNonExistentItemIsForbiddenNotNotFound(
        FunctionalTester $tester
    ): void {
        $tester->wantTo('verify auth check occurs before item lookup for non-existent item');

        $tester->amOnPage('/invoice/salesorderitem/edit/99999999');

        $tester->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }
}
