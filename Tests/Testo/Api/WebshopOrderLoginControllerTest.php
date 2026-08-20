<?php

declare(strict_types=1);

namespace Tests\Testo\Api;

use App\Api\WebshopOrderLoginController;
use App\Auth\AuthService;
use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\Token\Token;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\AppConstants;
use App\Service\WebControllerService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Covers WebshopOrderLoginController::login() — redemption of the
 * one-time login link OrderService issues after a webshop checkout (see
 * docs/STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md). Mirrors
 * UserInvController::signup()'s own unmask-plus-expiry shape, so the
 * cases here (valid, expired, unknown, wrong-type) match the ones that
 * matter for that established pattern.
 *
 * `Token`/`Identity`/`User` are `final`, mockable only because
 * Tests/testo.php enables DG\BypassFinals — same established pattern as
 * InvPaymentSettlementServiceTest/OrderServiceTest.
 */
#[Test]
final class WebshopOrderLoginControllerTest
{
    private function maskedToken(string $rawToken, int $timestamp): string
    {
        return TokenMask::apply($rawToken . '_' . $timestamp);
    }

    /**
     * A Token row for the given raw token/type, with a User+Identity
     * behind it. Doesn't stub setToken() itself — invalidation only
     * happens for a token this controller actually accepts, so each test
     * asserts that expectation (or its absence) explicitly.
     */
    private function tokenEntity(string $type, string $login): Token&m\MockInterface
    {
        /** @var Identity&m\MockInterface $identity */
        $identity = m::mock(Identity::class);
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldReceive('getLogin')->andReturn($login);
        $identity->shouldReceive('getUser')->andReturn($user);

        /** @var Token&m\MockInterface $token */
        $token = m::mock(Token::class);
        $token->shouldReceive('getType')->andReturn($type);
        $token->shouldReceive('getIdentity')->andReturn($identity);

        return $token;
    }

    public function logsInAndRedirectsToTheInvoiceOnAValidToken(): void
    {
        $rawToken = str_repeat('a', 32);
        $masked = $this->maskedToken($rawToken, time());
        $tokenEntity = $this->tokenEntity(AppConstants::TOKEN_TYPE_WEBSHOP_ORDER_LOGIN . ':900', 'ada@example.test');
        $tokenEntity->shouldReceive('setToken')->once()->with(m::on(
            static fn (string $v): bool => str_starts_with($v, 'already_used_token_'),
        ));

        /** @var TokenRepository&m\MockInterface $tokenRepository */
        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findTokenByToken')->once()->with($rawToken)->andReturn($tokenEntity);
        $tokenRepository->shouldReceive('save')->once()->with($tokenEntity);

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $authService->shouldReceive('oauthLogin')->once()->with('ada@example.test')->andReturn(true);

        /** @var ResponseInterface&m\MockInterface $expectedResponse */
        $expectedResponse = m::mock(ResponseInterface::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')
            ->once()->with('inv/view', ['id' => '900'])->andReturn($expectedResponse);

        $controller = $this->makeController($webService, $authService, $tokenRepository);

        $response = $controller->login($masked);

        Assert::same($expectedResponse, $response);
    }

    public function failsAndRedirectsToSiteIndexOnAnExpiredToken(): void
    {
        $rawToken = str_repeat('b', 32);
        $masked = $this->maskedToken($rawToken, time() - 3601);

        /** @var TokenRepository&m\MockInterface $tokenRepository */
        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldNotReceive('findTokenByToken');

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $authService->shouldNotReceive('oauthLogin');

        $this->assertFails($tokenRepository, $authService, $masked);
    }

    public function failsAndRedirectsToSiteIndexWhenTheTokenIsUnknown(): void
    {
        $rawToken = str_repeat('c', 32);
        $masked = $this->maskedToken($rawToken, time());

        /** @var TokenRepository&m\MockInterface $tokenRepository */
        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findTokenByToken')->once()->with($rawToken)->andReturn(null);

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $authService->shouldNotReceive('oauthLogin');

        $this->assertFails($tokenRepository, $authService, $masked);
    }

    public function failsAndRedirectsToSiteIndexWhenTheTokenTypeIsNotAWebshopOrderLogin(): void
    {
        $rawToken = str_repeat('d', 32);
        $masked = $this->maskedToken($rawToken, time());
        // Same masked-token shape, but a Token row belonging to a
        // different feature (e.g. the email-verification flow) —
        // must never be redeemable here.
        $tokenEntity = $this->tokenEntity('email-verification', 'someone@example.test');
        // The wrong-type path never actually invalidates the token —
        // it isn't this controller's token to consume.
        $tokenEntity->shouldReceive('setToken')->never();

        /** @var TokenRepository&m\MockInterface $tokenRepository */
        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findTokenByToken')->once()->with($rawToken)->andReturn($tokenEntity);

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $authService->shouldNotReceive('oauthLogin');

        $this->assertFails($tokenRepository, $authService, $masked);
    }

    public function failsAndRedirectsToSiteIndexWhenLoginItselfFails(): void
    {
        $rawToken = str_repeat('e', 32);
        $masked = $this->maskedToken($rawToken, time());
        $tokenEntity = $this->tokenEntity(AppConstants::TOKEN_TYPE_WEBSHOP_ORDER_LOGIN . ':901', 'gone@example.test');
        $tokenEntity->shouldReceive('setToken')->once()->with(m::on(
            static fn (string $v): bool => str_starts_with($v, 'already_used_token_'),
        ));

        /** @var TokenRepository&m\MockInterface $tokenRepository */
        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findTokenByToken')->once()->with($rawToken)->andReturn($tokenEntity);
        $tokenRepository->shouldReceive('save')->once()->with($tokenEntity);

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $authService->shouldReceive('oauthLogin')->once()->with('gone@example.test')->andReturn(false);

        $this->assertFails($tokenRepository, $authService, $masked);
    }

    private function assertFails(TokenRepository $tokenRepository, AuthService $authService, string $masked): void
    {
        /** @var ResponseInterface&m\MockInterface $expectedResponse */
        $expectedResponse = m::mock(ResponseInterface::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->once()->with('site/index')->andReturn($expectedResponse);

        $controller = $this->makeController($webService, $authService, $tokenRepository);

        $response = $controller->login($masked);

        Assert::same($expectedResponse, $response);
    }

    private function makeController(
        WebControllerService $webService,
        AuthService $authService,
        TokenRepository $tokenRepository,
    ): WebshopOrderLoginController {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('Not found');

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        return new WebshopOrderLoginController($webService, $translator, $flash, $authService, $tokenRepository);
    }
}
