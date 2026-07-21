<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\CountryHelper;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CountryHelperGetCountryIdentificationCodeWithCountryListTest
{
    private readonly CountryHelper $helper;

    public function __construct()
    {
        $this->helper = new CountryHelper();
    }

    public function returnsTheCodeForAKnownCountryName(): void
    {
        Assert::same($this->helper->getCountryIdentificationCodeWithCountryList('en', 'United States'), 'US');
    }

    public function returnsEmptyStringForAnUnknownCountryName(): void
    {
        Assert::same($this->helper->getCountryIdentificationCodeWithCountryList('en', 'Not A Real Country'), '');
    }
}
