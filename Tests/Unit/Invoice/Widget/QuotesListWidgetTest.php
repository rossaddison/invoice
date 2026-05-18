<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Widget;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Quote\Widget\QuotesListWidget;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Unit tests for QuotesListWidget.
 *
 * Covers the behaviour documented in docs/QUOTES_LIST_WIDGET.md:
 *  - Section 2  Widget Pattern  — immutable setters, CSRF type widening
 *  - Section 3  HTMX detection — render() guard returns '' when required deps absent
 *  - Section 5  Group-by       — makeGroupValueResolver and computeGroupTotals
 *
 * Implementation notes
 * --------------------
 * CurrentRoute is a final concrete class with a zero-arg constructor.
 * getName() returns null by default; the widget falls back to 'quote/index'.
 *
 * QR / SOR / SR are final Cycle ORM repositories.  For tests that only need
 * type-safe instances (not method calls), they are created via
 * ReflectionClass::newInstanceWithoutConstructor() — no database required.
 * For the 'status' group resolver test, $this->translator is injected into
 * the bare QR so that getSpecificStatusArrayLabel() can return a value.
 */
final class QuotesListWidgetTest extends TestCase
{
    private CurrentRoute $currentRoute;
    private UrlGeneratorInterface $urlGenerator;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        // CurrentRoute is final — instantiate directly (no-arg constructor).
        // getName() returns null; widget defaults to 'quote/index'.
        $this->currentRoute = new CurrentRoute();

        // UrlGeneratorInterface and TranslatorInterface are interfaces — stubs only.
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->translator   = $this->createStub(TranslatorInterface::class);

        $this->urlGenerator->method('generate')->willReturnArgument(0);
        $this->translator->method('translate')->willReturnArgument(0);
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    private function makeWidget(): QuotesListWidget
    {
        return new QuotesListWidget(
            $this->currentRoute,
            $this->urlGenerator,
            $this->translator,
        );
    }

    private function makeEmptyPaginator(): OffsetPaginator
    {
        return new OffsetPaginator(new IterableDataReader([]));
    }

    /** Returns a bare QR instance (no ORM connection). No methods touch $select. */
    private function makeQR(): QR
    {
        /** @var QR */
        return (new \ReflectionClass(QR::class))->newInstanceWithoutConstructor();
    }

    /** Returns a bare QR with the test translator injected for status-label lookup. */
    private function makeQRWithTranslator(): QR
    {
        $ref = new \ReflectionClass(QR::class);
        /** @var QR $qR */
        $qR = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('translator')->setValue($qR, $this->translator);
        return $qR;
    }

    private function makeSoR(): SOR
    {
        /** @var SOR */
        return (new \ReflectionClass(SOR::class))->newInstanceWithoutConstructor();
    }

    private function makeSR(): SR
    {
        /** @var SR */
        return (new \ReflectionClass(SR::class))->newInstanceWithoutConstructor();
    }

    private function makeQuoteMock(
        string $clientFullName = 'Test Client',
        ?string $clientGroup = 'Group A',
        int $statusId = 1,
        string $dateCreated = '2025-06-15',
        float $total = 250.00,
    ): Quote {
        $quoteAmount = $this->createStub(QuoteAmount::class);
        $quoteAmount->method('getTotal')->willReturn($total);

        $client = $this->createStub(Client::class);
        $client->method('getClientFullName')->willReturn($clientFullName);
        $client->method('getClientGroup')->willReturn($clientGroup);

        $quote = $this->createStub(Quote::class);
        $quote->method('getClient')->willReturn($client);
        $quote->method('reqStatusId')->willReturn($statusId);
        $quote->method('getDateCreated')->willReturn(new DateTimeImmutable($dateCreated));
        $quote->method('getQuoteAmount')->willReturn($quoteAmount);

        return $quote;
    }

    /** Invoke a private method on a freshly created widget. */
    private function callPrivate(string $method, mixed ...$args): mixed
    {
        return (new \ReflectionMethod(QuotesListWidget::class, $method))
            ->invoke($this->makeWidget(), ...$args);
    }

