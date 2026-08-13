<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Widget;

use App\Invoice\Quote\Widget\QuotesToolbar;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Unit tests for QuotesToolbar::build().
 *
 * CurrentRoute is a final concrete class with a zero-arg constructor;
 * getName() returns null, so the toolbar falls back to 'quote/index'.
 * UrlGeneratorInterface/TranslatorInterface are stubbed — build() only
 * calls generate()/translate() and doesn't need real routing/i18n.
 */
final class QuotesToolbarTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private CurrentRoute $currentRoute;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private UrlGeneratorInterface $urlGenerator;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentRoute = new CurrentRoute();
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->translator = $this->createStub(TranslatorInterface::class);

        $this->urlGenerator->method('generate')->willReturnArgument(0);
        $this->translator->method('translate')->willReturnArgument(0);
    }

    private function build(int $clientCount = 1, bool $enableGrouping = false): string
    {
        return QuotesToolbar::build(
            $this->translator,
            $this->urlGenerator,
            $this->currentRoute,
            'csrf-token',
            $clientCount,
            'none',
            $enableGrouping,
        );
    }

    public function testIncludesAutoFitColumnsButton(): void
    {
        self::assertStringContainsString('id="btn-autofit-columns"', $this->build());
    }

    public function testIncludesResetColumnWidthsButton(): void
    {
        self::assertStringContainsString('id="btn-reset-column-widths"', $this->build());
    }

    public function testAutoFitAndResetButtonsAreNotFormSubmitting(): void
    {
        // Both are client-side-only actions (ColumnResizer.autoFit()/reset()), not a
        // form submit/reload — type="button", not the default type="submit".
        $html = $this->build();
        self::assertMatchesRegularExpression(
            '/id="btn-autofit-columns"[^>]*type="button"|type="button"[^>]*id="btn-autofit-columns"/',
            $html,
        );
        self::assertMatchesRegularExpression(
            '/id="btn-reset-column-widths"[^>]*type="button"|type="button"[^>]*id="btn-reset-column-widths"/',
            $html,
        );
    }

    public function testIncludesResetAndAllVisibleButtons(): void
    {
        $html = $this->build();
        self::assertStringContainsString('id="btn-reset"', $html);
        self::assertStringContainsString('id="btn-all-visible"', $html);
    }

    public function testShowsDisabledAddButtonWhenNoClients(): void
    {
        self::assertStringContainsString('id="btn-disabled-quote-add-button"', $this->build(clientCount: 0));
    }

    public function testShowsEnabledAddButtonWhenClientsExist(): void
    {
        $html = $this->build(clientCount: 1);
        self::assertStringContainsString('id="btn-enabled-quote-add-button"', $html);
        self::assertStringNotContainsString('id="btn-disabled-quote-add-button"', $html);
    }

    public function testIncludesCollapseExpandButtonsOnlyWhenGroupingEnabled(): void
    {
        self::assertStringContainsString('data-action="toggle-all-groups"', $this->build(enableGrouping: true));
        self::assertStringNotContainsString('data-action="toggle-all-groups"', $this->build(enableGrouping: false));
    }

    public function testRendersAFormWrappingTheToolbar(): void
    {
        $html = $this->build();
        self::assertStringStartsWith('<form', $html);
        self::assertStringEndsWith('</form>', $html);
    }
}
