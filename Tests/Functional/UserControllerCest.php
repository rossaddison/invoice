<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class UserControllerCest
{
    public function testIndexPageLoads(FunctionalTester $tester): void
    {
        $tester->wantTo('see the user index page loads');
        $tester->amOnPage('/user');
        $tester->seeResponseCodeIs(200);
    }

    public function testIndexWithPagination(FunctionalTester $tester): void
    {
        $tester->wantTo('test user index with pagination');
        $tester->amOnPage('/user/1/10'); // page 1, pagesize 10
        $tester->seeResponseCodeIs(200);
    }

    public function testIndexWithDifferentPageSize(FunctionalTester $tester): void
    {
        $tester->wantTo('test user index with different page size');
        $tester->amOnPage('/user/2/5'); // page 2, pagesize 5
        $tester->seeResponseCodeIs(200);
    }

    public function testProfilePageWithLogin(FunctionalTester $tester): void
    {
        $tester->wantTo('see a user profile page');
        // Using a common login that exists - admin user
        $tester->amOnPage('/user/admin');
        $tester->seeResponseCodeIs(200);
    }

    public function testProfilePageWithNonExistentLogin(FunctionalTester $tester): void
    {
        $tester->wantTo('see 404 for non-existent user');
        $tester->amOnPage('/user/nonexistentuser12345');
        $tester->seeResponseCodeIs(404);
    }

    public function testIndexWithQueryParameters(FunctionalTester $tester): void
    {
        $tester->wantTo('test user index with query parameters');
        $tester->amOnPage('/user?test=value');
        $tester->seeResponseCodeIs(200);
    }

    public function testIndexTrailingSlash(FunctionalTester $tester): void
    {
        $tester->wantTo('test user index with trailing slash returns 404');
        $tester->amOnPage('/user/');
        $tester->seeResponseCodeIs(404);
    }

    public function testIndexWithComplexPath(FunctionalTester $tester): void
    {
        $tester->wantTo('test user index with complex URL pattern');
        $tester->amOnPage('/user/3/20'); // page 3, pagesize 20
        $tester->seeResponseCodeIs(200);
    }
}
