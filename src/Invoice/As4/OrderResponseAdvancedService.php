<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperSupplierTrait;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Ubl\{Address, Contact, Country, Generator, OrderResponse, OrderResponseCode, OrderResponseLine, OrderResponseLineStatusCode, Party, PartyLegalEntity, PartyTaxScheme, TaxScheme};
use DateTime;
use Psr\Log\LoggerInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Builds and sends a Peppol BIS Advanced Ordering OrderResponseAdvanced for
 * a SalesOrder -- the outbound counterpart to As4OrderImportService. This
 * app always plays Seller for Ordering (see As4OrderImportService's own
 * docblock): it never issues Order/OrderChange/OrderCancellation, this is
 * the only document it ever sends for this profile.
 *
 * Two ways to respond, both ending up on the same document-building core
 * (buildOrderResponse()):
 *  - send()/previewXml() -- the whole-order "Acknowledge" shortcut. Every
 *    line gets the same LineStatusCode, mapped from the header code via
 *    defaultLineStatusCodeFor() (e.g. AB -> Added, "not yet decided").
 *  - sendPerLine()/previewPerLineXml() -- real per-line staff decisions.
 *    The header OrderResponseCode is *derived* from what staff picked per
 *    line (OrderResponseCode::deriveFromLineStatusCodes()), never chosen
 *    directly -- AB never applies once individual lines have been decided.
 *
 * Reuses PeppolHelperSupplierTrait::buildSupplierParty() as-is for the
 * SellerSupplierParty -- it's built purely from SettingRepository config,
 * zero Inv coupling. The BuyerCustomerParty is built directly from this
 * SalesOrder's Client/ClientPeppol/PostalAddress below rather than sharing
 * PeppolHelperCustomerTrait, which takes an Inv and is Invoice-critical/
 * already verified -- not touched here, deliberately, for exactly that
 * reason (see the project's own precedent in As4OrderImportService's
 * docblock for not scope-creeping into unrelated pre-existing code).
 */
final class OrderResponseAdvancedService
{
    use PeppolHelperSupplierTrait;

