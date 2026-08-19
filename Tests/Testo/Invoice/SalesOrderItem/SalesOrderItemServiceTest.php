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
        /** @var ACSOIR&m\MockInterface $acsoiR */
        $acsoiR = $acsoiR ?? m::mock(ACSOIR::class);
        /** @var SOIR&m\MockInterface $repository */
        $repository = $repository ?? m::mock(SOIR::class);
        /** @var SOR&m\MockInterface $soR */
        $soR = $soR ?? m::mock(SOR::class);
        /** @var TRR&m\MockInterface $trR */
        $trR = $trR ?? m::mock(TRR::class);
        /** @var PR&m\MockInterface $pR */
        $pR = $pR ?? m::mock(PR::class);
        /** @var TaskR&m\MockInterface $taskR */
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
        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(55)->andReturn($salesOrder);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $taxRate = new TaxRate();
        $taxRate->setTaxRateId(3);
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(3)->andReturn($taxRate);

        $constructorProduct = new Product();
        $constructorProduct->setId(7);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(7)->andReturn($constructorProduct);

        $constructorTask = new Task();
        $constructorTask->setId(9);
        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $e4 = $taskRCtor->shouldReceive('repoTaskquery');
        $e4->once()->with(9)->andReturn($constructorTask);

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        $methodProduct = new Product(product_name: 'Widget', product_description: 'A nice widget');
        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $e6 = $pr->shouldReceive('repoProductquery');
        $e6->once()->with(7)->andReturn($methodProduct);
        $e7 = $pr->shouldReceive('repoCount');
        $e7->once()->with(7)->andReturn(2);

        $methodTask = new Task(name: 'Task Nine', description: 'Task description');
        /** @var TaskR&m\MockInterface $taskR */
        $taskR = m::mock(TaskR::class);
        $e8 = $taskR->shouldReceive('repoTaskquery');
        $e8->once()->with(9)->andReturn($methodTask);
        $e9 = $taskR->shouldReceive('repoCount');
        $e9->once()->with(9)->andReturn(1);

        $unit = new Unit(unit_name: 'Box');
        $unit->setId(4);
        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $e10 = $uR->shouldReceive('repoUnitquery');
        $e10->once()->with(4)->andReturn($unit);

        /** @var Translator&m\MockInterface $translator */
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

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(10)->andReturn(null);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');

        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $pr->shouldNotReceive('repoProductquery');
        /** @var TaskR&m\MockInterface $taskR */
        $taskR = m::mock(TaskR::class);
        $taskR->shouldNotReceive('repoTaskquery');

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $e3 = $uR->shouldReceive('repoUnitquery');
        $e3->once()->with(0)->andReturn(null);

        /** @var Translator&m\MockInterface $translator */
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

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        $constructorProduct = new Product();
        $constructorProduct->setId(20);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(20)->andReturn($constructorProduct);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, pR: $pR, trR: $trR, taskR: $taskRCtor, repository: $repository);

        $methodProduct = new Product(product_name: 'Gadget', product_description: 'Gadget description');
        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $e4 = $pr->shouldReceive('repoProductquery');
        $e4->once()->with(20)->andReturn($methodProduct);
        $e5 = $pr->shouldReceive('repoCount');
        $e5->once()->with(20)->andReturn(5);

        /** @var TaskR&m\MockInterface $taskR */
        $taskR = m::mock(TaskR::class);
        $taskR->shouldNotReceive('repoTaskquery');

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $e6 = $uR->shouldReceive('repoUnitquery');
        $e6->once()->with(0)->andReturn(null);

        /** @var Translator&m\MockInterface $translator */
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

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(99)->andReturn(null);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, pR: $pR, trR: $trR, taskR: $taskRCtor, repository: $repository);

        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $e4 = $pr->shouldReceive('repoProductquery');
        $e4->once()->with(99)->andReturn(null);
        $pr->shouldNotReceive('repoCount');

        /** @var TaskR&m\MockInterface $taskR */
        $taskR = m::mock(TaskR::class);
        $taskR->shouldNotReceive('repoTaskquery');

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $e5 = $uR->shouldReceive('repoUnitquery');
        $e5->once()->with(0)->andReturn(null);

        /** @var Translator&m\MockInterface $translator */
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

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        $constructorTask = new Task();
        $constructorTask->setId(30);
        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $e2 = $taskRCtor->shouldReceive('repoTaskquery');
        $e2->once()->with(30)->andReturn($constructorTask);

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');
        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, taskR: $taskRCtor, pR: $pR, trR: $trR, repository: $repository);

        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $pr->shouldNotReceive('repoProductquery');

        $methodTask = new Task(name: 'Cleanup', description: '');
        /** @var TaskR&m\MockInterface $taskR */
        $taskR = m::mock(TaskR::class);
        $e4 = $taskR->shouldReceive('repoTaskquery');
        $e4->once()->with(30)->andReturn($methodTask);
        $e5 = $taskR->shouldReceive('repoCount');
        $e5->once()->with(30)->andReturn(2);

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $e6 = $uR->shouldReceive('repoUnitquery');
        $e6->once()->with(0)->andReturn(null);

        /** @var Translator&m\MockInterface $translator */
        $translator = m::mock(Translator::class);
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

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(77)->andReturn(null);

        $taxRate = new TaxRate();
        $taxRate->setTaxRateId(8);
        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(8)->andReturn($taxRate);

        $constructorProduct = new Product();
        $constructorProduct->setId(12);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(12)->andReturn($constructorProduct);

        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e4 = $repository->shouldReceive('save');
        $e4->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        $methodProduct = new Product(product_name: 'Thing', product_description: 'Thing description');
        $methodProduct->setId(12);
        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $e5 = $pr->shouldReceive('repoProductquery');
        $e5->once()->with(12)->andReturn($methodProduct);
        $e6 = $pr->shouldReceive('repoCount');
        $e6->once()->with(12)->andReturn(1);

        $unit = new Unit(unit_name: 'Each');
        $unit->setId(6);
        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
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

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(3)->andReturn(null);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');
        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService(soR: $soR, trR: $trR, pR: $pR, taskR: $taskRCtor, repository: $repository);

        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $pr->shouldNotReceive('repoProductquery');
        $pr->shouldNotReceive('repoCount');

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
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

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(1)->andReturn(null);

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(55)->andReturn(null);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        /** @var TaskR&m\MockInterface $taskRCtor */
        $taskRCtor = m::mock(TaskR::class);
        $taskRCtor->shouldNotReceive('repoTaskquery');

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService(soR: $soR, pR: $pR, trR: $trR, taskR: $taskRCtor, repository: $repository);

        /** @var PR&m\MockInterface $pr */
        $pr = m::mock(PR::class);
        $e4 = $pr->shouldReceive('repoProductquery');
        $e4->once()->with(55)->andReturn(null);
        $pr->shouldNotReceive('repoCount');

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
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

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model)->andReturn(null);

        $service = $this->makeService(repository: $repository);

        $result = $service->savePeppolPoItemid($model, $array);

        Assert::same('ABC123', $model->getPeppolPoItemid());
        // The repository's save() is declared void, so its return value is
        // always null and this ternary always evaluates to false. See
        // PR #998 "Possible issues found".
        Assert::false($result);
    }

    public function savePeppolPoItemidSkipsSetWhenNotProvided(): void
    {
        $model = new SalesOrderItem();
        $array = [];

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
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

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
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

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
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

        /** @var TRR&m\MockInterface $trr */
        $trr = m::mock(TRR::class);
        $e = $trr->shouldReceive('repoTaxRatequery');
        $e->once()->with(5)->andReturn($taxRate);

        $service = $this->makeService();

        Assert::same(17.5, $service->taxratePercentage(5, $trr));
    }

    public function taxratePercentageReturnsNullWhenNotFound(): void
    {
        /** @var TRR&m\MockInterface $trr */
        $trr = m::mock(TRR::class);
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

        /** @var ACSOIR&m\MockInterface $acsoiR */
        $acsoiR = m::mock(ACSOIR::class);
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(5)->andReturn($this->readerYielding([$chargeItem, $allowanceItem]));

        $service = $this->makeService(acsoiR: $acsoiR);

        /** @var SoIAR&m\MockInterface $soiar */
        $soiar = m::mock(SoIAR::class);
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

        /** @var SoIAS&m\MockInterface $soias */
        $soias = m::mock(SoIAS::class);
        $e3 = $soias->shouldReceive('saveSalesOrderItemAmountNoForm');
        $e3->once()->with(
            m::on(fn (mixed $arg): bool => $arg instanceof SalesOrderItemAmount && !$arg->hasIdentity()),
            $expectedArray
        );

        $service->saveSalesOrderItemAmount(5, 2.0, 10.0, 1.0, 10.0, $soiar, $soias);
    }

    public function saveSalesOrderItemAmountTaxTotalZeroWhenPercentageNull(): void
    {
        /** @var ACSOIR&m\MockInterface $acsoiR */
        $acsoiR = m::mock(ACSOIR::class);
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(6)->andReturn($this->readerYielding([]));

        $service = $this->makeService(acsoiR: $acsoiR);

        /** @var SoIAR&m\MockInterface $soiar */
        $soiar = m::mock(SoIAR::class);
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

        /** @var SoIAS&m\MockInterface $soias */
        $soias = m::mock(SoIAS::class);
        $e3 = $soias->shouldReceive('saveSalesOrderItemAmountNoForm');
        $e3->once()->with(
            m::on(fn (mixed $arg): bool => $arg instanceof SalesOrderItemAmount),
            $expectedArray
        );

        $service->saveSalesOrderItemAmount(6, 1.0, 5.0, 0.0, null, $soiar, $soias);
    }

    public function saveSalesOrderItemAmountUpdatesExistingAmountWhenFound(): void
    {
        /** @var ACSOIR&m\MockInterface $acsoiR */
        $acsoiR = m::mock(ACSOIR::class);
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(7)->andReturn($this->readerYielding([]));

        $service = $this->makeService(acsoiR: $acsoiR);

        $existingAmount = new SalesOrderItemAmount();
        $existingAmount->setId(99);

        /** @var SoIAR&m\MockInterface $soiar */
        $soiar = m::mock(SoIAR::class);
        $e2 = $soiar->shouldReceive('repoCount');
        $e2->once()->with(7)->andReturn(2);
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

        /** @var SoIAS&m\MockInterface $soias */
        $soias = m::mock(SoIAS::class);
        $e4 = $soias->shouldReceive('saveSalesOrderItemAmountNoForm');
        $e4->once()->with($existingAmount, $expectedArray);

        $service->saveSalesOrderItemAmount(7, 2.0, 3.0, 0.5, 10.0, $soiar, $soias);
    }

    public function saveSalesOrderItemAmountSkipsSaveWhenExistingAmountNotFound(): void
    {
        /** @var ACSOIR&m\MockInterface $acsoiR */
        $acsoiR = m::mock(ACSOIR::class);
        $e = $acsoiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(8)->andReturn($this->readerYielding([]));

        $service = $this->makeService(acsoiR: $acsoiR);

        /** @var SoIAR&m\MockInterface $soiar */
        $soiar = m::mock(SoIAR::class);
        $e2 = $soiar->shouldReceive('repoCount');
        $e2->once()->with(8)->andReturn(1);
        $e3 = $soiar->shouldReceive('repoSalesOrderItemAmountquery');
        $e3->once()->with(8)->andReturn(null);

        /** @var SoIAS&m\MockInterface $soias */
        $soias = m::mock(SoIAS::class);
        $soias->shouldNotReceive('saveSalesOrderItemAmountNoForm');

        $service->saveSalesOrderItemAmount(8, 1.0, 1.0, 0.0, null, $soiar, $soias);
    }

    public function deleteSalesOrderItemCallsRepositoryDelete(): void
    {
        $model = new SalesOrderItem();

        /** @var SOIR&m\MockInterface $repository */
        $repository = m::mock(SOIR::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService(repository: $repository);

        $service->deleteSalesOrderItem($model);
    }
}
