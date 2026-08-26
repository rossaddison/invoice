<?php

declare(strict_types=1);

namespace Tests\Testo\Redirect;

use App\Redirect\RedirectController;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers RedirectController::buildCountryStyle() — specifically the real
 * bug where a country represented in world-map.svg as a `<g id="...">`
 * wrapping multiple `<path>` children (mainland + islands/territories —
 * the US, GB, and 35 others) never rendered any color at all under a
 * `#{code} { fill: ...; }`-only CSS rule: the base `path { fill: #e5e5e5;
 * ... }` rule matches every `<path>` directly, which always wins over a
 * value merely inherited from a less-specific ancestor `<g>` match.
 * Reflection-constructed via newInstanceWithoutConstructor() since this
 * method touches no injected dependency, matching
 * RedirectControllerBotDetectionTest's own established pattern for
 * testing a private method in isolation.
 */
#[Test]
final class RedirectControllerCountryStyleTest
{
    /**
     * @param array<string, int> $counts
     */
    private function buildCountryStyle(array $counts): string
    {
        $reflectionClass = new ReflectionClass(RedirectController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();
        $method = $reflectionClass->getMethod('buildCountryStyle');

        /** @var string */
        return $method->invoke($controller, $counts);
    }

    public function emitsOnlyTheDefaultFillWhenThereAreNoCounts(): void
    {
        $css = $this->buildCountryStyle([]);

        Assert::same('path { fill: #e5e5e5; stroke: #ffffff; stroke-width: 0.5; }' . "\n", $css);
    }

    public function colorsBothTheIdSelectorAndItsDescendantPaths(): void
    {
        // 'us' is a real <g id="us"> in world-map.svg wrapping several
        // <path> children (mainland + Alaska + Hawaii) -- this is the
        // exact shape that silently never colored before the fix.
        $css = $this->buildCountryStyle(['us' => 1]);

        Assert::true(str_contains($css, '#us, #us path { fill: #08306b; }'));
    }

    public function stillColorsASinglePathCountryTheSameWay(): void
    {
        // 'in' (India) is a plain <path id="in"> with no children -- the
        // added ", #in path" half is simply inert for this shape, so the
        // fix must not regress countries that already worked.
        $css = $this->buildCountryStyle(['in' => 1]);

        Assert::true(str_contains($css, '#in, #in path { fill: #08306b; }'));
    }

    public function interpolatesColorByShareOfTheHighestCount(): void
    {
        $css = $this->buildCountryStyle(['us' => 7, 'kz' => 1]);

        // us is the max -> full-strength dark blue.
        Assert::true(str_contains($css, '#us, #us path { fill: #08306b; }'));
        // kz is 1/7 of the max -> a pale interpolated shade, not the
        // default gray and not full dark blue.
        Assert::false(str_contains($css, '#kz, #kz path { fill: #08306b; }'));
        Assert::false(str_contains($css, '#kz, #kz path { fill: #e5e5e5; }'));
    }

    public function skipsACountryCodeThatSanitizesToNothing(): void
    {
        $css = $this->buildCountryStyle(['??' => 3]);

        Assert::false(str_contains($css, 'fill: #08306b'));
    }
}
