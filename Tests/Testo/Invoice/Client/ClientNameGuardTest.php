<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientForm;
use App\Invoice\Client\ClientNameGuard;
use App\Invoice\Client\ClientRepository;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\FormModel\Field;
use Yiisoft\Translator\TranslatorInterface;

/**
 * A second client with the same first name and surname as an existing one is refused, because the two cannot be told
 * apart in the client select of a quote, sales order or invoice.
 */
#[Test]
final class ClientNameGuardTest
{
    private int $lookups = 0;

    private function guard(?Client $existing, ?int &$exceptSeen = null): ClientNameGuard
    {
        $this->lookups = 0;
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('A client named %s already exists.');

        $clients = TypedMock::of(ClientRepository::class);
        $clients->shouldReceive('findByFullName')->andReturnUsing(
            function (string $name, string $surname, ?int $exceptId) use ($existing, &$exceptSeen): ?Client {
                ++$this->lookups;
                $exceptSeen = $exceptId;
                return $existing;
            },
        );

        return new ClientNameGuard($clients, $translator);
    }

    public function aClientWithNoNameAtAllIsNeverLookedUpOrRefused(): void
    {
        // the lookup would find "another client" (every nameless client would): it must not even be asked
        $guard = $this->guard(new Client());

        Assert::same([], $guard->errorsFor('', ''));
        Assert::same([], $guard->errorsFor('  ', ' '));
        Assert::same(0, $this->lookups);
    }

    public function isTakenSaysWhetherAnotherClientHasTheName(): void
    {
        Assert::true($this->guard(new Client())->isTaken('Virtual', 'Tester'));
        Assert::false($this->guard(null)->isTaken('Virtual', 'Tester'));
        Assert::false($this->guard(new Client())->isTaken('', ''));
    }

    public function aSurnameAloneIsStillCheckedSoAMissingFirstNameDoesNotSkipTheGuard(): void
    {
        $errors = $this->guard(new Client())->errorsFor('', 'Tester');

        Assert::same(['' => ['A client named Tester already exists.']], $errors);
    }

    public function aFreeNameGivesNoErrors(): void
    {
        Assert::same([], $this->guard(null)->errorsFor('Virtual', 'Tester'));
    }

    public function aTakenNameIsRefusedAndNamesTheClient(): void
    {
        $errors = $this->guard(new Client())->errorsFor(' Virtual ', 'Tester');

        Assert::same(['' => ['A client named Virtual Tester already exists.']], $errors);
    }

    public function theRefusalIsActuallyShownByTheFormsErrorSummary(): void
    {
        $errors = $this->guard(new Client())->errorsFor('Virtual', 'Tester');

        // configured exactly as resources/views/invoice/client/_form.php does
        $html = Field::errorSummary(new ClientForm())
            ->errors($errors)
            ->header('There were problems')
            ->onlyCommonErrors()
            ->render();

        Assert::true(str_contains($html, 'A client named Virtual Tester already exists.'));
    }

    public function theClientBeingEditedIsPassedOnSoItIsNotItsOwnDuplicate(): void
    {
        $seen = null;
        $this->guard(null, $seen)->errorsFor('Virtual', 'Tester', 42);

        Assert::same(42, $seen);
    }

    private function savedClient(string $name, string $surname, int $id = 42): Client
    {
        $client = new Client();
        $client->setId($id);
        $client->setClientName($name);
        $client->setClientSurname($surname);

        return $client;
    }

    public function renamingAClientToATakenNameIsRefusedAndTheClientItselfIsLeftOut(): void
    {
        $seen = null;
        $errors = $this->guard(new Client(), $seen)->errorsForEdit(
            $this->savedClient('Virtual', 'Tester'),
            ['client_name' => 'Home', 'client_surname' => 'Care'],
            'ClientForm',
        );

        Assert::same(['' => ['A client named Home Care already exists.']], $errors);
        Assert::same(42, $seen);
    }

    public function aNameThatIsLeftUnchangedIsNeverCheckedSoExistingDuplicatesStayEditable(): void
    {
        // the lookup would find the other client of the pair; it must not even be asked
        $guard = $this->guard(new Client());

        Assert::same([], $guard->errorsForEdit(
            $this->savedClient('Virtual', 'Tester'),
            ['client_name' => 'Virtual', 'client_surname' => 'Tester', 'client_mobile' => '+441632960001'],
            'ClientForm',
        ));
        Assert::same(0, $this->lookups);
    }

    public function changingOnlyTheCaseOrSpacesOfTheOwnNameIsNotARename(): void
    {
        $guard = $this->guard(new Client());

        Assert::same([], $guard->errorsForEdit(
            $this->savedClient('Virtual', 'Tester'),
            ['client_name' => '  virtual ', 'client_surname' => 'TESTER'],
            'ClientForm',
        ));
        Assert::same(0, $this->lookups);
    }

    public function theNameIsReadFromUnderTheFormNameToo(): void
    {
        $errors = $this->guard(new Client())->errorsForEdit(
            $this->savedClient('Virtual', 'Tester'),
            ['ClientForm' => ['client_name' => 'Home', 'client_surname' => 'Care']],
            'ClientForm',
        );

        Assert::same(['' => ['A client named Home Care already exists.']], $errors);
    }

    public function renamingToAFreeNameIsAllowed(): void
    {
        Assert::same([], $this->guard(null)->errorsForEdit(
            $this->savedClient('Virtual', 'Tester'),
            ['client_name' => 'Virtual', 'client_surname' => 'Renamed'],
            'ClientForm',
        ));
        Assert::same(1, $this->lookups);
    }
}
