<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrder;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrder\Widget\SalesOrdersColumnBuilder;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\Setting\SettingRepository as SR;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The Status cell of the sales-order list. For "Invoice Generate" (status 7) it wraps the badge in a link to turn the
 * order into an invoice. Html::tag() escapes its content, so the badge, already rendered markup, was escaped and the
 * cell showed the literal text <span class="badge text-bg-warning">Invoice Generate</span> instead of a badge.
 */
#[Test]
final class SalesOrdersStatusColumnTest
{
    private function cell(?int $statusId): string
    {
        $urlGenerator = TypedMock::of(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturnUsing(
            static fn (string $name, array $arguments = []): string => '/' . $name . '/' . (string) ($arguments['id'] ?? ''),
        );
        $translator = TypedMock::of(TranslatorInterface::class);
        $statuses = TypedMock::of(SoR::class);
        $statuses->shouldReceive('getSpecificStatusArrayLabel')->andReturnUsing(
            static fn (string $key): string => $key === '7' ? 'Invoice Generate' : 'Draft',
        );
        $statuses->shouldReceive('getSpecificStatusArrayClass')->andReturnUsing(
            static fn (int $status): string => $status === 7 ? 'warning' : 'secondary',
        );

        $builder = new SalesOrdersColumnBuilder(
            $urlGenerator,
            $translator,
            true,
            null,
            TypedMock::of(SR::class),
            $statuses,
            TypedMock::of(SoAR::class),
            [],
        );

        $order = new SalesOrder();
        $order->setId(17);
        if ($statusId !== null) {
            $order->setStatusId($statusId);
        }

        $content = $builder->buildStatusColumn()->content;
        if (!$content instanceof \Closure) {
            throw new \LogicException('the status column has no content closure');
        }
        return (string) $content($order);
    }

    public function invoiceGenerateIsABadgeInsideALinkNotEscapedText(): void
    {
        $html = $this->cell(7);

        Assert::true(str_contains($html, '<span class="badge text-bg-warning">Invoice Generate</span>'));
        Assert::true(str_starts_with($html, '<a '));
        Assert::true(str_contains($html, 'href="/salesorder/soToInvoice/17"'));
        Assert::false(str_contains($html, '&lt;'));
        Assert::false(str_contains($html, '&gt;'));
    }

    public function aDraftOrderIsJustTheBadge(): void
    {
        $html = $this->cell(1);

        Assert::same($html, '<span class="badge text-bg-secondary">Draft</span>');
    }

    public function anOrderWithNoStatusGivesAnEmptySpan(): void
    {
        Assert::same($this->cell(null), '<span></span>');
    }

    public function theColumnLeavesItsMarkupAloneBecauseTheCellsAreBuiltAsHtml(): void
    {
        $builder = new SalesOrdersColumnBuilder(
            TypedMock::of(UrlGeneratorInterface::class),
            TypedMock::of(TranslatorInterface::class),
            true,
            null,
            TypedMock::of(SR::class),
            TypedMock::of(SoR::class),
            TypedMock::of(SoAR::class),
            [],
        );

        Assert::same($builder->buildStatusColumn()->encodeContent, false);
    }
}
