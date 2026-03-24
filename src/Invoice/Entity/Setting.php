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

    public function __construct(#[Column(type: 'string(100)')]
        private string $setting_key = '', #[Column(type: 'string(191)')]
        private string $setting_value = '')
    {
    }

    public function getSettingId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): string
    {
        return $this->setting_key;
    }

    public function setSettingKey(string $setting_key): void
    {
        $this->setting_key = $setting_key;
    }

    public function getSettingValue(): string
    {
        return $this->setting_value;
    }

    public function setSettingValue(string $setting_value): void
    {
        $this->setting_value = $setting_value;
    }
}
