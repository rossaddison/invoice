<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Invoice\Entity\Setting;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SettingForm extends FormModel
{
    private ?string $setting_key = null;
    private ?string $setting_value = null;

    public function __construct(Setting $setting)
    {
        $this->setting_key = $setting->getSetting_key();
        $this->setting_value = $setting->getSetting_value();
    }

    public function getSetting_key(): string|null
    {
        return $this->setting_key;
    }

    public function getSetting_value(): string|null
    {
        return $this->setting_value;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }

    /**
     * @return Required[][]
     *
     * @psalm-return array{setting_key: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'setting_key' => [new Required()],
        ];
    }
}
