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
 *
 * makeController() builds and returns $sR itself rather than accepting
 * it as a parameter -- a `SettingRepository&m\MockInterface` parameter
 * type triggers Psalm's documented full-project-scope scale artifact
 * (intersection-with-MockInterface types inferred as `never`, see
 * project_psalm_test_scope_cleanup in memory / psalm-baseline.xml's own
 * history), confirmed live: CI's full-project Psalm run flagged exactly
 * that shape here even though a single-file `--no-cache` run didn't.
 * Returning it in the tuple instead (matching every other Mockery
 * helper in this suite -- none of them take one as a parameter either)
 * avoids it; Mockery expectations set on the returned $sR after
 * construction still apply, since nothing calls it until the actual
 * gridStickyHeader()/navbarSticky() call below.
 */
#[Test]
final class SettingToggleControllerTest
{
    /** @return array{0: SettingToggleController, 1: Response&m\MockInterface, 2: SettingRepository&m\MockInterface} */
    private function makeController(string $origin = 'inv'): array
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

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

        return [$controller, $redirect, $sR];
    }

    // -------------------------------------------------------------------
    // gridStickyHeader()
    // -------------------------------------------------------------------

    public function gridStickyHeaderTogglesAnExistingSettingFromOffToOn(): void
    {
        [$controller, $redirect, $sR] = $this->makeController();

        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('0');
        $setting->shouldReceive('setSettingValue')->once()->with('1');

        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        $result = $controller->gridStickyHeader('inv');

        Assert::same($redirect, $result);
    }

    public function gridStickyHeaderTogglesAnExistingSettingFromOnToOff(): void
    {
        [$controller, $redirect, $sR] = $this->makeController();

        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('1');
        $setting->shouldReceive('setSettingValue')->once()->with('0');

        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        $result = $controller->gridStickyHeader('inv');

        Assert::same($redirect, $result);
    }

    public function gridStickyHeaderCreatesANewSettingAtOnWhenNoneExistsYet(): void
    {
        [$controller, $redirect, $sR] = $this->makeController();

        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn(null);
        $sR->shouldReceive('save')->once()->with(m::on(
            static fn (mixed $setting): bool => $setting instanceof Setting
                && $setting->getSettingKey() === 'grid_sticky_header'
                && $setting->getSettingValue() === '1'
        ));

        $result = $controller->gridStickyHeader('inv');

        Assert::same($redirect, $result);
    }

    public function gridStickyHeaderRedirectsToTheGivenOriginsIndex(): void
    {
        [$controller, $redirect, $sR] = $this->makeController('quote');

        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('1');
        $setting->shouldReceive('setSettingValue')->once()->with('0');

        $sR->shouldReceive('withKey')->once()->with('grid_sticky_header')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        $result = $controller->gridStickyHeader('quote');

        Assert::same($redirect, $result);
    }

    // -------------------------------------------------------------------
    // navbarSticky()
    // -------------------------------------------------------------------

    public function navbarStickyTogglesAnExistingSettingFromOffToOn(): void
    {
        [$controller, $redirect, $sR] = $this->makeController();

        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('0');
        $setting->shouldReceive('setSettingValue')->once()->with('1');

        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        $result = $controller->navbarSticky('inv');

        Assert::same($redirect, $result);
    }

    public function navbarStickyTogglesAnExistingSettingFromOnToOff(): void
    {
        [$controller, $redirect, $sR] = $this->makeController();

        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('1');
        $setting->shouldReceive('setSettingValue')->once()->with('0');

        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        $result = $controller->navbarSticky('inv');

        Assert::same($redirect, $result);
    }

    public function navbarStickyCreatesANewSettingAtOnWhenNoneExistsYet(): void
    {
        [$controller, $redirect, $sR] = $this->makeController();

        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn(null);
        $sR->shouldReceive('save')->once()->with(m::on(
            static fn (mixed $setting): bool => $setting instanceof Setting
                && $setting->getSettingKey() === 'bootstrap5_layout_invoice_navbar_sticky'
                && $setting->getSettingValue() === '1'
        ));

        $result = $controller->navbarSticky('inv');

        Assert::same($redirect, $result);
    }

    public function navbarStickyRedirectsToTheGivenOriginsIndex(): void
    {
        [$controller, $redirect, $sR] = $this->makeController('quote');

        /** @var Setting&m\MockInterface $setting */
        $setting = m::mock(Setting::class);
        $setting->shouldReceive('getSettingValue')->once()->andReturn('0');
        $setting->shouldReceive('setSettingValue')->once()->with('1');

        $sR->shouldReceive('withKey')->once()
            ->with('bootstrap5_layout_invoice_navbar_sticky')->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        $result = $controller->navbarSticky('quote');

        Assert::same($redirect, $result);
    }
}
