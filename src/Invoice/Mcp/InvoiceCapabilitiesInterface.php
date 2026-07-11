<?php

declare(strict_types=1);

namespace App\Invoice\Mcp;

interface InvoiceCapabilitiesInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listInvoices(?string $from = null, ?string $to = null,
        ?int $statusId = null): array;

    /** @return array<string, mixed> */
    public function getInvoice(int $id): array;

    /** @return array<string, mixed> */
    public function createInvoice(int $clientId, string $dateCreated,
        ?string $note = null): array;

    /** @return array<string, mixed> */
    public function sendViaPeppol(int $invoiceId, string $recipientPeppolId): array;

    /** @return list<array<string, mixed>> */
    public function peppolStatus(int $invoiceId): array;

    /** @return array<int, array<string, mixed>> */
    public function listClients(?string $search = null): array;

    /** @return array<string, mixed> */
    public function getClient(int $id): array;

    /** @return array<int, array<string, mixed>> */
    public function listPurchaseEntries(?string $from = null, ?string $to = null): array;
}
