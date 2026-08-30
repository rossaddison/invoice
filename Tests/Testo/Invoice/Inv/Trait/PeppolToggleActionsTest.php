<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Inv\InvController;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;

/**
 * peppolDocCurrencyToggle() and peppolStreamToggle() (App\Invoice\Inv\
 * Trait\Peppol) were previously untested -- part of a follow-up pass to
 * cover the rest of the trait after the S1142/S1448 fixes in #1145.
 * Reflects into the real InvController, same technique as
 * PeppolEndpointidFormatProblemTest / PeppolResolveInvoiceAndDeliveryLocationTest
 * -- see either file's own docblock for why.
 */
#[Test]
final class PeppolToggleActionsTest
{
    private const int INV_ID = 42;

    /** @return array{0: object, 1: ReflectionClass<InvController>} */
    private function harness(SettingRepository $sR): array
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
        $refClass->getProperty('sR')->setValue($instance, $sR);
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

    private function callDocCurrencyToggle(int $id, CurrentUser $currentUser, SettingRepository $sR): ResponseInterface
    {
        [$instance, $refClass] = $this->harness($sR);
        $method = $refClass->getMethod('peppolDocCurrencyToggle');

        /** @var ResponseInterface $result */
        $result = $method->invoke($instance, $id, $currentUser);
        return $result;
    }

    private function callStreamToggle(int $id, CurrentUser $currentUser, SettingRepository $sR): ResponseInterface
    {
        [$instance, $refClass] = $this->harness($sR);
        $method = $refClass->getMethod('peppolStreamToggle');

        /** @var ResponseInterface $result */
        $result = $method->invoke($instance, $id, $currentUser);
        return $result;
    }

    // ── peppolDocCurrencyToggle() ────────────────────────────────────────

