<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\User\UserRepository;
use App\Auth\AuthService;
use Yiisoft\Form\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Validator\RulesProviderInterface;

final class ResetForm extends FormModel implements RulesProviderInterface
{
    private string $login = '';
    private string $password = '';
    private string $password_verify = '';
    private string $new_password = '';
    private string $new_password_verify = '';
    
    public function __construct(
        private AuthService $authService,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
        private UserRepository $userRepository,
    ) {
    }
    
    public function reset(): bool
    {
        if ($this->validator->validate($this)->isValid()) {
            $user = $this->userRepository->findByLogin($this->getLogin());
            if (null!==$user) {
              $user->setPassword($this->getNewPassword());
              // The cookie identity auth_key is regenerated on logout
              // Refer to ResetController reset function
              $this->userRepository->save($user);
              return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{login: string, password: string, password_verify: string, new_password: string, new_password_verify: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'login' => $this->translator->translate('layout.login'),
            'password' => $this->translator->translate('layout.password'),
            'password_verify' => $this->translator->translate('layout.password-verify'),
            'new_password' => $this->translator->translate('layout.password.new'),
            'new_password_verify' => $this->translator->translate('layout.password-verify.new'),
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'Reset'
     */
    public function getFormName(): string
    {
        return 'Reset';
    }
    
    public function getLogin(): string
    {
        return $this->login;
    }
    
    public function getPassword(): string
    {
        return $this->password;
    }
    
    public function getPasswordVerify(): string
    {  
        return $this->password_verify;
    }
    
    public function getNewPassword(): string
    {  
        return $this->new_password;
    }
    
    public function getNewPasswordVerify() : string
    {
        return $this->new_password_verify;
    }
    
    public function getRules(): array
    {
        return [
            'login' => [
                new Required(),
                new Length(min: 1, max: 48, skipOnError: true),
                function (mixed $value): Result {
                    $result = new Result();
                    if ($this->userRepository->findByLogin((string)$value) == null) {
                        $result->addError($this->translator->translate('validator.user.exist.not'));
                    }
                    return $result;
                },
            ],
            'password' => $this->PasswordRules(),
            'password_verify' => $this->PasswordVerifyRules(),
            'new_password' => [
                new Required(),
                new Length(min: 8),
            ], 
            'new_password_verify' => $this->NewPasswordVerifyRules()
        ];
    }
    
    private function PasswordRules(): array
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
        
    private function PasswordVerifyRules(): array
    {
        return [
            new Required(),
            new Callback(
                callback: function (): Result {
                    $result = new Result();
                    if (!($this->password === $this->password_verify)) {
                        $result->addError($this->translator->translate('validator.password.not.match'));
                    }
                    return $result;
                },
                skipOnEmpty: true,
            ),
        ];
    }
    
    private function NewPasswordVerifyRules(): array
    {
        return [
            new Required(),
            new Callback(
                callback: function (): Result {
                    $result = new Result();
                    if (!($this->new_password === $this->new_password_verify)) {
                      $result->addError($this->translator->translate('validator.password.not.match.new'));
                    }
                    return $result;
                },
                skipOnEmpty: true,
            ),
        ];
    }
}
