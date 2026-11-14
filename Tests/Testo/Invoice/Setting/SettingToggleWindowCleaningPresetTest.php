<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Enum\ServiceType;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Setting\SettingToggleController;
use App\Invoice\WindowCleaning\WindowCleaningPreset;
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
 * The Settings > Front Page "Set up as a home-services business" button: the services ticked on the
 * form it was posted from go to the preset, and what the preset did is flashed.
 */
#[Test]
final class SettingToggleWindowCleaningPresetTest
{
    /** What the preset reports when it did everything. */
    private const array DONE = ['settings' => 8, 'areas' => 0, 'runs' => 0, 'streets' => 0, 'product' => true, 'seo' => false];

    /** @var list<array{string, string}> level and message of each flash added */
    private array $flashed = [];

    /**
     * @return array{SettingToggleController, WindowCleaningPreset&m\MockInterface}
     */
    private function controller(): array
    {
        $this->flashed = [];

        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->with('setting/tabIndex')
            ->andReturn(m::mock(Response::class));

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnArg(0);

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var SettingRepository<Setting>&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->andReturnUsing(function (string $level, string $message): void {
            $this->flashed[] = [$level, $message];
        });

        /** @var WindowCleaningPreset&m\MockInterface $preset */
        $preset = m::mock(WindowCleaningPreset::class);

        return [
            new SettingToggleController($session, $sR, $translator, $userService, $webViewRenderer, $webService, $flash),
            $preset,
        ];
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function request(?array $body): Request&m\MockInterface
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('getParsedBody')->andReturn($body);
        return $request;
    }

    public function theTickedServicesGoToThePresetInTheOrderOfTheEnum(): void
    {
        [$controller, $preset] = $this->controller();
        $preset->shouldReceive('apply')->once()->with(
            WindowCleaningPreset::DEFAULT_RUNS,
            [ServiceType::WindowCleaning, ServiceType::HedgeCutting],
        )->andReturn(self::DONE);

        $controller->windowCleaningPreset(
            $this->request(['settings' => ['services_offered' => ['', 'hedge_cutting', 'window_cleaning', 'nonsense']]]),
            $preset,
        );

        Assert::count($this->flashed, 1);
        Assert::same($this->flashed[0][0], 'info');
    }

    public function noTickedServicesAreAnEmptyListSoThePresetDefaultsToWindowCleaning(): void
    {
        [$controller, $preset] = $this->controller();
        $preset->shouldReceive('apply')->twice()->with(WindowCleaningPreset::DEFAULT_RUNS, [])
            ->andReturn(self::DONE);

        $controller->windowCleaningPreset($this->request(null), $preset);
        $controller->windowCleaningPreset($this->request(['settings' => []]), $preset);
    }

    public function itWarnsWhenTheServiceProductsCouldNotBeAdded(): void
    {
        [$controller, $preset] = $this->controller();
        $preset->shouldReceive('apply')->andReturn(['product' => false] + self::DONE);

        $controller->windowCleaningPreset($this->request(['settings' => []]), $preset);

        Assert::count($this->flashed, 2);
        Assert::same($this->flashed[1][0], 'warning');
    }

    public function itSaysWhenTheCompanySeoDescriptionWasFilled(): void
    {
        [$controller, $preset] = $this->controller();
        $preset->shouldReceive('apply')->andReturn(['seo' => true] + self::DONE);

        $controller->windowCleaningPreset($this->request(['settings' => []]), $preset);

        Assert::count($this->flashed, 2);
        Assert::same($this->flashed[1], ['info', 'window.cleaning.preset.seo']);
    }

    public function itSaysNothingAboutTheSeoDescriptionWhenItWasLeftAlone(): void
    {
        [$controller, $preset] = $this->controller();
        $preset->shouldReceive('apply')->andReturn(self::DONE);

        $controller->windowCleaningPreset($this->request(['settings' => []]), $preset);

        Assert::count($this->flashed, 1);
    }
}
