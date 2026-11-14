<?php

declare(strict_types=1);

namespace App\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Enum\BusinessType;
use App\Invoice\Family\FamilyService;
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
 *    run dropdown is not empty and each run has streets to work with. Only when the install has no
 *    run yet, so applying the preset to a working install never adds to its real runs. The names are
 *    placeholders: "Example Area 01", "Example Run 01" and, under each run, "Street 1 on Run 1" to
 *    "Street 30 on Run 1".
 * 3. The default service: the shared "HomeCare Service" product every invoice line uses, so it is in
 *    the Product catalogue from the start (see WindowCleaningServiceProduct).
 *
 * Applying it again is safe: settings are only set again, and nothing is added once a run exists.
 */
final readonly class WindowCleaningPreset
{
    /** One run in its own area, for each of the 16 runs (4 a week over a 4 week cycle). */
    public const int DEFAULT_RUNS = 16;

    public const int STREETS_PER_RUN = 30;

    /**
     * The front page links a window-cleaning business does not use. no_front_X_page = '1' hides X.
     */
    private const array HIDDEN_FRONT_PAGE_SETTINGS = [
        'no_front_webshop_page',
        'no_front_gateway_status_page',
        'no_front_peppol_status_page',
        'no_front_hmrc_api_status_page',
    ];

    /**
     * HomeCare Settings tab: scanning a customer's QR code requests their next invoice.
     */
    public const string HOMECARE_AUTO_INVOICE_SETTING = 'homecare_auto_invoice_enabled';

    public function __construct(
        private SettingRepository $sR,
        private CategoryPrimaryService $cpS,
        private CategorySecondaryRepository $csR,
        private CategorySecondaryService $csS,
        private FamilyService $fS,
        private WindowCleaningServiceProduct $serviceProduct,
    ) {
    }

    /**
     * @return array{settings: int, areas: int, runs: int, streets: int, product: bool} how many
     *     settings were set, how many areas, runs and streets were created, and whether the default
     *     service is in the catalogue
     */
    public function apply(int $runs = self::DEFAULT_RUNS): array
    {
        $this->set(BusinessType::SETTING_KEY, BusinessType::WindowCleaning->value);
        foreach (self::HIDDEN_FRONT_PAGE_SETTINGS as $key) {
            $this->set($key, '1');
        }
        $this->set(self::HOMECARE_AUTO_INVOICE_SETTING, '1');
        $created = ['areas' => 0, 'runs' => 0, 'streets' => 0];
        if ([] === $this->csR->optionsDataCategorySecondaries()) {
            $created = $this->createAreasRunsAndStreets($runs);
        }
        // after the streets, so the product can take one of them
        $product = $this->serviceProduct->ensure();
        // business_type, the hidden front page links and the HomeCare switch
        return ['settings' => 2 + count(self::HIDDEN_FRONT_PAGE_SETTINGS)] + $created + ['product' => $product];
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

    /**
     * @return array{areas: int, runs: int, streets: int}
     */
    private function createAreasRunsAndStreets(int $runs): array
    {
        $streets = 0;
        for ($n = 1; $n <= $runs; $n++) {
            $area = new CategoryPrimary();
            $this->cpS->saveCategoryPrimary($area, ['name' => sprintf('Example Area %02d', $n)]);
            $run = new CategorySecondary();
            $this->csS->saveCategorySecondary($run, [
                'category_primary_id' => (string) $area->reqId(),
                'name' => sprintf('Example Run %02d', $n),
            ]);
            for ($s = 1; $s <= self::STREETS_PER_RUN; $s++) {
                $this->fS->findOrCreateByStreetName(sprintf('Street %d on Run %d', $s, $n), $run->reqId());
                ++$streets;
            }
        }
        return ['areas' => $runs, 'runs' => $runs, 'streets' => $streets];
    }
}
