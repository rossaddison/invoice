<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use Testo\Assert;
use Testo\Test;

/**
 * Everything about the offline PWA's installability that CAN be checked
 * without a real device — see
 * docs/HOMECARE_OFFLINE_PWA_DATA_FLOW_AUGUST_2026.md's own "Out of
 * scope" note. Deliberately not a substitute for that: real
 * airplane-mode / service-worker-install behavior on physical iOS
 * Safari and Android Chrome is still unverified, and nothing here can
 * verify it — jsdom doesn't implement service workers, and there's no
 * real device in CI. What this file DOES catch is the class of
 * regression that would break offline mode even on a perfect device: a
 * route rename `sw.ts`'s hardcoded URLs don't follow, a swapped or
 * missing icon file, a manifest field silently dropped.
 *
 * A green run here is NOT "airplane mode verified." Someone still owes
 * a phone with the wifi turned off — this test file existing is the
 * reminder that step is still outstanding, not a replacement for it.
 */
#[Test]
final class OfflinePwaInstallabilityTest
{
    private const string PUBLIC_DIR = __DIR__ . '/../../../../public';
    private const string ROUTES_FILE = __DIR__ . '/../../../../config/common/routes/routes-inv.php';

    public function manifestDeclaresAStandaloneDisplayMode(): void
    {
        $manifest = $this->readManifest();
        /** @var mixed $display */
        $display = $manifest['display'] ?? null;
        Assert::true(is_string($display), 'manifest.json "display" must be a string.');
        Assert::same($display, 'standalone');
    }

    public function manifestIconsExistOnDiskAtTheirDeclaredSize(): void
    {
        $manifest = $this->readManifest();
        /** @var mixed $iconsRaw */
        $iconsRaw = $manifest['icons'] ?? null;
        Assert::true(is_array($iconsRaw), 'manifest.json "icons" must be an array.');
        Assert::true(count($iconsRaw) >= 2, 'Expected at least two icons (192x192, 512x512) in manifest.json.');

        foreach ($iconsRaw as $icon) {
            Assert::true(is_array($icon), 'Each manifest.json icon entry must be an object.');
            /** @var array<string, mixed> $icon */
            /** @var mixed $src */
            $src = $icon['src'] ?? null;
            /** @var mixed $sizes */
            $sizes = $icon['sizes'] ?? null;
            Assert::true(is_string($src) && is_string($sizes), 'Icon entry missing a string src/sizes.');

            $path = self::PUBLIC_DIR . '/' . $src;
            Assert::true(file_exists($path), $src . ' referenced by manifest.json does not exist on disk.');

            $dimensions = array_map('intval', explode('x', $sizes));
            $info = getimagesize($path);
            Assert::true($info !== false, $src . ' is not a readable image.');
            /** @var array{0: int, 1: int} $info */
            Assert::same($info[0], $dimensions[0] ?? null, $src . ' width does not match manifest.json.');
            Assert::same($info[1], $dimensions[1] ?? null, $src . ' height does not match manifest.json.');
        }
    }

    /**
     * The actual regression this file exists to catch: if
     * inv/guest/offline's route path ever changes without sw.ts's
     * SHELL_URL following it, the service worker keeps precaching a URL
     * nobody serves the shell from any more — offline mode breaks
     * completely and silently, with zero failure anywhere else.
     * Cross-checks the real route registration against the built
     * service worker's own precache list rather than hardcoding the
     * same literal path on both sides of this test, which would just
     * duplicate the same mistake if it ever happened.
     */
    public function serviceWorkerPrecachesTheActualOfflineShellRoute(): void
    {
        $routePath = $this->extractRoutePath('offline');
        $serviceWorker = (string) file_get_contents(self::PUBLIC_DIR . '/sw.js');
        Assert::true(
            str_contains($serviceWorker, $routePath),
            'public/sw.js does not precache ' . $routePath
                . ' (routes-inv.php\'s inv/guest/offline route) — offline mode would silently break.',
        );
    }

    /**
     * The other half of "SW's job is narrow on purpose"
     * (HOMECARE_OFFLINE_PWA_DATA_FLOW_AUGUST_2026.md's own "Reading it"
     * note): the JSON data endpoint must always hit the network — see
     * Guest::guestOfflineData()'s own docblock — so it must never appear
     * in the precache list.
     */
    public function serviceWorkerNeverPrecachesTheJsonDataEndpoint(): void
    {
        $offlineDataRoute = $this->extractRoutePath('offline-data');
        $serviceWorker = (string) file_get_contents(self::PUBLIC_DIR . '/sw.js');
        Assert::false(
            str_contains($serviceWorker, $offlineDataRoute),
            'sw.js must never precache ' . $offlineDataRoute . ' — it always needs a live request.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(): array
    {
        $raw = file_get_contents(self::PUBLIC_DIR . '/manifest.json');
        Assert::true($raw !== false, 'public/manifest.json is missing.');
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }

    private function extractRoutePath(string $suffix): string
    {
        $routes = (string) file_get_contents(self::ROUTES_FILE);
        $pattern = '#Route::get\(\'(/client_invoices/' . preg_quote($suffix, '#') . ')\'\)#';
        $matched = preg_match($pattern, $routes, $matches) === 1;
        Assert::true($matched, 'Could not find the ' . $suffix . ' route in routes-inv.php — has it moved?');
        return $matches[1];
    }
}
