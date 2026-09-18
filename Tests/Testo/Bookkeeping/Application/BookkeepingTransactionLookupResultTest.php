<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Application;

use App\Bookkeeping\Application\BookkeepingTransactionLookupResult;
use Testo\Assert;
use Testo\Test;

#[Test]
final class BookkeepingTransactionLookupResultTest
{
    public function foundCarriesTheProviderReferenceAndNoMessage(): void
    {
        $result = BookkeepingTransactionLookupResult::found('XERO-9001');

        Assert::true($result->found);
        Assert::same('XERO-9001', $result->providerReference);
        Assert::same('', $result->message);
    }

    public function notFoundHasNoReferenceAndNoMessage(): void
    {
        $result = BookkeepingTransactionLookupResult::notFound();

        Assert::false($result->found);
        Assert::null($result->providerReference);
        Assert::same('', $result->message);
    }

    public function failedHasNoReferenceButCarriesTheMessage(): void
    {
        $result = BookkeepingTransactionLookupResult::failed('Xero API timeout');

        Assert::false($result->found);
        Assert::null($result->providerReference);
        Assert::same('Xero API timeout', $result->message);
    }
}
