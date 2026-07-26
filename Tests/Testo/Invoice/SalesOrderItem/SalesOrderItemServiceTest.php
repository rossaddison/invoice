<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrderItem;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge;
use App\Infrastructure\Persistence\SalesOrderItemAmount\SalesOrderItemAmount;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderItem\SalesOrderItemService;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as SoIAS;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Covers every public method of SalesOrderItemService, including the
 * previously-untestable repository-touching ones (save-and-delete methods):
 * the item-persist flows used by quote-to-salesorder conversion and the
 * salesorderitem edit form, the peppol id savers, the tax-rate percentage
 * lookup, the sales-order-item-amount recompute/save, and item deletion.
 */
#[Test]
final class SalesOrderItemServiceTest
{
    /** @param list<SalesOrderItemAllowanceCharge> $items */
    private function readerYielding(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        /** @var \Mockery\Expectation $e */
        $e = $reader->shouldReceive('getIterator');
        $e->andReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    private function makeService(
        ?ACSOIR $acsoiR = null,
        ?SOIR $repository = null,
        ?SOR $soR = null,
        ?TRR $trR = null,
        ?PR $pR = null,
        ?TaskR $taskR = null,
    ): SalesOrderItemService {
        $acsoiR = $acsoiR ?? m::mock(ACSOIR::class);
        $repository = $repository ?? m::mock(SOIR::class);
        $soR = $soR ?? m::mock(SOR::class);
        $trR = $trR ?? m::mock(TRR::class);
        $pR = $pR ?? m::mock(PR::class);
        $taskR = $taskR ?? m::mock(TaskR::class);
        return new SalesOrderItemService($acsoiR, $repository, $soR, $trR, $pR, $taskR);
    }

    public function addSoItemProductTaskWithProductAndTaskSetsAllFieldsAndSaves(): void
    {
        $model = new SalesOrderItem();
        $array = [
            'tax_rate_id' => 3,
            'product_id' => 7,
            'task_id' => 9,
            'quantity' => 3,
            'price' => 10.5,
            'discount_amount' => 2.0,
            'order' => 5,
            'product_unit_id' => 4,
        ];

        $salesOrder = new SalesOrder();
        $salesOrder->setId(55);
        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(55)->andReturn($salesOrder);

        $trR = m::mock(TRR::class);
        $taxRate = new TaxRate();
        $taxRate->setTaxRateId(3);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(3)->andReturn($taxRate);

        $constructorProduct = new Product();
        $constructorProduct->setId(7);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(7)->andReturn($constructorProduct);

        $constructorTask = new Task();
        $constructorTask->setId(9);
        $taskRCtor = m::mock(TaskR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $taskRCtor->shouldReceive('repoTaskquery');
        $e4->once()->with(9)->andReturn($constructorTask);

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        $methodProduct = new Product(product_name: 'Widget', product_description: 'A nice widget');
        $pr = m::mock(PR::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $pr->shouldReceive('repoProductquery');
        $e6->once()->with(7)->andReturn($methodProduct);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $pr->shouldReceive('repoCount');
        $e7->once()->with(7)->andReturn(2);

        $methodTask = new Task(name: 'Task Nine', description: 'Task description');
        $taskR = m::mock(TaskR::class);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $taskR->shouldReceive('repoTaskquery');
        $e8->once()->with(9)->andReturn($methodTask);
        /** @var \Mockery\Expectation $e9 */
        $e9 = $taskR->shouldReceive('repoCount');
        $e9->once()->with(9)->andReturn(1);

        $unit = new Unit(unit_name: 'Box');
        $unit->setId(4);
        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e10 */
        $e10 = $uR->shouldReceive('repoUnitquery');
        $e10->once()->with(4)->andReturn($unit);

        $translator = m::mock(Translator::class);
        $translator->shouldNotReceive('translate');

        $result = $service->addSoItemProductTask($model, $array, '55', $pr, $taskR, $uR, $translator);

        Assert::same($model, $result);
        Assert::same(55, $model->reqSalesOrderId());
        Assert::same(3, $model->getTaxRateId());
        Assert::same(7, $model->reqProductId());
        Assert::same(9, $model->reqTaskId());
        Assert::same(3.0, $model->getQuantity());
        Assert::same(10.5, $model->getPrice());
        Assert::same(2.0, $model->getDiscountAmount());
        Assert::same(5, $model->getOrder());
        Assert::same('Task Nine', $model->getName());
        Assert::same('Task description', $model->getDescription());
        Assert::same('Box', $model->getProductUnit());
        Assert::same(4, $model->getProductUnitId());
    }

    public function addSoItemProductTaskUsesDefaultsWhenOptionalFieldsMissing(): void
    {
        $model = new SalesOrderItem();
        $array = ['product_unit_id' => 0];

        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(10)->andReturn(null);

        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');

        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        $pr = m::mock(PR::class);
        $pr->shouldNotReceive('repoProductquery');
        $taskR = m::mock(TaskR::class);
        $taskR->shouldNotReceive('repoTaskquery');

        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $uR->shouldReceive('repoUnitquery');
        $e3->once()->with(0)->andReturn(null);

        $translator = m::mock(Translator::class);
        $translator->shouldNotReceive('translate');

        $result = $service->addSoItemProductTask($model, $array, '10', $pr, $taskR, $uR, $translator);

        Assert::same($model, $result);
        Assert::same(0.0, $model->getQuantity());
        Assert::same(0.00, $model->getPrice());
        Assert::same(0.00, $model->getDiscountAmount());
        Assert::same(0, $model->getOrder());
        Assert::same(0, $model->getProductUnitId());
        Assert::same('', $model->getProductUnit());
    }

    public function addSoItemProductTaskDescriptionFromArrayOverridesProductDescription(): void
    {
        $model = new SalesOrderItem();
        $array = ['product_id' => 20, 'description' => 'Custom desc', 'product_unit_id' => 0];

        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        $constructorProduct = new Product();
        $constructorProduct->setId(20);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(20)->andReturn($constructorProduct);

        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, pR: $pR, trR: $trR, taskR: $taskRCtor, repository: $repository);

        $methodProduct = new Product(product_name: 'Gadget', product_description: 'Gadget description');
        $pr = m::mock(PR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $pr->shouldReceive('repoProductquery');
        $e4->once()->with(20)->andReturn($methodProduct);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $pr->shouldReceive('repoCount');
        $e5->once()->with(20)->andReturn(5);

        $taskR = m::mock(TaskR::class);
        $taskR->shouldNotReceive('repoTaskquery');

        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $uR->shouldReceive('repoUnitquery');
        $e6->once()->with(0)->andReturn(null);

        $translator = m::mock(Translator::class);
        $translator->shouldNotReceive('translate');

        $service->addSoItemProductTask($model, $array, '1', $pr, $taskR, $uR, $translator);

        Assert::same('Gadget', $model->getName());
        Assert::same('Custom desc', $model->getDescription());
        Assert::same(20, $model->reqProductId());
    }

    public function addSoItemProductTaskProductNotFoundSkipsNameAndDescription(): void
    {
        $model = new SalesOrderItem();
        $array = ['product_id' => 99, 'product_unit_id' => 0];

        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(99)->andReturn(null);

        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, pR: $pR, trR: $trR, taskR: $taskRCtor, repository: $repository);

        $pr = m::mock(PR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $pr->shouldReceive('repoProductquery');
        $e4->once()->with(99)->andReturn(null);
        $pr->shouldNotReceive('repoCount');

        $taskR = m::mock(TaskR::class);
        $taskR->shouldNotReceive('repoTaskquery');

        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $uR->shouldReceive('repoUnitquery');
        $e5->once()->with(0)->andReturn(null);

        $translator = m::mock(Translator::class);
        $translator->shouldNotReceive('translate');

        $service->addSoItemProductTask($model, $array, '1', $pr, $taskR, $uR, $translator);

        Assert::same('', $model->getName());
        Assert::same('', $model->getDescription());
    }

    public function addSoItemProductTaskTaskDescriptionFallsBackToTranslatorWhenEmpty(): void
    {
        $model = new SalesOrderItem();
        $array = ['task_id' => 30, 'product_unit_id' => 0];

        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        $constructorTask = new Task();
        $constructorTask->setId(30);
        $taskRCtor = m::mock(TaskR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $taskRCtor->shouldReceive('repoTaskquery');
        $e2->once()->with(30)->andReturn($constructorTask);

        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, taskR: $taskRCtor, pR: $pR, trR: $trR, repository: $repository);

        $pr = m::mock(PR::class);
        $pr->shouldNotReceive('repoProductquery');

        $methodTask = new Task(name: 'Cleanup', description: '');
        $taskR = m::mock(TaskR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $taskR->shouldReceive('repoTaskquery');
        $e4->once()->with(30)->andReturn($methodTask);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $taskR->shouldReceive('repoCount');
        $e5->once()->with(30)->andReturn(2);

        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $uR->shouldReceive('repoUnitquery');
        $e6->once()->with(0)->andReturn(null);

        $translator = m::mock(Translator::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $translator->shouldReceive('translate');
        $e7->once()->with('not.available')->andReturn('N/A');

        $service->addSoItemProductTask($model, $array, '1', $pr, $taskR, $uR, $translator);

        Assert::same('Cleanup', $model->getName());
        Assert::same('N/A', $model->getDescription());
        Assert::same(30, $model->reqTaskId());
    }

    public function saveSalesOrderItemFullFlowReturnsTaxRateId(): void
    {
        $model = new SalesOrderItem();
        $array = [
            'product_id' => 12,
            'quantity' => 4,
            'price' => 20.0,
            'discount_amount' => 1.5,
            'peppol_po_itemid' => 'ITEM-1',
            'peppol_po_lineid' => 'LINE-1',
            'order' => 2,
            'tax_rate_id' => 8,
            'product_unit_id' => 6,
        ];

        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(77)->andReturn(null);

        $taxRate = new TaxRate();
        $taxRate->setTaxRateId(8);
        $trR = m::mock(TRR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(8)->andReturn($taxRate);

        $constructorProduct = new Product();
        $constructorProduct->setId(12);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(12)->andReturn($constructorProduct);

        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $repository->shouldReceive('save');
        $e4->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        $methodProduct = new Product(product_name: 'Thing', product_description: 'Thing description');
        $methodProduct->setId(12);
        $pr = m::mock(PR::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $pr->shouldReceive('repoProductquery');
        $e5->once()->with(12)->andReturn($methodProduct);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $pr->shouldReceive('repoCount');
        $e6->once()->with(12)->andReturn(1);

        $unit = new Unit(unit_name: 'Each');
        $unit->setId(6);
        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $uR->shouldReceive('repoUnitquery');
        $e7->once()->with(6)->andReturn($unit);

        $result = $service->saveSalesOrderItem($model, $array, '77', $pr, $uR);

        Assert::same(8, $result);
        Assert::same(4.0, $model->getQuantity());
        Assert::same(20.0, $model->getPrice());
        Assert::same(1.5, $model->getDiscountAmount());
        Assert::same('ITEM-1', $model->getPeppolPoItemid());
        Assert::same('LINE-1', $model->getPeppolPoLineid());
        Assert::same(2, $model->getOrder());
        Assert::same('Thing', $model->getName());
        Assert::same('Thing description', $model->getDescription());
        Assert::same('Each', $model->getProductUnit());
        Assert::same(6, $model->getProductUnitId());
        Assert::same(12, $model->reqProductId());
        Assert::same(8, $model->getTaxRateId());
    }

    public function saveSalesOrderItemDefaultsWhenOptionalFieldsMissing(): void
    {
        $model = new SalesOrderItem();
        $array = ['product_unit_id' => 0];

        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(3)->andReturn(null);

        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        $pr = m::mock(PR::class);
        $pr->shouldNotReceive('repoProductquery');
        $pr->shouldNotReceive('repoCount');

        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $uR->shouldReceive('repoUnitquery');
        $e3->once()->with(0)->andReturn(null);

        $result = $service->saveSalesOrderItem($model, $array, '3', $pr, $uR);

        Assert::same(0, $result);
        Assert::same(1.00, $model->getQuantity());
        Assert::same(0.00, $model->getPrice());
        Assert::same(0.00, $model->getDiscountAmount());
        Assert::same('', $model->getPeppolPoItemid());
        Assert::same('', $model->getPeppolPoLineid());
        Assert::null($model->getOrder());
        Assert::same(0, $model->getProductUnitId());
        Assert::same('', $model->getProductUnit());
    }

    public function saveSalesOrderItemProductNotFoundSkipsProductDetails(): void
    {
        $model = new SalesOrderItem();
        $array = ['product_id' => 55, 'product_unit_id' => 0];

        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(55)->andReturn(null);

        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, pR: $pR, trR: $trR, taskR: $taskRCtor, repository: $repository);

        $pr = m::mock(PR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $pr->shouldReceive('repoProductquery');
        $e4->once()->with(55)->andReturn(null);
        $pr->shouldNotReceive('repoCount');

        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $uR->shouldReceive('repoUnitquery');
        $e5->once()->with(0)->andReturn(null);

        $result = $service->saveSalesOrderItem($model, $array, '1', $pr, $uR);

        Assert::same(0, $result);
        Assert::same('', $model->getName());
    }

    public function savePeppolPoItemidSetsValueWhenProvided(): void
    {
        $model = new SalesOrderItem();
        $array = ['peppol_po_itemid' => 'ABC123'];

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model)->andReturn(null);

        $service = $this->makeService(repository: $repository);

        $result = $service->savePeppolPoItemid($model, $array);

        Assert::same('ABC123', $model->getPeppolPoItemid());
        // The repository's save() is declared void, so its return value is
        // always null and this ternary always evaluates to false. See
        // POSSIBLE ISSUE note in the final report.
        Assert::false($result);
    }

    public function savePeppolPoItemidSkipsSetWhenNotProvided(): void
    {
        $model = new SalesOrderItem();
        $array = [];

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model)->andReturn(null);

        $service = $this->makeService(repository: $repository);

        $result = $service->savePeppolPoItemid($model, $array);

        Assert::same('', $model->getPeppolPoItemid());
        Assert::false($result);
    }

    public function savePeppolPoLineidSetsValueWhenProvided(): void
    {
        $model = new SalesOrderItem();
        $array = ['peppol_po_lineid' => 'XYZ789'];

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model)->andReturn(null);

        $service = $this->makeService(repository: $repository);

        $result = $service->savePeppolPoLineid($model, $array);

        Assert::same('XYZ789', $model->getPeppolPoLineid());
        Assert::false($result);
    }

    public function savePeppolPoLineidSkipsSetWhenNotProvided(): void
    {
        $model = new SalesOrderItem();
        $array = [];

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model)->andReturn(null);

        $service = $this->makeService(repository: $repository);

        $result = $service->savePeppolPoLineid($model, $array);

        Assert::same('', $model->getPeppolPoLineid());
        Assert::false($result);
    }

    public function taxratePercentageReturnsPercentWhenFound(): void
    {
        $taxRate = new TaxRate(tax_rate_percent: 17.5);
        $taxRate->setTaxRateId(5);

        $trr = m::mock(TRR::class);
        /** @var \Mockery\Expectation $e */
        $e = $trr->shouldReceive('repoTaxRatequery');
        $e->once()->with(5)->andReturn($taxRate);

        $service = $this->makeService();

        Assert::same(17.5, $service->taxratePercentage(5, $trr));
    }

    public function taxratePercentageReturnsNullWhenNotFound(): void
    {
        $trr = m::mock(TRR::class);
        /** @var \Mockery\Expectation $e */
        $e = $trr->shouldReceive('repoTaxRatequery');
        $e->once()->with(6)->andReturn(null);

        $service = $this->makeService();

        Assert::null($service->taxratePercentage(6, $trr));
    }

    public function saveSalesOrderItemAmountCreatesNewAmountWithChargesAllowancesAndTax(): void
    {
        $chargeAc = new AllowanceCharge(identifier: true);
        $chargeItem = new SalesOrderItemAllowanceCharge(amount: 3.0);
        $chargeItem->setAllowanceCharge($chargeAc);

        $allowanceAc = new AllowanceCharge(identifier: false);
        $allowanceItem = new SalesOrderItemAllowanceCharge(amount: 1.0);
        $allowanceItem->setAllowanceCharge($allowanceAc);

        $acsoiR = m::mock(ACSOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(5)->andReturn($this->readerYielding([$chargeItem, $allowanceItem]));

        $service = $this->makeService(acsoiR: $acsoiR);

        $soiar = m::mock(SoIAR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soiar->shouldReceive('repoCount');
        $e2->once()->with(5)->andReturn(0);
        $soiar->shouldNotReceive('repoSalesOrderItemAmountquery');

        // quantity 2.0 x price 10.0 = subtotal 20.0; +charge 3.0 -allowance 1.0
        // = 22.0; discount 2.0 x 1.0 = 2.0; tax = (22.0 - 2.0) x 10% = 2.0;
        // total = 22.0 - 2.0 + 2.0 = 22.0
        $expectedArray = [
            'sales_order_item_id' => 5,
            'charge' => 3.0,
            'allowance' => 1.0,
            'discount' => 2.0,
            'subtotal' => 22.0,
            'taxtotal' => 2.0,
            'total' => 22.0,
        ];

        $soias = m::mock(SoIAS::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soias->shouldReceive('saveSalesOrderItemAmountNoForm');
        $e3->once()->with(
            m::on(fn (mixed $arg): bool => $arg instanceof SalesOrderItemAmount && !$arg->hasIdentity()),
            $expectedArray
        );

        $service->saveSalesOrderItemAmount(5, 2.0, 10.0, 1.0, 10.0, $soiar, $soias);
    }

    public function saveSalesOrderItemAmountTaxTotalZeroWhenPercentageNull(): void
    {
        $acsoiR = m::mock(ACSOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(6)->andReturn($this->readerYielding([]));

        $service = $this->makeService(acsoiR: $acsoiR);

        $soiar = m::mock(SoIAR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soiar->shouldReceive('repoCount');
        $e2->once()->with(6)->andReturn(0);

        $expectedArray = [
            'sales_order_item_id' => 6,
            'charge' => 0.0,
            'allowance' => 0.0,
            'discount' => 0.0,
            'subtotal' => 5.0,
            'taxtotal' => 0.00,
            'total' => 5.0,
        ];

        $soias = m::mock(SoIAS::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soias->shouldReceive('saveSalesOrderItemAmountNoForm');
        $e3->once()->with(
            m::on(fn (mixed $arg): bool => $arg instanceof SalesOrderItemAmount),
            $expectedArray
        );

        $service->saveSalesOrderItemAmount(6, 1.0, 5.0, 0.0, null, $soiar, $soias);
    }

    public function saveSalesOrderItemAmountUpdatesExistingAmountWhenFound(): void
    {
        $acsoiR = m::mock(ACSOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(7)->andReturn($this->readerYielding([]));

        $service = $this->makeService(acsoiR: $acsoiR);

        $existingAmount = new SalesOrderItemAmount();
        $existingAmount->setId(99);

        $soiar = m::mock(SoIAR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soiar->shouldReceive('repoCount');
        $e2->once()->with(7)->andReturn(2);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soiar->shouldReceive('repoSalesOrderItemAmountquery');
        $e3->once()->with(7)->andReturn($existingAmount);

        $expectedArray = [
            'sales_order_item_id' => 7,
            'charge' => 0.0,
            'allowance' => 0.0,
            'discount' => 1.0,
            'subtotal' => 6.0,
            'taxtotal' => 0.5,
            'total' => 5.5,
        ];

        $soias = m::mock(SoIAS::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $soias->shouldReceive('saveSalesOrderItemAmountNoForm');
        $e4->once()->with($existingAmount, $expectedArray);

        $service->saveSalesOrderItemAmount(7, 2.0, 3.0, 0.5, 10.0, $soiar, $soias);
    }

    public function saveSalesOrderItemAmountSkipsSaveWhenExistingAmountNotFound(): void
    {
        $acsoiR = m::mock(ACSOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(8)->andReturn($this->readerYielding([]));

        $service = $this->makeService(acsoiR: $acsoiR);

        $soiar = m::mock(SoIAR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soiar->shouldReceive('repoCount');
        $e2->once()->with(8)->andReturn(1);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soiar->shouldReceive('repoSalesOrderItemAmountquery');
        $e3->once()->with(8)->andReturn(null);

        $soias = m::mock(SoIAS::class);
        $soias->shouldNotReceive('saveSalesOrderItemAmountNoForm');

        $service->saveSalesOrderItemAmount(8, 1.0, 1.0, 0.0, null, $soiar, $soias);
    }

    public function deleteSalesOrderItemCallsRepositoryDelete(): void
    {
        $model = new SalesOrderItem();

        $repository = m::mock(SOIR::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService(repository: $repository);

        $service->deleteSalesOrderItem($model);
    }
}