    /** Invoke a private method on a given widget instance. */
    private function callPrivateOn(QuotesListWidget $widget, string $method, mixed ...$args): mixed
    {
        return (new \ReflectionMethod(QuotesListWidget::class, $method))
            ->invoke($widget, ...$args);
    }

    // -----------------------------------------------------------------------
    // Section 3 — render() guard
    // The very first lines of render() return '' when any of the four required
    // dependencies is absent.  GridView is never reached in these paths.
    // -----------------------------------------------------------------------

    public function testRenderReturnsEmptyStringWhenAllDepsAbsent(): void
    {
        $this->assertSame('', $this->makeWidget()->render());
    }

    public function testRenderReturnsEmptyWhenPaginatorNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withQR($this->makeQR())
            ->withSoR($this->makeSoR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenQRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withSoR($this->makeSoR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenSoRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withQR($this->makeQR())
            ->withSR($this->makeSR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenSRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withQR($this->makeQR())
            ->withSoR($this->makeSoR());

        $this->assertSame('', $widget->render());
    }

    // -----------------------------------------------------------------------
    // Section 2 — Immutable setter pattern
    // Every with*() call must return a distinct clone; the original must be
    // unchanged.  Verified via assertNotSame and ReflectionProperty reads.
    // -----------------------------------------------------------------------

    public function testWithPaginatorReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $paginator = $this->makeEmptyPaginator();
        $new = $original->withPaginator($paginator);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'paginator');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($paginator, $prop->getValue($new));
    }

    public function testWithQRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $qR = $this->makeQR();
        $new = $original->withQR($qR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'qR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($qR, $prop->getValue($new));
    }

    public function testWithSoRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $soR = $this->makeSoR();
        $new = $original->withSoR($soR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'soR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($soR, $prop->getValue($new));
    }

    public function testWithSRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $sR = $this->makeSR();
        $new = $original->withSR($sR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'sR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($sR, $prop->getValue($new));
    }

