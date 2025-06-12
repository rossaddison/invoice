<?php

declare(strict_types=1);

namespace App\Auth\Form;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Rule\Required;

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
     * @return (Required)[]
     *
     * @psalm-return list{Required}
     */
    private function codeRules(): array
    {
        return [
            new Required(),
        ];
    }
}
