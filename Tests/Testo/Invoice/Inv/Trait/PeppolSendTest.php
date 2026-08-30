<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Contract\ContractRepository;
use App\Invoice\Delivery\DeliveryRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\DeliveryParty\DeliveryPartyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvController;
use App\Invoice\Inv\InvPeppolChargeDeps;
use App\Invoice\Inv\InvPeppolCoreDeps;
use App\Invoice\Inv\InvPeppolInvDeps;
use App\Invoice\Inv\InvPeppolNetworkDeps;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\Peppol\PeppolSendServiceInterface;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\UnitPeppol\UnitPeppolRepository;
use App\Invoice\Upload\UploadRepository;
use App\Service\WebControllerService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;

/**
 * peppolSend() (App\Invoice\Inv\Trait\Peppol) is only exercised
 * end-to-end by a genuine successful UBL generation in production --
 * covers its own guard clauses here instead (guest, invoice not found,
 * zero client id, client not fully set up, missing delivery location),
 * plus confirming a transmitPeppolDocument() failure never leaks past
 * peppolSend() itself -- it always ends in a normal redirect either way.
 * See PeppolTransmitDocumentTest's own docblock for why a genuine
 * successful-generation happy path is out of scope for a unit test here.
 *
 * Reflects into the real InvController, same technique as this
 * directory's other Peppol.php trait tests.
 */
#[Test]
final class PeppolSendTest
{
    private const int INV_ID = 42;
    private const int CLIENT_ID = 7;

    /** @return array{0: object, 1: ReflectionClass<InvController>} */
    private function harness(): array
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturn('EUR');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturn('/fake-url');
        $webService = new WebControllerService($psr17, $psr17, $urlGenerator);

        $refClass = new ReflectionClass(InvController::class);
        $instance = $refClass->newInstanceWithoutConstructor();
        $refClass->getProperty('translator')->setValue($instance, $translator);
        $refClass->getProperty('sR')->setValue($instance, $sR);
        $refClass->getProperty('flash')->setValue($instance, $flash);
        $refClass->getProperty('webService')->setValue($instance, $webService);

