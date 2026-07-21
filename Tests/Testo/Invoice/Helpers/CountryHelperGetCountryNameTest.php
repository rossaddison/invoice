<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\CountryHelper;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CountryHelperGetCountryNameTest
{
    private readonly CountryHelper $helper;

    public function __construct()
    {
        $this->helper = new CountryHelper();
    }

    public function returnsTheEnglishNameForAKnownCode(): void
    {
        Assert::same($this->helper->getCountryName('en', 'GB'), 'United Kingdom');
    }

    public function returnsTheTranslatedNameForAKnownCodeInAnotherLocale(): void
    {
        Assert::same($this->helper->getCountryName('de', 'US'), 'Vereinigte Staaten');
    }

    public function returnsTheCodeItselfForAnUnknownCode(): void
    {
        Assert::same($this->helper->getCountryName('en', 'XX'), 'XX');
    }
}
