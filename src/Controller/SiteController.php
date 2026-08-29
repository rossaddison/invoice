<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRepository;
use App\Invoice\PaymentInformation\GatewayStatus\Widget\GatewayStatusListWidget;
use App\Invoice\Peppol\PeppolMessageRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
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

    /**
     * Public Peppol Access Point status page — same idea as
     * gatewayStatus() above (which of this app's payment gateways have
     * passed a real sandbox check), scoped down for the two Access Point
     * providers PeppolSendServiceRouter can resolve to. Deliberately not
     * built on gateway-status's full machinery (a second Cycle-managed
     * SQLite database, a JSON source of truth, two console commands, a
     * weekly CI workflow) — that pipeline earns its keep tracking 8+
     * gateways' SDK versions and sandbox pings automatically; for exactly
     * two rows, both derivable live from data this app already has, it
     * would be pure overhead. Storecove's "sandbox tested" status comes
     * from real send history (a genuine SENT PeppolMessage), not a
     * synthetic ping — neither provider exposes a side-effect-free
     * health-check call the way a payment gateway's balance/methods-list
     * endpoint does.
     *
     * Gated by no_front_peppol_status_page the same way
     * no_front_gateway_status_page gates gatewayStatus() above: this
     * 404s the route itself rather than only hiding a nav link, since
     * (like gateway-status) it can expose which provider is actually
     * configured.
     */
    public function peppolStatus(
        PeppolMessageRepository $peppolMessageRepository,
        Aliases $aliases,
        sR $sR,
        WebControllerService $webService,
    ): Response {
        if ($sR->getSetting('no_front_peppol_status_page') == '1') {
            return $webService->getNotFoundResponse();
        }

        $currentProvider = $sR->getSetting('peppol_access_point_provider') ?: 'storecove';
        $lastSent = $peppolMessageRepository->mostRecentByStatus('SENT');

        // PeppolMessage has no column recording which provider actually
        // sent it -- StorecovePeppolSendService and OxalisPeppolSendService
        // both write to the same table. Attributing an existing SENT row
        // to "whichever provider is currently configured" is the best
        // available signal without a schema change, and holds for the
        // common case (providers aren't switched often) -- noted here
        // rather than silently assumed.
        $storecoveTested = $currentProvider === 'storecove' && $lastSent !== null;
        $sentAt = $lastSent?->getSentAt();

        $rows = [
            [
                'name' => 'Storecove',
                'sdk_version' => $this->storecoveClientVersion($aliases),
                'sandbox_status' => $storecoveTested ? 'pass' : 'untested',
                'sandbox_tested_at' => $storecoveTested && $sentAt !== null
                    ? $sentAt->format('Y-m-d') : null,
                'notes' => 'Managed Access Point API — the default provider.',
            ],
            [
                'name' => 'Oxalis',
                'sdk_version' => null,
                'sandbox_status' => 'untested',
                'sandbox_tested_at' => null,
                'notes' => 'Self-hosted AS4 gateway — not yet used for a'
                    . ' real send in this deployment.',
            ],
        ];

        return $this->webViewRenderer->render('peppol-status', [
            'rows' => $rows,
        ]);
    }

    /**
     * rossaddison/storecove-client is a VCS (git) dependency, not a tagged
     * release -- composer.lock's 'version' field for it is literally the
     * branch name ('dev-master'), the same simple field gateway-status
     * reads for every gateway package. Shown as-is rather than resolved
     * to a commit hash, for consistency with how every row on this page
     * (and gateway-status's own rows) sources its version the same way.
     */
    private function storecoveClientVersion(Aliases $aliases): ?string
    {
        $path = $aliases->get('@root') . '/composer.lock';
        if (!is_file($path)) {
            return null;
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }
        /** @var array{packages?: list<array{name?: string, version?: string}>}|null $decoded */
        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return null;
        }
        foreach ($decoded['packages'] ?? [] as $package) {
            if (($package['name'] ?? null) === 'rossaddison/storecove-client') {
                return $package['version'] ?? null;
            }
        }
        return null;
    }
}
