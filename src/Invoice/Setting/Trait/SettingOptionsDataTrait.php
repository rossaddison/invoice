<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository as sR;

trait SettingOptionsDataTrait
{
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

    public function optionsDataSettingsValue(sR $sR): array
    {
        $optionsDataSettings = [];
        $settings = $sR->findAllPreloaded();
        /**
         * @var Setting $setting
         */
        foreach ($settings as $setting) {
            $settingValue = $setting->getSettingValue();
            // Remove repeats
            if (!in_array($setting->getSettingValue(), $optionsDataSettings)) {
                $optionsDataSettings[$settingValue] = $setting->getSettingValue();
            }
        }
        return $optionsDataSettings;
    }
}
