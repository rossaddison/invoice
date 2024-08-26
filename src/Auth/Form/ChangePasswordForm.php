<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\User\UserRepository;
use App\Auth\AuthService;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Length;
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
        private AuthService $authService,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
        private UserRepository $userRepository,
    ) {
    }
    
    public function change(): bool
    {
        if ($this->validator->validate($this)->isValid()) {
            $user = $this->userRepository->findByLogin($this->getLogin());
            if (null!==$user) {
                // Apply a new hash to the new password and save the resultant passwordHash
                $user->setPassword($this->getNewPassword());
                // The cookie identity auth_key is regenerated on logout
                // Refer to ChangeController change function
                $this->userRepository->save($user);
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     *
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
     *
     * @psalm-return 'ChangePassword'
     */
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
    
    public function getNewPasswordVerify() : string
    {
        return $this->newPasswordVerify;
    }
    
    public function getRules(): array
    {
        return [
            /**
             * @see ChangePasswordController function change  $login = $identity->getUser()?->getLogin();
             * @see resources\views\changepassword\change.php  
             * The login field will include the current login username or email address {$login} in a READONLY field 
             * for all users besides the administrator i.e.
             * $changePasswordForAnyUser  
                            ?   Field::text($formModel, 'login')
                                ->label($translator->translate('layout.login'))
                                ->addInputAttributes([
                                    'value' => $login ?? ''
                                ]) 
                            :   Field::text($formModel, 'login')
                                ->label($translator->translate('layout.login'))
                                ->addInputAttributes([
                                    'value' => $login ?? '', 
                                    'readonly' => 'readonly'
                                ]); 
             */
            'login' => [new Required()],
            'password' => $this->passwordRules(),
            'newPassword' => [
                new Required(),
                /**
                 * New Length(min: 8)
                 * @see https://github.com/yiisoft/demo/pull/602  Password length should not be limited
                 */
            ], 
            'newPasswordVerify' => $this->NewPasswordVerifyRules()
        ];
    }
    
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
           
    private function newPasswordVerifyRules(): array
    {
        return [
            new Required(),
            new Callback(
                callback: function (): Result {
                    $result = new Result();
                    if (!($this->newPassword === $this->newPasswordVerify)) {
                      $result->addError($this->translator->translate('validator.password.not.match.new'));
                    }
                    return $result;
                },
                skipOnEmpty: true,
            ),
        ];
    }
}
