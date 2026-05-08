<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

class CurrencyHelper
{
    /**
     * Create a new Currency object
     */
    private function __construct(
        private readonly mixed $code,
        private readonly mixed $numeric,
        private readonly mixed $decimals)
    {
    }

    /**
     * Get the three letter code for the currency
     *
     * @return mixed
     */
    public function getCode(): mixed
    {
        return $this->code;
    }

    /**
     * Get the numeric code for this currency
     *
     * @return mixed
     */
    public function getNumeric(): mixed
    {
        return $this->numeric;
    }

    /**
     * Get the number of decimal places for this currency
     *
     * @return int
     */
    public function getDecimals(): int
    {
        return (int) $this->decimals;
    }

    /**
     * Find a specific currency
     *
     * @param  string $currency_code The three letter currency code
     * @return mixed  A Currency object, or null if no currency was found
     */
    public function find(string $currency_code): mixed
    {
        $code = strtoupper($currency_code);
        $currencies = static::all();
        if (isset($currencies[$code])) {
            /**
             * @var string $currencies[$code]['numeric']
             * @var string $currencies[$code]['decimals']
             */
            return new self($code,
                $currencies[$code]['numeric'], $currencies[$code]['decimals']);
        }
        return null;
    }

    /**
     * Related logic: see ISO 4217 alpha-3 ie. 3 letter currency codes
     * Get an array of all supported currencies
     *
     * @return (int|string)[][]
     *
     * @psalm-return array{
        AED: array{numeric: '784', decimals: 2, stripe_v10: 1},...}
     */
    public static function all(): array
    {
        return [
            'AED' => ['numeric' => '784', 'decimals' => 2, 'stripe_v10' => 1],
            'AFN' => ['numeric' => '971', 'decimals' => 2, 'stripe_v10' => 1],
            'ALL' => ['numeric' => '008', 'decimals' => 2, 'stripe_v10' => 1],
            'AMD' => ['numeric' => '051', 'decimals' => 2, 'stripe_v10' => 1],
            'ANG' => ['numeric' => '532', 'decimals' => 2, 'stripe_v10' => 1],
            'AOA' => ['numeric' => '973', 'decimals' => 2, 'stripe_v10' => 1],
            'ARS' => ['numeric' => '032', 'decimals' => 2, 'stripe_v10' => 1],
            'AUD' => ['numeric' => '036', 'decimals' => 2, 'stripe_v10' => 1],
            'AWG' => ['numeric' => '533', 'decimals' => 2, 'stripe_v10' => 1],
            'AZN' => ['numeric' => '944', 'decimals' => 2, 'stripe_v10' => 1],
            'BAM' => ['numeric' => '977', 'decimals' => 2, 'stripe_v10' => 1],
            'BBD' => ['numeric' => '052', 'decimals' => 2, 'stripe_v10' => 1],
            'BDT' => ['numeric' => '050', 'decimals' => 2, 'stripe_v10' => 1],
            'BGN' => ['numeric' => '975', 'decimals' => 2, 'stripe_v10' => 1],
            'BHD' => ['numeric' => '048', 'decimals' => 3, 'stripe_v10' => 1],
            'BIF' => ['numeric' => '108', 'decimals' => 0, 'stripe_v10' => 1],
            'BMD' => ['numeric' => '060', 'decimals' => 2, 'stripe_v10' => 1],
            'BND' => ['numeric' => '096', 'decimals' => 2, 'stripe_v10' => 1],
            'BOB' => ['numeric' => '068', 'decimals' => 2, 'stripe_v10' => 1],
            'BOV' => ['numeric' => '984', 'decimals' => 2, 'stripe_v10' => 0],
            'BRL' => ['numeric' => '986', 'decimals' => 2, 'stripe_v10' => 1],
            'BSD' => ['numeric' => '044', 'decimals' => 2, 'stripe_v10' => 1],
            'BTN' => ['numeric' => '064', 'decimals' => 2, 'stripe_v10' => 1],
            'BWP' => ['numeric' => '072', 'decimals' => 2, 'stripe_v10' => 1],
            'BYN' => ['numeric' => '933', 'decimals' => 2, 'stripe_v10' => 1],
            'BZD' => ['numeric' => '084', 'decimals' => 2, 'stripe_v10' => 1],
            'CAD' => ['numeric' => '124', 'decimals' => 2, 'stripe_v10' => 1],
            'CDF' => ['numeric' => '976', 'decimals' => 2, 'stripe_v10' => 1],
            'CHE' => ['numeric' => '947', 'decimals' => 2, 'stripe_v10' => 0],
            'CHF' => ['numeric' => '756', 'decimals' => 2, 'stripe_v10' => 1],
            'CHW' => ['numeric' => '948', 'decimals' => 2, 'stripe_v10' => 0],
            'CLF' => ['numeric' => '990', 'decimals' => 4, 'stripe_v10' => 0],
            'CLP' => ['numeric' => '152', 'decimals' => 0, 'stripe_v10' => 1],
            'CNY' => ['numeric' => '156', 'decimals' => 2, 'stripe_v10' => 1],
            'COP' => ['numeric' => '170', 'decimals' => 2, 'stripe_v10' => 1],
            'COU' => ['numeric' => '970', 'decimals' => 2, 'stripe_v10' => 0],
            'CRC' => ['numeric' => '188', 'decimals' => 2, 'stripe_v10' => 1],
            'CUC' => ['numeric' => '931', 'decimals' => 2, 'stripe_v10' => 0],
            'CUP' => ['numeric' => '192', 'decimals' => 2, 'stripe_v10' => 0],
            'CVE' => ['numeric' => '132', 'decimals' => 2, 'stripe_v10' => 1],
            'CZK' => ['numeric' => '203', 'decimals' => 2, 'stripe_v10' => 1],
            'DJF' => ['numeric' => '262', 'decimals' => 0, 'stripe_v10' => 1],
            'DKK' => ['numeric' => '208', 'decimals' => 2, 'stripe_v10' => 1],
            'DOP' => ['numeric' => '214', 'decimals' => 2, 'stripe_v10' => 1],
            'DZD' => ['numeric' => '012', 'decimals' => 2, 'stripe_v10' => 1],
            'EEK' => ['numeric' => '233', 'decimals' => 2, 'stripe_v10' => 1],
            'EGP' => ['numeric' => '818', 'decimals' => 2, 'stripe_v10' => 1],
            'ERN' => ['numeric' => '232', 'decimals' => 2, 'stripe_v10' => 1],
            'ETB' => ['numeric' => '230', 'decimals' => 2, 'stripe_v10' => 1],
            'EUR' => ['numeric' => '978', 'decimals' => 2, 'stripe_v10' => 1],
            'FJD' => ['numeric' => '242', 'decimals' => 2, 'stripe_v10' => 1],
            'FKP' => ['numeric' => '238', 'decimals' => 2, 'stripe_v10' => 1],
            'GBP' => ['numeric' => '826', 'decimals' => 2, 'stripe_v10' => 1],
            'GEL' => ['numeric' => '981', 'decimals' => 2, 'stripe_v10' => 1],
            'GHS' => ['numeric' => '936', 'decimals' => 2, 'stripe_v10' => 1],
            'GIP' => ['numeric' => '292', 'decimals' => 2, 'stripe_v10' => 1],
            'GMD' => ['numeric' => '270', 'decimals' => 2, 'stripe_v10' => 1],
            'GNF' => ['numeric' => '324', 'decimals' => 0, 'stripe_v10' => 1],
            'GTQ' => ['numeric' => '320', 'decimals' => 2, 'stripe_v10' => 1],
            'GYD' => ['numeric' => '328', 'decimals' => 2, 'stripe_v10' => 1],
            'HKD' => ['numeric' => '344', 'decimals' => 2, 'stripe_v10' => 1],
            'HNL' => ['numeric' => '340', 'decimals' => 2, 'stripe_v10' => 1],
            'HRK' => ['numeric' => '191', 'decimals' => 2, 'stripe_v10' => 1],
            'HTG' => ['numeric' => '332', 'decimals' => 2, 'stripe_v10' => 1],
            'HUF' => ['numeric' => '348', 'decimals' => 2, 'stripe_v10' => 1],
            'IDR' => ['numeric' => '360', 'decimals' => 2, 'stripe_v10' => 1],
            'ILS' => ['numeric' => '376', 'decimals' => 2, 'stripe_v10' => 1],
            'INR' => ['numeric' => '356', 'decimals' => 2, 'stripe_v10' => 1],
            'IQD' => ['numeric' => '368', 'decimals' => 3, 'stripe_v10' => 0],
            'IRR' => ['numeric' => '364', 'decimals' => 2, 'stripe_v10' => 0],
            'ISK' => ['numeric' => '352', 'decimals' => 0, 'stripe_v10' => 1],
            'JMD' => ['numeric' => '388', 'decimals' => 2, 'stripe_v10' => 1],
            'JOD' => ['numeric' => '400', 'decimals' => 3, 'stripe_v10' => 1],
            'JPY' => ['numeric' => '392', 'decimals' => 0, 'stripe_v10' => 1],
            'KES' => ['numeric' => '404', 'decimals' => 2, 'stripe_v10' => 1],
            'KGS' => ['numeric' => '417', 'decimals' => 2, 'stripe_v10' => 1],
            'KHR' => ['numeric' => '116', 'decimals' => 2, 'stripe_v10' => 1],
            'KMF' => ['numeric' => '174', 'decimals' => 0, 'stripe_v10' => 1],
            'KPW' => ['numeric' => '408', 'decimals' => 2, 'stripe_v10' => 0],
            'KRW' => ['numeric' => '410', 'decimals' => 0, 'stripe_v10' => 1],
            'KWD' => ['numeric' => '414', 'decimals' => 3, 'stripe_v10' => 1],
            'KYD' => ['numeric' => '136', 'decimals' => 2, 'stripe_v10' => 1],
            'KZT' => ['numeric' => '398', 'decimals' => 2, 'stripe_v10' => 1],
            'LAK' => ['numeric' => '418', 'decimals' => 0, 'stripe_v10' => 1],
            'LBP' => ['numeric' => '422', 'decimals' => 2, 'stripe_v10' => 1],
            'LKR' => ['numeric' => '144', 'decimals' => 2, 'stripe_v10' => 1],
            'LRD' => ['numeric' => '430', 'decimals' => 2, 'stripe_v10' => 1],
            'LSL' => ['numeric' => '426', 'decimals' => 2, 'stripe_v10' => 1],
            'LYD' => ['numeric' => '434', 'decimals' => 3, 'stripe_v10' => 0],
            'LTL' => ['numeric' => '440', 'decimals' => 2, 'stripe_v10' => 1],
            'LVL' => ['numeric' => '428', 'decimals' => 2, 'stripe_v10' => 1],
            'MAD' => ['numeric' => '504', 'decimals' => 2, 'stripe_v10' => 1],
            'MDL' => ['numeric' => '498', 'decimals' => 2, 'stripe_v10' => 1],
            'MGA' => ['numeric' => '969', 'decimals' => 2, 'stripe_v10' => 1],
            'MKD' => ['numeric' => '807', 'decimals' => 2, 'stripe_v10' => 1],
            'MMK' => ['numeric' => '104', 'decimals' => 2, 'stripe_v10' => 1],
            'MNT' => ['numeric' => '496', 'decimals' => 2, 'stripe_v10' => 1],
            'MOP' => ['numeric' => '446', 'decimals' => 2, 'stripe_v10' => 1],
            'MRU' => ['numeric' => '929', 'decimals' => 2, 'stripe_v10' => 0],
            'MUR' => ['numeric' => '480', 'decimals' => 2, 'stripe_v10' => 1],
            'MVR' => ['numeric' => '462', 'decimals' => 2, 'stripe_v10' => 1],
            'MWK' => ['numeric' => '454', 'decimals' => 2, 'stripe_v10' => 1],
            'MXN' => ['numeric' => '484', 'decimals' => 2, 'stripe_v10' => 1],
            'MXV' => ['numeric' => '979', 'decimals' => 2, 'stripe_v10' => 1],
            'MYR' => ['numeric' => '458', 'decimals' => 2, 'stripe_v10' => 1],
            'MZN' => ['numeric' => '943', 'decimals' => 2, 'stripe_v10' => 1],
            'NAD' => ['numeric' => '516', 'decimals' => 2, 'stripe_v10' => 1],
            'NGN' => ['numeric' => '566', 'decimals' => 2, 'stripe_v10' => 1],
            'NIO' => ['numeric' => '558', 'decimals' => 2, 'stripe_v10' => 1],
            'NOK' => ['numeric' => '578', 'decimals' => 2, 'stripe_v10' => 1],
            'NPR' => ['numeric' => '524', 'decimals' => 2, 'stripe_v10' => 1],
            'NZD' => ['numeric' => '554', 'decimals' => 2, 'stripe_v10' => 1],
            'OMR' => ['numeric' => '512', 'decimals' => 3, 'stripe_v10' => 1],
            'PAB' => ['numeric' => '590', 'decimals' => 2, 'stripe_v10' => 1],
            'PEN' => ['numeric' => '604', 'decimals' => 2, 'stripe_v10' => 1],
            'PGK' => ['numeric' => '598', 'decimals' => 2, 'stripe_v10' => 1],
            'PHP' => ['numeric' => '608', 'decimals' => 2, 'stripe_v10' => 1],
            'PKR' => ['numeric' => '586', 'decimals' => 2, 'stripe_v10' => 1],
            'PLN' => ['numeric' => '985', 'decimals' => 2, 'stripe_v10' => 1],
            'PYG' => ['numeric' => '600', 'decimals' => 0, 'stripe_v10' => 1],
            'QAR' => ['numeric' => '634', 'decimals' => 2, 'stripe_v10' => 1],
            'RON' => ['numeric' => '946', 'decimals' => 2, 'stripe_v10' => 1],
            'RSD' => ['numeric' => '941', 'decimals' => 2, 'stripe_v10' => 1],
            'RUB' => ['numeric' => '643', 'decimals' => 2, 'stripe_v10' => 1],
            'RWF' => ['numeric' => '646', 'decimals' => 0, 'stripe_v10' => 1],
            'SAR' => ['numeric' => '682', 'decimals' => 2, 'stripe_v10' => 1],
            'SBD' => ['numeric' => '090', 'decimals' => 2, 'stripe_v10' => 1],
            'SCR' => ['numeric' => '690', 'decimals' => 2, 'stripe_v10' => 1],
            'SDG' => ['numeric' => '938', 'decimals' => 2, 'stripe_v10' => 0],
            'SEK' => ['numeric' => '752', 'decimals' => 2, 'stripe_v10' => 1],
            'SGD' => ['numeric' => '702', 'decimals' => 2, 'stripe_v10' => 1],
            'SHP' => ['numeric' => '654', 'decimals' => 2, 'stripe_v10' => 1],
            'SLE' => ['numeric' => '925', 'decimals' => 2, 'stripe_v10' => 1],
            'SLL' => ['numeric' => '694', 'decimals' => 2, 'stripe_v10' => 1],
            'SOS' => ['numeric' => '706', 'decimals' => 2, 'stripe_v10' => 1],
            'SRD' => ['numeric' => '968', 'decimals' => 2, 'stripe_v10' => 1],
            'SSP' => ['numeric' => '728', 'decimals' => 2, 'stripe_v10' => 0],
            'STN' => ['numeric' => '930', 'decimals' => 2, 'stripe_v10' => 0],
            'SVC' => ['numeric' => '222', 'decimals' => 2, 'stripe_v10' => 1],
            'SYP' => ['numeric' => '760', 'decimals' => 2, 'stripe_v10' => 1],
            'SZL' => ['numeric' => '748', 'decimals' => 2, 'stripe_v10' => 1],
            'THB' => ['numeric' => '764', 'decimals' => 2, 'stripe_v10' => 1],
            'TJS' => ['numeric' => '972', 'decimals' => 2, 'stripe_v10' => 1],
            'TOP' => ['numeric' => '776', 'decimals' => 2, 'stripe_v10' => 1],
            'TMT' => ['numeric' => '934', 'decimals' => 2, 'stripe_v10' => 0],
            'TND' => ['numeric' => '788', 'decimals' => 3, 'stripe_v10' => 0],
            'TRY' => ['numeric' => '949', 'decimals' => 2, 'stripe_v10' => 1],
            'TTD' => ['numeric' => '780', 'decimals' => 2, 'stripe_v10' => 1],
            'TWD' => ['numeric' => '901', 'decimals' => 2, 'stripe_v10' => 1],
            'TZS' => ['numeric' => '834', 'decimals' => 2, 'stripe_v10' => 1],
            'UAH' => ['numeric' => '980', 'decimals' => 2, 'stripe_v10' => 1],
            'UGX' => ['numeric' => '800', 'decimals' => 0, 'stripe_v10' => 1],
            'USD' => ['numeric' => '840', 'decimals' => 2, 'stripe_v10' => 1],
            'USN' => ['numeric' => '997', 'decimals' => 2, 'stripe_v10' => 0],
            'UYI' => ['numeric' => '940', 'decimals' => 0, 'stripe_v10' => 0],
            'UYU' => ['numeric' => '858', 'decimals' => 2, 'stripe_v10' => 1],
            'UYW' => ['numeric' => '927', 'decimals' => 4, 'stripe_v10' => 0],
            'UZS' => ['numeric' => '860', 'decimals' => 2, 'stripe_v10' => 1],
            'VED' => ['numeric' => '926', 'decimals' => 2, 'stripe_v10' => 0],
            'VES' => ['numeric' => '928', 'decimals' => 2, 'stripe_v10' => 0],
            'VEF' => ['numeric' => '937', 'decimals' => 2, 'stripe_v10' => 1],
            'VND' => ['numeric' => '704', 'decimals' => 0, 'stripe_v10' => 1],
            'VUV' => ['numeric' => '548', 'decimals' => 0, 'stripe_v10' => 1],
            'WST' => ['numeric' => '882', 'decimals' => 2, 'stripe_v10' => 1],
            'XAF' => ['numeric' => '950', 'decimals' => 0, 'stripe_v10' => 1],
            'XCD' => ['numeric' => '951', 'decimals' => 2, 'stripe_v10' => 1],
            'XOF' => ['numeric' => '952', 'decimals' => 0, 'stripe_v10' => 1],
            'XPF' => ['numeric' => '953', 'decimals' => 2, 'stripe_v10' => 1],
            'YER' => ['numeric' => '886', 'decimals' => 2, 'stripe_v10' => 1],
            'ZAR' => ['numeric' => '710', 'decimals' => 2, 'stripe_v10' => 1],
            'ZMW' => ['numeric' => '967', 'decimals' => 2, 'stripe_v10' => 1],
            'ZWL' => ['numeric' => '932', 'decimals' => 2, 'stripe_v10' => 1],
        ];
    }

