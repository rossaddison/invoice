<?php

declare(strict_types=1);

namespace Tests\PHPUnit;

use App\Invoice\Asset\pciAsset\AmazonPayTwoSevenAsset;
use App\Invoice\Asset\pciAsset\BraintreeDropInOneThirtyThreeSevenAsset;
use App\Invoice\Asset\pciAsset\StripeVersionTenAsset;
use PHPUnit\Framework\TestCase;
use Yiisoft\View\WebView;

class PciAssetTest extends TestCase
{
    // ── jsPosition = POSITION_HEAD ───────────────────────────────────────────

    public function testStripeAssetLoadsInHead(): void
    {
        $asset = new StripeVersionTenAsset();
        $this->assertSame(WebView::POSITION_HEAD, $asset->jsPosition);
    }

    public function testBraintreeAssetLoadsInHead(): void
    {
        $asset = new BraintreeDropInOneThirtyThreeSevenAsset();
        $this->assertSame(WebView::POSITION_HEAD, $asset->jsPosition);
    }

    public function testAmazonPayAssetLoadsInHead(): void
    {
        $asset = new AmazonPayTwoSevenAsset();
        $this->assertSame(WebView::POSITION_HEAD, $asset->jsPosition);
    }

    // ── All CDN URLs use explicit https:// ───────────────────────────────────

    public function testStripeJsUrlUsesHttps(): void
    {
        $asset = new StripeVersionTenAsset();
        /** @var string|array<string|int, string> $entry */
        foreach ($asset->js as $entry) {
            $url = is_array($entry) ? $entry[0] : $entry;
            $this->assertStringStartsWith('https://', $url, "Stripe JS URL must use https://: $url");
        }
    }

    public function testBraintreeJsUrlUsesHttps(): void
    {
        $asset = new BraintreeDropInOneThirtyThreeSevenAsset();
        /** @var string|array<string|int, string> $entry */
        foreach ($asset->js as $entry) {
            $url = is_array($entry) ? $entry[0] : $entry;
            $this->assertStringStartsWith('https://', $url, "Braintree JS URL must use https://: $url");
        }
    }

    public function testBraintreeCssUrlUsesHttps(): void
    {
        $asset = new BraintreeDropInOneThirtyThreeSevenAsset();
        /** @var string|array<string|int, string> $entry */
        foreach ($asset->css as $entry) {
            $url = is_array($entry) ? $entry[0] : $entry;
            $this->assertStringStartsWith('https://', $url, "Braintree CSS URL must use https://: $url");
        }
    }

    public function testAmazonPayJsUrlUsesHttps(): void
    {
        $asset = new AmazonPayTwoSevenAsset();
        /** @var string|array<string|int, string> $entry */
        foreach ($asset->js as $entry) {
            $url = is_array($entry) ? $entry[0] : $entry;
            $this->assertStringStartsWith('https://', $url, "Amazon Pay JS URL must use https://: $url");
        }
    }

    // ── No protocol-relative URLs ────────────────────────────────────────────

    public function testNoProtocolRelativeUrlsInStripeAsset(): void
    {
        $asset = new StripeVersionTenAsset();
        /** @var string|array<string|int, string> $entry */
        foreach ($asset->js as $entry) {
            $url = is_array($entry) ? $entry[0] : $entry;
            $this->assertStringStartsNotWith('//', $url, "Protocol-relative URL found in Stripe asset: $url");
        }
    }

    public function testNoProtocolRelativeUrlsInBraintreeAsset(): void
    {
        $asset = new BraintreeDropInOneThirtyThreeSevenAsset();
        /** @var string|array<string|int, string> $entry */
        foreach (array_merge($asset->js, $asset->css) as $entry) {
            $url = is_array($entry) ? $entry[0] : $entry;
            $this->assertStringStartsNotWith('//', $url, "Protocol-relative URL found in Braintree asset: $url");
        }
    }
}
