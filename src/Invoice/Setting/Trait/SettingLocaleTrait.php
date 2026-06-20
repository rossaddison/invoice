<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Invoice\Libraries\Lang;

trait SettingLocaleTrait
{

/**
 * @return string[]
 *
 * @psalm-return array{
    English: 'en_GB',
    French: 'fr_FR',
    German: 'de_DE',
    Japan: 'jp_JP',
    Italian: 'it_IT',
    Spanish: 'es_ES'}
 */
    public function amazonLanguages(): array
    {
        return [
            'English' => 'en_GB',
            'French' => 'fr_FR',
            'German' => 'de_DE',
            'Japan' => 'jp_JP',
            'Italian' => 'it_IT',
            'Spanish' => 'es_ES',
        ];
    }

    /**
     * @return array
     */
    public function amazonRegions(): array
    {
        return [
            'North America' => 'na',
            'Japan' => 'jp',
            'Europe' => 'eu',
        ];
    }

    /**
     * @return array
     */
    public function localeLanguageArray(): array
    {
        // locale => src/Invoice/Language/{language folder name}
        return [
            'af-ZA' => 'AfrikaansSouthAfrican',
            'ar-BH' => 'ArabicBahrainian',
            'az' => 'Azerbaijani',
            'be-BY' => 'Belarusian',
            'bs' => 'Bosnian',
            'de' => 'German',
            'en' => 'English',
            'fil' => 'Filipino',
            'fr' => 'French',
            'ha-NG' => 'HausaNigerian',
            'id' => 'Indonesian',
            'ig-NG' => 'IgboNigerian',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'pl-PL' => 'Polish',
            'pt-BR' => 'PortugeseBrazil',
            'nl' => 'Dutch',
            'ru' => 'Russian',
            'sk' => 'Slovakian',
            'sl' => 'Slovenian',
            'es' => 'Spanish',
            'uk' => 'Ukrainian',
            'uz' => 'Uzbek',
            'vi' => 'Vietnamese',
            'yo-NG' => 'YorubaNigerian',
            'zh-CN' => 'ChineseSimplified',
            'zh-TW' => 'TaiwaneseMandarin',
            'zu-ZA' => 'ZuluSouthAfrican',
        ];
    }

    /**
     * Used in: Google Translate Dropdown Box in
     * resources/views/invoice/setting/views/
     *  partial_settings_google_translate.php
     * Related logic: SettingController function tabIndex()
     *  'google_translate' => ['locales']
     * @return array
     */
    public function locales(): array
    {
        return [
            'af-ZA',
            'ar-BH', 'az',
            'be', 'bg', 'bs',
            'ca', 'cs',
            'da', 'de',
            'el', 'es', 'et',
            'fa', 'fi', 'fil', 'fr',
            'gd-GB',
            'ha-NG', 'he-IL', 'hr', 'hu', 'hy',
            'id', 'it', 'ig-NG',
            'ja',
            'ka', 'kk', 'ko', 'kz',
            'lt', 'lv',
            'ms',
            'nb-NO', 'nl',
            'pl', 'pt', 'pt-BR',
            'ro', 'ru',
            'sk', 'sl', 'sr', 'sr-Latn', 'sv',
            'tg', 'th', 'tr',
            'uk', 'uz',
            'vi',
            'yo-NG',
            'zh-CN', 'zh-TW',
            'zu-ZA',
        ];
    }

    /**
     * @return array
     */
    public function loadLanguageFolder(): array
    {
        $folder_language = 'English';
        $lang = new Lang();
        $lang->load('gateway', $folder_language);
        return $lang->uLanguage;
    }

    public function getTermsAndConditions(): array
    {
        return [
            // I have not accepted the terms
            $this->translator->translate('term.1'),
            // I have accepted the terms
            $this->translator->translate('term.2'),
        ];
    }

    /**
     * @param string $in_line
     * @return string
     */
    public function lang(string $in_line = ''): string
    {
        return $this->translator->translate($in_line);
    }

    /**
     * @return array<string, string> locale code =>
     *  ISO 3166-1 alpha-2 country code (lowercase)
     */
    public function getLocaleFlags(): array
    {
        return [
            'af-ZA' => 'za',
            'ar-BH' => 'bh',
            'az'    => 'az',
            'be-BY' => 'by',
            'bs'    => 'ba',
            'zh-CN' => 'cn',
            'zh-TW' => 'tw',
            'en'    => 'gb',
            'fil'   => 'ph',
            'fr'    => 'fr',
            'gd-GB' => 'gb-sct',
            'ha-NG' => 'ng',
            'he-IL' => 'il',
            'nl'    => 'nl',
            'de'    => 'de',
            'id'    => 'id',
            'ig-NG' => 'ng',
            'it'    => 'it',
            'ja'    => 'jp',
            'pl'    => 'pl',
            'pt-BR' => 'br',
            'ru'    => 'ru',
            'sk'    => 'sk',
            'sl'    => 'si',
            'es'    => 'es',
            'uk'    => 'ua',
            'uz'    => 'uz',
            'vi'    => 'vn',
            'yo-NG' => 'ng',
            'zu-ZA' => 'za',
        ];
    }
}