    /**
     * Map of currency codes to their primary country codes
     */
    private const array CURRENCY_TO_COUNTRY = [
        'AED' => 'AE', // UAE Dirham
        'AFN' => 'AF', // Afghan Afghani
        'ALL' => 'AL', // Albanian Lek
        'AMD' => 'AM', // Armenian Dram
        'ANG' => 'CW', // Netherlands Antillean Guilder (Curaçao)
        'AOA' => 'AO', // Angolan Kwanza
        'ARS' => 'AR', // Argentine Peso
        'AUD' => 'AU', // Australian Dollar
        'AWG' => 'AW', // Aruban Florin
        'AZN' => 'AZ', // Azerbaijani Manat
        'BAM' => 'BA', // Bosnia-Herzegovina Convertible Mark
        'BBD' => 'BB', // Barbadian Dollar
        'BDT' => 'BD', // Bangladeshi Taka
        'BGN' => 'BG', // Bulgarian Lev
        'BHD' => 'BH', // Bahraini Dinar
        'BIF' => 'BI', // Burundian Franc
        'BMD' => 'BM', // Bermudan Dollar
        'BND' => 'BN', // Brunei Dollar
        'BOB' => 'BO', // Bolivian Boliviano
        'BRL' => 'BR', // Brazilian Real
        'BSD' => 'BS', // Bahamian Dollar
        'BTN' => 'BT', // Bhutanese Ngultrum
        'BWP' => 'BW', // Botswanan Pula
        'BYN' => 'BY', // Belarusian Ruble
        'BZD' => 'BZ', // Belize Dollar
        'CAD' => 'CA', // Canadian Dollar
        'CDF' => 'CD', // Congolese Franc
        'CHF' => 'CH', // Swiss Franc
        'CLP' => 'CL', // Chilean Peso
        'CNY' => 'CN', // Chinese Yuan
        'COP' => 'CO', // Colombian Peso
        'CRC' => 'CR', // Costa Rican Colón
        'CUC' => 'CU', // Cuban Convertible Peso
        'CUP' => 'CU', // Cuban Peso
        'CVE' => 'CV', // Cape Verdean Escudo
        'CZK' => 'CZ', // Czech Koruna
        'DJF' => 'DJ', // Djiboutian Franc
        'DKK' => 'DK', // Danish Krone
        'DOP' => 'DO', // Dominican Peso
        'DZD' => 'DZ', // Algerian Dinar
        'EGP' => 'EG', // Egyptian Pound
        'ERN' => 'ER', // Eritrean Nakfa
        'ETB' => 'ET', // Ethiopian Birr
        'EUR' => 'EU', // Euro
        'FJD' => 'FJ', // Fijian Dollar
        'FKP' => 'FK', // Falkland Islands Pound
        'GBP' => 'GB', // British Pound Sterling
        'GEL' => 'GE', // Georgian Lari
        'GHS' => 'GH', // Ghanaian Cedi
        'GIP' => 'GI', // Gibraltar Pound
        'GMD' => 'GM', // Gambian Dalasi
        'GNF' => 'GN', // Guinean Franc
        'GTQ' => 'GT', // Guatemalan Quetzal
        'GYD' => 'GY', // Guyanaese Dollar
        'HKD' => 'HK', // Hong Kong Dollar
        'HNL' => 'HN', // Honduran Lempira
        'HRK' => 'HR', // Croatian Kuna
        'HTG' => 'HT', // Haitian Gourde
        'HUF' => 'HU', // Hungarian Forint
        'IDR' => 'ID', // Indonesian Rupiah
        'ILS' => 'IL', // Israeli New Shekel
        'INR' => 'IN', // Indian Rupee
        'IQD' => 'IQ', // Iraqi Dinar
        'IRR' => 'IR', // Iranian Rial
        'ISK' => 'IS', // Icelandic Króna
        'JMD' => 'JM', // Jamaican Dollar
        'JOD' => 'JO', // Jordanian Dinar
        'JPY' => 'JP', // Japanese Yen
        'KES' => 'KE', // Kenyan Shilling
        'KGS' => 'KG', // Kyrgystani Som
        'KHR' => 'KH', // Cambodian Riel
        'KMF' => 'KM', // Comorian Franc
        'KPW' => 'KP', // North Korean Won
        'KRW' => 'KR', // South Korean Won
        'KWD' => 'KW', // Kuwaiti Dinar
        'KYD' => 'KY', // Cayman Islands Dollar
        'KZT' => 'KZ', // Kazakhstani Tenge
        'LAK' => 'LA', // Laotian Kip
        'LBP' => 'LB', // Lebanese Pound
        'LKR' => 'LK', // Sri Lankan Rupee
        'LRD' => 'LR', // Liberian Dollar
        'LSL' => 'LS', // Lesotho Loti
        'LYD' => 'LY', // Libyan Dinar
        'MAD' => 'MA', // Moroccan Dirham
        'MDL' => 'MD', // Moldovan Leu
        'MGA' => 'MG', // Malagasy Ariary
        'MKD' => 'MK', // Macedonian Denar
        'MMK' => 'MM', // Myanma Kyat
        'MNT' => 'MN', // Mongolian Tugrik
        'MOP' => 'MO', // Macanese Pataca
        'MUR' => 'MU', // Mauritian Rupee
        'MVR' => 'MV', // Maldivian Rufiyaa
        'MWK' => 'MW', // Malawian Kwacha
        'MXN' => 'MX', // Mexican Peso
        'MYR' => 'MY', // Malaysian Ringgit
        'MZN' => 'MZ', // Mozambican Metical
        'NAD' => 'NA', // Namibian Dollar
        'NGN' => 'NG', // Nigerian Naira
        'NIO' => 'NI', // Nicaraguan Córdoba
        'NOK' => 'NO', // Norwegian Krone
        'NPR' => 'NP', // Nepalese Rupee
        'NZD' => 'NZ', // New Zealand Dollar
        'OMR' => 'OM', // Omani Rial
        'PAB' => 'PA', // Panamanian Balboa
        'PEN' => 'PE', // Peruvian Nuevo Sol
        'PGK' => 'PG', // Papua New Guinean Kina
        'PHP' => 'PH', // Philippine Peso
        'PKR' => 'PK', // Pakistani Rupee
        'PLN' => 'PL', // Polish Zloty
        'PYG' => 'PY', // Paraguayan Guarani
        'QAR' => 'QA', // Qatari Rial
        'RON' => 'RO', // Romanian Leu
        'RSD' => 'RS', // Serbian Dinar
        'RUB' => 'RU', // Russian Ruble
        'RWF' => 'RW', // Rwandan Franc
        'SAR' => 'SA', // Saudi Riyal
        'SBD' => 'SB', // Solomon Islands Dollar
        'SCR' => 'SC', // Seychellois Rupee
        'SDG' => 'SD', // Sudanese Pound
        'SEK' => 'SE', // Swedish Krona
        'SGD' => 'SG', // Singapore Dollar
        'SHP' => 'SH', // Saint Helena Pound
        'SLE' => 'SL', // Sierra Leonean Leone
        'SLL' => 'SL', // Sierra Leonean Leone (old)
        'SOS' => 'SO', // Somali Shilling
        'SRD' => 'SR', // Surinamese Dollar
        'SSP' => 'SS', // South Sudanese Pound
        'SVC' => 'SV', // Salvadoran Colón
        'SYP' => 'SY', // Syrian Pound
        'SZL' => 'SZ', // Swazi Lilangeni
        'THB' => 'TH', // Thai Baht
        'TJS' => 'TJ', // Tajikistani Somoni
        'TMT' => 'TM', // Turkmenistani Manat
        'TND' => 'TN', // Tunisian Dinar
        'TOP' => 'TO', // Tongan Paʻanga
        'TRY' => 'TR', // Turkish Lira
        'TTD' => 'TT', // Trinidad and Tobago Dollar
        'TWD' => 'TW', // New Taiwan Dollar
        'TZS' => 'TZ', // Tanzanian Shilling
        'UAH' => 'UA', // Ukrainian Hryvnia
        'UGX' => 'UG', // Ugandan Shilling
        'USD' => 'US', // United States Dollar
        'UYU' => 'UY', // Uruguayan Peso
        'UZS' => 'UZ', // Uzbekistan Som
        'VEF' => 'VE', // Venezuelan Bolívar (old)
        'VES' => 'VE', // Venezuelan Bolívar
        'VND' => 'VN', // Vietnamese Dong
        'VUV' => 'VU', // Vanuatu Vatu
        'WST' => 'WS', // Samoan Tala
        'XAF' => 'CM', // Central African CFA Franc (using Cameroon)
        'XCD' => 'AG', // East Caribbean Dollar (using Antigua)
        'XOF' => 'SN', // West African CFA Franc (using Senegal)
        'XPF' => 'PF', // CFP Franc (French Polynesia)
        'YER' => 'YE', // Yemeni Rial
        'ZAR' => 'ZA', // South African Rand
        'ZMW' => 'ZM', // Zambian Kwacha
        'ZWL' => 'ZW', // Zimbabwean Dollar
        // Legacy currencies included in your list
        'EEK' => 'EE', // Estonian Kroon (now EUR)
        'LTL' => 'LT', // Lithuanian Litas (now EUR)
        'LVL' => 'LV', // Latvian Lats (now EUR)
        'MXV' => 'MX', // Mexican Unidad de Inversion
    ];
}
