<?php

declare(strict_types=1);

namespace App\Redirect;

use App\Auth\Controller\AuthSecurityHelper;
use App\Infrastructure\Persistence\RedirectClick\RedirectClick;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * `go()` is a small, reusable tracked-redirect endpoint
 * (`GET /go/{key}`) — logs which country the click came from (see
 * GeoIpLookupService's own docblock for why only the country, never the
 * IP, is kept), then 302s to the real destination. Currently only wired
 * up for the homepage's own "View the source on GitHub" button
 * (`link_key: 'github'`), but `self::DESTINATIONS` is a small map so
 * adding more tracked links later is a one-line change, not a new
 * action.
 *
 * The redirect itself always succeeds, unconditionally — `shouldRecordClick()`
 * only ever suppresses the *data-logging* side for a request that looks
 * bot-driven (missing/known-bot User-Agent, or a Referer that isn't this
 * same site), never the redirect a real or automated visitor is expecting.
 * This raises the bar against casual scrapers/link-preview bots polluting
 * the country data; it does not, and cannot, stop a genuinely distributed
 * botnet using real browser User-Agents and IPs — the same honest limit
 * this app's own `/login` route documents for its rate limiter (see that
 * route's own comment in this file). `RateLimiter::global()`/`perIp()` on
 * this route (below) bound the worst case the same way.
 *
 * `map()` (`GET /redirect-map`, gated behind Permissions::EDIT_INV —
 * same access level as Settings) renders a choropleth of all-time click
 * counts per country for a given tracked link, using
 * `flekschas/simple-world-map` (CC BY-SA 3.0 — see
 * resources/redirect-map/world-map.svg's own header) as the base map.
 * Colors are generated as a plain inline `<style>` block server-side
 * (this app's CSP already permits `style-src 'unsafe-inline'`, unlike
 * `script-src`) — no client-side JS/TS needed for this at all.
 */
final class RedirectController
{
    /**
     * @var array<string, string>
     */
    private const DESTINATIONS = [
        'github' => 'https://github.com/rossaddison/invoice',
    ];

    public function __construct(
        private readonly RedirectClickRepository $redirectClickRepository,
        private readonly GeoIpLookupService $geoIpLookupService,
        private readonly AuthSecurityHelper $authSecurityHelper,
        private readonly ResponseFactoryInterface $responseFactory,
        private WebViewRenderer $webViewRenderer,
        private readonly LoggerInterface $logger,
    ) {
        $this->webViewRenderer = $this->webViewRenderer->withController($this);
    }

    /**
     * Case-insensitive substring match against the User-Agent header.
     * Covers common scrapers/HTTP libraries and link-preview/social-share
     * bots (Slack, Facebook, Twitter/X, Discord, WhatsApp) — the latter
     * aren't malicious, but a link shared once and preview-fetched by
     * several platforms would otherwise count as several "clicks" that
     * were never a person following the link.
     *
     * @var list<string>
     */
    private const BOT_USER_AGENT_SIGNATURES = [
        'bot', 'crawl', 'spider', 'curl/', 'wget/', 'python-requests',
        'go-http-client', 'scrapy', 'headlesschrome', 'phantomjs',
        'facebookexternalhit', 'slackbot', 'twitterbot', 'discordbot',
        'whatsapp', 'telegrambot',
    ];

    public function go(CurrentRoute $currentRoute, Request $request): Response
    {
        $key = $currentRoute->getArgument('key') ?? '';
        $destination = self::DESTINATIONS[$key] ?? null;
        if ($destination === null) {
            return $this->responseFactory->createResponse(404);
        }

        if ($this->shouldRecordClick($request)) {
            $ip = $this->authSecurityHelper->getClientIpAddress($request);
            $countryCode = $this->geoIpLookupService->lookupCountryCode($ip);
            $this->redirectClickRepository->save(new RedirectClick($key, $countryCode));
            // Permanent, low-noise instrumentation (one line per recorded
            // click, not every hit) — cheap to keep, and it's what
            // actually showed the ipapi.co -> ip-api.com fallback path
            // working correctly the first time a real click resolved a
            // country live (confirmed 2026-08-18).
            $this->logger->debug('RedirectController: click recorded.', ['key' => $key, 'countryCode' => $countryCode]);
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $destination);
    }

    /**
     * See this class's own docblock for what this does and doesn't
     * defend against. Never consulted before deciding whether to
     * redirect — only whether to log.
     */
    private function shouldRecordClick(Request $request): bool
    {
        $userAgent = strtolower($request->getHeaderLine('User-Agent'));
        if ($userAgent === '' || $this->isBotUserAgent($userAgent)) {
            return false;
        }

        return !$this->isCrossSiteReferer($request);
    }

    private function isBotUserAgent(string $userAgent): bool
    {
        foreach (self::BOT_USER_AGENT_SIGNATURES as $signature) {
            if (str_contains($userAgent, $signature)) {
                return true;
            }
        }
        return false;
    }

    private function isCrossSiteReferer(Request $request): bool
    {
        $referer = $request->getHeaderLine('Referer');
        if ($referer === '') {
            return false;
        }
        $refererHost = parse_url($referer, PHP_URL_HOST);
        return is_string($refererHost) && strcasecmp($refererHost, $request->getUri()->getHost()) !== 0;
    }

    public function map(): Response
    {
        $counts = $this->redirectClickRepository->countsByCountryQuery('github');
        $mapSvg = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/redirect-map/world-map.svg');

        return $this->webViewRenderer->render('map', [
            'mapSvg' => $mapSvg,
            'countryStyle' => $this->buildCountryStyle($counts),
            'counts' => $counts,
        ]);
    }

    /**
     * Builds a plain CSS `<style>` body (no `<style>` tags themselves —
     * the view wraps it) with one `#{country_code}, #{country_code}
     * path { fill: ...; }` rule per country that has at least one
     * recorded click, plus a default fill for every other country path.
     * Colors are a linear interpolation between a pale and a deep blue
     * based on each country's share of the highest single count — a
     * continuous gradient rather than fixed buckets, since the number of
     * distinct countries here is small enough that discrete quantile
     * buckets wouldn't have enough members each to look meaningful.
     *
     * The trailing `#{code} path` half matters: in
     * `resources/redirect-map/world-map.svg`, a country with outlying
     * territory (the US's mainland+Alaska+Hawaii, the UK's mainland
     * +islands, and 35 others — anything with its own `<g id="...">`
     * wrapping multiple `<path>` children rather than one `<path
     * id="...">` directly) never rendered any color at all under a
     * `#{code} { fill: ...; }`-only rule: SVG doesn't let a fill set on
     * an ancestor `<g>` override the base `path { fill: #e5e5e5; }` rule
     * above, which matches every `<path>` directly and always wins over
     * an inherited value from a less-specific ancestor match. Selecting
     * `#{code} path` too reaches those children directly, at higher
     * specificity than the base rule, so multi-path countries color
     * correctly alongside single-path ones.
     *
     * @param array<string, int> $counts
     */
    private function buildCountryStyle(array $counts): string
    {
        $css = 'path { fill: #e5e5e5; stroke: #ffffff; stroke-width: 0.5; }' . "\n";
        if ($counts === []) {
            return $css;
        }

        $max = max($counts);
        // #deebf7 (pale blue) -> #08306b (deep blue), matching a
        // conventional single-hue choropleth palette.
        $light = [0xde, 0xeb, 0xf7];
        $dark = [0x08, 0x30, 0x6b];

        foreach ($counts as $countryCode => $count) {
            $t = $max > 0 ? $count / $max : 0.0;
            $rgb = [];
            foreach ([0, 1, 2] as $i) {
                $rgb[$i] = (int) round($light[$i] + ($dark[$i] - $light[$i]) * $t);
            }
            $hex = sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
            $safeCode = preg_replace('/[^a-z0-9_]/', '', $countryCode) ?? '';
            if ($safeCode === '') {
                continue;
            }
            $css .= "#{$safeCode}, #{$safeCode} path { fill: {$hex}; }\n";
        }

        return $css;
    }
}
