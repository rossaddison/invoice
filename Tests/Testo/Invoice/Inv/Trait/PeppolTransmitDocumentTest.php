<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Contract\ContractRepository;
use App\Invoice\Delivery\DeliveryRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\DeliveryParty\DeliveryPartyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Helpers\Peppol\Exception\PeppolTaxCategoryCodeNotFoundException;
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
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

/**
 * transmitPeppolDocument() (App\Invoice\Inv\Trait\Peppol) wraps
 * PeppolHelper::generateInvoicePeppolUblXmlTempFile() -- a large,
 * heavily-interdependent real UBL-XML generator (dozen+ repositories,
 * real file I/O, non-nullable date fields) -- in a catch-all Throwable
 * that turns any failure into a flash message via
 * friendlyPeppolExceptionMessage(). Reaching a genuine *successful*
 * generation would need a full realistic invoice fixture (items,
 * amounts, tax rates, addresses, ...); out of scope for a focused unit
 * test, and better suited to this project's Codeception acceptance
 * suite. This covers the catch-all path instead: real, valuable
 * error-handling logic, reachable with a controlled failure at
 * generateInvoicePeppolUblXmlTempFile()'s first line ($invoice->reqId())
 * rather than needing the rest of the pipeline to be realistic at all.
 *
 * Reflects into the real InvController, same technique as this
 * directory's other Peppol.php trait tests.
 */
#[Test]
final class PeppolTransmitDocumentTest
{
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

        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturnUsing(
            static fn (string $route): string => '/' . $route,
        );
        $webService = new WebControllerService($psr17, $psr17, $urlGenerator);

        $refClass = new ReflectionClass(InvController::class);
        $instance = $refClass->newInstanceWithoutConstructor();
        $refClass->getProperty('translator')->setValue($instance, $translator);
        $refClass->getProperty('sR')->setValue($instance, $sR);
        $refClass->getProperty('flash')->setValue($instance, $flash);
        $refClass->getProperty('webService')->setValue($instance, $webService);

        return [$instance, $refClass];
    }

    private function invoiceThatThrows(\Throwable $e): Inv
    {
        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);

        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        // generateInvoicePeppolUblXmlTempFile()'s very first line -- fails
        // before anything else in the pipeline is ever touched.
        $invoice->shouldReceive('reqId')->andThrow($e);
        $invoice->shouldReceive('getInvAmount')->andReturn($invAmount);

        return $invoice;
    }

    private function core(): InvPeppolCoreDeps
    {
        /** @var InvRepository&m\MockInterface $invRepo */
        $invRepo = m::mock(InvRepository::class);
        /** @var InvItemAmountRepository&m\MockInterface $iiaR */
        $iiaR = m::mock(InvItemAmountRepository::class);
        /** @var ClientPeppolRepository&m\MockInterface $cpR */
        $cpR = m::mock(ClientPeppolRepository::class);
        /** @var DeliveryLocationRepository&m\MockInterface $dlR */
        $dlR = m::mock(DeliveryLocationRepository::class);
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

    private function call(\Throwable $thrown): ?string
    {
        [$instance, $refClass] = $this->harness();
        /** @var Flash&m\MockInterface $flash */
        $flash = $refClass->getProperty('flash')->getValue($instance);
        $captured = null;
        $flash->shouldReceive('add')->andReturnUsing(function () use (&$captured): void {
            $args = func_get_args();
            $captured = (string) $args[1];
        });

        /** @var DeliveryLocation&m\MockInterface $delloc */
        $delloc = m::mock(DeliveryLocation::class);
        /** @var PeppolSendServiceInterface&m\MockInterface $sendService */
        $sendService = m::mock(PeppolSendServiceInterface::class);

        $method = $refClass->getMethod('transmitPeppolDocument');
        $method->invoke(
            $instance,
            $this->invoiceThatThrows($thrown),
            $delloc,
            $this->core(),
            $this->net(),
            $this->charge(),
            $this->inv(),
            $sendService,
        );

        return $captured;
    }

    public function genericExceptionFlashesItsOwnMessage(): void
    {
        $captured = $this->call(new \RuntimeException('Something went wrong'));

        Assert::notNull($captured);
        Assert::true(str_contains($captured, 'Something went wrong'));
    }

    public function friendlyExceptionWithoutTaxRateIdFlashesItsNameAndNoLink(): void
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')
            ->with('peppol.tax.category.not.found')
            ->andReturn('peppol.tax.category.not.found');

        $captured = $this->call(new PeppolTaxCategoryCodeNotFoundException($translator, taxRateId: null));

        Assert::notNull($captured);
        Assert::true(str_contains($captured, 'peppol.tax.category.not.found'));
        Assert::false(str_contains($captured, 'taxrate/edit'));
    }

    public function friendlyExceptionWithTaxRateIdFlashesItsNameAndADeepLink(): void
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')
            ->with('peppol.tax.category.not.found')
            ->andReturn('peppol.tax.category.not.found');

        $captured = $this->call(new PeppolTaxCategoryCodeNotFoundException($translator, taxRateId: 5));

        Assert::notNull($captured);
        Assert::true(str_contains($captured, 'peppol.tax.category.not.found'));
        Assert::true(str_contains($captured, 'taxrate/edit'));
    }
}
