<?php

declare(strict_types=1);

namespace App\Auth\Form;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;

final class TwoFactorAuthenticationVerifyLoginForm extends FormModel
{
    private string $code = '';

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return string[]
     *
     * @psalm-return array{code: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'code' => $this->translator->translate('layout.password.otp'),
        ];
    }

    /**
     * @psalm-return 'TwoFactorAuthenticationVerifyLogin'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'TwoFactorAuthenticationVerifyLogin';
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
