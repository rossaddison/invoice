<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Currency;

use App\Service\WebControllerService;
use App\Webshop\Currency\CurrencyController;
use App\Webshop\Currency\CurrencyPreferenceService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;

/**
 * Covers CurrencyController::update() — same
 * WebControllerService::getRedirectToSameOriginPathResponse() open-
 * redirect guard as DeliveryControllerTest exercises, plus the
 * preference actually getting stored.
 */
#[Test]
final class CurrencyControllerTest
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
            static function (string $key, string $value) use (&$store): void {
                $store[$key] = $value;
            },
        );
        return $session;
    }

    private function controller(CurrencyPreferenceService $preferenceService): CurrencyController
    {
        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);

        return new CurrencyController(
            new WebControllerService($psr17, $psr17, $urlGenerator),
            $preferenceService,
        );
    }

    /** @param array<string, string> $body */
    private function request(array $body): ServerRequestInterface
    {
        return new Psr17Factory()
            ->createServerRequest('POST', '/shop/currency')
            ->withParsedBody($body);
    }

    public function storesThePreferenceAndRedirectsBackToTheGivenPath(): void
    {
        $preferenceService = new CurrencyPreferenceService($this->fakeSession());
        $response = $this->controller($preferenceService)->update($this->request([
            'preference' => CurrencyPreferenceService::DOCUMENT,
            'redirect' => '/shop/products/5',
        ]));

        Assert::same(302, $response->getStatusCode());
        Assert::same('/shop/products/5', $response->getHeaderLine('Location'));
        Assert::same(CurrencyPreferenceService::DOCUMENT, $preferenceService->get());
    }

    public function rejectsAProtocolRelativeRedirectAsAnOpenRedirect(): void
    {
        $preferenceService = new CurrencyPreferenceService($this->fakeSession());
        $response = $this->controller($preferenceService)->update($this->request([
            'preference' => CurrencyPreferenceService::NATIVE,
            'redirect' => '//attacker.example',
        ]));

        Assert::same('/', $response->getHeaderLine('Location'));
    }
}