        return [$instance, $refClass];
    }

    private function currentUser(bool $isGuest): CurrentUser
    {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldReceive('isGuest')->andReturn($isGuest);
        return $currentUser;
    }

    private function core(?Inv $invoice, ClientPeppolRepository $cpR, DeliveryLocationRepository $dlR): InvPeppolCoreDeps
    {
        /** @var InvRepository&m\MockInterface $invRepo */
        $invRepo = m::mock(InvRepository::class);
        $invRepo->shouldReceive('repoInvLoadInvAmountquery')->andReturn($invoice);

        /** @var InvItemAmountRepository&m\MockInterface $iiaR */
        $iiaR = m::mock(InvItemAmountRepository::class);
        /** @var PostalAddressRepository&m\MockInterface $paR */
        $paR = m::mock(PostalAddressRepository::class);
        /** @var SalesOrderRepository&m\MockInterface $soR */
        $soR = m::mock(SalesOrderRepository::class);
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);

        return new InvPeppolCoreDeps(invRepo: $invRepo, iiaR: $iiaR, cpR: $cpR, dlR: $dlR, paR: $paR, soR: $soR, gR: $gR);
    }

    private function net(): InvPeppolNetworkDeps
    {
        /** @var ContractRepository&m\MockInterface $contractRepo */
        $contractRepo = m::mock(ContractRepository::class);
        /** @var DeliveryRepository&m\MockInterface $delRepo */
        $delRepo = m::mock(DeliveryRepository::class);
        /** @var DeliveryPartyRepository&m\MockInterface $delPartyRepo */
        $delPartyRepo = m::mock(DeliveryPartyRepository::class);
        /** @var UnitPeppolRepository&m\MockInterface $unpR */
        $unpR = m::mock(UnitPeppolRepository::class);
        /** @var UploadRepository&m\MockInterface $upR */
        $upR = m::mock(UploadRepository::class);

        return new InvPeppolNetworkDeps(
            contractRepo: $contractRepo,
            delRepo: $delRepo,
            delPartyRepo: $delPartyRepo,
            unpR: $unpR,
            upR: $upR,
        );
    }

    private function charge(): InvPeppolChargeDeps
    {
        /** @var InvAllowanceChargeRepository&m\MockInterface $aciR */
        $aciR = m::mock(InvAllowanceChargeRepository::class);
        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        /** @var SalesOrderItemRepository&m\MockInterface $soiR */
        $soiR = m::mock(SalesOrderItemRepository::class);
        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);

        return new InvPeppolChargeDeps(aciR: $aciR, aciiR: $aciiR, soiR: $soiR, trR: $trR);
    }

    private function inv(): InvPeppolInvDeps
    {
        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var InvItemRepository&m\MockInterface $iiR */
        $iiR = m::mock(InvItemRepository::class);

        return new InvPeppolInvDeps(iaR: $iaR, iiR: $iiR);
    }

    private function emptyCpR(): ClientPeppolRepository
    {
        /** @var ClientPeppolRepository&m\MockInterface */
        return m::mock(ClientPeppolRepository::class);
    }

    private function emptyDlR(): DeliveryLocationRepository
    {
        /** @var DeliveryLocationRepository&m\MockInterface */
        return m::mock(DeliveryLocationRepository::class);
    }

    private function invoiceWithClient(?int $deliveryLocationId, ?int $reqIdOrThrow = null): Inv
    {
        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        $client->shouldReceive('reqId')->andReturn(self::CLIENT_ID);

        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);

        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('getClient')->andReturn($client);
        $invoice->shouldReceive('getDeliveryLocationId')->andReturn($deliveryLocationId);
        $invoice->shouldReceive('getInvAmount')->andReturn($invAmount);
        if ($reqIdOrThrow !== null) {
            $invoice->shouldReceive('reqId')->andReturn($reqIdOrThrow);
        } else {
            // transmitPeppolDocument() is reached but its very first line
            // (generateInvoicePeppolUblXmlTempFile()'s $invoice->reqId())
            // fails -- confirms peppolSend() itself still ends in a normal
            // redirect regardless (see PeppolTransmitDocumentTest for the
            // catch-all behaviour itself).
            $invoice->shouldReceive('reqId')->andThrow(new \RuntimeException('boom'));
        }

        return $invoice;
    }

    private function fullyValidClientPeppol(): ClientPeppol
    {
        return new ClientPeppol(
            client_id: self::CLIENT_ID,
            endpointid: 'ABC123',
            endpointid_schemeid: '9999',
            identificationid: 'ID1',
            identificationid_schemeid: '9999',
            taxschemecompanyid: 'TAX1',
            taxschemeid: 'VAT',
            legal_entity_registration_name: 'Acme Ltd',
            legal_entity_companyid: 'CO1',
            legal_entity_companyid_schemeid: '9999',
            legal_entity_company_legal_form: 'LTD',
            financial_institution_branchid: 'BR1',
            accounting_cost: 'AC1',
            supplier_assigned_accountid: 'SA1',
        );
    }

    private function call(
        int $id,
        CurrentUser $currentUser,
        InvPeppolCoreDeps $core,
    ): ResponseInterface {
        [$instance, $refClass] = $this->harness();
        $method = $refClass->getMethod('peppolSend');

        /** @var PeppolSendServiceInterface&m\MockInterface $sendService */
        $sendService = m::mock(PeppolSendServiceInterface::class);

        /** @var ResponseInterface $result */
        $result = $method->invoke(
            $instance,
            $id,
            $currentUser,
            $core,
            $this->net(),
            $this->charge(),
            $this->inv(),
            $sendService,
        );
        return $result;
    }

    public function guestGetsNotFound(): void
    {
        $core = $this->core(null, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(self::INV_ID, $this->currentUser(true), $core);

        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function zeroIdGetsNotFound(): void
    {
        $core = $this->core(null, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(0, $this->currentUser(false), $core);

        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function invoiceNotFoundGetsNotFound(): void
    {
        $core = $this->core(null, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(self::INV_ID, $this->currentUser(false), $core);

        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function zeroClientIdStillRedirects(): void
    {
        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('getClient')->andReturn(null);

        $core = $this->core($invoice, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(self::INV_ID, $this->currentUser(false), $core);

        Assert::same(Status::FOUND, $result->getStatusCode());
    }

    public function notFullySetupRedirectsWithoutTransmitting(): void
    {
        $invoice = $this->invoiceWithClient(null);

        /** @var ClientPeppolRepository&m\MockInterface $cpR */
        $cpR = m::mock(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientCount')->andReturn(0);

        $core = $this->core($invoice, $cpR, $this->emptyDlR());

        $result = $this->call(self::INV_ID, $this->currentUser(false), $core);

        Assert::same(Status::FOUND, $result->getStatusCode());
    }

    public function missingDeliveryLocationRedirectsWithoutTransmitting(): void
    {
        $invoice = $this->invoiceWithClient(9);
        $clientPeppol = $this->fullyValidClientPeppol();

        /** @var ClientPeppolRepository&m\MockInterface $cpR */
        $cpR = m::mock(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientCount')->andReturn(1);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn($clientPeppol);

        /** @var DeliveryLocationRepository&m\MockInterface $dlR */
        $dlR = m::mock(DeliveryLocationRepository::class);
        $dlR->shouldReceive('repoDeliveryLocationquery')->andReturn(null);

        $core = $this->core($invoice, $cpR, $dlR);

        $result = $this->call(self::INV_ID, $this->currentUser(false), $core);

        Assert::same(Status::FOUND, $result->getStatusCode());
    }

    public function transmitFailureIsCaughtAndStillRedirects(): void
    {
        $invoice = $this->invoiceWithClient(9);
        $clientPeppol = $this->fullyValidClientPeppol();

        /** @var ClientPeppolRepository&m\MockInterface $cpR */
        $cpR = m::mock(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientCount')->andReturn(1);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn($clientPeppol);

        /** @var DeliveryLocation&m\MockInterface $delloc */
        $delloc = m::mock(DeliveryLocation::class);
        /** @var DeliveryLocationRepository&m\MockInterface $dlR */
        $dlR = m::mock(DeliveryLocationRepository::class);
        $dlR->shouldReceive('repoDeliveryLocationquery')->andReturn($delloc);

        $core = $this->core($invoice, $cpR, $dlR);

        $result = $this->call(self::INV_ID, $this->currentUser(false), $core);

        Assert::same(Status::FOUND, $result->getStatusCode());
    }
}
