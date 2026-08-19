<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\HomeCareRunContext;
use App\Invoice\Inv\InvController;
use App\Invoice\Inv\InvIndexFilter;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Psr\Http\Message\ServerRequestInterface as Request;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers Trait\Index::indexHomeCareRunContext()'s "touched vs configured
 * default" resolution — the logic that lets inv/index default its Current
 * Run filter to the value configured in Settings on a plain visit, while
 * still letting a manager explicitly clear it to see every category_secondary.
 * Both cases produce an empty InvIndexFilter::$filterCategorySecondaryRun,
 * so the distinction has to come from whether the query string contained
 * the key at all — this test locks in that behaviour via
 * array_key_exists() on the raw request query params, checked directly
 * against Psr\Http\Message\ServerRequestInterface::getQueryParams() rather
 * than through the hydrated filter object.
 *
 * InvController is built via newInstanceWithoutConstructor() (its real
 * constructor pulls in the full DI dependency graph) with $sR injected by
 * reflection — the same technique InvsListWidgetTest.php already uses to
 * reach InvRepository, since InvController has no lighter-weight
 * constructor path for a unit test.
 */
#[Test]
final class HomeCareRunContextResolutionTest
{
    private const string LAST_RUN_DATE = '2026-07-01';

    private function invokeResolution(Request $request, InvIndexFilter $filter, SettingRepository $sR): HomeCareRunContext
    {
        $reflectionClass = new ReflectionClass(InvController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();

        $sRProperty = $reflectionClass->getProperty('sR');
        $sRProperty->setValue($controller, $sR);

        $method = $reflectionClass->getMethod('indexHomeCareRunContext');

        /** @var HomeCareRunContext */
        return $method->invoke($controller, $request, $filter);
    }

    public function untouchedQueryDefaultsToConfiguredRunAndAppliesDateFilter(): void
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $e = $request->shouldReceive('getQueryParams');
        $e->once()->andReturn([]);

        $filter = new InvIndexFilter();

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e2 = $sR->shouldReceive('getSetting');
        $e2->once()->with('homecare_current_run_category_secondary_id')->andReturn('5');
        $e3 = $sR->shouldReceive('getSetting');
        $e3->once()->with('homecare_current_run_last_run_date')->andReturn(self::LAST_RUN_DATE);

        $run = $this->invokeResolution($request, $filter, $sR);

        Assert::same(5, $run->categorySecondaryId);
        Assert::true($run->applyDateFilter);
        Assert::same(self::LAST_RUN_DATE, $run->lastRunDate);
    }

    public function explicitlyClearedFilterShowsEveryCategorySecondary(): void
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $e = $request->shouldReceive('getQueryParams');
        $e->once()->andReturn(['filterCategorySecondaryRun' => '']);

        $filter = new InvIndexFilter();
        $filter->filterCategorySecondaryRun = '';

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e2 = $sR->shouldReceive('getSetting');
        $e2->once()->with('homecare_current_run_category_secondary_id')->andReturn('5');
        $e3 = $sR->shouldReceive('getSetting');
        $e3->once()->with('homecare_current_run_last_run_date')->andReturn(self::LAST_RUN_DATE);

        $run = $this->invokeResolution($request, $filter, $sR);

        Assert::same(0, $run->categorySecondaryId);
        Assert::false($run->applyDateFilter);
    }

    public function browsingADifferentCategorySecondaryDoesNotApplyTheConfiguredDate(): void
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $e = $request->shouldReceive('getQueryParams');
        $e->once()->andReturn(['filterCategorySecondaryRun' => '9']);

        $filter = new InvIndexFilter();
        $filter->filterCategorySecondaryRun = '9';

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e2 = $sR->shouldReceive('getSetting');
        $e2->once()->with('homecare_current_run_category_secondary_id')->andReturn('5');
        $e3 = $sR->shouldReceive('getSetting');
        $e3->once()->with('homecare_current_run_last_run_date')->andReturn(self::LAST_RUN_DATE);

        $run = $this->invokeResolution($request, $filter, $sR);

        Assert::same(9, $run->categorySecondaryId);
        Assert::false($run->applyDateFilter);
    }

    public function selectingTheConfiguredRunExplicitlyStillAppliesTheDateFilter(): void
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $e = $request->shouldReceive('getQueryParams');
        $e->once()->andReturn(['filterCategorySecondaryRun' => '5']);

        $filter = new InvIndexFilter();
        $filter->filterCategorySecondaryRun = '5';

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e2 = $sR->shouldReceive('getSetting');
        $e2->once()->with('homecare_current_run_category_secondary_id')->andReturn('5');
        $e3 = $sR->shouldReceive('getSetting');
        $e3->once()->with('homecare_current_run_last_run_date')->andReturn(self::LAST_RUN_DATE);

        $run = $this->invokeResolution($request, $filter, $sR);

        Assert::same(5, $run->categorySecondaryId);
        Assert::true($run->applyDateFilter);
    }

    public function noConfiguredRunNeverAppliesTheDateFilter(): void
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $e = $request->shouldReceive('getQueryParams');
        $e->once()->andReturn([]);

        $filter = new InvIndexFilter();

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e2 = $sR->shouldReceive('getSetting');
        $e2->once()->with('homecare_current_run_category_secondary_id')->andReturn('0');
        $e3 = $sR->shouldReceive('getSetting');
        $e3->once()->with('homecare_current_run_last_run_date')->andReturn('');

        $run = $this->invokeResolution($request, $filter, $sR);

        Assert::same(0, $run->categorySecondaryId);
        Assert::false($run->applyDateFilter);
    }
}
