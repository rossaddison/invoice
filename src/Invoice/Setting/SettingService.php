<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Invoice\Entity\Setting;

final class SettingService
{
    private SettingRepository $repository;

    public function __construct(SettingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Setting $setting
     * @param array $body
     * @return void
     */
    public function saveSetting(Setting $setting, array $body): void
    {
        isset($body['setting_key']) ? $setting->setSetting_key((string)$body['setting_key']) : '';
        isset($body['setting_value']) ? $setting->setSetting_value((string)$body['setting_value']) : '';
        $this->repository->save($setting);
    }
    
    /**
     * 
     * @param Setting $setting
     * @return void
     */
    public function deleteSetting(Setting $setting): void
    {
        $this->repository->delete($setting);
    }
}
