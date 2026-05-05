<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Infrastructure\Persistence\Setting\Setting;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class SettingForm extends FormModel
{
    #[Required]
    #[Length(min: 0, max: 100)]
    private ?string $setting_key = null;
    #[Required]
    #[Length(min: 0, max: 191)]
    private ?string $setting_value = null;

    public static function show(Setting $setting): self
    {
        $form = new self();
        $form->setting_key = $setting->getSettingKey();
        $form->setting_value = $setting->getSettingValue();
        return $form;
    }

    public function getSettingKey(): ?string
    {
        return $this->setting_key;
    }

    public function getSettingValue(): ?string
    {
        return $this->setting_value;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
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
