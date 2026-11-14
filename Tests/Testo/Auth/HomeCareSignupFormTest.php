<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\Form\HomeCareSignupForm;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Setting\SettingRepository;
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
    /**
     * @return User&m\MockInterface
     */
    private function makeUserMock(): User
    {
        /** @var User&m\MockInterface $mock */
        $mock = m::mock(User::class);
        return $mock;
    }
    /**
     * @param string $services the services_offered setting value ('' = the business has not said)
     */
    private function makeForm(
        ?UserRepository $userR = null,
        ?CategorySecondaryRepository $csR = null,
        string $services = '',
    ): HomeCareSignupForm {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $e = $translator->shouldReceive('translate');
        $e->andReturnUsing(static fn (string $key): string => $key);

        if ($userR === null) {
            /** @var UserRepository&m\MockInterface $userR */
            $userR = m::mock(UserRepository::class);
            $e2 = $userR->shouldReceive('findByLogin');
            $e2->andReturn(null);
            $e3 = $userR->shouldReceive('findByEmail');
            $e3->andReturn(null);
        }

        if ($csR === null) {
            /** @var CategorySecondaryRepository&m\MockInterface $csR */
            $csR = m::mock(CategorySecondaryRepository::class);
            $e4 = $csR->shouldReceive('optionsDataCategorySecondaries');
            $e4->andReturn([7 => 'Weekly Round A']);
        }

        /** @var SettingRepository<Setting>&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('services_offered')->andReturn($services);

        return new HomeCareSignupForm($translator, $userR, $csR, $sR);
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
        $e = $userR->shouldReceive('findByLogin');
        $e->andReturn($this->makeUserMock());
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

    public function withNoServicesOfferedThereIsNothingToChooseAndNoServiceIsKept(): void
    {
        $form = $this->makeForm(services: '');
        $this->hydrate($form, $this->validValues());

        Assert::same($form->serviceOptions(), []);
        Assert::same($form->getService(), '');
        Assert::true(new Validator()->validate($form)->isValid());
    }

    public function withOneServiceOfferedItIsTheServiceWithoutAskingTheCustomer(): void
    {
        $form = $this->makeForm(services: 'bin_cleaning');
        $this->hydrate($form, $this->validValues());

        Assert::same($form->serviceOptions(), []);
        Assert::same($form->getService(), 'bin_cleaning');
        Assert::true(new Validator()->validate($form)->isValid());
    }

    public function withSeveralServicesOfferedTheCustomerChoosesOne(): void
    {
        $form = $this->makeForm(services: 'grass_mowing,bin_cleaning');
        $this->hydrate($form, [...$this->validValues(), 'service' => 'grass_mowing']);

        // labels come back translated (the test translator returns the key); in the order of the enum
        Assert::same($form->serviceOptions(), [
            'bin_cleaning' => 'service.bin.cleaning',
            'grass_mowing' => 'service.grass.mowing',
        ]);
        Assert::same($form->getService(), 'grass_mowing');
        Assert::true(new Validator()->validate($form)->isValid());
    }

    public function withSeveralServicesOfferedNotChoosingOneFailsValidation(): void
    {
        $form = $this->makeForm(services: 'grass_mowing,bin_cleaning');
        $this->hydrate($form, [...$this->validValues(), 'service' => '']);

        Assert::false(new Validator()->validate($form)->isValid());
    }

    public function withSeveralServicesOfferedAServiceNotOnOfferFailsValidation(): void
    {
        $form = $this->makeForm(services: 'grass_mowing,bin_cleaning');
        $this->hydrate($form, [...$this->validValues(), 'service' => 'tree_trimming']);

        Assert::false(new Validator()->validate($form)->isValid());
    }
}
