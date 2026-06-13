<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Yiisoft\Security\Random;

final class As4InvoiceImportService implements As4PayloadHandlerInterface
{
    public function __construct(
        private readonly UblXmlParser           $parser,
        private readonly ClientPeppolRepository $clientPeppolRepository,
        private readonly InvRepository          $invRepository,
        private readonly InvItemRepository      $invItemRepository,
        private readonly SettingRepository      $settingRepository,
        private readonly LoggerInterface        $logger,
    ) {}

    #[\Override]
    public function handle(string $payloadXml, string $senderPartyId, string $action): void
    {
        $data = $this->parser->parse($payloadXml);

        [$schemeId, $endpointId] = $this->splitPartyId($senderPartyId);
        $clientPeppol = $this->clientPeppolRepository->findByEndpointId($endpointId, $schemeId);

        if ($clientPeppol === null) {
            $this->logger->warning('AS4 invoice from unregistered Peppol party — skipped', [
                'senderPartyId' => $senderPartyId,
                'invoiceNumber' => $data->invoiceNumber,
            ]);
            return;
        }

        $inv = $this->buildInv($data, $clientPeppol->reqClientId());
        $this->invRepository->save($inv);

        foreach ($data->lines as $line) {
            $this->invItemRepository->save($this->buildInvItem($line, $inv->reqId()));
        }

        $this->logger->info('AS4 invoice imported', [
            'invoiceNumber' => $data->invoiceNumber,
            'invId'         => $inv->reqId(),
            'lineCount'     => count($data->lines),
        ]);
    }

    /** @return array{string, string} */
    private function splitPartyId(string $partyId): array
    {
        $pos = strpos($partyId, ':');
        if ($pos === false) {
            return ['', $partyId];
        }
        return [substr($partyId, 0, $pos), substr($partyId, $pos + 1)];
    }

    private function buildInv(UblInvoiceData $data, int $clientId): Inv
    {
        $userId  = max(1, (int) $this->settingRepository->getSetting('as4_system_user_id'));
        $groupId = max(1, (int) $this->settingRepository->getSetting('as4_default_group_id'));

        $inv = new Inv(client_id: $clientId, user_id: $userId, group_id: $groupId);
        $inv->setDateCreated($data->issueDate->format('Y-m-d'));
        $inv->setDateSupplied($data->issueDate);
        $inv->setDateTaxPoint($data->issueDate);
        $inv->setDateDue($this->settingRepository);
        $inv->setTimeCreated((new DateTimeImmutable('now'))->format('H:i:s'));
        $inv->setNumber($data->invoiceNumber);
        $inv->setUrlKey(Random::string(32));
        $inv->setNote($data->note ?? '');
        $inv->setDocumentDescription($data->documentType);
        $inv->setStatusId(1);
        $inv->setIsReadOnly(false);
        $inv->setDiscountAmount(0.00);
        $inv->setPaymentMethod(0);
        $inv->setTerms('');
        return $inv;
    }

    private function buildInvItem(UblInvoiceLineData $line, int $invId): InvItem
    {
        return new InvItem(
            name:             $line->name,
            description:      $line->description,
            quantity:         $line->quantity,
            price:            $line->unitPrice,
            discount_amount:  0.00,
            order:            0,
            is_recurring:     false,
            product_unit:     $line->unitCode,
            inv_id:           $invId,
            tax_rate_id:      0,
            peppol_po_itemid: $line->peppolPoItemId,
            peppol_po_lineid: $line->peppolPoLineId,
        );
    }
}
