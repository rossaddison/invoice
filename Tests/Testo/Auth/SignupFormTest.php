<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\Form\SignupForm;
use App\Infrastructure\Persistence\User\User;
use App\User\UserRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Validator;

/**
 * Validation rules of the generic /signup form (SignupForm). Several tests are characterisations
 * of what is accepted today; they pass because no rule forbids the input.
 */
#[Test]
final class SignupFormTest
{
    private function makeForm(?UserRepository $userR = null): SignupForm
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);

        if ($userR === null) {
            /** @var UserRepository&m\MockInterface $userR */
            $userR = m::mock(UserRepository::class);
            $userR->shouldReceive('findByLogin')->andReturn(null);
            $userR->shouldReceive('findByEmail')->andReturn(null);
        }
        return new SignupForm($translator, $userR);
    }

    /** @param array<string, string> $values */
    private function hydrate(SignupForm $form, array $values): void
    {
        $ref = new \ReflectionClass($form);
        foreach ($values as $property => $value) {
            $ref->getProperty($property)->setValue($form, $value);
        }
    }

    /** @return array<string, string> */
    private function validValues(): array
    {
        return [
            'login' => 'jane',
            'email' => 'jane@example.com',
            'password' => 'correct horse battery staple',
            'passwordVerify' => 'correct horse battery staple',
        ];
    }

    private function isValid(SignupForm $form): bool
    {
        return new Validator()->validate($form)->isValid();
    }

    public function validDataPassesValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, $this->validValues());

        Assert::true($this->isValid($form));
    }

    public function anEmptyFormFailsValidation(): void
    {
        Assert::false($this->isValid($this->makeForm()));
    }

    public function aMalformedEmailFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'email' => 'not-an-email']);

        Assert::false($this->isValid($form));
    }

    public function aPasswordMismatchFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'passwordVerify' => 'different']);

        Assert::false($this->isValid($form));
    }

    public function anExistingLoginFailsValidation(): void
    {
        /** @var UserRepository&m\MockInterface $userR */
        $userR = m::mock(UserRepository::class);
        $userR->shouldReceive('findByLogin')->andReturn(m::mock(User::class));
        $userR->shouldReceive('findByEmail')->andReturn(null);
        $form = $this->makeForm($userR);
        $this->hydrate($form, $this->validValues());

        Assert::false($this->isValid($form));
    }

    public function anExistingEmailFailsValidation(): void
    {
        /** @var UserRepository&m\MockInterface $userR */
        $userR = m::mock(UserRepository::class);
        $userR->shouldReceive('findByLogin')->andReturn(null);
        $userR->shouldReceive('findByEmail')->andReturn(m::mock(User::class));
        $form = $this->makeForm($userR);
        $this->hydrate($form, $this->validValues());

        Assert::false($this->isValid($form));
    }

    public function aLoginOverFortyEightCharactersFailsValidation(): void
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), 'login' => str_repeat('a', 49)]);

        Assert::false($this->isValid($form));
    }

    /** @param array<string, string> $overrides */
    private function validWith(array $overrides): bool
    {
        $form = $this->makeForm();
        $this->hydrate($form, [...$this->validValues(), ...$overrides]);

        return $this->isValid($form);
    }

    public function aOneCharacterPasswordFailsValidation(): void
    {
        Assert::false($this->validWith(['password' => 'a', 'passwordVerify' => 'a']));
    }

    public function aPasswordOfExactlyTheMinimumLengthPasses(): void
    {
        $password = str_repeat('a', SignupForm::PASSWORD_MIN_LENGTH);

        Assert::true($this->validWith(['password' => $password, 'passwordVerify' => $password]));
    }

    public function aPasswordOneShortOfTheMinimumFails(): void
    {
        $password = str_repeat('a', SignupForm::PASSWORD_MIN_LENGTH - 1);

        Assert::false($this->validWith(['password' => $password, 'passwordVerify' => $password]));
    }

    public function aPasswordOfExactlyTheMaximumLengthPasses(): void
    {
        $password = str_repeat('a', SignupForm::PASSWORD_MAX_LENGTH);

        Assert::true($this->validWith(['password' => $password, 'passwordVerify' => $password]));
    }

    public function aPasswordOneOverTheMaximumFails(): void
    {
        $password = str_repeat('a', SignupForm::PASSWORD_MAX_LENGTH + 1);

        Assert::false($this->validWith(['password' => $password, 'passwordVerify' => $password]));
    }

    public function anEmailOverTheColumnLengthFails(): void
    {
        $tooLong = str_repeat('a', 64) . '@' . str_repeat('b', 190) . '.com';
        Assert::true(strlen($tooLong) > SignupForm::EMAIL_MAX_LENGTH);
        Assert::false($this->validWith(['email' => $tooLong]));
    }

    public function loginsOfLettersDigitsDotsUnderscoresAndHyphensPass(): void
    {
        foreach (['jane', 'jane.doe', 'jane_doe', 'jane-doe', 'JaneDoe99', '007', 'abc', 'Zoë', 'иван'] as $login) {
            Assert::true($this->validWith(['login' => $login]), $login);
        }
    }

    public function loginsWithMarkupSpacesOrOtherSymbolsFail(): void
    {
        $bad = [
            '<script>alert(1)</script>',
            'jane doe',
            ' jane',
            'jane ',
            "jane\n",
            "jane\0",
            'jane@example.com',
            'jane;drop',
            'a"b',
            "o'brien",
            '.jane',
            '-jane',
            '_jane',
            'jane/../x',
            "jane\u{202E}",
        ];
        foreach ($bad as $login) {
            Assert::false($this->validWith(['login' => $login]), json_encode($login) ?: 'login');
        }
    }

    public function loginLengthBoundariesAreExact(): void
    {
        Assert::false($this->validWith(['login' => str_repeat('a', SignupForm::LOGIN_MIN_LENGTH - 1)]));
        Assert::true($this->validWith(['login' => str_repeat('a', SignupForm::LOGIN_MIN_LENGTH)]));
        Assert::true($this->validWith(['login' => str_repeat('a', SignupForm::LOGIN_MAX_LENGTH)]));
        Assert::false($this->validWith(['login' => str_repeat('a', SignupForm::LOGIN_MAX_LENGTH + 1)]));
    }

    public function aMegabytePasswordIsRejectedBeforeItCanBeHashed(): void
    {
        $long = str_repeat('x', 1_000_000);

        Assert::false($this->validWith(['password' => $long, 'passwordVerify' => $long]));
    }
}
