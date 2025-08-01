<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\Setting\SettingRepository::class)]
class Setting
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(#[Column(type: 'string(50)')]
        private string $setting_key = '', #[Column(type: 'string(191)')]
        private string $setting_value = '') {}

    public function getSetting_id(): ?int
    {
        return $this->id;
    }

    public function getSetting_key(): string
    {
        return $this->setting_key;
    }

    public function setSetting_key(string $setting_key): void
    {
        $this->setting_key = $setting_key;
    }

    public function getSetting_value(): string
    {
        return $this->setting_value;
    }

    public function setSetting_value(string $setting_value): void
    {
        $this->setting_value = $setting_value;
    }
}