    public function __construct(
        private readonly SettingRepository $s,
        private readonly Translator $t,
        private readonly ClientPeppolRepository $clientPeppolRepository,
        private readonly PostalAddressRepository $postalAddressRepository,
        private readonly SalesOrderRepository $salesOrderRepository,
        private readonly SalesOrderItemRepository $salesOrderItemRepository,
        private readonly As4MessageDispatcher $dispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Whole-order "Acknowledge" shortcut -- every line gets the same
     * LineStatusCode, mapped from $code.
     *
     * @throws As4OrderResponseException When the SalesOrder's Client has no
     *   ClientPeppol registration -- there's no Peppol participant ID to
     *   send the response to.
     */
    public function send(SalesOrder $salesOrder, OrderResponseCode $code): As4DispatchResult
    {
        [$client, $clientPeppol] = $this->resolveClientAndPeppol($salesOrder);
        $itemLineCodes = $this->resolveItemLineCodes($salesOrder, [], $this->defaultLineStatusCodeFor($code));
        $payloadXml = $this->buildPayloadXml($salesOrder, $client, $clientPeppol, $code, $itemLineCodes);
        $recipientPartyId = $clientPeppol->getEndpointidSchemeid() . ':' . $clientPeppol->getEndpointid();

        $result = $this->dispatchSafely($salesOrder, $code, $recipientPartyId, $payloadXml);
        $this->handleResult($salesOrder, $code, $result);

        return $result;
    }

    /**
     * Same document send() would dispatch, without dispatching it.
     *
     * @throws As4OrderResponseException Same conditions as send().
     */
    public function previewXml(SalesOrder $salesOrder, OrderResponseCode $code): string
    {
        [$client, $clientPeppol] = $this->resolveClientAndPeppol($salesOrder);
        $itemLineCodes = $this->resolveItemLineCodes($salesOrder, [], $this->defaultLineStatusCodeFor($code));
        return $this->buildPayloadXml($salesOrder, $client, $clientPeppol, $code, $itemLineCodes);
    }

    /**
     * Real per-line response: staff decide each SalesOrderItem
     * independently. Any item not present in $lineStatusCodes defaults to
     * Accepted. The header OrderResponseCode is derived from the full
     * resolved set (not just what staff explicitly touched), so a mix of
     * explicit Rejected lines and defaulted-Accepted lines still correctly
     * comes out as "mixed" (CA), not "all rejected" (RE).
     *
     * @param array<int, OrderResponseLineStatusCode> $lineStatusCodes Keyed
     *   by SalesOrderItem::reqId().
     * @throws As4OrderResponseException Same conditions as send().
     */
    public function sendPerLine(SalesOrder $salesOrder, array $lineStatusCodes): As4DispatchResult
    {
        [$client, $clientPeppol] = $this->resolveClientAndPeppol($salesOrder);
        [$headerCode, $itemLineCodes] = $this->resolvePerLineResponse($salesOrder, $lineStatusCodes);
        $payloadXml = $this->buildPayloadXml($salesOrder, $client, $clientPeppol, $headerCode, $itemLineCodes);
        $recipientPartyId = $clientPeppol->getEndpointidSchemeid() . ':' . $clientPeppol->getEndpointid();

        $result = $this->dispatchSafely($salesOrder, $headerCode, $recipientPartyId, $payloadXml);
        $this->handlePerLineResult($salesOrder, $headerCode, $itemLineCodes, $result);

        return $result;
    }

    /**
     * Same document sendPerLine() would dispatch, without dispatching it.
     *
     * @param array<int, OrderResponseLineStatusCode> $lineStatusCodes
     * @throws As4OrderResponseException Same conditions as send().
     */
    public function previewPerLineXml(SalesOrder $salesOrder, array $lineStatusCodes): string
    {
        [$client, $clientPeppol] = $this->resolveClientAndPeppol($salesOrder);
        [$headerCode, $itemLineCodes] = $this->resolvePerLineResponse($salesOrder, $lineStatusCodes);
        return $this->buildPayloadXml($salesOrder, $client, $clientPeppol, $headerCode, $itemLineCodes);
    }

    /**
     * @return array{0: Client, 1: ClientPeppol}
     * @throws As4OrderResponseException When the SalesOrder has no Client,
     *   or the Client has no ClientPeppol registration -- there's no
     *   Peppol participant ID to send/preview a response for.
     */
    private function resolveClientAndPeppol(SalesOrder $salesOrder): array
    {
        $client = $salesOrder->getClient();
        if ($client === null) {
            throw new As4OrderResponseException(
                $this->t->translate('salesorder.peppol.response.failed.no.client'),
            );
        }

        $clientPeppol = $this->clientPeppolRepository->repoClientPeppolLoadedquery($client->reqId());
        if ($clientPeppol === null) {
            throw new As4OrderResponseException(
                $this->t->translate('salesorder.peppol.response.failed.no.peppol'),
            );
        }

        return [$client, $clientPeppol];
    }

    /**
     * @param array<int, OrderResponseLineStatusCode> $lineStatusCodes
     * @return array{0: OrderResponseCode, 1: list<array{0: SalesOrderItem, 1: OrderResponseLineStatusCode}>}
     */
    private function resolvePerLineResponse(SalesOrder $salesOrder, array $lineStatusCodes): array
    {
        $itemLineCodes = $this->resolveItemLineCodes($salesOrder, $lineStatusCodes, OrderResponseLineStatusCode::Accepted);
        $headerCode = OrderResponseCode::deriveFromLineStatusCodes(...array_map(
            static fn (array $pair): OrderResponseLineStatusCode => $pair[1],
            $itemLineCodes,
        ));
        return [$headerCode, $itemLineCodes];
    }

    /**
     * Loads every SalesOrderItem belonging to $salesOrder and resolves the
     * LineStatusCode each one gets: $lineCodesByItemId's entry if present,
     * $defaultLineCode otherwise.
     *
     * @param array<int, OrderResponseLineStatusCode> $lineCodesByItemId
     * @return list<array{0: SalesOrderItem, 1: OrderResponseLineStatusCode}>
     */
    private function resolveItemLineCodes(
        SalesOrder $salesOrder,
        array $lineCodesByItemId,
        OrderResponseLineStatusCode $defaultLineCode,
    ): array {
        $resolved = [];
        /** @var SalesOrderItem $entity */
        foreach ($this->salesOrderItemRepository->repoSalesOrderquery($salesOrder->reqId()) as $entity) {
            $resolved[] = [$entity, $lineCodesByItemId[$entity->reqId()] ?? $defaultLineCode];
        }
        return $resolved;
    }

    /** The LineStatusCode every line gets under the whole-order "Acknowledge" shortcut. */
    private function defaultLineStatusCodeFor(OrderResponseCode $code): OrderResponseLineStatusCode
    {
        return match ($code) {
            OrderResponseCode::Acknowledged        => OrderResponseLineStatusCode::Added,
            OrderResponseCode::Accepted            => OrderResponseLineStatusCode::Accepted,
            OrderResponseCode::Rejected            => OrderResponseLineStatusCode::Rejected,
            OrderResponseCode::AcceptedWithChanges => OrderResponseLineStatusCode::Changed,
        };
    }

    /**
     * @param list<array{0: SalesOrderItem, 1: OrderResponseLineStatusCode}> $itemLineCodes
     */
    private function buildPayloadXml(
        SalesOrder $salesOrder,
        Client $client,
        ClientPeppol $clientPeppol,
        OrderResponseCode $headerCode,
        array $itemLineCodes,
    ): string {
        $orderResponse = $this->buildOrderResponse($salesOrder, $client, $clientPeppol, $headerCode, $itemLineCodes);
        $orderResponse->setDocumentCurrencyCode();
        return Generator::orderResponse($orderResponse, $this->s->getSetting('peppol_document_currency'));
    }

    /**
     * Wraps As4MessageDispatcher::dispatch() -- signing/network failures
     * (bad certs, an unreachable peer, ...) throw rather than returning a
     * failed As4DispatchResult (see As4TestSendCommand's own catch around
     * the same call, the established precedent for handling this). Treated
     * here as just another failed dispatch outcome, so callers only ever
     * need to handle As4OrderResponseException, not every possible
     * dispatch-time exception type.
     */
    private function dispatchSafely(
        SalesOrder $salesOrder,
        OrderResponseCode $code,
        string $recipientPartyId,
        string $payloadXml,
    ): As4DispatchResult {
        try {
            return $this->dispatcher->dispatch(new As4DispatchRequest(
                recipientPartyId: $recipientPartyId,
                documentTypeId:   As4Constants::PEPPOL_DOCTYPE_ORDER_RESPONSE_ADVANCED,
                processId:        As4Constants::PEPPOL_PROCESS_ADVANCED_ORDERING,
                payloadXml:       $payloadXml,
            ));
        } catch (\Throwable $e) {
            $this->logger->error('AS4 OrderResponseAdvanced dispatch threw', [
                'salesOrderId' => $salesOrder->reqId(),
                'code'         => $code->value,
                'error'        => $e->getMessage(),
            ]);
            throw new As4OrderResponseException(
                $this->t->translate('salesorder.peppol.response.failed'),
                previous: $e,
            );
        }
    }

    private function handleResult(SalesOrder $salesOrder, OrderResponseCode $code, As4DispatchResult $result): void
    {
        if ($result->success && !$result->hasError()) {
            $salesOrder->setPeppolOrderResponseCode($code->value);
            $this->salesOrderRepository->save($salesOrder);
            $this->logger->info('AS4 OrderResponseAdvanced sent', [
                'salesOrderId' => $salesOrder->reqId(),
                'code'         => $code->value,
                'messageId'    => $result->messageId,
            ]);
            return;
        }

        $this->logger->warning('AS4 OrderResponseAdvanced dispatch failed', [
            'salesOrderId' => $salesOrder->reqId(),
            'code'         => $code->value,
            'httpStatus'   => $result->httpStatus,
        ]);
    }

    /**
     * @param list<array{0: SalesOrderItem, 1: OrderResponseLineStatusCode}> $itemLineCodes
     */
    private function handlePerLineResult(
        SalesOrder $salesOrder,
        OrderResponseCode $headerCode,
        array $itemLineCodes,
        As4DispatchResult $result,
    ): void {
        if ($result->success && !$result->hasError()) {
            $salesOrder->setPeppolOrderResponseCode($headerCode->value);
            $this->salesOrderRepository->save($salesOrder);
            foreach ($itemLineCodes as [$item, $lineCode]) {
                $item->setPeppolLineResponseCode($lineCode->value);
                $this->salesOrderItemRepository->save($item);
            }
            $this->logger->info('AS4 OrderResponseAdvanced (per-line) sent', [
                'salesOrderId' => $salesOrder->reqId(),
                'headerCode'   => $headerCode->value,
                'messageId'    => $result->messageId,
            ]);
            return;
        }

        $this->logger->warning('AS4 OrderResponseAdvanced (per-line) dispatch failed', [
            'salesOrderId' => $salesOrder->reqId(),
            'headerCode'   => $headerCode->value,
            'httpStatus'   => $result->httpStatus,
        ]);
    }

    /**
     * @param list<array{0: SalesOrderItem, 1: OrderResponseLineStatusCode}> $itemLineCodes
     */
    private function buildOrderResponse(
        SalesOrder $salesOrder,
        Client $client,
        ClientPeppol $clientPeppol,
        OrderResponseCode $headerCode,
        array $itemLineCodes,
    ): OrderResponse {
        $lines = [];
        foreach ($itemLineCodes as [$item, $lineCode]) {
            $lines[] = $this->buildOrderResponseLine($item, $lineCode);
        }

        $number = $salesOrder->getNumber();
        return new OrderResponse(
            sR:                $this->s,
            id:                $number !== null && $number !== '' ? $number : (string) $salesOrder->reqId(),
            issueDate:         new DateTime(),
            orderResponseCode: $headerCode,
            orderReferenceId:  $salesOrder->getClientPoNumber(),
            sellerSupplierParty: $this->buildSupplierParty(),
            buyerCustomerParty:  $this->buildBuyerParty($client, $clientPeppol),
            lines: $lines,
        );
    }

    private function buildOrderResponseLine(SalesOrderItem $item, OrderResponseLineStatusCode $lineCode): OrderResponseLine
    {
        $lineId = $item->getPeppolPoLineid();
        return new OrderResponseLine(
            id:                        $lineId !== null && $lineId !== '' ? $lineId : (string) $item->reqId(),
            lineStatusCode:            $lineCode->value,
            orderLineReferenceLineId:  $lineId,
            itemName:                  $item->getName(),
        );
    }

    private function buildBuyerParty(Client $client, ClientPeppol $clientPeppol): Party
    {
        // PostalAddressRepository::repoClient() filters by PostalAddress.client_id,
        // so it's looked up by the Client's own id here -- not
        // Client::getPostaladdressId(), a separate FK column that points
        // the other way and isn't what this method's where() clause uses.
        $postalAddress = $this->postalAddressRepository->repoClient($client->reqId());
        $countryHelper = new CountryHelper();

        $address = new Address(
            $postalAddress?->getStreetName() ?? (string) $client->getClientAddress1(),
            $postalAddress?->getAdditionalStreetName() ?? (string) $client->getClientAddress2(),
            $postalAddress?->getBuildingNumber() ?? (string) $client->getClientBuildingNumber(),
            $postalAddress?->getCityName() ?? (string) $client->getClientCity(),
            $postalAddress?->getPostalZone() ?? (string) $client->getClientZip(),
            $postalAddress?->getCountrysubentity() ?? (string) $client->getClientState(),
            new Country(
                $countryHelper->getCountryIdentificationCodeWithLeague(
                    $postalAddress?->getCountry() ?? (string) $client->getClientCountry(),
                ),
                'ISO3166-1:Alpha2',
            ),
            false,
            true,
            false,
        );

        return new Party(
            $this->t,
            $client->getClientFullName(),
            $clientPeppol->getIdentificationid(),
            $clientPeppol->getIdentificationidSchemeid(),
            $address,
            null,
            new Contact(
                $client->getClientName(),
                '',
                '',
                (string) $client->getClientPhone(),
                null,
                $client->getClientEmail(),
            ),
            new PartyTaxScheme(
                $clientPeppol->getTaxschemecompanyid(),
                new TaxScheme($clientPeppol->getTaxschemeid()),
            ),
            new PartyLegalEntity(
                $clientPeppol->getLegalEntityRegistrationName(),
                $clientPeppol->getLegalEntityCompanyid(),
                ['schemeID' => $clientPeppol->getLegalEntityCompanyidSchemeid()],
                $clientPeppol->getLegalEntityCompanyLegalForm(),
            ),
            $clientPeppol->getEndpointid(),
            $clientPeppol->getEndpointidSchemeid(),
        );
    }
}
