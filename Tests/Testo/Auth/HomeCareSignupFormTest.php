<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\Form\HomeCareSignupForm;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\User\UserRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Validator;

/**
 * Covers the validation rules specific to HomeCareSignupForm: the
 * price/paymentOption allow-lists, the required address fields, and the
 * inherited login/email-uniqueness and password-match checks shared with
 * the generic SignupForm.
 */
#[Test]
final class HomeCareSignupFormTest
{
    private function makeForm(?UserRepository $userR = null, ?CategorySecondaryRepository $csR = null): HomeCareSignupForm
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $translator->shouldReceive('translate');
        $e->andReturnUsing(static fn (string $key): string => $key);

        if ($userR === null) {
            /** @var UserRepository&m\MockInterface $userR */
            $userR = m::mock(UserRepository::class);
            /** @var \Mockery\Expectation $e2 */
            $e2 = $userR->shouldReceive('findByLogin');
            $e2->andReturn(null);
            /** @var \Mockery\Expectation $e3 */
            $e3 = $userR->shouldReceive('findByEmail');
            $e3->andReturn(null);
        }

        if ($csR === null) {
            /** @var CategorySecondaryRepository&m\MockInterface $csR */
            $csR = m::mock(CategorySecondaryRepository::class);
            /** @var \Mockery\Expectation $e4 */
            $e4 = $csR->shouldReceive('optionsDataCategorySecondaries');
            $e4->andReturn([7 => 'Weekly Round A']);
        }

        return new HomeCareSignupForm($translator, $userR, $csR);
    }

    /** @param array<string, string|int> $values */
    private function hydrate(HomeCareSignupForm $form, array $values): void
    {
        $ref = new \ReflectionClass($form);
        foreach ($values as $property => $value) {
            $ref->getProperty($property)->setValue($form, $value);
        }
    }

    /** @return array<string, string|int> */
    private function validValues(): array
    {
        return [
            'login' => 'jane',
            'email' => 'jane@example.com',
            'password' => 'correct horse battery staple',
            'passwordVerify' => 'correct horse battery staple',
            'clientName' => 'Jane',
            'clientSurname' => 'Doe',
            'street' => 'Elm Street',
            'buildingNumber' => '12',
            'price' => 20,
            'paymentOption' => HomeCareSignupForm::PAYMENT_WILL_PAY_TODAY,
        ];
    }

    public function validDataPassesValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, $this->validValues());

        $result = new Validator()->validate($form);

        Assert::true($result->isValid());
    }

    public function priceOutsideAllowedListFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'price' => 3]);

        $result = new Validator()->validate($form);

        Assert::false($result->isValid());
    }

    public function paymentOptionInvalidValueFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'paymentOption' => 'bank_transfer']);

        $result = new Validator()->validate($form);

        Assert::false($result->isValid());
    }

    public function missingStreetFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'street' => '']);

        $result = new Validator()->validate($form);

        Assert::false($result->isValid());
    }

    public function missingBuildingNumberFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'buildingNumber' => '']);

        $result = new Validator()->validate($form);

        Assert::false($result->isValid());
    }

    public function existingLoginFailsValidation(): void
    {
        /** @var UserRepository&m\MockInterface $userR */
        $userR = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $userR->shouldReceive('findByLogin');
        $e->andReturn(m::mock(User::class));
        /** @var \Mockery\Expectation $e2 */
        $e2 = $userR->shouldReceive('findByEmail');
        $e2->andReturn(null);

        $form = $this->makeForm($userR);
        $this->hydrate($form, $this->validValues());

        $result = new Validator()->validate($form);

        Assert::false($result->isValid());
    }

    public function passwordMismatchFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'passwordVerify' => 'a different password']);

        $result = new Validator()->validate($form);

        Assert::false($result->isValid());
    }

    public function allowedPricesConstantMatchesOneThenFivePoundSteps(): void
    {
        Assert::same(
            [...range(5, 15, 1), ...range(20, 100, 5)],
            HomeCareSignupForm::ALLOWED_PRICES,
        );
    }

    public function newAreaSentinelSecondaryCategoryPassesValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [
            ...$this->validValues(),
            'secondaryCategoryId' => HomeCareSignupForm::NEW_AREA_SECONDARY_CATEGORY_ID,
        ]);

        $result = new Validator()->validate($form);

        Assert::true($result->isValid());
    }

    public function existingSecondaryCategoryIdPassesValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'secondaryCategoryId' => 7]);

        $result = new Validator()->validate($form);

        Assert::true($result->isValid());
    }

    public function unknownSecondaryCategoryIdFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'secondaryCategoryId' => 999]);

        $result = new Validator()->validate($form);

        Assert::false($result->isValid());
    }
}
