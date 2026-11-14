<?php

declare(strict_types=1);

namespace App\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\AppConstants;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Enum\BusinessType;
use App\Invoice\Enum\ServiceType;
use App\Invoice\Setting\SettingRepository;

/**
 * Sets an install up as a window-cleaning business in one go.
 *
 * 1. Settings: business_type = window_cleaning (the front page, the staff menu and the dashboard
 *    read it), and the Webshop, Gateway status, Peppol status and HMRC API status links are taken
 *    off the public front page. Sign up, HomeCare sign up and Login are not touched. HomeCare QR
 *    Auto-Invoice is switched on. The lead worker, the current run, the last run date, the Vision API
 *    key and the hidden inv columns belong to the business and are never set here.
 * 2. Areas, runs and streets (CategoryPrimary, CategorySecondary and Family): so the sign up form's
 *    run dropdown is not empty and each run has streets to work with (see PlaceholderRuns). Only when
 *    the install has no run yet, so applying the preset to a working install never adds to its real
 *    runs. They are created all together or not at all, so a failure part way leaves no half set that
 *    a second press would mistake for real runs.
 * 3. The services (see ServiceType): services_offered is set to the ticked ones (window cleaning when
 *    none are given), and the Product catalogue gets the shared "HomeCare Service" product and one
 *    product for each service, from the start (see ServiceProducts). The current company's SEO
 *    description is filled with the services too, when it is empty or still the preset's own text (see
 *    CompanySeoDescription).
 *
 * Applying it again is safe: settings are only set again, and nothing is added once a run exists.
 */
final readonly class WindowCleaningPreset
{
    /** One run in its own area, for each of the 16 runs (4 a week over a 4 week cycle). */
    public const int DEFAULT_RUNS = 16;

    /**
     * The front page links a window-cleaning business does not use. no_front_X_page = '1' hides X.
     */
    private const array HIDDEN_FRONT_PAGE_SETTINGS = [
        AppConstants::SETTING_NO_FRONT_WEBSHOP_PAGE,
        AppConstants::SETTING_NO_FRONT_GATEWAY_STATUS_PAGE,
        AppConstants::SETTING_NO_FRONT_PEPPOL_STATUS_PAGE,
        AppConstants::SETTING_NO_FRONT_HMRC_API_STATUS_PAGE,
    ];

    /**
     * HomeCare Settings tab: scanning a customer's QR code requests their next invoice.
     */
    public const string HOMECARE_AUTO_INVOICE_SETTING = 'homecare_auto_invoice_enabled';

    public function __construct(
        private SettingRepository $sR,
        private CategorySecondaryRepository $csR,
        private PlaceholderRuns $placeholderRuns,
        private ServiceProducts $serviceProducts,
        private CompanySeoDescription $companySeo,
    ) {
    }

    /**
     * @param list<ServiceType> $services the services the business offers; none given means window
     *     cleaning
     * @return array{settings: int, areas: int, runs: int, streets: int, product: bool, seo: bool} how
     *     many settings were set, how many areas, runs and streets were created, whether every service
     *     product is in the catalogue, and whether the company's SEO description was written
     */
    public function apply(int $runs = self::DEFAULT_RUNS, array $services = []): array
    {
        $services = [] === $services ? [ServiceType::WindowCleaning] : $services;
        $previous = ServiceType::fromCsv($this->sR->getSetting(ServiceType::SETTING_KEY));
        $this->set(BusinessType::SETTING_KEY, BusinessType::WindowCleaning->value);
        $this->set(ServiceType::SETTING_KEY, ServiceType::toCsv($services));
        foreach (self::HIDDEN_FRONT_PAGE_SETTINGS as $key) {
            $this->set($key, '1');
        }
        $this->set(self::HOMECARE_AUTO_INVOICE_SETTING, '1');
        $created = ['areas' => 0, 'runs' => 0, 'streets' => 0];
        if ([] === $this->csR->optionsDataCategorySecondaries()) {
            $created = $this->placeholderRuns->create($runs);
        }
        // after the streets, so the product can take one of them
        $product = $this->serviceProducts->ensure($services);
        $seo = $this->companySeo->fill($services, $previous);
        // business_type, the services, the hidden front page links and the HomeCare switch
        return ['settings' => 3 + count(self::HIDDEN_FRONT_PAGE_SETTINGS)] + $created
            + ['product' => $product, 'seo' => $seo];
    }

    private function set(string $key, string $value): void
    {
        $setting = $this->sR->withKey($key);
        if (null === $setting) {
            $setting = new Setting();
            $setting->setSettingKey($key);
        }
        $setting->setSettingValue($value);
        $this->sR->save($setting);
        // withKey() reads the table, but getSetting() serves from a cache loaded once per request
        $this->sR->setSetting($key, $value);
    }
}
