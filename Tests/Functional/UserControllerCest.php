<?php

declare(strict_types=1);

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class UserControllerCest
{
    public function testIndexPageLoads(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see the user index page loads');
        $functionalTester->amOnPage('/user');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testIndexWithPagination(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test user index with pagination');
        $functionalTester->amOnPage('/user/1/10'); // page 1, pagesize 10
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testIndexWithDifferentPageSize(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test user index with different page size');
        $functionalTester->amOnPage('/user/2/5'); // page 2, pagesize 5
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testProfilePageWithLogin(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see a user profile page');
        // Using a common login that exists - admin user
        $functionalTester->amOnPage('/user/admin');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testProfilePageWithNonExistentLogin(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('see 404 for non-existent user');
        $functionalTester->amOnPage('/user/nonexistentuser12345');
        $functionalTester->seeResponseCodeIs(404);
    }

    public function testIndexWithQueryParameters(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test user index with query parameters');
        $functionalTester->amOnPage('/user?test=value');
        $functionalTester->seeResponseCodeIs(200);
    }

    public function testIndexTrailingSlash(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test user index with trailing slash returns 404');
        $functionalTester->amOnPage('/user/');
        $functionalTester->seeResponseCodeIs(404);
    }

    public function testIndexWithComplexPath(FunctionalTester $functionalTester): void
    {
        $functionalTester->wantTo('test user index with complex URL pattern');
        $functionalTester->amOnPage('/user/3/20'); // page 3, pagesize 20
        $functionalTester->seeResponseCodeIs(200);
    }
}
