<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvController;
use App\Invoice\Inv\InvPeppolCoreDeps;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
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
 * resolveInvoiceAndDeliveryLocation() (App\Invoice\Inv\Trait\Peppol) is
 * private -- split out of peppol() so the controller action itself
 * doesn't carry this method's two guard-clause returns on top of its own
 * catch/success returns (Sonar S1142). Reflects into the real
 * InvController for the same reason as PeppolEndpointidFormatProblemTest
 * -- see that file's own docblock for why a hand-rolled stub class fails
 * Psalm here.
 */
#[Test]
final class PeppolResolveInvoiceAndDeliveryLocationTest
{
    private const int INV_ID = 42;
    private const int CLIENT_ID = 7;

    /** @return array{0: object, 1: ReflectionClass<InvController>} */
    private function harness(): array
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

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
        $refClass->getProperty('flash')->setValue($instance, $flash);
        $refClass->getProperty('webService')->setValue($instance, $webService);

        return [$instance, $refClass];
    }

    private function core(
        ?Inv $invoice,
        ClientPeppolRepository $cpR,
        DeliveryLocationRepository $dlR,
    ): InvPeppolCoreDeps {
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

        return new InvPeppolCoreDeps(
            invRepo: $invRepo,
            iiaR: $iiaR,
            cpR: $cpR,
            dlR: $dlR,
            paR: $paR,
            soR: $soR,
            gR: $gR,
        );
    }

    private function currentUser(bool $isGuest): CurrentUser
    {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldReceive('isGuest')->andReturn($isGuest);
        return $currentUser;
    }

    private function invoiceWithClient(?int $deliveryLocationId): Inv
    {
        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        $client->shouldReceive('reqId')->andReturn(self::CLIENT_ID);

        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('getClient')->andReturn($client);
        $invoice->shouldReceive('getDeliveryLocationId')->andReturn($deliveryLocationId);

        return $invoice;
    }

    /** Every required field filled with a non-empty, non-"@" value, on a
     * scheme (9999) with no registered checksum validator -- a fully
     * valid setup, per validateClientPeppolSetup()'s own rules. */
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

    /** @return ResponseInterface|array{0: Inv, 1: DeliveryLocation} */
    private function call(int $id, CurrentUser $currentUser, InvPeppolCoreDeps $core): ResponseInterface|array
    {
        [$instance, $refClass] = $this->harness();
        $method = $refClass->getMethod('resolveInvoiceAndDeliveryLocation');

        /** @var ResponseInterface|array{0: Inv, 1: DeliveryLocation} $result */
        $result = $method->invoke($instance, $id, $currentUser, $core);
        return $result;
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

    public function guestUserGetsNotFound(): void
    {
        $core = $this->core(null, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(self::INV_ID, $this->currentUser(true), $core);

        Assert::true($result instanceof ResponseInterface);
        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function zeroIdGetsNotFound(): void
    {
        $core = $this->core(null, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(0, $this->currentUser(false), $core);

        Assert::true($result instanceof ResponseInterface);
        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function invoiceNotFoundGetsNotFound(): void
    {
        $core = $this->core(null, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(self::INV_ID, $this->currentUser(false), $core);

        Assert::true($result instanceof ResponseInterface);
        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function zeroClientIdGetsNotFound(): void
    {
        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('getClient')->andReturn(null);

        $core = $this->core($invoice, $this->emptyCpR(), $this->emptyDlR());

        $result = $this->call(self::INV_ID, $this->currentUser(false), $core);

        Assert::true($result instanceof ResponseInterface);
        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function happyPathReturnsInvoiceAndDeliveryLocation(): void
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

        $result = $this->call(self::INV_ID, $this->currentUser(false), $this->core($invoice, $cpR, $dlR));

        Assert::true(is_array($result));
        Assert::same($invoice, $result[0]);
        Assert::same($delloc, $result[1]);
    }

    public function missingDeliveryLocationWhenFullySetupRedirects(): void
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

        $result = $this->call(self::INV_ID, $this->currentUser(false), $this->core($invoice, $cpR, $dlR));

        Assert::true($result instanceof ResponseInterface);
        Assert::same(Status::FOUND, $result->getStatusCode());
    }

    public function noClientPeppolRecordAtAllRedirects(): void
    {
        $invoice = $this->invoiceWithClient(null);

        /** @var ClientPeppolRepository&m\MockInterface $cpR */
        $cpR = m::mock(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientCount')->andReturn(0);

        $result = $this->call(
            self::INV_ID,
            $this->currentUser(false),
            $this->core($invoice, $cpR, $this->emptyDlR()),
        );

        Assert::true($result instanceof ResponseInterface);
        Assert::same(Status::FOUND, $result->getStatusCode());
    }

    public function invalidClientPeppolSetupRedirects(): void
    {
        $invoice = $this->invoiceWithClient(null);

        // client_id set (avoids reqClientId() throwing inside
        // clientPeppolFieldLink()); every required field left at its
        // empty default -- validateClientPeppolSetup() returns false.
        $clientPeppol = new ClientPeppol(client_id: self::CLIENT_ID);

        /** @var ClientPeppolRepository&m\MockInterface $cpR */
        $cpR = m::mock(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientCount')->andReturn(1);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn($clientPeppol);

        $result = $this->call(
            self::INV_ID,
            $this->currentUser(false),
            $this->core($invoice, $cpR, $this->emptyDlR()),
        );

        Assert::true($result instanceof ResponseInterface);
        Assert::same(Status::FOUND, $result->getStatusCode());
    }
}
