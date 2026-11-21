<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Invoice\Inv\InvController;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use ReflectionClass;
use ReflectionMethod;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

/**
 * blockedByValidation() (App\Invoice\Inv\Trait\Peppol) is the gate that stops inv/peppolSend handing an invoice that
 * fails Peppol validation to the access point. Called here on the real InvController with a mocked flash, the same
 * technique as PeppolTransmitDocumentTest, and the real PeppolValidator on a real generated invoice.
 *
 * What it cannot show is the send itself: the generator behind it needs a full database fixture (see
 * PeppolTransmitDocumentTest and the acceptance suite). transmitPeppolDocument() sends only when this returns false.
 */
#[Test]
final class PeppolSendValidationGateTest
{
    private const int INVOICE_ID = 15;

    /** @var list<array{0: string, 1: string}> */
    private array $flashes = [];

    /** @param array<string, string> $settings */
    private function controller(array $settings): InvController
    {
        $this->flashes = [];
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturnUsing(static fn (string $key): string => $settings[$key] ?? '');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->andReturnUsing(function (string $level, string $message): void {
            $this->flashes[] = [$level, $message];
        });

        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturnUsing(
            static fn (string $route, array $arguments = []): string => '/' . $route . '/' . (string) ($arguments['id'] ?? ''),
        );

        $refClass = new ReflectionClass(InvController::class);
        $instance = $refClass->newInstanceWithoutConstructor();
        $refClass->getProperty('translator')->setValue($instance, $translator);
        $refClass->getProperty('sR')->setValue($instance, $sR);
        $refClass->getProperty('flash')->setValue($instance, $flash);
        $refClass->getProperty('webService')->setValue($instance, new WebControllerService($psr17, $psr17, $urlGenerator));

        return $instance;
    }

    private function blocked(InvController $controller, string $ublXml): bool
    {
        return (bool) (new ReflectionMethod($controller, 'blockedByValidation'))->invoke($controller, $ublXml, self::INVOICE_ID);
    }

    private function validInvoice(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/Peppol/Fixtures/valid-peppol-invoice.xml');
    }

    private function invoiceWithAWrongTotal(): string
    {
        return (string) preg_replace('~(<cbc:PayableAmount[^>]*>)[^<]*~', '${1}999.99', $this->validInvoice(), 1);
    }

    public function aValidInvoiceIsNotBlockedAndNothingIsShown(): void
    {
        Assert::false($this->blocked($this->controller(['peppol_validate_before_send' => '1']), $this->validInvoice()));
        Assert::same($this->flashes, []);
    }

    public function anInvoiceWithErrorsIsBlockedAndTheUserIsToldWhy(): void
    {
        Assert::true($this->blocked($this->controller(['peppol_validate_before_send' => '1']), $this->invoiceWithAWrongTotal()));

        Assert::same(count($this->flashes), 1);
        [$level, $message] = $this->flashes[0];
        Assert::same($level, 'warning');
        Assert::true(str_contains($message, 'peppol.send.blocked.by.validation'));
        Assert::true(str_contains($message, '<li><code>BR-CO-14</code>'));
        Assert::true(str_contains($message, 'peppol.send.blocked.preview'));
        Assert::true(str_contains($message, '/inv/peppol/' . self::INVOICE_ID));
    }

    public function theGateIsOnWhenTheSettingWasNeverSaved(): void
    {
        Assert::true($this->blocked($this->controller([]), $this->invoiceWithAWrongTotal()));
        Assert::true($this->blocked($this->controller(['peppol_validate_before_send' => '']), $this->invoiceWithAWrongTotal()));
    }

    public function switchingTheSettingOffLetsAnInvalidInvoiceThroughWithoutAMessage(): void
    {
        Assert::false($this->blocked($this->controller(['peppol_validate_before_send' => '0']), $this->invoiceWithAWrongTotal()));
        Assert::same($this->flashes, []);
    }

    public function textThatIsNotXmlIsBlocked(): void
    {
        Assert::true($this->blocked($this->controller(['peppol_validate_before_send' => '1']), 'not xml'));
        Assert::same(count($this->flashes), 1);
    }
}
