<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\Infrastructure\Persistence\User\User;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\User\UserRepository;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\PropertyTranslator\ArrayPropertyTranslator;
use Yiisoft\Validator\PropertyTranslatorInterface;
use Yiisoft\Validator\PropertyTranslatorProviderInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

/**
 * Public HomeCare-specific signup form — separate from the generic
 * `SignupForm`. Creates a real, login-capable User account, same as
 * generic signup, plus the extra answers needed to unconditionally create a
 * Client, resolve/create the street (Family) and house-number (Product,
 * Service-type), and raise the first invoice on confirmation-link click.
 */
final class HomeCareSignupForm extends FormModel implements RulesProviderInterface, PropertyTranslatorProviderInterface
{
    public const string PAYMENT_WILL_PAY_TODAY = 'will_pay_today';
    public const string PAYMENT_HAVE_PAID_CASH = 'have_paid_cash';

    /**
     * Sentinel value of `secondaryCategoryId` meaning "this address is in a
     * new area — no CategorySecondary has been set up for it yet." Never a
     * real CategorySecondary id, since those are auto-increment primary keys
     * starting at 1. HomeCareSignupController::confirm() auto-creates a
     * placeholder CategorySecondary (named `not_set_yet_<timestamp>`) for
     * staff to rename later, rather than resolving this to an existing row.
     */
    public const int NEW_AREA_SECONDARY_CATEGORY_ID = 0;

    /**
     * £1 steps from £5 to £15 (finer granularity at the low end, where a £1
     * difference matters most), then £5 steps up to £100.
     *
     * @var list<int>
     */
    public const array ALLOWED_PRICES = [
        5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
        20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100,
    ];

    private string $login = '';
    private string $email = '';
    private string $password = '';
    private string $passwordVerify = '';
    private string $clientName = '';
    private string $clientSurname = '';
    private string $street = '';
    private string $buildingNumber = '';
    private int $price = 0;
    private string $paymentOption = '';
    private int $secondaryCategoryId = self::NEW_AREA_SECONDARY_CATEGORY_ID;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
        private readonly CategorySecondaryRepository $categorySecondaryRepository,
    ) {
    }

    /**
     * @psalm-return array{login: string, email: string, password: string, passwordVerify: string,
     *     clientName: string, clientSurname: string, street: string, buildingNumber: string,
     *     price: string, paymentOption: string, secondaryCategoryId: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'login' => $this->translator->translate('layout.login'),
            'email' => $this->translator->translate('email'),
            'password' => $this->translator->translate('layout.password'),
            'passwordVerify' => $this->translator->translate('layout.password-verify'),
            'clientName' => $this->translator->translate('client.name'),
            'clientSurname' => $this->translator->translate('client.surname'),
            'street' => $this->translator->translate('homecare.signup.street.name'),
            'buildingNumber' => $this->translator->translate('client.postaladdress.building.number'),
            'price' => $this->translator->translate('price'),
            'paymentOption' => $this->translator->translate('payment.option'),
            'secondaryCategoryId' => $this->translator->translate('category.secondary'),
        ];
    }

    #[\Override]
    public function getFormName(): string
    {
        return 'HomeCareSignup';
    }

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

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function getClientSurname(): string
    {
        return $this->clientSurname;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getBuildingNumber(): string
    {
        return $this->buildingNumber;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getPaymentOption(): string
    {
        return $this->paymentOption;
    }

    public function getSecondaryCategoryId(): int
    {
        return $this->secondaryCategoryId;
    }

    public function signup(): User
    {
        $user = new User($this->getLogin(), $this->getEmail(), $this->getPassword());
        $this->userRepository->save($user);
        return $user;
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'login' => [
                new Required(),
                new Length(min: 1, max: 48, skipOnError: true),
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
            'clientName' => [
                new Required(),
            ],
            'clientSurname' => [
                new Required(),
            ],
            'street' => [
                new Required(),
            ],
            'buildingNumber' => [
                new Required(),
            ],
            'price' => [
                new In(self::ALLOWED_PRICES),
            ],
            'paymentOption' => [
                new In([self::PAYMENT_WILL_PAY_TODAY, self::PAYMENT_HAVE_PAID_CASH]),
            ],
            'secondaryCategoryId' => [
                new In([
                    self::NEW_AREA_SECONDARY_CATEGORY_ID,
                    ...array_keys($this->categorySecondaryRepository->optionsDataCategorySecondaries()),
                ]),
            ],
        ];
    }
}
