<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Widget;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Inv\Widget\InvsListWidget;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Widget\GridComponents;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Unit tests for InvsListWidget.
 *
 * Mirrors the QuotesListWidgetTest structure.  Key differences:
 *  - Constructor requires GridComponents (in addition to CurrentRoute /
 *    UrlGeneratorInterface / TranslatorInterface).
 *  - render() guard requires paginator + iR + irR + islR + sR (5 deps).
 *  - computeGroupTotals tracks paid and balance in addition to count/total.
 *  - makeGroupValueResolver takes only (string $groupBy) and asserts $this->iR.
 *
 * IR / IRR / ISLR / SR are final Cycle ORM repositories.  Instances for tests
 * that only need type-safe objects are created via
 * ReflectionClass::newInstanceWithoutConstructor().
 */
final class InvsListWidgetTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private CurrentRoute $currentRoute;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private UrlGeneratorInterface $urlGenerator;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private TranslatorInterface $translator;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private GridComponents $gridComponents;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentRoute = new CurrentRoute();

        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->translator   = $this->createStub(TranslatorInterface::class);

        $this->urlGenerator->method('generate')->willReturnArgument(0);
        $this->translator->method('translate')->willReturnArgument(0);

        $this->gridComponents = new GridComponents(
            $this->currentRoute,
            $this->translator,
        );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function makeWidget(): InvsListWidget
    {
        return new InvsListWidget(
            $this->currentRoute,
            $this->urlGenerator,
            $this->translator,
            $this->gridComponents,
        );
    }

    private function makeEmptyPaginator(): OffsetPaginator
    {
        return new OffsetPaginator(new IterableDataReader([]));
    }

    private function makeIR(): IR
    {
        /** @var IR */
        return (new \ReflectionClass(IR::class))->newInstanceWithoutConstructor();
    }

    /** Returns a bare IR with the test translator injected for status-label lookup. */
    private function makeIRWithTranslator(): IR
    {
        $ref = new \ReflectionClass(IR::class);
        /** @var IR $iR */
        $iR = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('translator')->setValue($iR, $this->translator);
        return $iR;
    }

    private function makeIRR(): IRR
    {
        /** @var IRR */
        return (new \ReflectionClass(IRR::class))->newInstanceWithoutConstructor();
    }

    private function makeISLR(): ISLR
    {
        /** @var ISLR */
        return (new \ReflectionClass(ISLR::class))->newInstanceWithoutConstructor();
    }

    private function makeSR(): SR
    {
        /** @var SR */
        return (new \ReflectionClass(SR::class))->newInstanceWithoutConstructor();
    }

    private function makeInvMock(
        string $clientFullName = 'Test Client',
        ?string $clientGroup = 'Group A',
        int $statusId = 1,
        string $dateCreated = '2025-06-15',
        float $total = 250.00,
        float $paid = 100.00,
        float $balance = 150.00,
    ): Inv {
        $invAmount = $this->createStub(InvAmount::class);
        $invAmount->method('getTotal')->willReturn($total);
        $invAmount->method('getPaid')->willReturn($paid);
        $invAmount->method('getBalance')->willReturn($balance);

        $client = $this->createStub(Client::class);
        $client->method('getClientFullName')->willReturn($clientFullName);
        $client->method('getClientGroup')->willReturn($clientGroup);

        $inv = $this->createStub(Inv::class);
        $inv->method('getClient')->willReturn($client);
        $inv->method('reqStatusId')->willReturn($statusId);
        $inv->method('getDateCreated')->willReturn(new DateTimeImmutable($dateCreated));
        $inv->method('getInvAmount')->willReturn($invAmount);

        return $inv;
    }

    /** Invoke a private method on a freshly created widget. */
    private function callPrivate(string $method, mixed ...$args): mixed
    {
        return (new \ReflectionMethod(InvsListWidget::class, $method))
            ->invoke($this->makeWidget(), ...$args);
    }

    /** Invoke a private method on a given widget instance. */
    private function callPrivateOn(InvsListWidget $widget, string $method, mixed ...$args): mixed
    {
        return (new \ReflectionMethod(InvsListWidget::class, $method))
            ->invoke($widget, ...$args);
    }

    // -------------------------------------------------------------------------
    // render() guard — returns '' when any required dependency is absent
    // Required: paginator, iR, irR, islR, sR
    // -------------------------------------------------------------------------

    public function testRenderReturnsEmptyStringWhenAllDepsAbsent(): void
    {
        $this->assertSame('', $this->makeWidget()->render());
    }

    public function testRenderReturnsEmptyWhenPaginatorNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withIR($this->makeIR())
            ->withIrR($this->makeIRR())
            ->withIslR($this->makeISLR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenIRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withIrR($this->makeIRR())
            ->withIslR($this->makeISLR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenIrRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withIR($this->makeIR())
            ->withIslR($this->makeISLR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenIslRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withIR($this->makeIR())
            ->withIrR($this->makeIRR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenSRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withIR($this->makeIR())
            ->withIrR($this->makeIRR())
            ->withIslR($this->makeISLR());

        $this->assertSame('', $widget->render());
    }

    // -------------------------------------------------------------------------
    // Immutable setter pattern — every with*() returns a distinct clone;
    // the original is unchanged.
    // -------------------------------------------------------------------------

    public function testWithPaginatorReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original  = $this->makeWidget();
        $paginator = $this->makeEmptyPaginator();
        $new       = $original->withPaginator($paginator);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'paginator');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($paginator, $prop->getValue($new));
    }

    public function testWithIRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $iR       = $this->makeIR();
        $new      = $original->withIR($iR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'iR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($iR, $prop->getValue($new));
    }

    public function testWithIrRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $irR      = $this->makeIRR();
        $new      = $original->withIrR($irR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'irR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($irR, $prop->getValue($new));
    }

    public function testWithIslRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $islR     = $this->makeISLR();
        $new      = $original->withIslR($islR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'islR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($islR, $prop->getValue($new));
    }

    public function testWithSRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $sR       = $this->makeSR();
        $new      = $original->withSR($sR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'sR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($sR, $prop->getValue($new));
    }

    public function testWithQRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        /** @var QR $qR */
        $qR  = (new \ReflectionClass(QR::class))->newInstanceWithoutConstructor();
        $new = $original->withQR($qR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'qR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($qR, $prop->getValue($new));
    }

    public function testWithSoRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        /** @var SOR $soR */
        $soR = (new \ReflectionClass(SOR::class))->newInstanceWithoutConstructor();
        $new = $original->withSoR($soR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'soR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($soR, $prop->getValue($new));
    }

    public function testWithCsrfReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withCsrf('my-token');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'csrf');
        $this->assertSame('', (string) $prop->getValue($original));
        $this->assertSame('my-token', (string) $prop->getValue($new));
    }

    public function testWithDecimalPlacesReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withDecimalPlaces(4);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'decimalPlaces');
        $this->assertSame(2, $prop->getValue($original));
        $this->assertSame(4, $prop->getValue($new));
    }

    public function testWithVisibleReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withVisible(true);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'visible');
        $this->assertFalse($prop->getValue($original));
        $this->assertTrue($prop->getValue($new));
    }

    public function testWithVisibleInvSentLogColumnReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withVisibleInvSentLogColumn(true);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'visibleInvSentLogColumn');
        $this->assertFalse($prop->getValue($original));
        $this->assertTrue($prop->getValue($new));
    }

    public function testWithGroupByReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withGroupBy('status');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'groupBy');
        $this->assertSame('none', $prop->getValue($original));
        $this->assertSame('status', $prop->getValue($new));
    }

    public function testWithClientCountReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withClientCount(7);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'clientCount');
        $this->assertSame(0, $prop->getValue($original));
        $this->assertSame(7, $prop->getValue($new));
    }

    public function testWithGridSummaryReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withGridSummary('Showing 1–10 of 50');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'gridSummary');
        $this->assertSame('', $prop->getValue($original));
        $this->assertSame('Showing 1–10 of 50', $prop->getValue($new));
    }

    public function testWithSortStringReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withSortString('id');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'sortString');
        $this->assertSame('-id', $prop->getValue($original));
        $this->assertSame('id', $prop->getValue($new));
    }

    public function testWithLabelReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withLabel('Draft');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'label');
        $this->assertSame('', $prop->getValue($original));
        $this->assertSame('Draft', $prop->getValue($new));
    }

    public function testWithOptionsInvNumberDropDownFilterReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withOptionsInvNumberDropDownFilter(['INV-001' => 'INV-001']);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'optionsInvNumberDropDownFilter');
        $this->assertSame([], $prop->getValue($original));
        $this->assertSame(['INV-001' => 'INV-001'], $prop->getValue($new));
    }

    public function testWithOptionsStatusDropDownFilterReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withOptionsStatusDropDownFilter([1 => 'Draft', 2 => 'Sent']);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(InvsListWidget::class, 'optionsStatusDropDownFilter');
        $this->assertSame([], $prop->getValue($original));
        $this->assertSame([1 => 'Draft', 2 => 'Sent'], $prop->getValue($new));
    }

    // -------------------------------------------------------------------------
    // CSRF type widening — withCsrf() accepts string and Stringable
    // -------------------------------------------------------------------------

    public function testWithCsrfAcceptsRawString(): void
    {
        $widget = $this->makeWidget()->withCsrf('raw-token-value');

        $prop = new \ReflectionProperty(InvsListWidget::class, 'csrf');
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

        $prop = new \ReflectionProperty(InvsListWidget::class, 'csrf');
        $this->assertSame('view-csrf-token', (string) $prop->getValue($widget));
    }

    // -------------------------------------------------------------------------
    // makeGroupValueResolver
    // The widget's private closure maps every documented groupBy key to the
    // correct value extracted from the Inv object.
    // makeGroupValueResolver asserts $this->iR !== null, so it is invoked on a
    // widget that has iR injected.
    // -------------------------------------------------------------------------

    /** Resolve a group key for an invoice using the widget's private resolver. */
    private function resolveGroup(string $groupBy, Inv $inv, ?IR $iR = null): string
    {
        $iR ??= $this->makeIR();
        $widget = $this->makeWidget()->withIR($iR);
        /** @var \Closure(Inv): string $resolver */
        $resolver = $this->callPrivateOn($widget, 'makeGroupValueResolver', $groupBy);
        return $resolver($inv);
    }

    public function testGroupResolverReturnsClientFullName(): void
    {
        $inv = $this->makeInvMock(clientFullName: 'Jane Smith');
        $this->assertSame('Jane Smith', $this->resolveGroup('client', $inv));
    }

    public function testGroupResolverFallsBackToUnknownClientWhenClientIsNull(): void
    {
        $inv = $this->createStub(Inv::class);
        $inv->method('getClient')->willReturn(null);
        $inv->method('getInvAmount')->willReturn($this->createStub(InvAmount::class));
        $inv->method('getDateCreated')->willReturn(new DateTimeImmutable('2025-01-01'));
        $inv->method('reqStatusId')->willReturn(1);

        $this->assertSame('Unknown Client', $this->resolveGroup('client', $inv));
    }

    /**
     * The 'status' branch calls $iR->getSpecificStatusArrayLabel().
     * A bare IR instance with translator injected via reflection is sufficient.
     * The test translator returns the key as-is, so status 2 ('sent' key) → 'sent'.
     */
    public function testGroupResolverReturnsStatusLabel(): void
    {
        $iR  = $this->makeIRWithTranslator();
        $inv = $this->makeInvMock(statusId: 2);

        $result = $this->resolveGroup('status', $inv, $iR);

        $this->assertSame('sent', $result);
    }

    public function testGroupResolverReturnsYearMonth(): void
    {
        $inv = $this->makeInvMock(dateCreated: '2025-11-05');
        $this->assertSame('2025-11', $this->resolveGroup('month', $inv));
    }

    public function testGroupResolverReturnsYear(): void
    {
        $inv = $this->makeInvMock(dateCreated: '2025-11-05');
        $this->assertSame('2025', $this->resolveGroup('year', $inv));
    }

    public function testGroupResolverReturnsFullDate(): void
    {
        $inv = $this->makeInvMock(dateCreated: '2025-11-05');
        $this->assertSame('2025-11-05', $this->resolveGroup('date', $inv));
    }

    public function testGroupResolverReturnsClientGroupName(): void
    {
        $inv = $this->makeInvMock(clientGroup: 'Premium');
        $this->assertSame('Premium', $this->resolveGroup('client_group', $inv));
    }

    public function testGroupResolverFallsBackToNoGroupWhenClientGroupIsNull(): void
    {
        $inv = $this->makeInvMock(clientGroup: null);
        $this->assertSame('No Group', $this->resolveGroup('client_group', $inv));
    }

    public function testGroupResolverAmountRangeLow(): void
    {
        $inv = $this->makeInvMock(total: 50.00);
        $this->assertSame('< $100', $this->resolveGroup('amount_range', $inv));
    }

    public function testGroupResolverAmountRangeLowerMid(): void
    {
        $inv = $this->makeInvMock(total: 250.00);
        $this->assertSame('$100 - $500', $this->resolveGroup('amount_range', $inv));
    }

    public function testGroupResolverAmountRangeUpperMid(): void
    {
        $inv = $this->makeInvMock(total: 750.00);
        $this->assertSame('$500 - $1000', $this->resolveGroup('amount_range', $inv));
    }

    public function testGroupResolverAmountRangeHigh(): void
    {
        $inv = $this->makeInvMock(total: 5000.00);
        $this->assertSame('> $1000', $this->resolveGroup('amount_range', $inv));
    }

    public function testGroupResolverReturnsNoGroupForUnknownKey(): void
    {
        $inv = $this->makeInvMock();
        $this->assertSame('No Group', $this->resolveGroup('not_a_real_key', $inv));
    }

    // -------------------------------------------------------------------------
    // computeGroupTotals
    // Unlike the quote equivalent, invoice totals include paid and balance.
    // IterableDataReader wraps in-memory Inv mocks — no database needed.
    // -------------------------------------------------------------------------

    public function testComputeGroupTotalsReturnsEmptyArrayWhenPaginatorIsEmpty(): void
    {
        $paginator     = $this->makeEmptyPaginator();
        $getGroupValue = static fn(Inv $_inv): string => 'unused';

        /** @var array<string, array{count: int, total: float, paid: float, balance: float}> $result */
        $result = $this->callPrivate('computeGroupTotals', $paginator, $getGroupValue);

        $this->assertSame([], $result);
    }

    public function testComputeGroupTotalsAggregatesSingleGroup(): void
    {
        $i1        = $this->makeInvMock(total: 100.00, paid: 40.00, balance: 60.00);
        $i2        = $this->makeInvMock(total: 200.00, paid: 80.00, balance: 120.00);
        $paginator = new OffsetPaginator(new IterableDataReader([$i1, $i2]));

        $getGroupValue = static fn(Inv $_inv): string => 'All';

        /** @var array<string, array{count: int, total: float, paid: float, balance: float}> $result */
        $result = $this->callPrivate('computeGroupTotals', $paginator, $getGroupValue);

        $this->assertCount(1, $result);
        $this->assertSame(2,      $result['All']['count']);
        $this->assertSame(300.00, $result['All']['total']);
        $this->assertSame(120.00, $result['All']['paid']);
        $this->assertSame(180.00, $result['All']['balance']);
    }

    public function testComputeGroupTotalsSeparatesDistinctGroups(): void
    {
        $i1 = $this->makeInvMock(clientFullName: 'Alice', total: 100.00, paid: 100.00, balance: 0.00);
        $i2 = $this->makeInvMock(clientFullName: 'Bob',   total: 200.00, paid: 50.00,  balance: 150.00);
        $i3 = $this->makeInvMock(clientFullName: 'Alice', total: 150.00, paid: 75.00,  balance: 75.00);

        $paginator = new OffsetPaginator(new IterableDataReader([$i1, $i2, $i3]));
        $widget    = $this->makeWidget()->withIR($this->makeIR());
        /** @var \Closure(Inv): string $getGroupValue */
        $getGroupValue = $this->callPrivateOn($widget, 'makeGroupValueResolver', 'client');

        /** @var array<string, array{count: int, total: float, paid: float, balance: float}> $result */
        $result = $this->callPrivateOn($widget, 'computeGroupTotals', $paginator, $getGroupValue);

        $this->assertCount(2, $result);
        $this->assertSame(2,      $result['Alice']['count']);
        $this->assertSame(250.00, $result['Alice']['total']);
        $this->assertSame(175.00, $result['Alice']['paid']);
        $this->assertSame(75.00,  $result['Alice']['balance']);
        $this->assertSame(1,      $result['Bob']['count']);
        $this->assertSame(200.00, $result['Bob']['total']);
        $this->assertSame(50.00,  $result['Bob']['paid']);
        $this->assertSame(150.00, $result['Bob']['balance']);
    }

    public function testComputeGroupTotalsHandlesNullInvAmount(): void
    {
        $inv = $this->createStub(Inv::class);
        $inv->method('getClient')->willReturn(null);
        $inv->method('getDateCreated')->willReturn(new DateTimeImmutable('2025-01-01'));
        $inv->method('reqStatusId')->willReturn(1);

        $nullAmount = $this->createStub(InvAmount::class);
        $nullAmount->method('getTotal')->willReturn(null);
        $nullAmount->method('getPaid')->willReturn(null);
        $nullAmount->method('getBalance')->willReturn(null);
        $inv->method('getInvAmount')->willReturn($nullAmount);

        $paginator     = new OffsetPaginator(new IterableDataReader([$inv]));
        $getGroupValue = static fn(Inv $_i): string => 'NullAmounts';

        /** @var array<string, array{count: int, total: float, paid: float, balance: float}> $result */
        $result = $this->callPrivate('computeGroupTotals', $paginator, $getGroupValue);

        $this->assertSame(1,    $result['NullAmounts']['count']);
        $this->assertSame(0.00, $result['NullAmounts']['total']);
        $this->assertSame(0.00, $result['NullAmounts']['paid']);
        $this->assertSame(0.00, $result['NullAmounts']['balance']);
    }

    public function testComputeGroupTotalsTotalPaidBalanceRoundTrip(): void
    {
        $inv = $this->makeInvMock(total: 1000.00, paid: 600.00, balance: 400.00);
        $paginator     = new OffsetPaginator(new IterableDataReader([$inv]));
        $getGroupValue = static fn(Inv $_i): string => 'Check';

        /** @var array<string, array{count: int, total: float, paid: float, balance: float}> $result */
        $result = $this->callPrivate('computeGroupTotals', $paginator, $getGroupValue);

        // paid + balance = total
        $this->assertSame(
            $result['Check']['total'],
            $result['Check']['paid'] + $result['Check']['balance']
        );
    }
}
