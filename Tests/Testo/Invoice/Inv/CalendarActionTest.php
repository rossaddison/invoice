<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\Inv\InvController;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers Trait\Calendar::calendar() end to end -- the public action itself,
 * on top of CalendarDateMathTest's coverage of its private date/bucketing
 * helpers. Built the same way HomeCareRunContextResolutionTest.php reaches
 * a private method on this class: InvController::class via
 * newInstanceWithoutConstructor(), with only the BaseController properties
 * this action's own path actually touches (sR, webViewRenderer, flash)
 * injected by reflection -- lighter than GuestOfflineTest.php's dedicated
 * harness subclass, and sufficient here since calendar() is itself public.
 */
#[Test]
final class CalendarActionTest
{
    /**
     * @return array{0: InvController, 1: SettingRepository&m\MockInterface, 2: WebViewRenderer&m\MockInterface, 3: Flash&m\MockInterface}
     */
    private function makeController(): array
    {
        $reflectionClass = new ReflectionClass(InvController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);

        $reflectionClass->getProperty('sR')->setValue($controller, $sR);
        $reflectionClass->getProperty('webViewRenderer')->setValue($controller, $webViewRenderer);
        $reflectionClass->getProperty('flash')->setValue($controller, $flash);

        return [$controller, $sR, $webViewRenderer, $flash];
    }

    private function emptyEntityReader(): EntityReader&m\MockInterface
    {
        /** @var EntityReader&m\MockInterface $entityReader */
        $entityReader = m::mock(EntityReader::class);
        $entityReader->shouldReceive('getIterator')->andReturn((static function (): \Generator {
            yield from [];
        })());
        return $entityReader;
    }

    public function rendersTheCalendarViewWithSevenMonthsInTheWindow(): void
    {
        [$controller, $sR, $webViewRenderer] = $this->makeController();
        $sR->shouldReceive('getSetting')
            ->with('bootstrap5_calendar_accent_color')
            ->andReturn('success');

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoDateRangeQuery')->andReturn($this->emptyEntityReader());

        /** @var CSR&m\MockInterface $csR */
        $csR = m::mock(CSR::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn(['3' => 'Elm Street']);

        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $webViewRenderer->shouldReceive('renderPartialAsString')->andReturn('');
        $webViewRenderer->shouldReceive('render')
            ->withArgs(function (string $view, array $parameters) use (&$captured): bool {
                $captured = $parameters;
                return $view === 'calendar';
            })
            ->andReturn($response);

        $result = $controller->calendar($iR, $csR, '2026', '9');

        Assert::same($response, $result);
        Assert::notNull($captured);
        /** @psalm-var array{months: list<array{monthStart: \DateTimeImmutable, active: bool}>, bootstrap5CalendarAccentColor: string, categoryNames: array<array-key, string>} $captured */
        Assert::same(7, count($captured['months']));
        Assert::same('success', $captured['bootstrap5CalendarAccentColor']);
        Assert::same('Elm Street', $captured['categoryNames']['3']);

        $activeCount = 0;
        $activeMonth = null;
        foreach ($captured['months'] as $monthData) {
            if ($monthData['active']) {
                $activeCount++;
                $activeMonth = $monthData['monthStart'];
            }
        }
        Assert::same(1, $activeCount);
        Assert::notNull($activeMonth);
        Assert::same('2026-09-01', $activeMonth->format('Y-m-d'));
    }

    public function fallsBackToPrimaryWhenNoAccentColorIsConfigured(): void
    {
        [$controller, $sR, $webViewRenderer] = $this->makeController();
        $sR->shouldReceive('getSetting')
            ->with('bootstrap5_calendar_accent_color')
            ->andReturn('');

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoDateRangeQuery')->andReturn($this->emptyEntityReader());
        /** @var CSR&m\MockInterface $csR */
        $csR = m::mock(CSR::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn([]);

        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $webViewRenderer->shouldReceive('renderPartialAsString')->andReturn('');
        $webViewRenderer->shouldReceive('render')
            ->withArgs(function (string $view, array $parameters) use (&$captured): bool {
                $captured = $parameters;
                return true;
            })
            ->andReturn($response);

        $controller->calendar($iR, $csR);

        Assert::notNull($captured);
        /** @psalm-var array{bootstrap5CalendarAccentColor: string} $captured */
        Assert::same('primary', $captured['bootstrap5CalendarAccentColor']);
    }

    public function bucketsInvoicesFromTheScopedRepositoryQueryIntoTheDaysParameter(): void
    {
        [$controller, $sR, $webViewRenderer] = $this->makeController();
        $sR->shouldReceive('getSetting')->andReturn('primary');

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('getFirstItemCategorySecondaryId')->andReturn(4);
        $inv->shouldReceive('getDateCreated')->andReturn(new \DateTimeImmutable('2026-09-15'));

        /** @var EntityReader&m\MockInterface $entityReader */
        $entityReader = m::mock(EntityReader::class);
        $entityReader->shouldReceive('getIterator')->andReturn((static function () use ($inv): \Generator {
            yield $inv;
        })());

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoDateRangeQuery')->andReturn($entityReader);
        /** @var CSR&m\MockInterface $csR */
        $csR = m::mock(CSR::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn([]);

        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $webViewRenderer->shouldReceive('renderPartialAsString')->andReturn('');
        $webViewRenderer->shouldReceive('render')
            ->withArgs(function (string $view, array $parameters) use (&$captured): bool {
                $captured = $parameters;
                return true;
            })
            ->andReturn($response);

        $controller->calendar($iR, $csR, '2026', '9');

        Assert::notNull($captured);
        /** @psalm-var array{days: array<string, array<int, int>>} $captured */
        Assert::same(1, $captured['days']['2026-09-15'][4]);
    }
}
