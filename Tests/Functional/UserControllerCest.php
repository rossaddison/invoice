<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class UserControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        // Setup before each test if needed
    }

    public function testIndexPageLoads(FunctionalTester $I): void
    {
        $I->wantTo('see the user index page loads');
        $I->amOnPage('/user');
        $I->seeResponseCodeIs(200);
    }

    public function testIndexWithPagination(FunctionalTester $I): void
    {
        $I->wantTo('test user index with pagination');
        $I->amOnPage('/user/1/10'); // page 1, pagesize 10
        $I->seeResponseCodeIs(200);
    }

    public function testIndexWithDifferentPageSize(FunctionalTester $I): void
    {
        $I->wantTo('test user index with different page size');
        $I->amOnPage('/user/2/5'); // page 2, pagesize 5
        $I->seeResponseCodeIs(200);
    }

    public function testProfilePageWithLogin(FunctionalTester $I): void
    {
        $I->wantTo('see a user profile page');
        // Using a common login that exists - admin user
        $I->amOnPage('/user/admin');
        $I->seeResponseCodeIs(200);
    }

    public function testProfilePageWithNonExistentLogin(FunctionalTester $I): void
    {
        $I->wantTo('see 404 for non-existent user');
        $I->amOnPage('/user/nonexistentuser12345');
        $I->seeResponseCodeIs(404);
    }

    public function testIndexWithQueryParameters(FunctionalTester $I): void
    {
        $I->wantTo('test user index with query parameters');
        $I->amOnPage('/user?test=value');
        $I->seeResponseCodeIs(200);
    }

    public function testIndexTrailingSlash(FunctionalTester $I): void
    {
        $I->wantTo('test user index with trailing slash returns 404');
        $I->amOnPage('/user/');
        $I->seeResponseCodeIs(404);
    }

    public function testIndexWithComplexPath(FunctionalTester $I): void
    {
        $I->wantTo('test user index with complex URL pattern');
        $I->amOnPage('/user/3/20'); // page 3, pagesize 20
        $I->seeResponseCodeIs(200);
    }
}