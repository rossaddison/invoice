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
    private function guard(?Client $existing, ?int &$exceptSeen = null): ClientNameGuard
    {
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('A client named %s already exists.');

        $clients = TypedMock::of(ClientRepository::class);
        $clients->shouldReceive('findByFullName')->andReturnUsing(
            static function (string $name, string $surname, ?int $exceptId) use ($existing, &$exceptSeen): ?Client {
                $exceptSeen = $exceptId;
                return $existing;
            },
        );

        return new ClientNameGuard($clients, $translator);
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
}