    public function testWithCsrfReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $new = $original->withCsrf('my-token');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'csrf');
        $this->assertSame('', (string) $prop->getValue($original));
        $this->assertSame('my-token', (string) $prop->getValue($new));
    }

    public function testWithDecimalPlacesReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withDecimalPlaces(4);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'decimalPlaces');
        $this->assertSame(2, $prop->getValue($original));
        $this->assertSame(4, $prop->getValue($new));
    }

    public function testWithVisibleReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withVisible(true);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'visible');
        $this->assertFalse($prop->getValue($original));
        $this->assertTrue($prop->getValue($new));
    }

    public function testWithGroupByReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withGroupBy('status');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'groupBy');
        $this->assertSame('none', $prop->getValue($original));
        $this->assertSame('status', $prop->getValue($new));
    }

    public function testWithClientCountReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withClientCount(7);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'clientCount');
        $this->assertSame(0, $prop->getValue($original));
        $this->assertSame(7, $prop->getValue($new));
    }

    public function testWithGridSummaryReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withGridSummary('Showing 1–10 of 50');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'gridSummary');
        $this->assertSame('', $prop->getValue($original));
        $this->assertSame('Showing 1–10 of 50', $prop->getValue($new));
    }

    public function testWithSortStringReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withSortString('id');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'sortString');
        $this->assertSame('-id', $prop->getValue($original));
        $this->assertSame('id', $prop->getValue($new));
    }

    public function testWithOptionsDataClientsDropdownFilterReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withOptionsDataClientsDropdownFilter(['Alice' => 'Alice']);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'optionsDataClientsDropdownFilter');
        $this->assertSame([], $prop->getValue($original));
        $this->assertSame(['Alice' => 'Alice'], $prop->getValue($new));
    }

    public function testWithOptionsDataStatusDropDownFilterReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new = $original->withOptionsDataStatusDropDownFilter([1 => 'Draft', 2 => 'Sent']);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(QuotesListWidget::class, 'optionsDataStatusDropDownFilter');
        $this->assertSame([], $prop->getValue($original));
        $this->assertSame([1 => 'Draft', 2 => 'Sent'], $prop->getValue($new));
    }

    // -----------------------------------------------------------------------
    // Section 2 — CSRF type widening
    // withCsrf() accepts both string and Stringable.  The view renderer injects
    // a Yiisoft\Yii\View\Renderer\Csrf object (Stringable); the trait path
    // passes (string) cast from the request body.
    // -----------------------------------------------------------------------

    public function testWithCsrfAcceptsRawString(): void
    {
        $widget = $this->makeWidget()->withCsrf('raw-token-value');

        $prop = new \ReflectionProperty(QuotesListWidget::class, 'csrf');
        $this->assertSame('raw-token-value', (string) $prop->getValue($widget));
    }

    public function testWithCsrfAcceptsStringableObject(): void
    {
        $stringable = new class ('view-csrf-token') implements \Stringable {
            public function __construct(private readonly string $value) {}
            public function __toString(): string { return $this->value; }
        };

        $widget = $this->makeWidget()->withCsrf($stringable);

        $prop = new \ReflectionProperty(QuotesListWidget::class, 'csrf');
        $this->assertSame('view-csrf-token', (string) $prop->getValue($widget));
    }

    // -----------------------------------------------------------------------
    // Section 5 — makeGroupValueResolver
    // The private closure must map every documented groupBy key to the correct
    // value extracted from the Quote object.
    // -----------------------------------------------------------------------

    /** Resolve a group key for a quote using the widget's private resolver. */
    private function resolveGroup(string $groupBy, Quote $quote, ?QR $qR = null): string
    {
        $qR ??= $this->makeQR();
        /** @var \Closure(Quote): string $resolver */
        $resolver = $this->callPrivate('makeGroupValueResolver', $qR, $groupBy);
        return $resolver($quote);
    }

    public function testGroupResolverReturnsClientFullName(): void
    {
        $quote = $this->makeQuoteMock(clientFullName: 'Jane Smith');
        $this->assertSame('Jane Smith', $this->resolveGroup('client', $quote));
    }

    public function testGroupResolverFallsBackToUnknownClientWhenClientIsNull(): void
    {
        $quote = $this->createStub(Quote::class);
        $quote->method('getClient')->willReturn(null);
        $quote->method('getQuoteAmount')->willReturn(null);
        $quote->method('getDateCreated')->willReturn(new DateTimeImmutable('2025-01-01'));
        $quote->method('reqStatusId')->willReturn(1);

        $this->assertSame('Unknown Client', $this->resolveGroup('client', $quote));
    }

    /**
     * The 'status' branch calls $qR->getSpecificStatusArrayLabel().
     * A bare QR instance (no ORM connection) with translator injected via
     * reflection is sufficient — getStatuses() only uses $this->translator.
     * The test translator returns the translation key as-is, so status 3
     * ('viewed' translation key) maps to the string 'viewed'.
     */
    public function testGroupResolverReturnsStatusLabel(): void
    {
        $qR    = $this->makeQRWithTranslator();
        $quote = $this->makeQuoteMock(statusId: 3);

        $result = $this->resolveGroup('status', $quote, $qR);

        $this->assertSame('viewed', $result);
    }

    public function testGroupResolverReturnsYearMonth(): void
    {
        $quote = $this->makeQuoteMock(dateCreated: '2025-11-05');
        $this->assertSame('2025-11', $this->resolveGroup('month', $quote));
    }

    public function testGroupResolverReturnsYear(): void
    {
        $quote = $this->makeQuoteMock(dateCreated: '2025-11-05');
        $this->assertSame('2025', $this->resolveGroup('year', $quote));
    }

    public function testGroupResolverReturnsFullDate(): void
    {
        $quote = $this->makeQuoteMock(dateCreated: '2025-11-05');
        $this->assertSame('2025-11-05', $this->resolveGroup('date', $quote));
    }

    public function testGroupResolverReturnsClientGroupName(): void
    {
        $quote = $this->makeQuoteMock(clientGroup: 'Premium');
        $this->assertSame('Premium', $this->resolveGroup('client_group', $quote));
    }

    public function testGroupResolverFallsBackToNoGroupWhenClientGroupIsNull(): void
    {
        $quote = $this->makeQuoteMock(clientGroup: null);
        $this->assertSame('No Group', $this->resolveGroup('client_group', $quote));
    }

    public function testGroupResolverAmountRangeLow(): void
    {
        $quote = $this->makeQuoteMock(total: 50.00);
        $this->assertSame('< $100', $this->resolveGroup('amount_range', $quote));
    }

    public function testGroupResolverAmountRangeLowerMid(): void
    {
        $quote = $this->makeQuoteMock(total: 250.00);
        $this->assertSame('$100 - $500', $this->resolveGroup('amount_range', $quote));
    }

    public function testGroupResolverAmountRangeUpperMid(): void
    {
        $quote = $this->makeQuoteMock(total: 750.00);
        $this->assertSame('$500 - $1000', $this->resolveGroup('amount_range', $quote));
    }

    public function testGroupResolverAmountRangeHigh(): void
    {
        $quote = $this->makeQuoteMock(total: 5000.00);
        $this->assertSame('> $1000', $this->resolveGroup('amount_range', $quote));
    }

    public function testGroupResolverReturnsNoGroupForUnknownKey(): void
    {
        $quote = $this->makeQuoteMock();
        $this->assertSame('No Group', $this->resolveGroup('not_a_real_key', $quote));
    }

    // -----------------------------------------------------------------------
    // Section 5 — computeGroupTotals
    // Aggregates count and amount per group across one paginator page.
    // IterableDataReader wraps in-memory Quote mocks — no database needed.
    // -----------------------------------------------------------------------

    public function testComputeGroupTotalsReturnsEmptyArrayWhenPaginatorIsEmpty(): void
    {
        $paginator = $this->makeEmptyPaginator();
        $getGroupValue = static fn(Quote $q): string => 'unused';

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $this->callPrivate('computeGroupTotals', $paginator, $getGroupValue);

        $this->assertSame([], $result);
    }

    public function testComputeGroupTotalsAggregatesSingleGroup(): void
    {
        $q1 = $this->makeQuoteMock(total: 100.00);
        $q2 = $this->makeQuoteMock(total: 200.00);
        $paginator = new OffsetPaginator(new IterableDataReader([$q1, $q2]));
        $getGroupValue = static fn(Quote $q): string => 'All';

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $this->callPrivate('computeGroupTotals', $paginator, $getGroupValue);

        $this->assertCount(1, $result);
        $this->assertSame(2,      $result['All']['count']);
        $this->assertSame(300.00, $result['All']['total']);
    }

    public function testComputeGroupTotalsSeparatesDistinctGroups(): void
    {
        $q1 = $this->makeQuoteMock(clientFullName: 'Alice', total: 100.00);
        $q2 = $this->makeQuoteMock(clientFullName: 'Bob',   total: 200.00);
        $q3 = $this->makeQuoteMock(clientFullName: 'Alice', total: 150.00);

        $paginator     = new OffsetPaginator(new IterableDataReader([$q1, $q2, $q3]));
        $widget        = $this->makeWidget();
        $qR            = $this->makeQR();
        /** @var \Closure(Quote): string $getGroupValue */
        $getGroupValue = $this->callPrivateOn($widget, 'makeGroupValueResolver', $qR, 'client');

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $this->callPrivateOn($widget, 'computeGroupTotals', $paginator, $getGroupValue);

        $this->assertCount(2, $result);
        $this->assertSame(2,      $result['Alice']['count']);
        $this->assertSame(250.00, $result['Alice']['total']);
        $this->assertSame(1,      $result['Bob']['count']);
        $this->assertSame(200.00, $result['Bob']['total']);
    }

    public function testComputeGroupTotalsHandlesNullQuoteAmount(): void
    {
        $quote = $this->createStub(Quote::class);
        $quote->method('getClient')->willReturn(null);
        $quote->method('getQuoteAmount')->willReturn(null);
        $quote->method('getDateCreated')->willReturn(new DateTimeImmutable('2025-01-01'));
        $quote->method('reqStatusId')->willReturn(1);

        $paginator     = new OffsetPaginator(new IterableDataReader([$quote]));
        $getGroupValue = static fn(Quote $q): string => 'NullAmounts';

        /** @var array<string, array{count: int, total: float}> $result */
        $result = $this->callPrivate('computeGroupTotals', $paginator, $getGroupValue);

        $this->assertSame(1,    $result['NullAmounts']['count']);
        $this->assertSame(0.00, $result['NullAmounts']['total']);
    }
}
