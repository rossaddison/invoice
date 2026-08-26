<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRepository;
use App\Invoice\PaymentInformation\GatewayStatus\Widget\GatewayStatusListWidget;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SiteController
{
    public function __construct(private WebViewRenderer $webViewRenderer)
    {
        $this->webViewRenderer = $webViewRenderer->withController($this);
    }

    public function index(): Response
    {
        return $this->webViewRenderer->render('index');
    }

    /**
     * Nine near-identical "lonely page" actions below (about, accreditations,
     * gallery, team, pricing, privacypolicy, termsofservice, testimonial,
     * contact) all gated the same way as gatewayStatus() -- see that
     * method's own docblock for why. Previously only LayoutViewInjection/
     * main.php's `no_front_X_page` reads hid these pages' navbar links
     * while the routes themselves stayed reachable by direct URL (found
     * live 2026-08-26, starting from the same gap already fixed for
     * `no_front_webshop_page` -- see WebshopAvailabilityMiddleware). Kept
     * as one shared private helper rather than 9 copies of the same
     * if/return, per this project's own duplication-reduction convention.
     */
    private function renderUnlessDisabled(
        string $view,
        string $settingKey,
        sR $sR,
        WebControllerService $webService,
    ): Response {
        if ($sR->getSetting($settingKey) == '1') {
            return $webService->getNotFoundResponse();
        }
        return $this->webViewRenderer->render($view);
    }

    public function about(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('about', 'no_front_about_page', $sR, $webService);
    }

    public function accreditations(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('accreditations', 'no_front_accreditations_page', $sR, $webService);
    }

    public function gallery(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('gallery', 'no_front_gallery_page', $sR, $webService);
    }

    public function team(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('team', 'no_front_team_page', $sR, $webService);
    }

    public function pricing(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('pricing', 'no_front_pricing_page', $sR, $webService);
    }

    public function privacypolicy(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('privacypolicy', 'no_front_privacy_policy_page', $sR, $webService);
    }

    public function termsofservice(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('termsofservice', 'no_front_terms_of_service_page', $sR, $webService);
    }

    public function testimonial(sR $sR, WebControllerService $webService): Response
    {
        return $this->renderUnlessDisabled('testimonial', 'no_front_testimonial_page', $sR, $webService);
    }

    public function oauth2autherror(#[RouteArgument('message')] string $message): Response
    {
        return $this->webViewRenderer->render('oauth2autherror', ['message' => $message]);
    }

    public function oauth2callbackresultunauthorised(): Response
    {
        return $this->webViewRenderer->render('oauth2callbackresultunauthorised');
    }

    public function usercancelledoauth2(): Response
    {
        return $this->webViewRenderer->render('usercancelledoauth2');
    }

    public function adminmustmakeactive(): Response
    {
        return $this->webViewRenderer->render('adminmustmakeactive');
    }

    public function emailnotverified(): Response
    {
        return $this->webViewRenderer->render('emailnotverified');
    }

    public function contact(sR $sR, WebControllerService $webService): Response
    {
        // Gated by no_front_contact_us_page -- main.php's menu.contact.us
        // NavLink is what actually points at this route. A second setting,
        // no_front_contact_details_page, used to exist alongside it with a
        // checkbox in Settings > Front Page but no route or view of its own
        // anywhere in this app (found 2026-08-26, removed the same day):
        // this contact() page has always been address/phone/email details,
        // not a form, so the dead checkbox was pure noise, not a second
        // page silently left unlinked. A distinct "Trade Quote"/contact
        // form (App\Contact\ContactController::interest(), route
        // /interest) is gated separately by no_front_contact_interest_page
        // -- see that controller's own comment.
        return $this->renderUnlessDisabled('contact', 'no_front_contact_us_page', $sR, $webService);
    }

    /**
     * Public payment-gateway coverage table — see
     * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md. Reads the gateway_status
     * SQLite database (a synced projection of resources/gateway-status/gateways.json,
     * rebuilt by `php yii gateway-status/rebuild`). Sortable columns, an
     * optional region filter, and pagination are all real — the same
     * GridView mechanics as the app's other list widgets (see
     * GatewayStatusListWidget) — not a bespoke static table.
     *
     * Gated by the `no_front_gateway_status_page` setting (Settings >
     * Front Page), the same "no_front_X_page" naming convention every
     * other front-page toggle uses — but unlike those, which only hide a
     * navbar link while the page itself stays reachable
     * (LayoutViewInjection/main.php), this one actually 404s the route
     * when set, since the homepage link is the only way to reach this
     * page (no navbar entry exists for it) and the setting is meant to
     * let the page be turned off outright, not just unlinked.
     */
    public function gatewayStatus(
        Request $request,
        CurrentRoute $currentRoute,
        UrlGeneratorInterface $urlGenerator,
        GatewayStatusRepository $gatewayStatusRepository,
        TranslatorInterface $translator,
        sR $sR,
        WebControllerService $webService,
    ): Response {
        if ($sR->getSetting('no_front_gateway_status_page') == '1') {
            return $webService->getNotFoundResponse();
        }

        $gateways = $gatewayStatusRepository->findAllquery();

        $allRegions = [];
        foreach ($gateways as $gateway) {
            foreach ($gateway->getRegionsList() as $region) {
                $allRegions[$region] = true;
            }
        }
        $regionOptions = array_keys($allRegions);
        sort($regionOptions);

        /** @var string $selectedRegion */
        $selectedRegion = $request->getQueryParams()['region'] ?? '';
        if ($selectedRegion !== '') {
            $gateways = array_values(array_filter(
                $gateways,
                static fn (GatewayStatus $gateway): bool => in_array($selectedRegion, $gateway->getRegionsList(), true),
            ));
        }

        $rows = array_map(
            static fn (GatewayStatus $gateway): array => [
                'name' => $gateway->getName(),
                'regions' => implode(', ', array_map('ucwords', $gateway->getRegionsList())),
                'sdk_version' => $gateway->getSdkVersion(),
                'last_updated' => $gateway->getLastUpdated(),
                'sandbox_status' => $gateway->getSandboxStatus(),
                'sandbox_tested_at' => $gateway->getSandboxTestedAt(),
                'live_tested_at' => $gateway->getLiveTestedAt(),
                'region_priority' => in_array('asia', $gateway->getRegionsList(), true) ? 0 : 1,
            ],
            $gateways,
        );

        $sort = Sort::only(['region_priority', 'name', 'sdk_version', 'last_updated', 'sandbox_status', 'live_tested_at'])
            ->withOrder(['region_priority' => 'asc', 'name' => 'asc']);

        /**
         * @var OffsetPaginator<array-key, array{
         *     name: string,
         *     regions: string,
         *     sdk_version: string|null,
         *     last_updated: string,
         *     sandbox_status: string|null,
         *     sandbox_tested_at: string|null,
         *     live_tested_at: string|null,
         *     region_priority: int,
         * }> $paginator
         */
        $paginator = (new OffsetPaginator(new IterableDataReader($rows)))->withSort($sort);

        $widget = (new GatewayStatusListWidget($currentRoute, $urlGenerator, $translator))->withPaginator($paginator);

        return $this->webViewRenderer->render('gateway-status', [
            'gatewayStatusGrid' => $widget,
            'regionOptions' => $regionOptions,
            'selectedRegion' => $selectedRegion,
        ]);
    }
}
