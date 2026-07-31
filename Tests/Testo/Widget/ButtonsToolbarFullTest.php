<?php

declare(strict_types=1);

namespace Tests\Testo\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\Setting\SettingRepository;
use App\Widget\ButtonsToolbarFull;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Covers the "Chromium PDF (Playwright)" button added to the advanced
 * button group — restricted to admin/observer (via the $canPlaywrightPdf
 * flag computed in View::view()), deliberately narrower than the other pdf*
 * actions since it shells out to Node/Chromium per request. Every other
 * button-producing branch is steered inert (invEdit false, non-draft
 * status, no sales order, peppol/pdf_stream/deletion settings off) so this
 * covers only the one new branch, not the whole toolbar.
 */
#[Test]
final class ButtonsToolbarFullTest
{
    private function render(bool $canPlaywrightPdf): string
    {
        $translator = m::mock(TranslatorInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $translator->shouldReceive('translate');
        $e->andReturnUsing(static fn (string $key): string => $key);

        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $urlGenerator->shouldReceive('generate');
        $e2->andReturn('/dummy-url');

        $settingRepository = m::mock(SettingRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $settingRepository->shouldReceive('getSetting');
        $e3->andReturn('0');

        $inv = m::mock(Inv::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $inv->shouldReceive('reqId');
        $e4->andReturn(233);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $inv->shouldReceive('reqStatusId');
        $e5->andReturn(2);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $inv->shouldReceive('getSoId');
        $e6->andReturn(0);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $inv->shouldReceive('getQuoteId');
        $e7->andReturn(0);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $inv->shouldReceive('getCreditinvoiceParentId');
        $e8->andReturn(0);
        /** @var \Mockery\Expectation $e9 */
        $e9 = $inv->shouldReceive('getIsReadOnly');
        $e9->andReturn(false);

        $iaR = m::mock(InvAmountRepository::class);
        /** @var \Mockery\Expectation $e10 */
        $e10 = $iaR->shouldReceive('repoInvAmountcount');
        $e10->andReturn(0);

        $toolbar = new ButtonsToolbarFull($translator, $urlGenerator, $settingRepository);

        return $toolbar->render($inv, $iaR, false, false, '0', false, $canPlaywrightPdf);
    }

    public function buttonIsPresentWhenAdminOrObserver(): void
    {
        Assert::true(str_contains($this->render(true), 'id="toolbar-full-pdf-playwright"'));
    }

    public function buttonIsAbsentOtherwise(): void
    {
        Assert::false(str_contains($this->render(false), 'id="toolbar-full-pdf-playwright"'));
    }
}
