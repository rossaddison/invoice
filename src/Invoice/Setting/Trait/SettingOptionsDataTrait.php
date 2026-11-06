<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Worker\WorkerRepository as WR;

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
