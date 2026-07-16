<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Infrastructure\Persistence\Setting\Setting;

interface SettingRepositoryInterface
{
    public function getSetting(string $key): string;

    public function withKey(string $setting_key): ?Setting;

    public function save(?Setting $setting): void;
}
