<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Delivery;

use App\Service\WebControllerService;
use App\Webshop\Delivery\DeliveryAddressService;
use App\Webshop\Delivery\DeliveryController;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;

/**
 * Covers DeliveryController::update() — in particular
 * safeSameOriginPath()'s open-redirect guard on the `redirect` field,
 * since that's the one part of this controller with any real logic in
 * it (the rest is a straight pass-through to DeliveryAddressService).
 */
#[Test]
final class DeliveryControllerTest
{
    private function fakeSession(): SessionInterface
    {
        /** @var array<string, mixed> $store */
        $store = [];
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('get')->andReturnUsing(
            static function (string $key, mixed $default = null) use (&$store): mixed {
                return $store[$key] ?? $default;
            },
        );
        $session->shouldReceive('set')->andReturnUsing(
            static function (string $key, array $value) use (&$store): void {
                $store[$key] = $value;
            },
        );
        $session->shouldReceive('remove')->andReturnUsing(
            static function (string $key) use (&$store): void {
                unset($store[$key]);
            },
        );
        return $session;
    }

    private function controller(): DeliveryController
    {
        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);

        return new DeliveryController(
            new WebControllerService($psr17, $psr17, $urlGenerator),
            new DeliveryAddressService($this->fakeSession()),
        );
    }

    /** @param array<string, string> $body */
    private function request(array $body): ServerRequestInterface
    {
        return new Psr17Factory()
            ->createServerRequest('POST', '/shop/delivery-address')
            ->withParsedBody($body);
    }

    public function redirectsBackToTheGivenSameOriginPath(): void
    {
        $response = $this->controller()->update($this->request([
            'name' => 'Mr',
            'surname' => 'Smith',
            'city' => 'Glasgow',
            'postcode' => 'G32 6AB',
            'redirect' => '/shop/products/5',
        ]));

        Assert::same(302, $response->getStatusCode());
        Assert::same('/shop/products/5', $response->getHeaderLine('Location'));
    }

    public function fallsBackToRootWhenRedirectIsMissing(): void
    {
        $response = $this->controller()->update($this->request([
            'name' => 'Mr',
            'surname' => 'Smith',
        ]));

        Assert::same('/', $response->getHeaderLine('Location'));
    }

    public function rejectsAProtocolRelativeRedirectAsAnOpenRedirect(): void
    {
        $response = $this->controller()->update($this->request([
            'name' => 'Mr',
            'surname' => 'Smith',
            'redirect' => '//attacker.example',
        ]));

        Assert::same('/', $response->getHeaderLine('Location'));
    }

    public function rejectsAnAbsoluteUrlRedirectAsAnOpenRedirect(): void
    {
        $response = $this->controller()->update($this->request([
            'name' => 'Mr',
            'surname' => 'Smith',
            'redirect' => 'https://attacker.example/phish',
        ]));

        Assert::same('/', $response->getHeaderLine('Location'));
    }
}
