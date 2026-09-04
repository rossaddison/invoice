<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wired onto the whole `Group::create('/shop')` in routes-shop.php.
 *
 * The `no_front_webshop_page` setting (Settings > Front Page — see
 * partial_settings_front_page.php) only ever hid the storefront's navbar
 * link (LayoutViewInjection's `noFrontPageWebshop` / main.php's `NavLink`)
 * — every `/shop/*` route stayed reachable by URL regardless, unlike
 * `no_front_gateway_status_page`, whose own docblock in SiteController
 * documents actually 404ing the route when set (that page has no navbar
 * entry at all, so unlinking it doesn't unreach it). The storefront has the
 * exact same problem despite having a navbar link: it's also linked from
 * `inv/guest`'s "Returns & Orders" redirect (App\Invoice\Inv\Trait\Guest)
 * and can always just be typed into the address bar, so hiding only the
 * navbar link never actually achieved "no entry". This applies the same
 * true-404-the-route treatment to the entire `/shop` group instead of only
 * `shop/catalog/index`, since cart/checkout/etc. are all equally reachable
 * by direct URL and none of them are meant to work with the storefront
 * turned off.
 */
final class WebshopAvailabilityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly WebControllerService $webService,
    ) {
    }

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($this->settingRepository->getSetting('no_front_webshop_page') == '1') {
            return $this->webService->getNotFoundResponse();
        }

        return $handler->handle($request);
    }
}
