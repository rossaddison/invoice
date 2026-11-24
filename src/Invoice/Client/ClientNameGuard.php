<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Stops a second client being added, or an existing client being renamed, to the same first name and surname as
 * another client. Two clients with the same name cannot be told apart in the client select of a quote, sales order or
 * invoice.
 */
final readonly class ClientNameGuard
{
    public function __construct(
        private ClientRepository $clients,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param int|null $exceptId the client being edited, which is not a duplicate of itself
     *
     * @return array<string, list<string>> form errors indexed by property; empty when the name is free
     *
     * The error is a common (form-level) one, key '', not one for client_name. The client form shows only common
     * errors in its summary (->onlyCommonErrors() in _form.php), and the name field shows only the form's own
     * validation result, so a client_name key was never displayed at all.
     */
    public function errorsFor(string $name, string $surname, ?int $exceptId = null): array
    {
        if (!$this->isTaken($name, $surname, $exceptId)) {
            return [];
        }
        return [
            '' => [
                sprintf(
                    $this->translator->translate('client.name.already.exists'),
                    trim(trim($name) . ' ' . trim($surname)),
                ),
            ],
        ];
    }

    /**
     * Whether another client already has this first name and surname. A client with no name at all (one made from an
     * email alone at sign-up) has nothing to compare: every such client would otherwise look like a duplicate of
     * every other, so an empty name is never taken.
     *
     * @param int|null $exceptId the client being edited, which is not a duplicate of itself
     */
    public function isTaken(string $name, string $surname, ?int $exceptId = null): bool
    {
        if ('' === trim($name) && '' === trim($surname)) {
            return false;
        }

        return null !== $this->clients->findByFullName($name, $surname, $exceptId);
    }

    /**
     * The same check for the edit form, which only applies when the name is being changed. A client whose name is
     * unchanged is left alone, so the two clients that already share a name before this guard existed can still be
     * edited (phone number, address, ...) without being forced to rename. Case and surrounding spaces do not count as
     * a change.
     *
     * @param array<array-key, mixed> $body what the edit form posted, flat or under $formName
     *
     * @return array<string, list<string>> see errorsFor()
     */
    public function errorsForEdit(Client $client, array $body, string $formName): array
    {
        $posted = isset($body[$formName]) && is_array($body[$formName]) ? $body[$formName] : $body;
        $name = self::text($posted['client_name'] ?? null);
        $surname = self::text($posted['client_surname'] ?? null);

        $current = self::key($client->getClientName(), $client->getClientSurname() ?? '');
        if ($current === self::key($name, $surname)) {
            return [];
        }
        return $this->errorsFor($name, $surname, $client->reqId());
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private static function key(string $name, string $surname): string
    {
        return mb_strtolower(trim($name)) . "\n" . mb_strtolower(trim($surname));
    }
}
