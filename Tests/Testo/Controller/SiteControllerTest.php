<?php

declare(strict_types=1);

namespace Tests\Testo\Controller;

use App\Controller\SiteController;
use App\Invoice\Peppol\PeppolStatusPageBuilder;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers the nine "lonely page" actions' renderUnlessDisabled() gating
 * (about, accreditations, gallery, team, pricing, privacypolicy,
 * termsofservice, testimonial, contact) -- each previously stayed fully
 * reachable by direct URL regardless of its `no_front_X_page` setting,
 * which only ever hid the navbar link (LayoutViewInjection/main.php).
 * Same gap already fixed for the storefront, see
 * Tests\Testo\Middleware\WebshopAvailabilityMiddlewareTest.
 *
 * @see SiteController
 */
#[Test]
final class SiteControllerTest
{
    private const string RENDERED_VIEW_HEADER = 'X-Rendered-View';

    private function webViewRenderer(): WebViewRenderer
    {
        /** @var WebViewRenderer&m\MockInterface $renderer */
        $renderer = m::mock(WebViewRenderer::class);
        $renderer->shouldReceive('withController')->andReturnSelf();
        $renderer->shouldReceive('render')->andReturnUsing(
            static fn (string $view): ResponseInterface => new Psr17Factory()
                ->createResponse(Status::OK)
                ->withHeader(self::RENDERED_VIEW_HEADER, $view),
        );
        return $renderer;
    }

    private function settingRepository(string $settingKey, string $value): sR
    {
        /** @var sR&m\MockInterface $s */
        $s = m::mock(sR::class);
        $s->shouldReceive('getSetting')->with($settingKey)->andReturn($value);
        return $s;
    }

    private function webService(): WebControllerService
    {
        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        return new WebControllerService($psr17, $psr17, $urlGenerator);
    }

    /** @param callable(SiteController, sR, WebControllerService): ResponseInterface $call */
    private function assertGatedWhenDisabled(string $settingKey, callable $call): void
    {
        $controller = new SiteController($this->webViewRenderer());
        $result = $call($controller, $this->settingRepository($settingKey, '1'), $this->webService());
        Assert::same($result->getStatusCode(), Status::NOT_FOUND);
    }

    /** @param callable(SiteController, sR, WebControllerService): ResponseInterface $call */
    private function assertRendersWhenEnabled(string $settingKey, string $expectedView, callable $call): void
    {
        $controller = new SiteController($this->webViewRenderer());
        $result = $call($controller, $this->settingRepository($settingKey, '0'), $this->webService());
        Assert::same($result->getStatusCode(), Status::OK);
        Assert::same($result->getHeaderLine(self::RENDERED_VIEW_HEADER), $expectedView);
    }

    public function aboutIsGatedByNoFrontAboutPage(): void
    {
        $this->assertGatedWhenDisabled('no_front_about_page', static fn ($c, $s, $w) => $c->about($s, $w));
        $this->assertRendersWhenEnabled('no_front_about_page', 'about', static fn ($c, $s, $w) => $c->about($s, $w));
    }

    public function accreditationsIsGatedByNoFrontAccreditationsPage(): void
    {
        $this->assertGatedWhenDisabled(
            'no_front_accreditations_page',
            static fn ($c, $s, $w) => $c->accreditations($s, $w),
        );
        $this->assertRendersWhenEnabled(
            'no_front_accreditations_page',
            'accreditations',
            static fn ($c, $s, $w) => $c->accreditations($s, $w),
        );
    }

    public function galleryIsGatedByNoFrontGalleryPage(): void
    {
        $this->assertGatedWhenDisabled('no_front_gallery_page', static fn ($c, $s, $w) => $c->gallery($s, $w));
        $this->assertRendersWhenEnabled(
            'no_front_gallery_page',
            'gallery',
            static fn ($c, $s, $w) => $c->gallery($s, $w),
        );
    }

    public function teamIsGatedByNoFrontTeamPage(): void
    {
        $this->assertGatedWhenDisabled('no_front_team_page', static fn ($c, $s, $w) => $c->team($s, $w));
        $this->assertRendersWhenEnabled('no_front_team_page', 'team', static fn ($c, $s, $w) => $c->team($s, $w));
    }

    public function pricingIsGatedByNoFrontPricingPage(): void
    {
        $this->assertGatedWhenDisabled('no_front_pricing_page', static fn ($c, $s, $w) => $c->pricing($s, $w));
        $this->assertRendersWhenEnabled(
            'no_front_pricing_page',
            'pricing',
            static fn ($c, $s, $w) => $c->pricing($s, $w),
        );
    }

    public function privacypolicyIsGatedByNoFrontPrivacyPolicyPage(): void
    {
        $this->assertGatedWhenDisabled(
            'no_front_privacy_policy_page',
            static fn ($c, $s, $w) => $c->privacypolicy($s, $w),
        );
        $this->assertRendersWhenEnabled(
            'no_front_privacy_policy_page',
            'privacypolicy',
            static fn ($c, $s, $w) => $c->privacypolicy($s, $w),
        );
    }

