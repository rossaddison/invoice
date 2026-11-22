<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Worker\WorkerRepository as WR;

trait SettingOptionsDataTrait
{
    /** The most characters an option of the setting-value filter shows. */
    public const int VALUE_FILTER_LABEL_WIDTH = 40;

    public function optionsDataSettingsKey(sR $sR): array
    {
        $optionsDataSettings = [];
        $settings = $sR->findAllPreloaded();
        /**
         * @var Setting $setting
         */
        foreach ($settings as $setting) {
            $settingKey = $setting->getSettingKey();
            // Remove repeats
            if (!in_array($setting->getSettingKey(), $optionsDataSettings)) {
                $optionsDataSettings[$settingKey] = $setting->getSettingKey();
            }
        }
        return $optionsDataSettings;
    }

    /**
     * The options of the setting-value filter: each distinct stored value, as the key the filter matches on, with a
     * label shortened to VALUE_FILTER_LABEL_WIDTH characters.
     *
     * @return array<array-key, string>
     */
    public function optionsDataSettingsValue(sR $sR): array
    {
        $optionsDataSettings = [];
        $settings = $sR->findAllPreloaded();
        /**
         * @var Setting $setting
         */
        foreach ($settings as $setting) {
            $settingValue = $setting->getSettingValue();
            // Remove repeats. The key is the full value, which is what the filter matches on; the label is shortened,
            // because a select is as wide as its widest option and one long value (an API key, a URL, a template)
            // stretched the filter, then the whole table, past the window, taking the row buttons with it.
            if (!isset($optionsDataSettings[$settingValue])) {
                $optionsDataSettings[$settingValue] = mb_strimwidth(
                    $settingValue,
                    0,
                    self::VALUE_FILTER_LABEL_WIDTH,
                    '…',
                );
            }
        }
        return $optionsDataSettings;
    }

    /**
     * Active workers for the HomeCare lead-worker dropdown.
     *
     * @return array<int, string> worker id => "Firstname Lastname"
     */
    public function optionsDataWorkers(WR $wR): array
    {
        $options = [];
        foreach ($wR->findAllActive() as $worker) {
            $options[$worker->reqId()] = trim($worker->getFirstname() . ' ' . $worker->getLastname());
        }
        return $options;
    }
}
