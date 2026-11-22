<?php

declare(strict_types=1);

namespace Tests\Testo\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\Setting\SettingRepository;
use App\Widget\ButtonsToolbarFull;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The invoice toolbar's Peppol, Ecosio validator, Storecove JSON and Chromium PDF buttons open in a new window. They
 * were rendered as href="#" with an inline onclick="window.open(...)". The Content-Security-Policy has no
 * 'unsafe-inline' for scripts, so the browser refused the handler and the buttons did nothing. They are now real
 * links with target="_blank".
 */
#[Test]
final class ButtonsToolbarFullWindowButtonsTest
{
    private const string ECOSIO = 'https://ecosio.com/en/peppol-e-invoice-xml-document-validator/';

    private function toolbar(): string
    {
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);

        $urlGenerator = TypedMock::of(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturnUsing(
            static fn (string $name, array $arguments = []): string => '/invoice/' . $name . '/' . (string) ($arguments['id'] ?? ''),
        );

        // Peppol on; the other settings off or empty
        $settings = TypedMock::of(SettingRepository::class);
        $settings->shouldReceive('getSetting')->andReturnUsing(
            static fn (string $key): string => $key === 'enable_peppol' ? '1' : '0',
        );

        // a sent invoice (not a draft) that came from sales order 7
        $inv = new Inv();
        $inv->setId(233);
        $inv->setStatusId(2);
        $inv->setSoId(7);
        $inv->setClientId(9);
        $inv->setQuoteId(0);

        $amounts = TypedMock::of(InvAmountRepository::class);
        $amounts->shouldReceive('repoInvAmountcount')->andReturn(0);

        return (new ButtonsToolbarFull($translator, $urlGenerator, $settings))
            ->render($inv, $amounts, true, false, '0', false, true);
    }

    /** The opening tag of the toolbar button with this id. */
    private function button(string $html, string $id): string
    {
        $start = strpos($html, 'id="toolbar-full-' . $id . '"');
        if ($start === false) {
            throw new \LogicException('no toolbar button with id ' . $id);
        }
        $tagStart = strrpos(substr($html, 0, $start), '<a ');
        $tagEnd = strpos($html, '>', $start);
        if ($tagStart === false || $tagEnd === false) {
            throw new \LogicException('could not isolate the button ' . $id);
        }
        return substr($html, $tagStart, $tagEnd - $tagStart + 1);
    }

    public function theFourWindowButtonsAreRealLinksThatOpenInANewTab(): void
    {
        $html = $this->toolbar();
        $expected = [
            'peppol' => '/invoice/inv/peppol/233',
            'storecove' => '/invoice/inv/storecove/233',
            'ecosio-validator' => self::ECOSIO,
        ];

        foreach ($expected as $id => $href) {
            $button = $this->button($html, $id);

            Assert::true(str_contains($button, 'href="' . $href . '"'), $id . ' should link to ' . $href);
            Assert::true(str_contains($button, 'target="_blank"'), $id . ' should open in a new tab');
            Assert::true(str_contains($button, 'rel="noopener noreferrer"'), $id . ' should not hand the window opener over');
        }
    }

    public function theChromiumPdfButtonIsARealLinkToo(): void
    {
        $button = $this->button($this->toolbar(), 'chrome-pdf-viewer');

        Assert::true(str_contains($button, 'target="_blank"'));
        Assert::false(str_contains($button, 'href="#"'));
    }

    public function noButtonInTheToolbarUsesAnInlineHandlerTheCspWouldRefuse(): void
    {
        $html = $this->toolbar();

        Assert::false(str_contains($html, 'onclick'));
        Assert::false(str_contains($html, 'window.open'));
    }

    public function noWindowButtonJustJumpsToTheTopOfThePage(): void
    {
        $html = $this->toolbar();

        foreach (['peppol', 'storecove', 'ecosio-validator'] as $id) {
            Assert::false(str_contains($this->button($html, $id), 'href="#"'), $id . ' must not be href="#"');
        }
    }
}
