<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\User\UserRepository;
use App\Auth\AuthService;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Validator\RulesProviderInterface;

final class ChangePasswordForm extends FormModel implements RulesProviderInterface
{
    private string $login = '';
    private string $password = '';
    private string $newPassword = '';
    private string $newPasswordVerify = '';

    public function __construct(
        private readonly AuthService $authService,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
    ) {}

    public function change(): bool
    {
        if ($this->validator->validate($this)->isValid()) {
            $user = $this->userRepository->findByLogin($this->getLogin());
            if (null !== $user) {
                $user->setPassword($this->getNewPassword());
                $this->userRepository->save($user);
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     * @psalm-return array{login: string, password: string, newPassword: string, newPasswordVerify: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'login' => $this->translator->translate('layout.login'),
            'password' => $this->translator->translate('layout.password'),
            'newPassword' => $this->translator->translate('layout.password.new'),
            'newPasswordVerify' => $this->translator->translate('layout.password-verify.new'),
        ];
    }

    /**
     * @return string
     * @psalm-return 'ChangePassword'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'ChangePassword';
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
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
            'login' => [new Required()],
            'password' => $this->passwordRules(),
            'newPassword' => [new Required()],
            'newPasswordVerify' => $this->newPasswordVerifyRules(),
        ];
    }

    /**
     * @return list<callable|\Yiisoft\Validator\RuleInterface>
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
