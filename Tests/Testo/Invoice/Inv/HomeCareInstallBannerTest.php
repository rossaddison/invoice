<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\Widget\HomeCareInstallBanner;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The install nudge is hidden/shown by homecare-install-prompt.ts toggling
 * `d-none` on the banner element, so that element must never also carry
 * `d-flex` (which overrides `d-none` and left an empty banner on screen).
 */
#[Test]
final class HomeCareInstallBannerTest
{
    private function html(): string
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        return HomeCareInstallBanner::html($translator);
    }

    public function theBannerElementStartsHiddenAndCarriesNoFlexClass(): void
    {
        $html = $this->html();

        Assert::true((bool) preg_match('/<div id="homecare-install-banner" class="([^"]*)"/', $html, $m));
        $classes = explode(' ', $m[1] ?? '');
        Assert::true(in_array('d-none', $classes, true));
        Assert::false(in_array('d-flex', $classes, true));
    }

    public function theFlexLayoutLivesOnAnInnerWrapper(): void
    {
        $html = $this->html();

        Assert::true(str_contains($html, '<div class="d-flex align-items-center justify-content-between">'));
    }

    public function keepsEveryElementTheScriptLooksUpById(): void
    {
        $html = $this->html();

        foreach ([
            'homecare-install-banner',
            'homecare-install-message-ios',
            'homecare-install-message-generic',
            'homecare-install-button',
            'homecare-install-dismiss',
        ] as $id) {
            Assert::true(str_contains($html, 'id="' . $id . '"'));
        }
    }

    public function theMessagesAndButtonStartHiddenToo(): void
    {
        $html = $this->html();

        foreach (['homecare-install-message-ios', 'homecare-install-message-generic', 'homecare-install-button'] as $id) {
            Assert::true((bool) preg_match('/id="' . $id . '"[^>]*class="[^"]*d-none/', $html) || (bool) preg_match('/class="[^"]*d-none[^"]*"[^>]*id="' . $id . '"/', $html));
        }
    }

    public function theTagsAreBalanced(): void
    {
        $html = $this->html();

        Assert::same(substr_count($html, '<div'), substr_count($html, '</div>'));
        Assert::same(substr_count($html, '<button'), substr_count($html, '</button>'));
        Assert::same(substr_count($html, '<span'), substr_count($html, '</span>'));
    }
}
