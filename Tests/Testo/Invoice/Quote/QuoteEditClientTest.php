<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote;

use App\Invoice\Quote\QuoteEditClient;
use Testo\Assert;
use Testo\Test;

/**
 * The quote edit form must look the owner up for the client it is SAVING the quote for, which is the posted one when
 * staff change the client, and not for the quote's old client. That was the bug: see QuoteOwnerFollowsClientTest.
 */
#[Test]
final class QuoteEditClientTest
{
    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    public function theClientThatWasPostedIsTheOneBeingSaved(): void
    {
        Assert::same(QuoteEditClient::beingSaved(['client_id' => '12'], 1), 12);
        Assert::same(QuoteEditClient::beingSaved(['client_id' => 12], 1), 12);
    }

    public function withoutAUsableClientInThePostTheQuotesOwnClientIsUsed(): void
    {
        Assert::same(QuoteEditClient::beingSaved([], 1), 1);
        Assert::same(QuoteEditClient::beingSaved(['client_id' => ''], 1), 1);
        Assert::same(QuoteEditClient::beingSaved(['client_id' => '0'], 1), 1);
        Assert::same(QuoteEditClient::beingSaved(['client_id' => '-4'], 1), 1);
        Assert::same(QuoteEditClient::beingSaved(['client_id' => 'abc'], 1), 1);
        Assert::same(QuoteEditClient::beingSaved(['client_id' => null], 1), 1);
        Assert::same(QuoteEditClient::beingSaved(['client_id' => ['12']], 1), 1);
    }

    public function theEditFormLooksTheOwnerUpForTheClientBeingSavedNotTheOldOne(): void
    {
        $edit = (string) file_get_contents($this->root() . '/src/Invoice/Quote/Trait/Edit.php');

        Assert::true(str_contains($edit, 'QuoteEditClient::beingSaved($body, $quote->reqClientId())'));
        Assert::true(str_contains($edit, 'activeUserOrWarn('));
        // the old line: the owner looked up for whatever client the quote already had
        Assert::false(str_contains($edit, '$user = $this->activeUser($client_id'));
    }

    public function aRefusedMoveSaysWhyInEnglish(): void
    {
        $messages = (string) file_get_contents($this->root() . '/resources/messages/en/app.php');
        $controller = (string) file_get_contents($this->root() . '/src/Invoice/Quote/QuoteController.php');

        Assert::same(preg_match("/'quote\\.client\\.no\\.single\\.active\\.user'\\s*=>/", $messages), 1);
        Assert::true(str_contains($controller, "translate('quote.client.no.single.active.user')"));
    }
}
