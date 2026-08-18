<?php

declare(strict_types=1);

namespace Tests\Testo\Redirect;

use App\Redirect\GeoIpLookupService;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers GeoIpLookupService — every path must fail closed to null, never
 * throw, since a broken geo-IP lookup must never be allowed to break the
 * redirect it's a side effect of (RedirectController::go()).
 */
#[Test]
final class GeoIpLookupServiceTest
{
    private function makeService(MockHandler $mock): GeoIpLookupService
    {
        $httpClient = new HttpClient(['handler' => HandlerStack::create($mock)]);
        /** @var LoggerInterface $logger */
        $logger = \Mockery::spy(LoggerInterface::class);

        return new GeoIpLookupService($logger, $httpClient);
    }

    public function returnsLowercaseCountryCodeOnSuccess(): void
    {
        $service = $this->makeService(new MockHandler([new Response(200, [], 'GB')]));

        Assert::same('gb', $service->lookupCountryCode('81.2.69.142'));
    }

    public function lowercasesAndTrimsTheResponseBody(): void
    {
        $service = $this->makeService(new MockHandler([new Response(200, [], "  US\n")]));

        Assert::same('us', $service->lookupCountryCode('8.8.8.8'));
    }

    public function returnsNullOnHttpFailure(): void
    {
        $service = $this->makeService(new MockHandler([new Response(500)]));

        Assert::null($service->lookupCountryCode('8.8.8.8'));
    }

    public function returnsNullOnUnexpectedResponseShape(): void
    {
        $service = $this->makeService(new MockHandler([new Response(200, [], 'Rate limit exceeded')]));

        Assert::null($service->lookupCountryCode('8.8.8.8'));
    }

    public function returnsNullForPrivateIpWithoutMakingAnHttpCall(): void
    {
        $mock = new MockHandler([]);
        $service = $this->makeService($mock);

        Assert::null($service->lookupCountryCode('127.0.0.1'));
        Assert::null($service->lookupCountryCode('10.0.0.5'));
        Assert::null($service->lookupCountryCode('192.168.1.1'));
        // If either call above had actually hit the HTTP client, the
        // empty MockHandler queue would throw "no more items" — reaching
        // this line at all is the assertion.
    }
}
