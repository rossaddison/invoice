<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Setting\SettingToggleController;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers gridStickyHeader() and navbarSticky() — both new this session,
 * each a direct mirror of the pre-existing (and, before this file, also
 * untested) visible() toggle action: flip an existing '0'/'1' Setting,
 * or create one fresh at '1' when it doesn't exist yet, then redirect to
 * "{origin}/index". Same minimal-harness technique as SetWorkerTest:
 * SettingToggleController is directly constructable (no trait mixing
 * needed here, unlike Index::setWorker()), so no test-only subclass is
 * required.
 */
#[Test]
final class SettingToggleControllerTest
{
    /** @return array{0: SettingToggleController, 1: Response&m\MockInterface} */
    private function makeController(SettingRepository&m\MockInterface $sR, string $origin = 'inv'): array
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->once()
            ->with($origin . '/index')->andReturn($redirect);

        // BaseController::initializeViewRenderer() reads all of these
        // unconditionally on construction — same stubs SetWorkerTest's
        // own makeHarness() uses, picking the same EDIT_INV +
        // tfa_verified branch since these tests don't care which layout
        // gets picked, only that the toggle actions themselves behave.
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);

        $controller = new SettingToggleController(
            $session,
            $sR,
            $translator,
            $userService,
            $webViewRenderer,
            $webService,
            $flash,
        );

        return [$controller, $redirect];
    }

    // -------------------------------------------------------------------
    // gridStickyHeader()
    // -------------------------------------------------------------------

    public function gridStickyHeaderTogglesAnExistingSettingFromOffToOn(): void
    {
        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('0');
        $setting->shouldReceive('setSettingValue')->once()->with('1');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        [$controller, $redirect] = $this->makeController($sR);
        $result = $controller->gridStickyHeader('inv');

        Assert::same($redirect, $result);
    }

    public function gridStickyHeaderTogglesAnExistingSettingFromOnToOff(): void
    {
        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('1');
        $setting->shouldReceive('setSettingValue')->once()->with('0');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        [$controller, $redirect] = $this->makeController($sR);
        $result = $controller->gridStickyHeader('inv');

        Assert::same($redirect, $result);
    }

    public function gridStickyHeaderCreatesANewSettingAtOnWhenNoneExistsYet(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn(null);
        $sR->shouldReceive('save')->once()->with(m::on(
            static fn (mixed $setting): bool => $setting instanceof Setting
                && $setting->getSettingKey() === 'grid_sticky_header'
                && $setting->getSettingValue() === '1'
        ));

        [$controller, $redirect] = $this->makeController($sR);
        $result = $controller->gridStickyHeader('inv');

        Assert::same($redirect, $result);
    }

    public function gridStickyHeaderRedirectsToTheGivenOriginsIndex(): void
    {
        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('1');
        $setting->shouldReceive('setSettingValue')->once()->with('0');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        [$controller, $redirect] = $this->makeController($sR, 'quote');
        $result = $controller->gridStickyHeader('quote');

        Assert::same($redirect, $result);
    }

    // -------------------------------------------------------------------
    // navbarSticky()
    // -------------------------------------------------------------------

    public function navbarStickyTogglesAnExistingSettingFromOffToOn(): void
    {
        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('0');
        $setting->shouldReceive('setSettingValue')->once()->with('1');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        [$controller, $redirect] = $this->makeController($sR);
        $result = $controller->navbarSticky('inv');

        Assert::same($redirect, $result);
    }

    public function navbarStickyTogglesAnExistingSettingFromOnToOff(): void
    {
        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('1');
        $setting->shouldReceive('setSettingValue')->once()->with('0');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        [$controller, $redirect] = $this->makeController($sR);
        $result = $controller->navbarSticky('inv');

        Assert::same($redirect, $result);
    }

    public function navbarStickyCreatesANewSettingAtOnWhenNoneExistsYet(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn(null);
        $sR->shouldReceive('save')->once()->with(m::on(
            static fn (mixed $setting): bool => $setting instanceof Setting
                && $setting->getSettingKey() === 'bootstrap5_layout_invoice_navbar_sticky'
                && $setting->getSettingValue() === '1'
        ));

        [$controller, $redirect] = $this->makeController($sR);
        $result = $controller->navbarSticky('inv');

        Assert::same($redirect, $result);
    }

    public function navbarStickyRedirectsToTheGivenOriginsIndex(): void
    {
        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('0');
        $setting->shouldReceive('setSettingValue')->once()->with('1');

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        [$controller, $redirect] = $this->makeController($sR, 'quote');
        $result = $controller->navbarSticky('quote');

        Assert::same($redirect, $result);
    }
}
