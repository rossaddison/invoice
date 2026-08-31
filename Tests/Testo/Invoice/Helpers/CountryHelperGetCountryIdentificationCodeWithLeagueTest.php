<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\CountryHelper;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CountryHelperGetCountryIdentificationCodeWithLeagueTest
{
    private readonly CountryHelper $helper;

    public function __construct()
    {
        $this->helper = new CountryHelper();
    }

    public function returnsTheAlpha2CodeForAKnownCountryName(): void
    {
        Assert::same($this->helper->getCountryIdentificationCodeWithLeague('France'), 'FR');
    }

    // PostalAddress::country/DeliveryLocation::country are free-text fields
    // holding an alpha2 code in real Peppol data (unlike Client::client_country,
    // which comes from a proper country-name dropdown) -- this used to throw
    // an uncaught League\ISO3166\Exception\OutOfBoundsException instead of
    // returning the code back. Found & fixed 2026-08-31.
    public function returnsTheAlpha2CodeUnchangedWhenGivenAnAlpha2CodeDirectly(): void
    {
        Assert::same($this->helper->getCountryIdentificationCodeWithLeague('GB'), 'GB');
    }

    public function alpha2LookupIsCaseInsensitive(): void
    {
        Assert::same($this->helper->getCountryIdentificationCodeWithLeague('gb'), 'GB');
    }

    public function returnsEmptyStringForAValueMatchingNeitherAnAlpha2CodeNorACountryName(): void
    {
        Assert::same($this->helper->getCountryIdentificationCodeWithLeague('Not A Country'), '');
    }
}
