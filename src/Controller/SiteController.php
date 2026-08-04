<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRepository;
use App\Invoice\PaymentInformation\GatewayStatus\Widget\GatewayStatusListWidget;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
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

    public function about(): Response
    {
        return $this->webViewRenderer->render('about');
    }

    public function accreditations(): Response
    {
        return $this->webViewRenderer->render('accreditations');
    }

    public function gallery(): Response
    {
        return $this->webViewRenderer->render('gallery');
    }

    public function team(): Response
    {
        return $this->webViewRenderer->render('team');
    }

    public function pricing(): Response
    {
        return $this->webViewRenderer->render('pricing');
    }

    public function privacypolicy(): Response
    {
        return $this->webViewRenderer->render('privacypolicy');
    }

    public function termsofservice(): Response
    {
        return $this->webViewRenderer->render('termsofservice');
    }

    public function testimonial(): Response
    {
        return $this->webViewRenderer->render('testimonial');
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

    public function contact(): Response
    {
        return $this->webViewRenderer->render('contact');
    }

    /**
     * Public payment-gateway coverage table — see
     * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md. Reads the gateway_status
     * SQLite database (a synced projection of resources/gateway-status/gateways.json,
     * rebuilt by `php yii gateway-status/rebuild`). Sortable columns, an
     * optional region filter, and pagination are all real — the same
     * GridView mechanics as the app's other list widgets (see
     * GatewayStatusListWidget) — not a bespoke static table.
     */
    public function gatewayStatus(
        Request $request,
        CurrentRoute $currentRoute,
        UrlGeneratorInterface $urlGenerator,
        GatewayStatusRepository $gatewayStatusRepository,
    ): Response {
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

        $widget = (new GatewayStatusListWidget($currentRoute, $urlGenerator))->withPaginator($paginator);

        return $this->webViewRenderer->render('gateway-status', [
            'gatewayStatusGrid' => $widget,
            'regionOptions' => $regionOptions,
            'selectedRegion' => $selectedRegion,
        ]);
    }
}
