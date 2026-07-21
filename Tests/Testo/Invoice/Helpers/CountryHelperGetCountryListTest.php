<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\CountryHelper;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
final class CountryHelperGetCountryListTest
{
    private readonly CountryHelper $helper;

    public function __construct()
    {
        $this->helper = new CountryHelper();
    }

    /** @return list<array{0: string}> */
    public static function localeProvider(): array
    {
        return [
            ['ar'], ['bg'], ['ca'], ['cs'], ['da'], ['de'], ['de_CH'], ['el'], ['en'],
            ['es'], ['fa'], ['fi'], ['fr'], ['fr_CA'], ['he'], ['hr'], ['id'], ['it'],
            ['ja'], ['lt'], ['lv'], ['nl'], ['no'], ['pl'], ['pt_BR'], ['pt_PT'], ['ro'],
            ['ru'], ['sk'], ['sl'], ['sq'], ['sr_CS'], ['sv_SE'], ['tr'], ['vi'],
        ];
    }

    #[DataProvider('localeProvider')]
    public function returnsAnArrayOfMoreThanOneHundredCountriesForEachSupportedLocale(string $cldr): void
    {
        $countries = $this->helper->getCountryList($cldr);

        Assert::array($countries);
        Assert::true(\count($countries) > 100, "Locale '$cldr' should list more than 100 countries.");
    }

    #[DataProvider('localeProvider')]
    public function everyEntryIsATwoLetterCodeMappedToANonEmptyName(string $cldr): void
    {
        /** @var array<string, string> $countries */
        $countries = $this->helper->getCountryList($cldr);

        foreach ($countries as $code => $name) {
            Assert::true(\strlen($code) === 2, "Code '$code' in locale '$cldr' should be a 2-letter string.");
            Assert::true($name !== '', "Name for '$code' in locale '$cldr' should not be empty.");
        }
    }

    public function fallsBackToEnglishForAnUnsupportedLocale(): void
    {
        /** @var array<string, string> $countries */
        $countries = $this->helper->getCountryList('xx-does-not-exist');

        Assert::same($countries['US'], 'United States');
    }
}
