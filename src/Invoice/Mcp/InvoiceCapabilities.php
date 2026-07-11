<?php

declare(strict_types=1);

namespace App\Invoice\Mcp;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Peppol\PeppolMessageRepository;
use App\Invoice\Peppol\PeppolSendService;
use App\Invoice\Peppol\UblXmlGeneratorService;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use DateTimeImmutable;
use Mcp\Capability\Attribute\McpTool;
use Psr\Log\LoggerInterface;

final class InvoiceCapabilities implements InvoiceCapabilitiesInterface
{
    public function __construct(
        private readonly InvRepository           $invRepo,
        private readonly ClientRepository        $clientRepo,
        private readonly PurchaseEntryRepository $purchaseEntryRepo,
        private readonly PeppolMessageRepository $peppolMessageRepo,
        private readonly UblXmlGeneratorService  $ublXmlGenerator,
        private readonly PeppolSendService       $peppolSendService,
        private readonly LoggerInterface         $logger,
    ) {}

    #[\Override]
    #[McpTool(
        name: 'invoice_list',
        description: 'List invoices. Optional filters: from (YYYY-MM-DD),'
     . ' to (YYYY-MM-DD), statusId (1=draft, 2=sent, 3=viewed, 4=paid).',
    )]
    public function listInvoices(?string $from = null,
            ?string $to = null, ?int $statusId = null): array
    {
        $reader = $statusId !== null
            ? $this->invRepo->findAllWithStatus($statusId)
            : $this->invRepo->findAllPreloaded();

        $fromDt = $from !== null ? new DateTimeImmutable($from) : null;
        $toDt   = $to   !== null ? new DateTimeImmutable($to)   : null;

        $result = [];
        /** @var Inv $inv */
        foreach ($reader as $inv) {
            $created = $inv->getDateCreated();
            if ($fromDt !== null && $created < $fromDt) {
                continue;
            }
            if ($toDt !== null && $created > $toDt) {
                continue;
            }
            $result[] = $this->serializeInv($inv);
        }
        return $result;
    }

    #[\Override]
    #[McpTool(
        name: 'invoice_get',
        description: 'Get a single invoice by its integer ID.',
    )]
    public function getInvoice(int $id): array
    {
        $inv = $this->invRepo->repoInvLoadedquery($id);
        if ($inv === null) {
            return ['error' => "Invoice #{$id} not found."];
        }
        return $this->serializeInv($inv);
    }

    #[\Override]
    #[McpTool(
        name: 'invoice_create',
        description: 'Create a draft invoice for an existing client.'
     . ' Returns confirmation.',
    )]
    public function createInvoice(int $clientId, string $dateCreated,
        ?string $note = null): array
    {
        $client = $this->clientRepo->repoClientqueryOrig($clientId);
        if ($client === null) {
            return ['error' => "Client #{$clientId} not found."];
        }

        $inv = new Inv(
            client_id: $clientId,
            note: $note ?? '',
        );

        $this->invRepo->save($inv);

        $this->logger->info('MCP invoice_create: draft created', [
            'client_id' => $clientId,
        ]);

        return ['status' => 'draft created', 'client_id' => $clientId];
    }

    #[\Override]
    #[McpTool(
        name: 'invoice_send_peppol',
        description: 'Send an invoice via the Peppol AS4 network.'
     . ' Requires valid UBL 2.4 XML to have been prepared.',
    )]
    public function sendViaPeppol(int $invoiceId, string $recipientPeppolId): array
    {
        $inv = $this->invRepo->repoInvLoadInvAmountquery($invoiceId);
        if ($inv === null) {
            return ['error' => "Invoice #{$invoiceId} not found."];
        }

        /** @var PeppolMessage $msg */
        foreach ($this->peppolMessageRepo->repoInvMessages($invoiceId) as $msg) {
            if ($msg->getStatus() === 'SENT') {
                return ['error' => "Invoice #{$invoiceId} already sent via"
                . " Peppol (message: {$msg->getMessageId()})."];
            }
        }

        try {
            $ublXml = $this->ublXmlGenerator->generate($invoiceId);
        } catch (\RuntimeException $e) {
            $this->logger->warning('MCP invoice_send_peppol: UBL generation'
                    . ' failed', [
                'invoice_id' => $invoiceId,
                'error'      => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage(), 'invoice_id' => $invoiceId];
        }

        $message = $this->peppolSendService->send($invoiceId, $ublXml, $recipientPeppolId);

        $this->logger->info('MCP invoice_send_peppol: dispatch attempted', [
            'invoice_id'          => $invoiceId,
            'recipient_peppol_id' => $recipientPeppolId,
            'status'              => $message->getStatus(),
        ]);

        return [
            'status'              => $message->getStatus(),
            'message_id'          => $message->getMessageId(),
            'error'               => $message->getErrorMessage(),
            'invoice_id'          => $invoiceId,
            'recipient_peppol_id' => $recipientPeppolId,
        ];
    }

    #[\Override]
    #[McpTool(
        name: 'peppol_status',
        description: 'Check Peppol AS4 delivery status for all messages sent'
     . ' for a given invoice ID.',
    )]
    public function peppolStatus(int $invoiceId): array
    {
        $result = [];
        /** @var PeppolMessage $msg */
        foreach ($this->peppolMessageRepo->repoInvMessages($invoiceId) as $msg) {
            $result[] = [
                'id'           => $msg->isPersisted() ? $msg->reqId() : null,
                'message_id'   => $msg->getMessageId(),
                'status'       => $msg->getStatus(),
                'recipient_id' => $msg->getRecipientId(),
                'sent_at'      => $msg->getSentAt()?->format('Y-m-d H:i:s'),
            ];
        }
        if ($result === []) {
            $result[] = ['message' => "No Peppol messages found"
                . " for invoice #{$invoiceId}."];
        }
        return $result;
    }

    #[\Override]
    #[McpTool(
        name: 'client_list',
        description: 'List all active clients. Optional: search string matched'
     . ' against client name.',
    )]
    public function listClients(?string $search = null): array
    {
        $result = [];
        /** @var Client $client */
        foreach ($this->clientRepo->findAllWithActive(1) as $client) {
            if ($search !== null
                && stripos($client->getClientFullName(), $search) === false
            ) {
                continue;
            }
            $result[] = [
                'id'    => $client->reqId(),
                'name'  => $client->getClientFullName(),
                'email' => $client->getClientEmail(),
            ];
        }
        return $result;
    }

    #[\Override]
    #[McpTool(
        name: 'client_get',
        description: 'Get a single client by their integer ID.',
    )]
    public function getClient(int $id): array
    {
        $client = $this->clientRepo->repoClientqueryOrig($id);
        if ($client === null) {
            return ['error' => "Client #{$id} not found."];
        }
        return [
            'id'           => $client->reqId(),
            'name'         => $client->getClientFullName(),
            'email'        => $client->getClientEmail(),
            'date_created' => $client->getClientDateCreated()->format('Y-m-d'),
        ];
    }

    #[\Override]
    #[McpTool(
        name: 'purchase_entry_list',
        description: 'List received purchase invoices. Optional filters:'
     . ' from (YYYY-MM-DD), to (YYYY-MM-DD).',
    )]
    public function listPurchaseEntries(?string $from = null, ?string $to = null): array
    {
        $reader = ($from !== null && $to !== null)
            ? $this->purchaseEntryRepo->repoFindForPeriod($from, $to)
            : $this->purchaseEntryRepo->findAllPreloaded();

        $result = [];
        /** @var PurchaseEntry $entry */
        foreach ($reader as $entry) {
            $date = $entry->getDate();
            $result[] = [
                'id'          => $entry->isPersisted() ? $entry->reqId() : null,
                'supplier'    => $entry->getSupplier(),
                'description' => $entry->getDescription(),
                'date'        =>
                    $date instanceof DateTimeImmutable ?
                        $date->format('Y-m-d') : (string) $date,
                'total'       => $entry->getAmountExVat() + $entry->getVatAmount(),
            ];
        }
        return $result;
    }

    /** @return array<string, mixed> */
    private function serializeInv(Inv $inv): array
    {
        return [
            'id'           => $inv->reqId(),
            'number'       => $inv->getNumber(),
            'status_id'    => $inv->reqStatusId(),
            'date_created' => $inv->getDateCreated()->format('Y-m-d'),
            'date_due'     => $inv->getDateDue()->format('Y-m-d'),
            'note'         => $inv->getNote(),
            'client_id'    => $inv->getClient()?->reqId(),
            'client_name'  => $inv->getClient()?->getClientFullName(),
        ];
    }
}
