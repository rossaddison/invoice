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
}
