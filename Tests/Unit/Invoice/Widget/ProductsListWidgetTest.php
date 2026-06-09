<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Widget;

use App\Invoice\Product\Widget\ProductsListWidget;
use App\Invoice\ProductClient\ProductClientRepository as PcR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Widget\GridComponents;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Unit tests for ProductsListWidget.
 *
 * Covers:
 *  - render() guard returns '' when any required dep (paginator/sR/pcR) is absent
 *  - Immutable setter pattern — every with*() returns a distinct clone
 *  - CSRF type widening — withCsrf() accepts string and Stringable
 *
 * PcR / SR are final Cycle ORM repositories; type-safe instances are created
 * via ReflectionClass::newInstanceWithoutConstructor() (no DB needed).
 * ProductsListWidget has no grouping logic, so no group resolver tests needed.
 */
final class ProductsListWidgetTest extends TestCase
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

    private function makeWidget(): ProductsListWidget
    {
        return new ProductsListWidget(
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

    private function makePcR(): PcR
    {
        /** @var PcR */
        return (new \ReflectionClass(PcR::class))->newInstanceWithoutConstructor();
    }

    private function makeSR(): SR
    {
        /** @var SR */
        return (new \ReflectionClass(SR::class))->newInstanceWithoutConstructor();
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
            ->withSR($this->makeSR())
            ->withPcR($this->makePcR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenSRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withPcR($this->makePcR());

        $this->assertSame('', $widget->render());
    }

    public function testRenderReturnsEmptyWhenPcRNotSet(): void
    {
        $widget = $this->makeWidget()
            ->withPaginator($this->makeEmptyPaginator())
            ->withSR($this->makeSR());

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
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'paginator');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($paginator, $prop->getValue($new));
    }

    public function testWithSRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $sR       = $this->makeSR();
        $new      = $original->withSR($sR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'sR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($sR, $prop->getValue($new));
    }

    public function testWithPcRReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $pcR      = $this->makePcR();
        $new      = $original->withPcR($pcR);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'pcR');
        $this->assertNull($prop->getValue($original));
        $this->assertSame($pcR, $prop->getValue($new));
    }

    public function testWithCsrfReturnsNewInstanceAndOriginalUnchanged(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withCsrf('my-token');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'csrf');
        $this->assertSame('', (string) $prop->getValue($original));
        $this->assertSame('my-token', (string) $prop->getValue($new));
    }

    public function testWithVisibleReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withVisible(true);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'visible');
        $this->assertFalse($prop->getValue($original));
        $this->assertTrue($prop->getValue($new));
    }

    public function testWithGridSummaryReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withGridSummary('Showing 1–5 of 20');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'gridSummary');
        $this->assertSame('', $prop->getValue($original));
        $this->assertSame('Showing 1–5 of 20', $prop->getValue($new));
    }

    public function testWithSortStringReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withSortString('product_name');

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'sortString');
        $this->assertSame('-id', $prop->getValue($original));
        $this->assertSame('product_name', $prop->getValue($new));
    }

    public function testWithOptionsDataProductsDropdownFilterReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withOptionsDataProductsDropdownFilter(['SKU-001' => 'SKU-001']);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'optionsDataProductsDropdownFilter');
        $this->assertSame([], $prop->getValue($original));
        $this->assertSame(['SKU-001' => 'SKU-001'], $prop->getValue($new));
    }

    public function testWithOptionsDataFamiliesDropdownFilterReturnsNewInstance(): void
    {
        $original = $this->makeWidget();
        $new      = $original->withOptionsDataFamiliesDropdownFilter(['Electronics' => 'Electronics']);

        $this->assertNotSame($original, $new);
        $prop = new \ReflectionProperty(ProductsListWidget::class, 'optionsDataFamiliesDropdownFilter');
        $this->assertSame([], $prop->getValue($original));
        $this->assertSame(['Electronics' => 'Electronics'], $prop->getValue($new));
    }

    // -------------------------------------------------------------------------
    // CSRF type widening
    // -------------------------------------------------------------------------

    public function testWithCsrfAcceptsRawString(): void
    {
        $widget = $this->makeWidget()->withCsrf('raw-token-value');

        $prop = new \ReflectionProperty(ProductsListWidget::class, 'csrf');
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

        $prop = new \ReflectionProperty(ProductsListWidget::class, 'csrf');
        $this->assertSame('view-csrf-token', (string) $prop->getValue($widget));
    }
}
