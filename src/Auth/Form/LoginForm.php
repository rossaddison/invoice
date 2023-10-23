<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\Auth\AuthService;
use Yiisoft\Form\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class LoginForm extends FormModel implements RulesProviderInterface
{
    private string $login = '';
    private string $password = '';
    private bool $rememberMe = false;

    public function __construct(private AuthService $authService, private TranslatorInterface $translator)
    {
    }

    /**
     * @return string[]
     *
     * @psalm-return array{login: string, password: string, rememberMe: string}
     */
    public function getAttributeLabels(): array
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
    public function getFormName(): string
    {
        return 'Login';
    }

    /**
     * @return array
     * @psalm-suppress ImplementedReturnTypeMismatch 
     */
    public function getRules(): array
    {
        return [
            'login' => [new Required()],
            'password' => $this->passwordRules(),
        ];
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
