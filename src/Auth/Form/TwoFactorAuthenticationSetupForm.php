<?php

declare(strict_types=1);

namespace App\Auth\Form;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Regex;

final class TwoFactorAuthenticationSetupForm extends FormModel
{
    #[Required]
    private string $code = '';

    public function __construct(
        private readonly TranslatorInterface $translator
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
     * @return string
     *
     * @psalm-return 'TwoFactorAuthenticationSetup'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'TwoFactorAuthenticationSetup';
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getRules(): array
    {
        return [
            'code' => $this->codeRules(),
        ];
    }

    /**
     * @return (Required|Length|Regex)[]
     *
     * @psalm-return list{Required, Length, Regex}
     */
    private function codeRules(): array
    {
        return [
            new Required(),
            // TOTP codes are exactly 6 digits during setup
            new Length(min: 6, max: 6),
            // Only allow digits for TOTP codes during setup
            new Regex('/^\d{6}$/', message: $this->translator->translate('layout.password.otp.setup.invalid.format')),
        ];
    }
}
