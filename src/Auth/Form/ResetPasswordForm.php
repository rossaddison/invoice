<?php

declare(strict_types=1);

namespace App\Auth\Form;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class ResetPasswordForm extends FormModel implements RulesProviderInterface
{
    private string $newPassword = '';
    private string $newPasswordVerify = '';

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @return string[]
     * @psalm-return array{newPassword: string, newPasswordVerify: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'newPassword' => $this->translator->translate('layout.password.new'),
            'newPasswordVerify' => $this->translator->translate('layout.password-verify.new'),
        ];
    }

    /**
     * @return string
     * @psalm-return 'ResetPassword'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'ResetPassword';
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function getNewPasswordVerify(): string
    {
        return $this->newPasswordVerify;
    }

    /**
     * {@inheritDoc}
     * @return iterable<int|string, callable|iterable<int, callable|\Yiisoft\Validator\RuleInterface>|\Yiisoft\Validator\RuleInterface>
     */
    #[\Override]
    public function getRules(): iterable
    {
        return [
            'newPassword' => [new Required()],
            'newPasswordVerify' => $this->newPasswordVerifyRules(),
        ];
    }

    /**
     * @return list<callable|\Yiisoft\Validator\RuleInterface>
     */
    private function newPasswordVerifyRules(): array
    {
        return [
            new Required(),
            new Callback(
                callback: function (): Result {
                    $result = new Result();
                    if ($this->newPassword !== $this->newPasswordVerify) {
                        $result->addError($this->translator->translate('validator.password.not.match.new'));
                    }
                    return $result;
                },
                skipOnEmpty: true,
            ),
        ];
    }
}
