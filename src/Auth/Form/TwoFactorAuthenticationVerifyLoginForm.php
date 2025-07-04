<?php

declare(strict_types=1);

namespace App\Auth\Form;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Regex;

final class TwoFactorAuthenticationVerifyLoginForm extends FormModel
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
            // Allow both 6-digit TOTP codes and 8-character backup recovery codes
            new Length(min: 6, max: 8),
            // Only allow digits for TOTP codes or alphanumeric for backup codes
            new Regex('/^[A-Za-z0-9]+$/', message: $this->translator->translate('layout.password.otp.invalid.format')),
        ];
    }
}
