<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Client;

use App\Invoice\Client\PostedClientId;
use App\Invoice\Quote\QuoteEditClient;
use Testo\Assert;
use Testo\Test;

/**
 * The owner of a quote, sales order or invoice must be looked up for the client the edit form is SAVING it for: the
 * posted one when staff change the client, otherwise the record's own.
 */
#[Test]
final class PostedClientIdTest
{
    public function aPostedClientIsTheOneBeingSaved(): void
    {
        Assert::same(PostedClientId::from(['client_id' => '12'], 1), 12);
        Assert::same(PostedClientId::from(['client_id' => 12], 1), 12);
    }

    public function withoutAUsableClientTheRecordsOwnIsUsed(): void
    {
        Assert::same(PostedClientId::from([], 7), 7);
        Assert::same(PostedClientId::from(['client_id' => ''], 7), 7);
        Assert::same(PostedClientId::from(['client_id' => '0'], 7), 7);
        Assert::same(PostedClientId::from(['client_id' => '-3'], 7), 7);
        Assert::same(PostedClientId::from(['client_id' => 'abc'], 7), 7);
        Assert::same(PostedClientId::from(['client_id' => ['12']], 7), 7);
    }

    public function theQuoteHelperGivesTheSameAnswer(): void
    {
        foreach ([[['client_id' => '12'], 1], [[], 7], [['client_id' => 'abc'], 7]] as [$body, $current]) {
            Assert::same(QuoteEditClient::beingSaved($body, $current), PostedClientId::from($body, $current));
        }
    }
}
