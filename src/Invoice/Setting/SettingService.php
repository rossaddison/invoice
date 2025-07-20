<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Invoice\Entity\Setting;

final readonly class SettingService
{
    public function __construct(private SettingRepository $repository) {}

    /**
     * @param Setting $setting
     * @param array $body
     */
    public function saveSetting(Setting $setting, array $body): void
    {
        isset($body['setting_key']) ? $setting->setSetting_key((string) $body['setting_key']) : '';
        isset($body['setting_value']) ? $setting->setSetting_value((string) $body['setting_value']) : '';
        $this->repository->save($setting);
    }

    /**
     * @param Setting $setting
     */
    public function deleteSetting(Setting $setting): void
    {
        $this->repository->delete($setting);
    }
}
