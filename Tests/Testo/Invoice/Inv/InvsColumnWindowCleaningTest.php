<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\Widget\InvsColumnBuilder;
use App\Invoice\Inv\Widget\InvsColumnParams;
use App\Invoice\Inv\Widget\InvsFilterOptions;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\InvSentLog\InvSentLogRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Worker\WorkerRepository;
use App\Widget\GridComponents;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\DataView\GridView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * A window-cleaning business (business_type setting) has no quotes or sales orders, so the invoice list leaves out the
 * quote link, sales-order link and workflow columns, which would only ever be empty. Any other install keeps them.
 */
#[Test]
final class InvsColumnWindowCleaningTest
{
    /** @return list<ColumnInterface> */
    private function columnsFor(string $businessType): array
    {
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);
        $urlGenerator = TypedMock::of(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturn('/x');

        $settings = TypedMock::of(SettingRepository::class);
        $settings->shouldReceive('getSetting')->andReturnUsing(
            static fn (string $key): string => $key === 'business_type' ? $businessType : '',
        );

        $workers = TypedMock::of(WorkerRepository::class);
        $workers->shouldReceive('findAllActive')->andReturn([]);

        $params = new InvsColumnParams(
            iR: TypedMock::of(InvRepository::class),
            irR: TypedMock::of(InvRecurringRepository::class),
            islR: TypedMock::of(InvSentLogRepository::class),
            sR: $settings,
            wR: $workers,
            csR: TypedMock::of(CategorySecondaryRepository::class),
            dwR: TypedMock::of(DwellingRepository::class),
            dp: 2,
            totalAmount: 0.0,
            totalPaid: 0.0,
            totalBalance: 0.0,
            qR: TypedMock::of(QuoteRepository::class),
            soR: TypedMock::of(SalesOrderRepository::class),
            dlR: TypedMock::of(DeliveryLocationRepository::class),
        );

        $builder = new InvsColumnBuilder(
            $translator,
            $urlGenerator,
            new GridComponents(new CurrentRoute(), new Translator()),
            new InvsFilterOptions(),
            true,
            true,
        );

        return array_values($builder->buildColumns($params));
    }

    /** @param list<ColumnInterface> $columns */
    private function hasProperty(array $columns, string $property): bool
    {
        foreach ($columns as $column) {
            if ($column instanceof DataColumn && $column->property === $property) {
                return true;
            }
        }
        return false;
    }

    /** @param list<ColumnInterface> $columns */
    private function hasWorkflowColumn(array $columns): bool
    {
        foreach ($columns as $column) {
            if ($column instanceof DataColumn && $column->isVisible() && str_contains((string) $column->header, '🔀')) {
                return true;
            }
        }
        return false;
    }

    public function aStandardBusinessKeepsTheQuoteSalesOrderAndWorkflowColumns(): void
    {
        $columns = $this->columnsFor('standard');

        Assert::true($this->hasProperty($columns, 'quote_id'));
        Assert::true($this->hasProperty($columns, 'so_id'));
        Assert::true($this->hasWorkflowColumn($columns));
    }

    public function anInstallWithNoBusinessTypeSavedYetKeepsThemToo(): void
    {
        $columns = $this->columnsFor('');

        Assert::true($this->hasProperty($columns, 'quote_id'));
        Assert::true($this->hasProperty($columns, 'so_id'));
    }

    public function aWindowCleaningBusinessLosesThem(): void
    {
        $columns = $this->columnsFor('window_cleaning');

        Assert::false($this->hasProperty($columns, 'quote_id'));
        Assert::false($this->hasProperty($columns, 'so_id'));
        Assert::false($this->hasWorkflowColumn($columns));
    }

    public function aWindowCleaningBusinessStillGetsTheDeliveryLocationAndInvoiceColumns(): void
    {
        $columns = $this->columnsFor('window_cleaning');

        Assert::true($this->hasProperty($columns, 'delivery_location_id'));
        Assert::true(count($columns) > 5);
    }
}
