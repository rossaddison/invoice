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
use Tests\Testo\Support\TypedMock;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * /invoice/setting/listlimit/{setting_id}/{limit}/{origin} is open to every role with VIEW_INV and takes the
 * setting id from the URL. It used to write the value into ANY setting row, so an observer could overwrite the
 * Telegram bot token, the webhook secret, SMTP credentials and so on. Only the page size may be written, and
 * only with a whole number from 1 to 1000.
 */
#[Test]
final class SettingToggleListLimitTest
{
    /** @return array{0: SettingToggleController, 1: SettingRepository&m\MockInterface} */
    private function makeController(): array
    {
        $sR = TypedMock::of(SettingRepository::class);

        $webService = TypedMock::of(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturn(TypedMock::of(Response::class));

        $userService = TypedMock::of(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        $webViewRenderer = TypedMock::of(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        $controller = new SettingToggleController(
            $session,
            $sR,
            TypedMock::of(TranslatorInterface::class),
            $userService,
            $webViewRenderer,
            $webService,
            TypedMock::of(Flash::class),
        );

        return [$controller, $sR];
    }

    private function route(string $limit): CurrentRoute
    {
        $route = TypedMock::of(CurrentRoute::class);
        $route->shouldReceive('getArgument')->with('setting_id')->andReturn('7');
        $route->shouldReceive('getArgument')->with('origin')->andReturn('inv');
        $route->shouldReceive('getArgument')->with('limit')->andReturn($limit);
        return $route;
    }

    /** @return Setting&m\MockInterface */
    private function settingWithKey(string $key): Setting
    {
        $setting = TypedMock::of(Setting::class);
        $setting->shouldReceive('getSettingKey')->andReturn($key);
        return $setting;
    }

    public function thePageSizeSettingIsWritten(): void
    {
        [$controller, $sR] = $this->makeController();
        $setting = $this->settingWithKey('default_list_limit');
        $setting->shouldReceive('setSettingValue')->once()->with('25');
        $sR->shouldReceive('repoSettingquery')->with(7)->andReturn($setting);
        $sR->shouldReceive('save')->once()->with($setting);

        $controller->listlimit($this->route('25'));
        Assert::true(true);
    }

    public function aSecretSettingIsNeverWritten(): void
    {
        foreach (['telegram_token', 'telegram_webhook_secret_token', 'smtp_password'] as $key) {
            [$controller, $sR] = $this->makeController();
            $setting = $this->settingWithKey($key);
            $setting->shouldReceive('setSettingValue')->never();
            $sR->shouldReceive('repoSettingquery')->with(7)->andReturn($setting);
            $sR->shouldReceive('save')->never();

            $controller->listlimit($this->route('stolen'));
        }
        Assert::true(true);
    }

    public function aValueThatIsNotAPageSizeIsNeverWritten(): void
    {
        foreach (['', '0', '-1', '1001', '25abc', 'abc', '2.5', '0100', ' 25'] as $limit) {
            [$controller, $sR] = $this->makeController();
            $setting = $this->settingWithKey('default_list_limit');
            $setting->shouldReceive('setSettingValue')->never();
            $sR->shouldReceive('repoSettingquery')->with(7)->andReturn($setting);
            $sR->shouldReceive('save')->never();

            $controller->listlimit($this->route($limit));
        }
        Assert::true(true);
    }

    public function theLimitsOfTheRangeAreAccepted(): void
    {
        foreach (['1', '1000'] as $limit) {
            [$controller, $sR] = $this->makeController();
            $setting = $this->settingWithKey('default_list_limit');
            $setting->shouldReceive('setSettingValue')->once()->with($limit);
            $sR->shouldReceive('repoSettingquery')->with(7)->andReturn($setting);
            $sR->shouldReceive('save')->once();

            $controller->listlimit($this->route($limit));
        }
        Assert::true(true);
    }
}
