<?php

declare(strict_types=1);

namespace Tests\Testo\Auth\Client;

use App\Auth\Client\HmrcDeveloperHubLinks;
use Testo\Assert;
use Testo\Test;

/**
 * Covers HmrcDeveloperHubLinks: accountLinks() always returns its fixed set
 * of account-level URLs, applicationLinks() is empty for a blank application
 * ID and otherwise builds every per-application URL with that ID urlencoded
 * in, and every URL from both methods points at the real Developer Hub host.
 */
#[Test]
final class HmrcDeveloperHubLinksTest
{
    private const string EXPECTED_ACCOUNT_LABEL_KEYS = 'mtd.hmrc.developer.hub.';

    public function accountLinksReturnsTheFixedEightEntryList(): void
    {
        $links = HmrcDeveloperHubLinks::accountLinks();

        Assert::same(8, count($links));
    }

    public function accountLinksAreAllUnderTheDeveloperHubHost(): void
    {
        foreach (HmrcDeveloperHubLinks::accountLinks() as $link) {
            Assert::true(str_starts_with(
                $link['url'],
                'https://developer.service.hmrc.gov.uk/developer/',
            ));
        }
    }

    public function accountLinksLabelKeysAllShareTheMtdHmrcDeveloperHubPrefix(): void
    {
        foreach (HmrcDeveloperHubLinks::accountLinks() as $link) {
            Assert::true(str_starts_with(
                $link['labelKey'],
                self::EXPECTED_ACCOUNT_LABEL_KEYS,
            ));
        }
    }

    public function accountLinksIncludeTheLoginAndLogoutPages(): void
    {
        $urls = array_column(HmrcDeveloperHubLinks::accountLinks(), 'url');

        Assert::true(in_array(
            'https://developer.service.hmrc.gov.uk/developer/login',
            $urls,
            true,
        ));
        Assert::true(in_array(
            'https://developer.service.hmrc.gov.uk/developer/logout',
            $urls,
            true,
        ));
    }

    public function applicationLinksIsEmptyWhenNoApplicationIdIsConfigured(): void
    {
        Assert::same([], HmrcDeveloperHubLinks::applicationLinks(''));
    }

    public function applicationLinksReturnsTheFixedNineEntryListWhenIdIsSet(): void
    {
        $links = HmrcDeveloperHubLinks::applicationLinks('abc-123');

        Assert::same(9, count($links));
    }

    public function applicationLinksBuildsEachUrlWithTheGivenApplicationId(): void
    {
        $links = HmrcDeveloperHubLinks::applicationLinks('abc-123');

        foreach ($links as $link) {
            Assert::true(str_starts_with(
                $link['url'],
                'https://developer.service.hmrc.gov.uk/developer/applications/abc-123/',
            ));
        }
    }

    public function applicationLinksUrlencodesAnApplicationIdWithSpecialCharacters(): void
    {
        $links = HmrcDeveloperHubLinks::applicationLinks('id with spaces');

        foreach ($links as $link) {
            Assert::true(str_contains($link['url'], 'id+with+spaces'));
            Assert::false(str_contains($link['url'], 'id with spaces'));
        }
    }

    public function applicationLinksIncludeManageAndDeletePages(): void
    {
        $urls = array_column(HmrcDeveloperHubLinks::applicationLinks('abc-123'), 'url');

        Assert::true(in_array(
            'https://developer.service.hmrc.gov.uk/developer/applications/abc-123/manage',
            $urls,
            true,
        ));
        Assert::true(in_array(
            'https://developer.service.hmrc.gov.uk/developer/applications/abc-123/delete',
            $urls,
            true,
        ));
    }
}
