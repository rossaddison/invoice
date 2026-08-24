<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Currency;

use App\Webshop\Currency\CurrencyContext;
use App\Webshop\Currency\CurrencyInfo;
use App\Webshop\Currency\CurrencyInfoProvider;
use App\Webshop\Currency\CurrencyPreferenceService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CurrencyContext — the display-only conversion every storefront
 * view renders a price through (see that class's own docblock for why
 * it's never a source of truth for what anything actually costs, and for
 * why it takes CurrencyInfoProvider/CurrencyPreferenceService themselves
 * rather than already-resolved values — resolving eagerly used to be
 * exactly what broke the standalone webshop app's own session
 * persistence before this was merged in-process).
 */
#[Test]
final class CurrencyContextTest
{
    private function dualCurrencyInfo(): CurrencyInfo
    {
        return new CurrencyInfo(
            native: 'GBP',
            document: 'EUR',
            nativeToDocumentRate: 1.17,
            documentToNativeRate: 0.85,
        );
    }

    private function context(?CurrencyInfo $info, string $preference): CurrencyContext
    {
        /** @var CurrencyInfoProvider&m\MockInterface $provider */
        $provider = m::mock(CurrencyInfoProvider::class);
        $provider->shouldReceive('get')->andReturn($info);

        /** @var CurrencyPreferenceService&m\MockInterface $preferenceService */
        $preferenceService = m::mock(CurrencyPreferenceService::class);
        $preferenceService->shouldReceive('get')->andReturn($preference);

        return new CurrencyContext($provider, $preferenceService);
    }

    public function withNoInfoFormatsAsABarePlainNumber(): void
    {
        $context = $this->context(null, CurrencyPreferenceService::NATIVE);

        Assert::same('19.99', $context->format(19.99));
        Assert::null($context->activeCode());
        Assert::false($context->isDualCurrency());
    }

    public function defaultingToNativeFormatsInTheNativeCurrency(): void
    {
        $context = $this->context($this->dualCurrencyInfo(), CurrencyPreferenceService::NATIVE);

        Assert::same('£19.99', $context->format(19.99));
        Assert::same('GBP', $context->activeCode());
        Assert::false($context->isShowingDocumentCurrency());
    }

    public function preferringDocumentConvertsAtTheNativeToDocumentRate(): void
    {
        $context = $this->context($this->dualCurrencyInfo(), CurrencyPreferenceService::DOCUMENT);

        Assert::same('€23.39', $context->format(19.99));
        Assert::same('EUR', $context->activeCode());
        Assert::true($context->isShowingDocumentCurrency());
    }

    public function preferringDocumentIsIgnoredWhenNativeAndDocumentAreTheSame(): void
    {
        $sameCurrency = new CurrencyInfo(native: 'GBP', document: 'GBP', nativeToDocumentRate: 1.0, documentToNativeRate: 1.0);
        $context = $this->context($sameCurrency, CurrencyPreferenceService::DOCUMENT);

        Assert::false($context->isDualCurrency());
        Assert::false($context->isShowingDocumentCurrency());
        Assert::same('£19.99', $context->format(19.99));
    }

    public function anUnmappedCurrencyCodeFallsBackToTheCodeItself(): void
    {
        $exotic = new CurrencyInfo(native: 'XYZ', document: 'XYZ', nativeToDocumentRate: 1.0, documentToNativeRate: 1.0);
        $context = $this->context($exotic, CurrencyPreferenceService::NATIVE);

        Assert::same('XYZ 19.99', $context->format(19.99));
    }

    public function infoIsOnlyFetchedOnceEvenAcrossMultipleCalls(): void
    {
        /** @var CurrencyInfoProvider&m\MockInterface $provider */
        $provider = m::mock(CurrencyInfoProvider::class);
        $provider->shouldReceive('get')->once()->andReturn($this->dualCurrencyInfo());

        /** @var CurrencyPreferenceService&m\MockInterface $preferenceService */
        $preferenceService = m::mock(CurrencyPreferenceService::class);
        $preferenceService->shouldReceive('get')->andReturn(CurrencyPreferenceService::NATIVE);

        $context = new CurrencyContext($provider, $preferenceService);

        $context->format(1.0);
        $context->activeCode();
        $context->isDualCurrency();
        $context->info();
    }
}
