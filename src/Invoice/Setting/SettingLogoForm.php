<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use Yiisoft\FormModel\FormModel;

final class SettingLogoForm extends FormModel
{
    private ?array $attachLogoFile = null;

    /**
     * @psalm-return 'SettingLogoForm'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'SettingLogoForm';
    }
}
