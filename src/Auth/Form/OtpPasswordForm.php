<?php

declare(strict_types=1);

namespace App\Auth\Form;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class OtpPasswordForm extends FormModel implements RulesProviderInterface
{
    private string $otpPassword = '';

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @return string[]
     *
     * @psalm-return array{otpPassword: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'otpPassword' => $this->translator->translate('layout.password.otp'),
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'OtpPassword'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'OtpPassword';
    }

    public function getOtpPassword(): string
    {
        return $this->otpPassword;
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'otpPassword' => $this->otpPasswordRules(),
        ];
    }

    /**
     * @return (Required)[]
     *
     * @psalm-return list{Required}
     */
    private function otpPasswordRules(): array
    {
        return [
            new Required(),
        ];
    }
}
