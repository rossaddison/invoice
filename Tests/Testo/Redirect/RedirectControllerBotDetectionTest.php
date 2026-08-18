<?php

declare(strict_types=1);

namespace Tests\Testo\Redirect;

use App\Redirect\RedirectController;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface as Request;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers RedirectController::shouldRecordClick() — the bot-detection gate
 * that decides whether a /go/{key} hit gets logged for the country
 * choropleth, never whether it gets redirected (that always happens
 * regardless — see the class's own docblock). Constructed via
 * newInstanceWithoutConstructor() since this method touches no injected
 * dependency, only the request itself, matching
 * GoCardlessPaymentControllerChargeDateTest's own established pattern
 * for testing a private method in isolation.
 */
#[Test]
final class RedirectControllerBotDetectionTest
{
    private function shouldRecordClick(Request $request): bool
    {
        $reflectionClass = new ReflectionClass(RedirectController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();
        $method = $reflectionClass->getMethod('shouldRecordClick');

        /** @var bool */
        return $method->invoke($controller, $request);
    }

    private function makeRequest(string $userAgent = 'Mozilla/5.0', string $referer = '', string $host = 'yii3i.online'): Request
    {
        $request = new ServerRequest('GET', 'https://' . $host . '/go/github');
        if ($userAgent !== '') {
            $request = $request->withHeader('User-Agent', $userAgent);
        }
        if ($referer !== '') {
            $request = $request->withHeader('Referer', $referer);
        }
        return $request;
    }

    public function recordsAGenuineBrowserClickFromTheSameSite(): void
    {
        $request = $this->makeRequest(
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            referer: 'https://yii3i.online/',
        );

        Assert::true($this->shouldRecordClick($request));
    }

    public function recordsAClickWithNoRefererAtAll(): void
    {
        // A privacy-conscious browser/extension stripping Referer is a
        // real, legitimate case — not treated as suspicious on its own.
        $request = $this->makeRequest(userAgent: 'Mozilla/5.0');

        Assert::true($this->shouldRecordClick($request));
    }

    public function rejectsAMissingUserAgent(): void
    {
        $request = $this->makeRequest(userAgent: '');

        Assert::false($this->shouldRecordClick($request));
    }

    /**
     * @return list<array{string}>
     */
    public static function botUserAgentProvider(): array
    {
        return [
            ['python-requests/2.31.0'],
            ['curl/8.4.0'],
            ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            ['facebookexternalhit/1.1'],
            ['Slackbot-LinkExpanding 1.0'],
            ['Go-http-client/1.1'],
        ];
    }

    public function rejectsKnownBotUserAgents(): void
    {
        foreach (self::botUserAgentProvider() as [$userAgent]) {
            $request = $this->makeRequest(userAgent: $userAgent);
            Assert::false($this->shouldRecordClick($request), "Expected {$userAgent} to be rejected.");
        }
    }

    public function rejectsARefererFromADifferentSite(): void
    {
        $request = $this->makeRequest(
            userAgent: 'Mozilla/5.0',
            referer: 'https://some-random-scraper.example/',
            host: 'yii3i.online',
        );

        Assert::false($this->shouldRecordClick($request));
    }
}
