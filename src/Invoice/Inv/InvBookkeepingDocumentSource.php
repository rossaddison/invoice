<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Bookkeeping\Application\BookkeepingDocument;
use App\Bookkeeping\Application\BookkeepingDocumentSourceInterface;

final readonly class InvBookkeepingDocumentSource implements BookkeepingDocumentSourceInterface
{
    public function __construct(
        private InvRepository $invRepository,
    ) {
    }

    #[\Override]
    public function forInvoice(int $invId): ?BookkeepingDocument
    {
        $invoice = $this->invRepository->repoInvUnLoadedquery($invId);
        $client = $invoice?->getClient();
        if ($invoice === null || $client === null) {
            return null;
        }

        $number = $invoice->getNumber();
        $address = implode(', ', array_filter([
            $client->getClientAddress1(),
            $client->getClientAddress2(),
            $client->getClientCity(),
            $client->getClientZip(),
        ], static fn(?string $part): bool => $part !== null && $part !== ''));

        return new BookkeepingDocument(
            'Y3I-' . $client->reqId(),
            $client->getClientFullName(),
            $address !== '' ? $address : 'N/A',
            ($number !== null && $number !== '') ? $number : '#' . $invoice->reqId(),
            $invoice->getDateDue(),
        );
    }
}
