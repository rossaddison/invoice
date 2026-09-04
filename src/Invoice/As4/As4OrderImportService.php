<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Client\ClientRepository;
use App\Invoice\ClientPeppol\ClientPeppolRepositoryInterface;
use App\Invoice\Group\GroupRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\Setting\SettingRepositoryInterface;
use App\Invoice\TaxRate\TaxRateRepository;
use App\User\UserRepository;
use Psr\Log\LoggerInterface;
use Yiisoft\Security\Random;

/**
 * Imports an inbound Peppol BIS Order (T01) as a new SalesOrder — the
 * SalesOrder-side counterpart to As4InvoiceImportService.
 *
 * This app only ever plays Seller for Peppol Ordering: it receives
 * Order/OrderChange/OrderCancellation from a buyer's system and sends
 * OrderResponseAdvanced back — it never issues an Order itself. If this
 * business ever needs to buy from a supplier, that happens by logging
 * into the supplier's own instance of this same app as a buyer there,
 * not by this instance sending Order.
 *
 * Unlike buildInv()/buildInvItem() in As4InvoiceImportService, this
 * class resolves and sets the actual Client/Group/User/TaxRate
 * *objects* (not just their scalar ids) before saving —
 * SalesOrder.client/group/user and SalesOrderItem.sales_order/tax_rate
 * are all `BelongsTo(..., nullable: false)`, and Cycle ORM needs the
 * relation object itself to persist a nullable:false BelongsTo
 * correctly, not just the matching *_id column value (see
 * SalesOrderService::persist(), which this mirrors for exactly that
 * reason — As4InvoiceImportService predates that lesson and still only
 * sets scalar ids; not touched here since fixing it is a separate,
 * unrelated change).
 *
 * A Peppol Order line carries no tax information at all (tax is
 * resolved later, at invoicing — see docs.peppol.eu/poacc/upgrade-3/syntax/Order/cac-OrderLine/cac-LineItem/,
 * which lists no TaxTotal/TaxCategory child), so every imported line
 * gets TaxRateRepository::repoFirstByIdQuery()'s tax rate as a
 * placeholder — never invented from thin air, always overridable by
 * staff before this SalesOrder is ever converted to an Invoice.
 */
final class As4OrderImportService implements As4PayloadHandlerInterface
{
    use As4PartyIdSplitTrait;

    public function __construct(
        private readonly UblOrderXmlParser $parser,
        private readonly ClientPeppolRepositoryInterface $clientPeppolRepository,
        private readonly ClientRepository $clientRepository,
        private readonly GroupRepository $groupRepository,
        private readonly UserRepository $userRepository,
        private readonly TaxRateRepository $taxRateRepository,
        private readonly SalesOrderRepository $salesOrderRepository,
        private readonly SalesOrderItemRepository $salesOrderItemRepository,
        private readonly SettingRepositoryInterface $settingRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function handle(string $payloadXml, string $senderPartyId, string $action): void
    {
        $data = $this->parser->parse($payloadXml);

        [$schemeId, $endpointId] = $this->splitPartyId($senderPartyId);
        $clientPeppol = $this->clientPeppolRepository->findByEndpointId($endpointId, $schemeId);

        if ($clientPeppol === null) {
            $this->logger->warning('AS4 order from unregistered Peppol party — skipped', [
                'senderPartyId' => $senderPartyId,
                'orderNumber'   => $data->orderNumber,
            ]);
            return;
        }

        $this->importOrder($data, $clientPeppol->reqClientId());
    }

    /**
     * Split out of handle() purely to keep its own return count within
     * SonarQube's limit (php:S1142) — the unregistered-sender guard
     * above happens before any of this method's own two failure/success
     * outcomes (missing Group vs. a successful import).
     */
    private function importOrder(UblOrderData $data, int $clientId): void
    {
        $groupId = max(1, (int) $this->settingRepository->getSetting('as4_default_group_id'));
        $group   = $this->groupRepository->repoGroupquery($groupId);

        if ($group === null) {
            $this->logger->warning('AS4 order: as4_default_group_id Setting has no matching Group — skipped', [
                'orderNumber' => $data->orderNumber,
                'groupId'     => $groupId,
            ]);
            return;
        }

        $taxRate    = $this->taxRateRepository->repoFirstByIdQuery();
        $salesOrder = $this->buildSalesOrder($data, $clientId, $group, $groupId);
        $this->salesOrderRepository->save($salesOrder);

        foreach ($data->lines as $line) {
            $this->salesOrderItemRepository->save(
                $this->buildSalesOrderItem($line, $salesOrder, $taxRate),
            );
        }

        $this->logger->info('AS4 order imported', [
            'orderNumber'  => $data->orderNumber,
            'salesOrderId' => $salesOrder->reqId(),
            'lineCount'    => count($data->lines),
        ]);
    }

    private function buildSalesOrder(
        UblOrderData $data,
        int $clientId,
        Group $group,
        int $groupId,
    ): SalesOrder {
        $userId = max(1, (int) $this->settingRepository->getSetting('as4_system_user_id'));
        $client = $this->clientRepository->repoClientquery($clientId);
        $user   = $this->userRepository->findById($userId);

        $salesOrder = new SalesOrder(
            client_id: $clientId,
            user_id: $userId,
            group_id: $groupId,
            status_id: 1,
            // No dedicated "awaiting Peppol response" status exists yet
            // in SalesOrderStatusTrait — every other status there
            // describes a step in the staff-authored workflow (sent to
            // customer, confirmed, delivered...) that doesn't apply here.
            // peppol_order_response_code (still null at this point) is
            // this order's actual "not yet responded to" signal.
            client_po_number: $data->orderNumber,
        );
        $salesOrder->setClient($client);
        $salesOrder->setGroup($group);
        $salesOrder->setUser($user);
        $salesOrder->setNumber((string) $this->groupRepository->generateNumber($groupId, true));
        $salesOrder->setUrlKey(Random::string(32));
        $salesOrder->setNotes($data->note ?? '');

        return $salesOrder;
    }

    private function buildSalesOrderItem(UblOrderLineData $line, SalesOrder $salesOrder, ?TaxRate $taxRate): SalesOrderItem
    {
        $item = new SalesOrderItem(
            peppol_po_itemid: $line->lineId,
            peppol_po_lineid: $line->lineId,
            name:             $line->name,
            description:      $line->description,
            quantity:         $line->quantity,
            price:            $line->unitPrice,
            discount_amount:  0.00,
            order:            0,
            product_unit:     $line->unitCode,
            sales_order_id:   $salesOrder->reqId(),
        );
        $item->setSalesOrder($salesOrder);
        if ($taxRate !== null) {
            $item->setTaxRate($taxRate);
        }
        return $item;
    }
}
