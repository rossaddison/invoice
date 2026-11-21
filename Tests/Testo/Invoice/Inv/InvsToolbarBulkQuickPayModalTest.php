<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\Widget\InvsToolbar;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The bulk Quick Pay modal on inv/index: payment date, bank ref and the optional "Append date to Note"
 * checkbox that the page's JavaScript reads by id.
 */
#[Test]
final class InvsToolbarBulkQuickPayModalTest
{
    private function modal(): string
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        $method = new \ReflectionMethod(InvsToolbar::class, 'buildBulkQuickPayModal');
        /** @var string $html */
        $html = $method->invoke(null, $translator, '2026-11-21');
        return $html;
    }

    public function offersAnAppendDateToNoteCheckboxThatStartsUnticked(): void
    {
        $html = $this->modal();

        Assert::true(str_contains($html, 'id="bulk-quick-pay-append-date"'));
        Assert::true(str_contains($html, 'type="checkbox"'));
        Assert::false(str_contains($html, 'checked'));
    }

    public function labelsTheCheckboxWithTheAppendDateTranslationKey(): void
    {
        $html = $this->modal();

        Assert::true(str_contains($html, 'for="bulk-quick-pay-append-date"'));
        Assert::true(str_contains($html, 'append.date.to.note'));
    }

    public function keepsThePaymentDateAndBankRefFields(): void
    {
        $html = $this->modal();

        Assert::true(str_contains($html, 'id="bulk-quick-pay-date"'));
        Assert::true(str_contains($html, 'value="2026-11-21"'));
        Assert::true(str_contains($html, 'id="bulk-quick-pay-note"'));
        Assert::true(str_contains($html, 'id="bulk-quick-pay-confirm"'));
    }
}