    public function termsofserviceIsGatedByNoFrontTermsOfServicePage(): void
    {
        $this->assertGatedWhenDisabled(
            'no_front_terms_of_service_page',
            static fn ($c, $s, $w) => $c->termsofservice($s, $w),
        );
        $this->assertRendersWhenEnabled(
            'no_front_terms_of_service_page',
            'termsofservice',
            static fn ($c, $s, $w) => $c->termsofservice($s, $w),
        );
    }

    public function testimonialIsGatedByNoFrontTestimonialPage(): void
    {
        $this->assertGatedWhenDisabled(
            'no_front_testimonial_page',
            static fn ($c, $s, $w) => $c->testimonial($s, $w),
        );
        $this->assertRendersWhenEnabled(
            'no_front_testimonial_page',
            'testimonial',
            static fn ($c, $s, $w) => $c->testimonial($s, $w),
        );
    }

    /**
     * Gated by no_front_contact_us_page -- see SiteController::contact()'s
     * own comment for the sibling dead setting that was removed alongside
     * this test (no_front_contact_details_page).
     */
    public function contactIsGatedByNoFrontContactUsPage(): void
    {
        $this->assertGatedWhenDisabled('no_front_contact_us_page', static fn ($c, $s, $w) => $c->contact($s, $w));
        $this->assertRendersWhenEnabled(
            'no_front_contact_us_page',
            'contact',
            static fn ($c, $s, $w) => $c->contact($s, $w),
        );
    }

    /**
     * peppolStatus() -- unlike the nine "lonely page" actions above -- 404s
     * outright rather than just hiding a nav link (see the method's own
     * docblock for why), and reads a second setting
     * (peppol_access_point_provider) to pass through to
     * PeppolStatusPageBuilder::build(), so it needs its own setup rather
     * than the shared settingRepository()/assert*() helpers above (those
     * assume exactly one getSetting() key).
     */
    public function peppolStatusIsGatedByNoFrontPeppolStatusPage(): void
    {
        /** @var sR&m\MockInterface $sR */
        $sR = m::mock(sR::class);
        $sR->shouldReceive('getSetting')->with('no_front_peppol_status_page')->andReturn('1');

        /** @var PeppolStatusPageBuilder&m\MockInterface $builder */
        $builder = m::mock(PeppolStatusPageBuilder::class);
        $builder->shouldNotReceive('build');

        $controller = new SiteController($this->webViewRenderer());
        $result = $controller->peppolStatus($builder, $sR, $this->webService());

        Assert::same($result->getStatusCode(), Status::NOT_FOUND);
    }

    public function peppolStatusRendersWithBuilderDataWhenEnabled(): void
    {
        /** @var sR&m\MockInterface $sR */
        $sR = m::mock(sR::class);
        $sR->shouldReceive('getSetting')->with('no_front_peppol_status_page')->andReturn('0');
        $sR->shouldReceive('getSetting')->with('peppol_access_point_provider')->andReturn('storecove');

        /** @var PeppolStatusPageBuilder&m\MockInterface $builder */
        $builder = m::mock(PeppolStatusPageBuilder::class);
        $builder->shouldReceive('build')->with('storecove')->andReturn([
            'rows' => [],
            'referenceProviders' => [],
            'as4Bilateral' => ['tested' => false, 'tested_at' => null, 'peer_party_id' => null],
        ]);

        $controller = new SiteController($this->webViewRenderer());
        $result = $controller->peppolStatus($builder, $sR, $this->webService());

        Assert::same($result->getStatusCode(), Status::OK);
        Assert::same($result->getHeaderLine(self::RENDERED_VIEW_HEADER), 'peppol-status');
    }

    /**
     * peppol_access_point_provider defaults to 'storecove' when unset --
     * mirrors PeppolSendServiceRouter's own default, see that class's
     * docblock for why (Storecove has actually been live, Oxalis hasn't).
     */
    public function peppolStatusDefaultsProviderToStorecoveWhenSettingIsEmpty(): void
    {
        /** @var sR&m\MockInterface $sR */
        $sR = m::mock(sR::class);
        $sR->shouldReceive('getSetting')->with('no_front_peppol_status_page')->andReturn('0');
        $sR->shouldReceive('getSetting')->with('peppol_access_point_provider')->andReturn('');

        /** @var PeppolStatusPageBuilder&m\MockInterface $builder */
        $builder = m::mock(PeppolStatusPageBuilder::class);
        $builder->shouldReceive('build')->with('storecove')->andReturn([
            'rows' => [],
            'referenceProviders' => [],
            'as4Bilateral' => ['tested' => false, 'tested_at' => null, 'peer_party_id' => null],
        ]);

        $controller = new SiteController($this->webViewRenderer());
        $result = $controller->peppolStatus($builder, $sR, $this->webService());

        Assert::same($result->getStatusCode(), Status::OK);
    }
}
