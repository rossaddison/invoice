<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

interface SettingRepositoryInterface
{
    public function getSetting(string $key): string;
}
