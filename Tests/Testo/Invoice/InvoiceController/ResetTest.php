<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvoiceController;

use App\Auth\Permissions;
use App\Invoice\InvoiceController;
use App\Invoice\Setting\QuoteSalesOrderInvResetService;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * InvoiceController::resetQuoteSalesOrderInvConfirm()/
 * resetQuoteSalesOrderInv()/resetQuoteSalesOrderInvFinish() -- the
 * debug-mode-only, password-gated UI for
 * QuoteSalesOrderInvResetService::dropAndClearSchema()/resetAutoIncrement()
 * (see that service's own test file and
 * project_sales_order_amount_so_id_column_incident memory).
 *
 * $_ENV['YII_DEBUG']/$_ENV['DB_PASSWORD'] are read directly by
 * debugResetAllowed()/resetQuoteSalesOrderInv() (matching the existing
 * debugLogs() action's own pattern), so every test here saves and restores
 * both keys rather than leaving global state mutated for later tests.
 */
#[Test]
final class ResetTest
{
    private const string ROUTE_INDEX = 'invoice/index';

    /** @var array<string, string> */
    private array $originalEnv = [];

    private function stashEnv(): void
    {
        $this->originalEnv = [
            'YII_DEBUG' => $_ENV['YII_DEBUG'] ?? '',
            'DB_PASSWORD' => $_ENV['DB_PASSWORD'] ?? '',
        ];
    }

    private function restoreEnv(): void
    {
        $_ENV['YII_DEBUG'] = $this->originalEnv['YII_DEBUG'];
        $_ENV['DB_PASSWORD'] = $this->originalEnv['DB_PASSWORD'];
    }

    /**
     * @return array{0: InvoiceController, 1: WebControllerService&m\MockInterface}
     */
    private function makeController(bool $editPermission): array
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn($editPermission);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key) => $key);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->with('invoice')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $webViewRenderer->shouldReceive('renderPartialAsString')
            ->with('//invoice/layout/alert', m::any())->andReturn('<alerts/>');

        $controller = new InvoiceController(
            $webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash,
        );

        return [$controller, $webService];
    }

    private function makeRequest(string $dbPassword): Request
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('getParsedBody')->andReturn(['db_password' => $dbPassword]);
        return $request;
    }

    /**
     * @return QuoteSalesOrderInvResetService&m\MockInterface
     */
    private function makeUnusedResetService(): QuoteSalesOrderInvResetService
    {
        /** @var QuoteSalesOrderInvResetService&m\MockInterface $resetService */
        $resetService = m::mock(QuoteSalesOrderInvResetService::class);
        $resetService->shouldNotReceive('dropAndClearSchema');
        $resetService->shouldNotReceive('resetAutoIncrement');
        return $resetService;
    }

    // -- resetQuoteSalesOrderInvConfirm() ------------------------------------

    public function confirmReturnsNotFoundWhenDebugModeIsOff(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'false';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $notFound */
            $notFound = m::mock(Response::class);
            $webService->shouldReceive('getNotFoundResponse')->once()->andReturn($notFound);

            Assert::same($controller->resetQuoteSalesOrderInvConfirm(), $notFound);
        } finally {
            $this->restoreEnv();
        }
    }

    public function confirmReturnsNotFoundWhenTheUserLacksEditPermission(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'true';
            [$controller, $webService] = $this->makeController(false);
            /** @var Response&m\MockInterface $notFound */
            $notFound = m::mock(Response::class);
            $webService->shouldReceive('getNotFoundResponse')->once()->andReturn($notFound);

            Assert::same($controller->resetQuoteSalesOrderInvConfirm(), $notFound);
        } finally {
            $this->restoreEnv();
        }
    }

    // -- resetQuoteSalesOrderInv() --------------------------------------------

    public function resetReturnsNotFoundWhenDebugModeIsOff(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'false';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $notFound */
            $notFound = m::mock(Response::class);
            $webService->shouldReceive('getNotFoundResponse')->once()->andReturn($notFound);

            $result = $controller->resetQuoteSalesOrderInv(
                $this->makeRequest('anything'),
                $this->makeUnusedResetService(),
            );

            Assert::same($result, $notFound);
        } finally {
            $this->restoreEnv();
        }
    }

    public function resetRedirectsBackToConfirmWithoutTouchingTheDatabaseWhenThePasswordIsWrong(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'true';
            $_ENV['DB_PASSWORD'] = 'correct-horse-battery-staple';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $redirect */
            $redirect = m::mock(Response::class);
            $webService->shouldReceive('getRedirectResponse')->once()
                ->with('invoice/resetQuoteSalesOrderInvConfirm')->andReturn($redirect);

            $result = $controller->resetQuoteSalesOrderInv(
                $this->makeRequest('wrong-password'),
                $this->makeUnusedResetService(),
            );

            Assert::same($result, $redirect);
        } finally {
            $this->restoreEnv();
        }
    }

    public function resetRedirectsToConfirmWhenNoDbPasswordIsConfiguredAtAll(): void
    {
        // An empty $_ENV['DB_PASSWORD'] must never compare equal to an
        // empty submitted field -- the '' === '' guard exists specifically
        // to close that hole.
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'true';
            $_ENV['DB_PASSWORD'] = '';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $redirect */
            $redirect = m::mock(Response::class);
            $webService->shouldReceive('getRedirectResponse')->once()
                ->with('invoice/resetQuoteSalesOrderInvConfirm')->andReturn($redirect);

            $result = $controller->resetQuoteSalesOrderInv(
                $this->makeRequest(''),
                $this->makeUnusedResetService(),
            );

            Assert::same($result, $redirect);
        } finally {
            $this->restoreEnv();
        }
    }

    public function resetDropsTheTreeAndRedirectsToFinishWhenThePasswordMatches(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'true';
            $_ENV['DB_PASSWORD'] = 'correct-horse-battery-staple';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $redirect */
            $redirect = m::mock(Response::class);
            $webService->shouldReceive('getRedirectResponse')->once()
                ->with('invoice/resetQuoteSalesOrderInvFinish')->andReturn($redirect);

            /** @var QuoteSalesOrderInvResetService&m\MockInterface $resetService */
            $resetService = m::mock(QuoteSalesOrderInvResetService::class);
            $resetService->shouldReceive('dropAndClearSchema')->once()->andReturn(['quote']);

            $result = $controller->resetQuoteSalesOrderInv(
                $this->makeRequest('correct-horse-battery-staple'),
                $resetService,
            );

            Assert::same($result, $redirect);
        } finally {
            $this->restoreEnv();
        }
    }

    public function resetRedirectsToIndexWhenTheServiceThrows(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'true';
            $_ENV['DB_PASSWORD'] = 'correct-horse-battery-staple';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $redirect */
            $redirect = m::mock(Response::class);
            $webService->shouldReceive('getRedirectResponse')->once()
                ->with(self::ROUTE_INDEX)->andReturn($redirect);

            /** @var QuoteSalesOrderInvResetService&m\MockInterface $resetService */
            $resetService = m::mock(QuoteSalesOrderInvResetService::class);
            $resetService->shouldReceive('dropAndClearSchema')->once()
                ->andThrow(new \RuntimeException('mysqldump failed'));

            $result = $controller->resetQuoteSalesOrderInv(
                $this->makeRequest('correct-horse-battery-staple'),
                $resetService,
            );

            Assert::same($result, $redirect);
        } finally {
            $this->restoreEnv();
        }
    }

    // -- resetQuoteSalesOrderInvFinish() --------------------------------------

    public function finishReturnsNotFoundWhenDebugModeIsOff(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'false';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $notFound */
            $notFound = m::mock(Response::class);
            $webService->shouldReceive('getNotFoundResponse')->once()->andReturn($notFound);

            $result = $controller->resetQuoteSalesOrderInvFinish($this->makeUnusedResetService());

            Assert::same($result, $notFound);
        } finally {
            $this->restoreEnv();
        }
    }

    public function finishResetsAutoIncrementAndRedirectsToIndexOnSuccess(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'true';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $redirect */
            $redirect = m::mock(Response::class);
            $webService->shouldReceive('getRedirectResponse')->once()
                ->with(self::ROUTE_INDEX)->andReturn($redirect);

            /** @var QuoteSalesOrderInvResetService&m\MockInterface $resetService */
            $resetService = m::mock(QuoteSalesOrderInvResetService::class);
            $resetService->shouldReceive('resetAutoIncrement')->once()
                ->with(QuoteSalesOrderInvResetService::tables());

            Assert::same($controller->resetQuoteSalesOrderInvFinish($resetService), $redirect);
        } finally {
            $this->restoreEnv();
        }
    }

    public function finishStillRedirectsToIndexWhenTheAutoIncrementResetFails(): void
    {
        $this->stashEnv();
        try {
            $_ENV['YII_DEBUG'] = 'true';
            [$controller, $webService] = $this->makeController(true);
            /** @var Response&m\MockInterface $redirect */
            $redirect = m::mock(Response::class);
            $webService->shouldReceive('getRedirectResponse')->once()
                ->with(self::ROUTE_INDEX)->andReturn($redirect);

            /** @var QuoteSalesOrderInvResetService&m\MockInterface $resetService */
            $resetService = m::mock(QuoteSalesOrderInvResetService::class);
            $resetService->shouldReceive('resetAutoIncrement')->once()
                ->andThrow(new \RuntimeException('table missing'));

            Assert::same($controller->resetQuoteSalesOrderInvFinish($resetService), $redirect);
        } finally {
            $this->restoreEnv();
        }
    }
}
