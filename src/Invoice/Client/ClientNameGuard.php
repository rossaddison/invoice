<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use Yiisoft\Translator\TranslatorInterface;

/**
 * Stops a second client being added with the same first name and surname as an existing one. Two clients with the
 * same name cannot be told apart in the client select of a quote, sales order or invoice.
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
        $existing = $this->clients->findByFullName($name, $surname, $exceptId);
        if (null === $existing) {
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
}
