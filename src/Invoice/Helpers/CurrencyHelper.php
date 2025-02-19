<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

class CurrencyHelper
{
    /**
     * Create a new Currency object
     */
    private function __construct(private readonly mixed $code, private readonly mixed $numeric, private readonly mixed $decimals)
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
        return (int)$this->decimals;
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
            return new self($code, $currencies[$code]['numeric'], $currencies[$code]['decimals']);
        }
        return null;
    }

    /**
     * @see ISO 4217 alpha-3 ie. 3 letter currency codes
     * Get an array of all supported currencies
     *
     * @return (int|string)[][]
     *
     * @psalm-return array{AED: array{numeric: '784', decimals: 2, stripe_v10: 1}, AFN: array{numeric: '971', decimals: 2, stripe_v10: 1}, ALL: array{numeric: '008', decimals: 2, stripe_v10: 1}, AMD: array{numeric: '051', decimals: 2, stripe_v10: 1}, ANG: array{numeric: '532', decimals: 2, stripe_v10: 1}, AOA: array{numeric: '973', decimals: 2, stripe_v10: 1}, ARS: array{numeric: '032', decimals: 2, stripe_v10: 1}, AUD: array{numeric: '036', decimals: 2, stripe_v10: 1}, AWG: array{numeric: '533', decimals: 2, stripe_v10: 1}, AZN: array{numeric: '944', decimals: 2, stripe_v10: 1}, BAM: array{numeric: '977', decimals: 2, stripe_v10: 1}, BBD: array{numeric: '052', decimals: 2, stripe_v10: 1}, BDT: array{numeric: '050', decimals: 2, stripe_v10: 1}, BGN: array{numeric: '975', decimals: 2, stripe_v10: 1}, BHD: array{numeric: '048', decimals: 3, stripe_v10: 1}, BIF: array{numeric: '108', decimals: 0, stripe_v10: 1}, BMD: array{numeric: '060', decimals: 2, stripe_v10: 1}, BND: array{numeric: '096', decimals: 2, stripe_v10: 1}, BOB: array{numeric: '068', decimals: 2, stripe_v10: 1}, BOV: array{numeric: '984', decimals: 2, stripe_v10: 0}, BRL: array{numeric: '986', decimals: 2, stripe_v10: 1}, BSD: array{numeric: '044', decimals: 2, stripe_v10: 1}, BTN: array{numeric: '064', decimals: 2, stripe_v10: 1}, BWP: array{numeric: '072', decimals: 2, stripe_v10: 1}, BYN: array{numeric: '933', decimals: 2, stripe_v10: 1}, BZD: array{numeric: '084', decimals: 2, stripe_v10: 1}, CAD: array{numeric: '124', decimals: 2, stripe_v10: 1}, CDF: array{numeric: '976', decimals: 2, stripe_v10: 1}, CHE: array{numeric: '947', decimals: 2, stripe_v10: 0}, CHF: array{numeric: '756', decimals: 2, stripe_v10: 1}, CHW: array{numeric: '948', decimals: 2, stripe_v10: 0}, CLF: array{numeric: '990', decimals: 4, stripe_v10: 0}, CLP: array{numeric: '152', decimals: 0, stripe_v10: 1}, CNY: array{numeric: '156', decimals: 2, stripe_v10: 1}, COP: array{numeric: '170', decimals: 2, stripe_v10: 1}, COU: array{numeric: '970', decimals: 2, stripe_v10: 0}, CRC: array{numeric: '188', decimals: 2, stripe_v10: 1}, CUC: array{numeric: '931', decimals: 2, stripe_v10: 0}, CUP: array{numeric: '192', decimals: 2, stripe_v10: 0}, CVE: array{numeric: '132', decimals: 2, stripe_v10: 1}, CZK: array{numeric: '203', decimals: 2, stripe_v10: 1}, DJF: array{numeric: '262', decimals: 0, stripe_v10: 1}, DKK: array{numeric: '208', decimals: 2, stripe_v10: 1}, DOP: array{numeric: '214', decimals: 2, stripe_v10: 1}, DZD: array{numeric: '012', decimals: 2, stripe_v10: 1}, EEK: array{numeric: '233', decimals: 2, stripe_v10: 1}, EGP: array{numeric: '818', decimals: 2, stripe_v10: 1}, ERN: array{numeric: '232', decimals: 2, stripe_v10: 1}, ETB: array{numeric: '230', decimals: 2, stripe_v10: 1}, EUR: array{numeric: '978', decimals: 2, stripe_v10: 1}, FJD: array{numeric: '242', decimals: 2, stripe_v10: 1}, FKP: array{numeric: '238', decimals: 2, stripe_v10: 1}, GBP: array{numeric: '826', decimals: 2, stripe_v10: 1}, GEL: array{numeric: '981', decimals: 2, stripe_v10: 1}, GHS: array{numeric: '936', decimals: 2, stripe_v10: 1}, GIP: array{numeric: '292', decimals: 2, stripe_v10: 1}, GMD: array{numeric: '270', decimals: 2, stripe_v10: 1}, GNF: array{numeric: '324', decimals: 0, stripe_v10: 1}, GTQ: array{numeric: '320', decimals: 2, stripe_v10: 1}, GYD: array{numeric: '328', decimals: 2, stripe_v10: 1}, HKD: array{numeric: '344', decimals: 2, stripe_v10: 1}, HNL: array{numeric: '340', decimals: 2, stripe_v10: 1}, HRK: array{numeric: '191', decimals: 2, stripe_v10: 1}, HTG: array{numeric: '332', decimals: 2, stripe_v10: 1}, HUF: array{numeric: '348', decimals: 2, stripe_v10: 1}, IDR: array{numeric: '360', decimals: 2, stripe_v10: 1}, ILS: array{numeric: '376', decimals: 2, stripe_v10: 1}, INR: array{numeric: '356', decimals: 2, stripe_v10: 1}, IQD: array{numeric: '368', decimals: 3, stripe_v10: 0}, IRR: array{numeric: '364', decimals: 2, stripe_v10: 0}, ISK: array{numeric: '352', decimals: 0, stripe_v10: 1}, JMD: array{numeric: '388', decimals: 2, stripe_v10: 1}, JOD: array{numeric: '400', decimals: 3, stripe_v10: 1}, JPY: array{numeric: '392', decimals: 0, stripe_v10: 1}, KES: array{numeric: '404', decimals: 2, stripe_v10: 1}, KGS: array{numeric: '417', decimals: 2, stripe_v10: 1}, KHR: array{numeric: '116', decimals: 2, stripe_v10: 1}, KMF: array{numeric: '174', decimals: 0, stripe_v10: 1}, KPW: array{numeric: '408', decimals: 2, stripe_v10: 0}, KRW: array{numeric: '410', decimals: 0, stripe_v10: 1}, KWD: array{numeric: '414', decimals: 3, stripe_v10: 1}, KYD: array{numeric: '136', decimals: 2, stripe_v10: 1}, KZT: array{numeric: '398', decimals: 2, stripe_v10: 1}, LAK: array{numeric: '418', decimals: 0, stripe_v10: 1}, LBP: array{numeric: '422', decimals: 2, stripe_v10: 1}, LKR: array{numeric: '144', decimals: 2, stripe_v10: 1}, LRD: array{numeric: '430', decimals: 2, stripe_v10: 1}, LSL: array{numeric: '426', decimals: 2, stripe_v10: 1}, LYD: array{numeric: '434', decimals: 3, stripe_v10: 0}, LTL: array{numeric: '440', decimals: 2, stripe_v10: 1}, LVL: array{numeric: '428', decimals: 2, stripe_v10: 1}, MAD: array{numeric: '504', decimals: 2, stripe_v10: 1}, MDL: array{numeric: '498', decimals: 2, stripe_v10: 1}, MGA: array{numeric: '969', decimals: 2, stripe_v10: 1}, MKD: array{numeric: '807', decimals: 2, stripe_v10: 1}, MMK: array{numeric: '104', decimals: 2, stripe_v10: 1}, MNT: array{numeric: '496', decimals: 2, stripe_v10: 1}, MOP: array{numeric: '446', decimals: 2, stripe_v10: 1}, MRU: array{numeric: '929', decimals: 2, stripe_v10: 0}, MUR: array{numeric: '480', decimals: 2, stripe_v10: 1}, MVR: array{numeric: '462', decimals: 2, stripe_v10: 1},...}
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
}
