<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use League\ISO3166\Exception\ISO3166Exception;
use League\ISO3166\ISO3166;
use Yiisoft\Aliases\Aliases;

class CountryHelper
{
    /**
     * Returns an array list of cldr => country, translated in the language $cldr.
     * If there is no translated country list, return english.
     *
     * @param string $cldr
     * @return mixed
     */
    public function getCountryList(string $cldr): mixed
    {
        $new_aliases = new Aliases(
            [
                '@helpers' => __DIR__,
                '@country_list' => '@helpers/Country-list'
            ]
        );
        $file = $new_aliases->get('@country_list')
                . DIRECTORY_SEPARATOR
                . $cldr
                . DIRECTORY_SEPARATOR
                . 'country.php';
        $default_english = $new_aliases->get('@country_list')
                . DIRECTORY_SEPARATOR
                . 'en'
                . DIRECTORY_SEPARATOR
                . 'country.php';
        if (file_exists($file)) {
            return include $file; // NOSONAR — data file returns an array; include_once returns true on second call
        }
        /**
         * @psalm-suppress UnresolvableInclude
         */
        return include $default_english; // NOSONAR — data file returns an array; include_once returns true on second call
    }

    /**
     * Returns the countryname of a given $countrycode, translated in the
     * language $cldr.
     *
     * @param string $cldr
     * @param string $countrycode
     * @return string
     */
    public function getCountryName(string $cldr, string $countrycode): string
    {
        /** @var array $countries */
        $countries = $this->getCountryList($cldr);
        /** @var string $countries[$countrycode] */
        return $countries[$countrycode] ?? $countrycode;
    }

    /**
     * @param string $cldr
     * @param string $country_name
     * @return string
     */
    public function getCountryIdentificationCodeWithCountryList(
        string $cldr,
        string $country_name
    ): string {
        /** @var array<string, string> $countries */
        $countries = $this->getCountryList($cldr);
        foreach ($countries as $key => $value) {
            if ($country_name === $value) {
                return $key;
            }
        }
        return '';
    }

    /**
     * Related logic: see PeppolHelper ubl_delivery_location function
     *
     * Accepts either a 2-letter alpha2 code (e.g. "GB" -- what
     * PostalAddress::country/DeliveryLocation::country actually hold, since
     * both are plain free-text fields with no format guidance) or a full
     * country name (e.g. "United Kingdom" -- what Client::client_country
     * holds, since its own form field is a proper locale-aware dropdown of
     * country names). Tries alpha2 first since it's the cheaper, more
     * specific match, falling back to name; a value matching neither format
     * (a typo, "UK", an unrecognised name) still returns ''. Previously
     * only tried name(), which threw for every alpha2 caller -- found
     * 2026-08-31 seeding a Peppol test fixture with a real DeliveryLocation
     * country of "GB".
     *
     * @param string $name
     * @return string
     */
    public function getCountryIdentificationCodeWithLeague(
        string $name
    ): string {
        //https://github.com/thephpleague/iso3166
        $iso3166 = new ISO3166();
        try {
            $data = $iso3166->alpha2($name);
        } catch (ISO3166Exception) {
            try {
                $data = $iso3166->name($name);
            } catch (ISO3166Exception) {
                return '';
            }
        }
        // return the 2-letter country code
        /** @var string $data['alpha2'] */
        return !empty($data['alpha2']) ? $data['alpha2'] : '';
    }
}
