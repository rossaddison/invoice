<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\Infrastructure\Persistence\User\User;
use App\User\UserRepository;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\PropertyTranslator\ArrayPropertyTranslator;
use Yiisoft\Validator\PropertyTranslatorInterface;
use Yiisoft\Validator\PropertyTranslatorProviderInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Equal;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Regex;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class SignupForm extends FormModel implements RulesProviderInterface, PropertyTranslatorProviderInterface
{
    /** Shortest accepted password. */
    public const int PASSWORD_MIN_LENGTH = 8;

    /** Longest accepted password: the value is hashed on every signup and login attempt. */
    public const int PASSWORD_MAX_LENGTH = 128;

    /** Shortest accepted login; the user.login column is string(48). */
    public const int LOGIN_MIN_LENGTH = 3;
    public const int LOGIN_MAX_LENGTH = 48;

    /**
     * A login starts with a letter or digit and continues with letters, digits, dot, underscore or
     * hyphen: no spaces, markup or control characters, since the login is echoed into pages and into
     * the confirmation email's subject line.
     */
    public const string LOGIN_PATTERN = '/^[\p{L}\p{N}][\p{L}\p{N}._-]*$/uD'; // D: "$" must not match before a trailing newline

    /** The user.email column is string(254), the longest address RFC 5321 allows. */
    public const int EMAIL_MAX_LENGTH = 254;

    private string $login = '';
    private string $email = '';
    private string $password = '';
    private string $passwordVerify = '';
    private bool $consentPeriodicInvoice = false;
    private bool $consentTelegramOutstanding = false;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @return string[]
     *
     * @psalm-return array{login: string, email: string, password: string, passwordVerify: string, consentPeriodicInvoice: string, consentTelegramOutstanding: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'login' => $this->translator->translate('layout.login'),
            'email' => $this->translator->translate('email'),
            'password' => $this->translator->translate('layout.password'),
            'passwordVerify' => $this->translator->translate('layout.password-verify'),
            'consentPeriodicInvoice' => $this->translator->translate('consent.periodic.invoice'),
            'consentTelegramOutstanding' => $this->translator->translate('consent.telegram.outstanding'),
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'Signup'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'Signup';
    }

    /**
     * @return PropertyTranslatorInterface|null
     */
    #[\Override]
    public function getPropertyTranslator(): ?PropertyTranslatorInterface
    {
        return new ArrayPropertyTranslator($this->getPropertyLabels());
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
        return $this->passwordVerify;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getConsentPeriodicInvoice(): bool
    {
        return $this->consentPeriodicInvoice;
    }

    public function getConsentTelegramOutstanding(): bool
    {
        return $this->consentTelegramOutstanding;
    }

    public function signup(): User
    {
        // In the constuct of a new User, a new Identity is created.
        // In the contruct of the new Identity, a new auth key is created.
        $user = new User($this->getLogin(), $this->getEmail(), $this->getPassword());
        $this->userRepository->save($user);
        return $user;
    }

    /**
     * @return (\Closure|Email|Equal|Length|Required)[][]
     *
     * @psalm-return array{
     *     login: list{Required, Length, Regex, \Closure(mixed):Result},
     *     email: list{Required, Length, Email, \Closure(mixed):Result},
     *     password: list{Required, Length},
     *     passwordVerify: list{Required, \Closure(mixed):Result}
     * }
     */
    #[\Override]
    public function getRules(): array
    {
        return [
            'login' => [
                new Required(),
                new Length(min: self::LOGIN_MIN_LENGTH, max: self::LOGIN_MAX_LENGTH, skipOnError: true),
                new Regex(
                    self::LOGIN_PATTERN,
                    message: $this->translator->translate('validator.login.format'),
                    skipOnError: true,
                ),
                function (mixed $value): Result {
                    $result = new Result();
                    if ($this->userRepository->findByLogin((string) $value) !== null) {
                        $result->addError($this->translator->translate('validator.user.exist'));
                    }
                    return $result;
                },
            ],
            'email' => [
                new Required(),
                new Length(max: self::EMAIL_MAX_LENGTH, skipOnError: true),
                new Email(),
                function (mixed $value): Result {
                    $result = new Result();
                    if ($this->userRepository->findByEmail((string) $value) !== null) {
                        $result->addError($this->translator->translate('validator.user.exist'));
                    }
                    return $result;
                },
            ],
            'password' => [
                new Required(),
                new Length(min: self::PASSWORD_MIN_LENGTH, max: self::PASSWORD_MAX_LENGTH, skipOnError: true),
            ],
            'passwordVerify' => [
                new Required(),
                function (mixed $value): Result {
                    $result = new Result();
                    if ($this->password !== $value) {
                        $result->addError($this->translator->translate('validator.password.not.match'));
                    }
                    return $result;
                },
            ],
        ];
    }
}
