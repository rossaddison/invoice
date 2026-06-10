<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Widget;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrder\Widget\SalesOrdersGroupingRenderer;
use App\Invoice\SalesOrder\Widget\SalesOrdersListWidget;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\Setting\SettingRepository as SR;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Unit tests for SalesOrdersListWidget.
 *
 * Covers:
 *  - render() guard returns '' when any required dep (paginator/soR/soaR/sR) is absent
 *  - Immutable setter pattern — every with*() returns a distinct clone
 *  - CSRF type widening — withCsrf() accepts string and Stringable
 *  - makeGroupValueResolver — all documented group keys + default fallback
 *  - computeGroupTotals — empty paginator, single group, multiple groups, null amount
 *
 * SoR / SoAR / SR are final Cycle ORM repositories; type-safe instances
 * are created via ReflectionClass::newInstanceWithoutConstructor() (no DB needed).
 * The 'status' group resolver test injects the translator into a bare SoR via
 * reflection so that getSpecificStatusArrayLabel() can resolve the translation key.
 */
final class SalesOrdersListWidgetTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private CurrentRoute $currentRoute;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private UrlGeneratorInterface $urlGenerator;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentRoute = new CurrentRoute();

        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->translator   = $this->createStub(TranslatorInterface::class);

        $this->urlGenerator->method('generate')->willReturnArgument(0);
        $this->translator->method('translate')->willReturnArgument(0);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function makeWidget(): SalesOrdersListWidget
    {
        return new SalesOrdersListWidget(
            $this->currentRoute,
            $this->urlGenerator,
            $this->translator,
        );
    }

    private function makeEmptyPaginator(): OffsetPaginator
    {
        return new OffsetPaginator(new IterableDataReader([]));
    }

    private function makeSoR(): SoR
    {
        /** @var SoR */
        return (new \ReflectionClass(SoR::class))->newInstanceWithoutConstructor();
    }

    /** Returns a bare SoR with the test translator injected for status-label lookup. */
    private function makeSoRWithTranslator(): SoR
    {
        $ref = new \ReflectionClass(SoR::class);
        /** @var SoR $soR */
        $soR = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('translator')->setValue($soR, $this->translator);
        return $soR;
    }

    private function makeSoAR(): SoAR
    {
        /** @var SoAR */
        return (new \ReflectionClass(SoAR::class))->newInstanceWithoutConstructor();
    }

    private function makeSR(): SR
    {
        /** @var SR */
        return (new \ReflectionClass(SR::class))->newInstanceWithoutConstructor();
    }

    private function makeSalesOrderMock(
        string $clientFullName = 'Test Client',
        int $statusId = 1,
        string $dateCreated = '2025-06-15',
        float $total = 250.00,
    ): SalesOrder {
        $soAmount = $this->createStub(SalesOrderAmount::class);
        $soAmount->method('getTotal')->willReturn($total);

        $client = $this->createStub(Client::class);
        $client->method('getClientFullName')->willReturn($clientFullName);

        $so = $this->createStub(SalesOrder::class);
        $so->method('getClient')->willReturn($client);
        $so->method('getStatusId')->willReturn($statusId);
        $so->method('getDateCreated')->willReturn(new DateTimeImmutable($dateCreated));
        $so->method('getSalesOrderAmount')->willReturn($soAmount);

        return $so;
    }

    private function makeGroupingRenderer(?SoR $soR = null, ?SR $sR = null): SalesOrdersGroupingRenderer
    {
        return new SalesOrdersGroupingRenderer(
            $soR ?? $this->makeSoR(),
            $sR  ?? $this->makeSR(),
        );
    }

    /**
     * The 'status' branch calls $soR->getSpecificStatusArrayLabel().
     * With the stub translator returning the key as-is, status 1 ('draft' key)
     * maps to the string 'draft'.
     */
    private function resolveGroup(
        string $groupBy,
        SalesOrder $so,
        bool $withTranslator = false,
    ): string {
        $soR      = $withTranslator ? $this->makeSoRWithTranslator() : $this->makeSoR();
        $renderer = $this->makeGroupingRenderer($soR);
        /** @var \Closure(SalesOrder): string $resolver */
        $resolver = $renderer->makeGroupValueResolver($groupBy);
        return $resolver($so);
    }

    // -------------------------------------------------------------------------
    // render() guard
    // -------------------------------------------------------------------------

    public function testRenderReturnsEmptyWhenAllDepsAbsent(): void
    {
        $this->assertSame('', $this->makeWidget()->render());
    }

    public function testRenderReturnsEmptyWhenPaginatorNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withSoR($this->makeSoR())
            ->withSoAR($this->makeSoAR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenSoRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withSoAR($this->makeSoAR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenSoARNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withSoR($this->makeSoR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenSRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withSoR($this->makeSoR())
            ->withSoAR($this->makeSoAR());

        $this->assertSame('', $widget->render());
    }

    // -------------------------------------------------------------------------
    // Immutable setter pattern
    // -------------------------------------------------------------------------

    public function testWithPaginatorReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original  = $this->makeWidget();
        $paginator = $this->makeEmptyPaginator();
        $new       = $original->withPaginator($paginator);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'paginator');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($paginator, $prop->getValue($new));
    }

    public function testWithSoRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $soR      = $this->makeSoR();
        $new      = $original->withSoR($soR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'soR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($soR, $prop->getValue($new));
    }

    public function testWithSoARReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $soaR     = $this->makeSoAR();
        $new      = $original->withSoAR($soaR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'soaR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($soaR, $prop->getValue($new));
    }

    public function testWithSRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $sR       = $this->makeSR();
        $new      = $original->withSR($sR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'sR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($sR, $prop->getValue($new));
    }

    public function testWithCsrfReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withCsrf('my-token');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'csrf');
        $this->assertSame('', (string) $prop->getValue($original));
        $this->assertSame('my-token', (string) $prop->getValue($new));
    }

    public function testWithVisibleReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withVisible(true);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'visible');
        $this->assertFalse($prop->getValue($original));
        $this->assertTrue($prop->getValue($new));
    }

    public function testWithGroupByReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withGroupBy('status');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'groupBy');
        $this->assertSame('none', $prop->getValue($original));
        $this->assertSame('status', $prop->getValue($new));
    }

    public function testWithGridSummaryReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withGridSummary('Showing 1–10 of 50');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'gridSummary');
        $this->assertSame('', $prop->getValue($original));
        $this->assertSame('Showing 1–10 of 50', $prop->getValue($new));
    }

    public function testWithSortStringReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withSortString('id');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'sortString');
        $this->assertSame('-id', $prop->getValue($original));
        $this->assertSame('id', $prop->getValue($new));
    }

    public function testWithStatusReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withStatus(3);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'status');
        $this->assertSame(0, $prop->getValue($original));
        $this->assertSame(3, $prop->getValue($new));
    }

    public function testWithSalesOrderToolbarReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withSalesOrderToolbar('<div>toolbar</div>');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'salesOrderToolbar');
        $this->assertSame('', $prop->getValue($original));
        $this->assertSame('<div>toolbar</div>', $prop->getValue($new));
    }

    public function testWithOptionsDataClientsDropdownFilterReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withOptionsDataClientsDropdownFilter(['Alice' => 'Alice']);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'optionsDataClientsDropdownFilter');
        $this->assertSame([], $prop->getValue($original));
        $this->assertSame(['Alice' => 'Alice'], $prop->getValue($new));
    }

    // -------------------------------------------------------------------------
    // CSRF type widening
    // -------------------------------------------------------------------------

    public function testWithCsrfAcceptsRawString(): void
    {
        $widget = $this->makeWidget()->withCsrf('raw-token-value');

        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'csrf');
        $this->assertSame('raw-token-value', (string) $prop->getValue($widget));
    }

    public function testWithCsrfAcceptsStringableObject(): void
    {
        $stringable = new class ('view-csrf-token') implements \Stringable {
            public function __construct(private readonly string $value) {}
            #[\Override]
            public function __toString(): string { return $this->value; }
        };

        $widget = $this->makeWidget()->withCsrf($stringable);

        $prop = new \ReflectionProperty(SalesOrdersListWidget::class, 'csrf');
        $this->assertSame('view-csrf-token', (string) $prop->getValue($widget));
    }

    // -------------------------------------------------------------------------
    // makeGroupValueResolver
    // -------------------------------------------------------------------------

    public function testGroupResolverReturnsClientFullName(): void
    {
        $so = $this->makeSalesOrderMock(clientFullName: 'Jane Smith');
        $this->assertSame('Jane Smith', $this->resolveGroup('client', $so));
    }

    public function testGroupResolverFallsBackToUnknownClientWhenClientIsNull(): void
    {
        $so = $this->createStub(SalesOrder::class);
        $so->method('getClient')->willReturn(null);
        $so->method('getStatusId')->willReturn(1);
        $so->method('getDateCreated')->willReturn(new DateTimeImmutable('2025-01-01'));

        $this->assertSame('Unknown Client', $this->resolveGroup('client', $so));
    }

    /**
     * The 'status' branch calls $soR->getSpecificStatusArrayLabel().
     * With the stub translator returning the key as-is, status 1 ('draft' key)
     * maps to the string 'draft'.
     */
    public function testGroupResolverReturnsStatusLabel(): void
    {
        $so = $this->makeSalesOrderMock(statusId: 1);
        $this->assertSame('draft', $this->resolveGroup('status', $so, withTranslator: true));
    }

    public function testGroupResolverReturnsYearMonth(): void
    {
        $so = $this->makeSalesOrderMock(dateCreated: '2025-11-05');
        $this->assertSame('2025-11', $this->resolveGroup('month', $so));
    }

    public function testGroupResolverReturnsYear(): void
    {
        $so = $this->makeSalesOrderMock(dateCreated: '2025-11-05');
        $this->assertSame('2025', $this->resolveGroup('year', $so));
    }

    public function testGroupResolverReturnsFullDate(): void
    {
        $so = $this->makeSalesOrderMock(dateCreated: '2025-11-05');
        $this->assertSame('2025-11-05', $this->resolveGroup('date', $so));
    }

    public function testGroupResolverReturnsNoGroupForUnknownKey(): void
    {
        $so = $this->makeSalesOrderMock();
        $this->assertSame('No Group', $this->resolveGroup('not_a_real_key', $so));
    }

    // -------------------------------------------------------------------------
    // computeGroupTotals
    // -------------------------------------------------------------------------

    public function testComputeGroupTotalsReturnsEmptyArrayWhenPaginatorIsEmpty(): void
    {
        $paginator     = $this->makeEmptyPaginator();
        $getGroupValue = static fn(SalesOrder $_so): string => 'unused';

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $this->makeGroupingRenderer()->computeGroupTotals($paginator, $getGroupValue);

        $this->assertSame([], $result);
    }

    public function testComputeGroupTotalsAggregatesSingleGroup(): void
    {
        $so1       = $this->makeSalesOrderMock(total: 100.00);
        $so2       = $this->makeSalesOrderMock(total: 200.00);
        $paginator = new OffsetPaginator(new IterableDataReader([$so1, $so2]));

        $getGroupValue = static fn(SalesOrder $_so): string => 'All';

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $this->makeGroupingRenderer()->computeGroupTotals($paginator, $getGroupValue);

        $this->assertCount(1, $result);
        $this->assertSame(2,      $result['All']['count']);
        $this->assertSame(300.00, $result['All']['total']);
    }

    public function testComputeGroupTotalsSeparatesDistinctGroups(): void
    {
        $so1 = $this->makeSalesOrderMock(clientFullName: 'Alice', total: 100.00);
        $so2 = $this->makeSalesOrderMock(clientFullName: 'Bob',   total: 200.00);
        $so3 = $this->makeSalesOrderMock(clientFullName: 'Alice', total: 150.00);

        $paginator = new OffsetPaginator(new IterableDataReader([$so1, $so2, $so3]));
        $renderer  = $this->makeGroupingRenderer($this->makeSoR());

        /** @var \Closure(SalesOrder): string $getGroupValue */
        $getGroupValue = $renderer->makeGroupValueResolver('client');

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $renderer->computeGroupTotals($paginator, $getGroupValue);

        $this->assertCount(2, $result);
        $this->assertSame(2,      $result['Alice']['count']);
        $this->assertSame(250.00, $result['Alice']['total']);
        $this->assertSame(1,      $result['Bob']['count']);
        $this->assertSame(200.00, $result['Bob']['total']);
    }

    public function testComputeGroupTotalsHandlesNullTotal(): void
    {
        $soAmount = $this->createStub(SalesOrderAmount::class);
        $soAmount->method('getTotal')->willReturn(null);

        $so = $this->createStub(SalesOrder::class);
        $so->method('getSalesOrderAmount')->willReturn($soAmount);

        $paginator     = new OffsetPaginator(new IterableDataReader([$so]));
        $getGroupValue = static fn(SalesOrder $_so): string => 'NullTotal';

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $this->makeGroupingRenderer()->computeGroupTotals($paginator, $getGroupValue);

        $this->assertSame(1,    $result['NullTotal']['count']);
        $this->assertSame(0.00, $result['NullTotal']['total']);
    }
}
