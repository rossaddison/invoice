<?php

declare(strict_types=1);

namespace App\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Enum\BusinessType;
use App\Invoice\Setting\SettingRepository;

/**
 * Sets an install up as a window-cleaning business in one go.
 *
 * 1. Settings: business_type = window_cleaning (the front page, the staff menu and the dashboard
 *    read it), and the Webshop, Gateway status, Peppol status and HMRC API status links are taken
 *    off the public front page. Sign up, HomeCare sign up and Login are not touched. HomeCare QR
 *    Auto-Invoice is switched on. The lead worker, the current run, the last run date, the Vision API
 *    key and the hidden inv columns belong to the business and are never set here.
 * 2. Areas and runs (CategoryPrimary and CategorySecondary): so the sign up form's run dropdown is
 *    not empty. Only when the install has no run yet, so applying the preset to a working install
 *    never adds to its real runs. The names are placeholders ("Example Area 01" / "Example Run 01");
 *    streets (Family) are created by sign ups and by the run import, never here.
 *
 * Applying it again is safe: settings are only set again, and nothing is added once a run exists.
 */
final readonly class WindowCleaningPreset
{
    /** One run in its own area, for each of the 16 runs. */
    public const int DEFAULT_RUNS = 16;

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
        private CategoryPrimaryRepository $cpR,
        private CategoryPrimaryService $cpS,
        private CategorySecondaryRepository $csR,
        private CategorySecondaryService $csS,
    ) {
    }

    /**
     * @return array{settings: int, areas: int, runs: int} how many settings were set and how many
     *     areas and runs were created
     */
    public function apply(int $runs = self::DEFAULT_RUNS): array
    {
        $this->set(BusinessType::SETTING_KEY, BusinessType::WindowCleaning->value);
        foreach (self::HIDDEN_FRONT_PAGE_SETTINGS as $key) {
            $this->set($key, '1');
        }
        $this->set(self::HOMECARE_AUTO_INVOICE_SETTING, '1');
        $created = ['areas' => 0, 'runs' => 0];
        if ([] === $this->csR->optionsDataCategorySecondaries()) {
            $created = $this->createAreasAndRuns($runs);
        }
        // business_type, the hidden front page links and the HomeCare switch
        return ['settings' => 2 + count(self::HIDDEN_FRONT_PAGE_SETTINGS)] + $created;
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
     * @return array{areas: int, runs: int}
     */
    private function createAreasAndRuns(int $runs): array
    {
        $areas = 0;
        for ($n = 1; $n <= $runs; $n++) {
            $area = new CategoryPrimary();
            $this->cpS->saveCategoryPrimary($area, ['name' => sprintf('Example Area %02d', $n)]);
            $run = new CategorySecondary();
            $this->csS->saveCategorySecondary($run, [
                'category_primary_id' => (string) $area->reqId(),
                'name' => sprintf('Example Run %02d', $n),
            ]);
            ++$areas;
        }
        return ['areas' => $areas, 'runs' => $areas];
    }
}
