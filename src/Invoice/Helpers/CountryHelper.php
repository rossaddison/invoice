<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use League\ISO3166\ISO3166;
use Yiisoft\Aliases\Aliases;

Class CountryHelper
{

/**
 * Returns an array list of cldr => country, translated in the language $cldr.
 * If there is no translated country list, return english.
 *
 * @param string $cldr
 * @return mixed
 */    
public function get_country_list(string $cldr) : mixed
{
    $new_aliases = new Aliases(['@helpers' => __DIR__, '@country_list' => '@helpers/Country-list']);
    $file = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR. $cldr .DIRECTORY_SEPARATOR.'country.php';
    $default_english = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.'country.php';
    if (file_exists($file)) {
        /**
         * @psalm-suppress UnresolvableInclude
         */
        return (include $file);
    } else {
        /**
         * @psalm-suppress UnresolvableInclude
         */
        return (include $default_english);
    }
}

/**
 * Returns the countryname of a given $countrycode, translated in the language $cldr.
 *
 * @param string $cldr
 * @param string $countrycode
 * @return mixed
 */
public function get_country_name(string $cldr, string $countrycode) : mixed
{
    /** @var array $countries */
    $countries = $this->get_country_list($cldr);
    /** @var string $countries[$countrycode] */
    return (isset($countries[$countrycode]) ? $countries[$countrycode] : $countrycode);
}

/**
 * @param string $cldr
 * @param string $country_name
 * @return string
 */
public function get_country_identification_code_with_country_list(string $cldr, string $country_name) : string {
    /** @var array $countries */
    $countries = $this->get_country_list($cldr);
    /**
     * @var array $key
     * @var string $value
     */
    foreach ($countries as $key => $value) {
        if ($country_name === $key[$value]) {
            return $value;
        }
    }
    return '';  
}

/**
 * @see PeppolHelper ubl_delivery_location function
 * @param string $name
 * @return string
 */
public function get_country_identification_code_with_league(string $name) : string {
    //https://github.com/thephpleague/iso3166
    $data = (new ISO3166)->name($name); 
    // return the 2-letter country code
    /** @var string $data['alpha2'] */
    return (!empty($data['alpha2']) ? $data['alpha2'] : '');
}

}