    public function docCurrencyToggleGuestGetsNotFound(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getDocumentCurrencyCodeFromPeppolDetails')->andReturn('EUR');

        $result = $this->callDocCurrencyToggle(self::INV_ID, $this->currentUser(true), $sR);

        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function docCurrencyToggleFlipsOnWhenCurrentlyOff(): void
    {
        $toggleSetting = new Setting('peppol_doc_currency_toggle', '0');
        $docCurrencySetting = new Setting('peppol_document_currency', 'EUR');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getDocumentCurrencyCodeFromPeppolDetails')->andReturn('EUR');
        $sR->shouldReceive('repoCount')->with('peppol_doc_currency_toggle')->andReturn(1);
        $sR->shouldReceive('withKey')->with('peppol_doc_currency_toggle')->andReturn($toggleSetting);
        $sR->shouldReceive('getSetting')->with('peppol_doc_currency_toggle')->andReturn('0');
        $sR->shouldReceive('getSetting')->with('currency_code_from')->andReturn('GBP');
        $sR->shouldReceive('repoCount')->with('peppol_document_currency')->andReturn(1);
        $sR->shouldReceive('withKey')->with('peppol_document_currency')->andReturn($docCurrencySetting);
        $sR->shouldReceive('save');

        $result = $this->callDocCurrencyToggle(self::INV_ID, $this->currentUser(false), $sR);

        Assert::same(Status::FOUND, $result->getStatusCode());
        Assert::same('1', $toggleSetting->getSettingValue());
        Assert::same('GBP', $docCurrencySetting->getSettingValue());
    }

    public function docCurrencyToggleFlipsOffWhenCurrentlyOn(): void
    {
        $toggleSetting = new Setting('peppol_doc_currency_toggle', '1');
        $docCurrencySetting = new Setting('peppol_document_currency', 'GBP');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getDocumentCurrencyCodeFromPeppolDetails')->andReturn('GBP');
        $sR->shouldReceive('repoCount')->with('peppol_doc_currency_toggle')->andReturn(1);
        $sR->shouldReceive('withKey')->with('peppol_doc_currency_toggle')->andReturn($toggleSetting);
        $sR->shouldReceive('getSetting')->with('peppol_doc_currency_toggle')->andReturn('1');
        $sR->shouldReceive('getSetting')->with('currency_code_to')->andReturn('EUR');
        $sR->shouldReceive('repoCount')->with('peppol_document_currency')->andReturn(1);
        $sR->shouldReceive('withKey')->with('peppol_document_currency')->andReturn($docCurrencySetting);
        $sR->shouldReceive('save');

        $result = $this->callDocCurrencyToggle(self::INV_ID, $this->currentUser(false), $sR);

        Assert::same(Status::FOUND, $result->getStatusCode());
        Assert::same('0', $toggleSetting->getSettingValue());
        Assert::same('EUR', $docCurrencySetting->getSettingValue());
    }

    public function docCurrencyToggleLeavesCurrencyUntouchedWhenToggleSettingMissing(): void
    {
        $docCurrencySetting = new Setting('peppol_document_currency', 'EUR');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getDocumentCurrencyCodeFromPeppolDetails')->andReturn('EUR');
        $sR->shouldReceive('repoCount')->with('peppol_doc_currency_toggle')->andReturn(0);
        $sR->shouldReceive('repoCount')->with('peppol_document_currency')->andReturn(1);
        $sR->shouldReceive('withKey')->with('peppol_document_currency')->andReturn($docCurrencySetting);
        $sR->shouldReceive('save');

        $result = $this->callDocCurrencyToggle(self::INV_ID, $this->currentUser(false), $sR);

        Assert::same(Status::FOUND, $result->getStatusCode());
        // Untouched by the (skipped) toggle block -- stays at the
        // getDocumentCurrencyCodeFromPeppolDetails() initial value.
        Assert::same('EUR', $docCurrencySetting->getSettingValue());
    }

    public function docCurrencyToggleRedirectsToSettingsWhenNoDocumentCurrencySettingExists(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getDocumentCurrencyCodeFromPeppolDetails')->andReturn('EUR');
        $sR->shouldReceive('repoCount')->with('peppol_doc_currency_toggle')->andReturn(0);
        $sR->shouldReceive('repoCount')->with('peppol_document_currency')->andReturn(0);

        $result = $this->callDocCurrencyToggle(self::INV_ID, $this->currentUser(false), $sR);

        Assert::same(Status::FOUND, $result->getStatusCode());
        Assert::true(str_contains($result->getHeaderLine(Header::LOCATION), 'fake-url'));
    }

    // ── peppolStreamToggle() ─────────────────────────────────────────────

    public function streamToggleGuestGetsNotFound(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        $result = $this->callStreamToggle(self::INV_ID, $this->currentUser(true), $sR);

        Assert::same(Status::NOT_FOUND, $result->getStatusCode());
    }

    public function streamToggleFlipsOnWhenCurrentlyOff(): void
    {
        $streamSetting = new Setting('peppol_xml_stream', '0');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('repoCount')->with('peppol_xml_stream')->andReturn(1);
        $sR->shouldReceive('withKey')->with('peppol_xml_stream')->andReturn($streamSetting);
        $sR->shouldReceive('getSetting')->with('peppol_xml_stream')->andReturn('0');
        $sR->shouldReceive('save');

        $result = $this->callStreamToggle(self::INV_ID, $this->currentUser(false), $sR);

        Assert::same(Status::FOUND, $result->getStatusCode());
        Assert::same('1', $streamSetting->getSettingValue());
    }

    public function streamToggleFlipsOffWhenCurrentlyOn(): void
    {
        $streamSetting = new Setting('peppol_xml_stream', '1');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('repoCount')->with('peppol_xml_stream')->andReturn(1);
        $sR->shouldReceive('withKey')->with('peppol_xml_stream')->andReturn($streamSetting);
        $sR->shouldReceive('getSetting')->with('peppol_xml_stream')->andReturn('1');
        $sR->shouldReceive('save');

        $result = $this->callStreamToggle(self::INV_ID, $this->currentUser(false), $sR);

        Assert::same(Status::FOUND, $result->getStatusCode());
        Assert::same('0', $streamSetting->getSettingValue());
    }

    public function streamToggleNoOpWhenSettingMissing(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('repoCount')->with('peppol_xml_stream')->andReturn(0);

        $result = $this->callStreamToggle(self::INV_ID, $this->currentUser(false), $sR);

        Assert::same(Status::FOUND, $result->getStatusCode());
    }
}
