<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\User\User;
use App\User\UserRepository;
use Yiisoft\Form\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Equal;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Validator\RulesProviderInterface;

final class SignupForm extends FormModel implements RulesProviderInterface
{
    private string $login = '';
    private string $password = '';
    private string $passwordVerify = '';

    public function __construct(
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @return string[]
     *
     * @psalm-return array{login: string, password: string, passwordVerify: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'login' => $this->translator->translate('layout.login'),
            'password' => $this->translator->translate('layout.password'),
            'passwordVerify' => $this->translator->translate('layout.password-verify'),
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'Signup'
     */
    public function getFormName(): string
    {
        return 'Signup';
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function signup(): false|User
    {
        if ($this->validator->validate($this)->isValid()) {
            $user = new User($this->getLogin(), $this->getPassword());
            $this->userRepository->save($user);
            
            return $user;
        }

        return false;
    }

    /**
     * @return (Equal|Length|Required|\Closure)[][]
     *
     * @psalm-return array{login: list{Required, Length, \Closure(mixed):Result}, password: list{Required, Length}, passwordVerify: list{Required, Equal}}
     */
    public function getRules(): array
    {
        return [
            'login' => [
                new Required(),
                new Length(min: 1, max: 48, skipOnError: true),
                function (mixed $value): Result {
                    $result = new Result();
                    if ($this->userRepository->findByLogin((string)$value) !== null) {
                        $result->addError($this->translator->translate('validator.user.exist'));
                    }
                    return $result;
                },
            ],
            'password' => [
                new Required(),
                new Length(min: 8),
            ],
            'passwordVerify' => [
                new Required(),
                new Equal(
                    targetValue: $this->password,
                    message: $this->translator->translate('validator.password.not.match')
                ),
            ],
        ];
    }
}
