<?php

declare(strict_types=1);

namespace Tests\Testo\Widget;

use App\Widget\SubMenu;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * SubMenu::generate() renders the flyout markup shared by the Settings
 * menu's Company/Email/Access/Preferences/More groups and the Performance
 * menu's Prometheus Monitoring entry (see resources/views/layout/
 * invoice.php and .dropdown-submenu in components.css) -- a toggle
 * <span class="dropdown-submenu-toggle"> plus a nested
 * <ul class="dropdown-menu dropdown-menu-submenu"> of links, one per
 * $items entry, each resolved via UrlGeneratorInterface::generate().
 *
 * A fresh UrlGeneratorInterface mock is built per test method rather than
 * once in a constructor -- Testo calls every test method on the same class
 * instance, so a constructor-built mock's expectations would otherwise
 * leak between tests (confirmed live: sharing one caused two tests further
 * down the file to fail on stale expectations from an earlier test).
 */
#[Test]
final class SubMenuTest
{
    /** @return UrlGeneratorInterface&m\MockInterface */
    private function urlGeneratorReturning(string $url): UrlGeneratorInterface
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $e = $urlGenerator->shouldReceive('generate');
        $e->andReturn($url);
        return $urlGenerator;
    }

    public function rendersTheToggleRowWithTitleAndCaret(): void
    {
        $html = SubMenu::generate('Company', $this->urlGeneratorReturning('/company/index'), 'Arial', '14', [
            0 => ['items' => ['Company' => ['company/index', []]]],
        ]);

        Assert::true(str_contains($html, 'class="dropdown-item dropdown-submenu-toggle"'));
        Assert::true(str_contains($html, '>Company<'));
        Assert::true(str_contains($html, 'bi bi-chevron-right submenu-caret'));
        Assert::true(str_contains($html, 'font-size: 14px'));
        Assert::true(str_contains($html, 'font-family: Arial'));
    }

    public function rendersOneLinkPerItemResolvedThroughTheUrlGenerator(): void
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $e = $urlGenerator->shouldReceive('generate');
        $e->with('company/index', [])->andReturn('/company/index');
        $e2 = $urlGenerator->shouldReceive('generate');
        $e2->with('companyprivate/index', [])->andReturn('/companyprivate/index');

        $html = SubMenu::generate('Company', $urlGenerator, 'Arial', '14', [
            0 => ['items' => [
                'Company Public Details' => ['company/index', []],
                'Company Private Details' => ['companyprivate/index', []],
            ]],
        ]);

        Assert::true(str_contains($html, '<ul class="dropdown-menu dropdown-menu-submenu">'));
        Assert::true(str_contains($html, 'href="/company/index"'));
        Assert::true(str_contains($html, '>Company Public Details<'));
        Assert::true(str_contains($html, 'href="/companyprivate/index"'));
        Assert::true(str_contains($html, '>Company Private Details<'));
    }

    public function passesRouteArgumentsThroughToTheUrlGenerator(): void
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $e = $urlGenerator->shouldReceive('generate');
        $e->with('prometheus/metrics', ['foo' => 'bar'])->andReturn('/prometheus/metrics?foo=bar');

        $html = SubMenu::generate('Performance', $urlGenerator, 'Arial', '14', [
            0 => ['items' => ['Raw Metrics' => ['prometheus/metrics', ['foo' => 'bar']]]],
        ]);

        Assert::true(str_contains($html, 'href="/prometheus/metrics?foo=bar"'));
    }

    public function encodesLabelsAndTitlesToPreventBrokenMarkup(): void
    {
        $html = SubMenu::generate('<b>Title</b>', $this->urlGeneratorReturning('/x'), 'Arial', '14', [
            0 => ['items' => ['<script>alert(1)</script>' => ['x', []]]],
        ]);

        Assert::false(str_contains($html, '<b>Title</b>'));
        Assert::true(str_contains($html, '&lt;b&gt;Title&lt;/b&gt;'));
        Assert::false(str_contains($html, '<script>alert(1)</script>'));
        Assert::true(str_contains($html, '&lt;script&gt;'));
    }

    public function returnsAnEmptyStringWhenGivenNoLevels(): void
    {
        $html = SubMenu::generate('Company', $this->urlGeneratorReturning('/x'), 'Arial', '14', []);

        Assert::same('', $html);
    }
}
