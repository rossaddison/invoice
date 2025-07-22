<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\Auth\AuthService;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\PropertyTranslator\ArrayPropertyTranslator;
use Yiisoft\Validator\PropertyTranslatorInterface;
use Yiisoft\Validator\PropertyTranslatorProviderInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class LoginForm extends FormModel implements RulesProviderInterface, PropertyTranslatorProviderInterface
{
    private string $login = '';
    private string $password = '';
    private bool $rememberMe = false;

    public function __construct(private readonly AuthService $authService, private readonly TranslatorInterface $translator) {}

    /**
     * @return string[]
     *
     * @psalm-return array{login: string, password: string, rememberMe: string}
     */
    #[\Override]
    public function getPropertyLabels(): array
    {
        return [
            'login' => $this->translator->translate('layout.login'),
            'password' => $this->translator->translate('layout.password'),
            'rememberMe' => $this->translator->translate('layout.remember'),
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'Login'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'Login';
    }

    #[\Override]
    public function getPropertyTranslator(): ?PropertyTranslatorInterface
    {
        return new ArrayPropertyTranslator($this->getPropertyLabels());
    }

    /**
     * @return array
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    #[\Override]
    public function getRules(): array
    {
        return [
            'login' => $this->loginRules(),
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * Purpose: Use the yiisoft/validator's error messages folder
     * Related logic: see config/common/di/translator.php
     * Related logic: see config/common/params.php 'yiisoft/translator' => ['validatorCategory' => 'yii-validator']
     * Related logic: see config/common/params.php 'yiisoft/aliases' => ['aliases' => ['@validatorMessages' => '@vendor/yiisoft/validator/messages']]
     *
     * @return array
     */
    private function loginRules(): array
    {
        $propertyLabels = $this->getPropertyLabels();
        /**
         * @var string $propertyLabels['login']
         */
        $login = $propertyLabels['login'];
        $required = new Required();
        $englishErrorMessageId = $required->getMessage();
        $currentLocale = $this->translator->getLocale();
        $translatedErrorMessage = $this->translator->translate($englishErrorMessageId, [], 'yii-validator', $currentLocale);
        return [new Required(str_replace('{Property}', $login, $translatedErrorMessage))];
    }

    /**
     * @return (Callback|Required)[]
     *
     * @psalm-return list{Required, Callback}
     */
    private function passwordRules(): array
    {
        return [
            new Required(),
            new Callback(
                callback: function (): Result {
                    $result = new Result();

                    if (!$this->authService->login($this->login, $this->password)) {
                        $result->addError($this->translator->translate('validator.invalid.login.password'));
                    }

                    return $result;
                },
                skipOnEmpty: true,
            ),
        ];
    }
}
