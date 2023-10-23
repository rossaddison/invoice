<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use Yiisoft\Form\FormModel;

final class SettingLogoForm extends FormModel
{
    private ?array $attachLogoFile = null; 

    /**
     * @return string
     *
     * @psalm-return 'SettingLogoForm'
     */
    public function getFormName(): string
    {
        return 'SettingLogoForm';
    }
}
