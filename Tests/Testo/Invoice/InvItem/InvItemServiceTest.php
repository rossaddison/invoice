<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvItem;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvItem\IiAddProductDeps;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountService;
use App\Invoice\Product\ProductRepository;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Task\TaskRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Covers InvItemService, whose constructor previously depended directly on
 * the final Cycle ORM repository InvItemRepository (and, transitively, on
 * InvRepository/TaxRateRepository/ProductRepository/TaskRepository), making
 * it impossible to mock with Mockery before DG\BypassFinals was enabled in
 * testo.php. Every public method is exercised here, with particular
 * attention to the ones that write to or read from a repository.
 */
#[Test]
final class InvItemServiceTest
{
    /** @param list<object> $items */
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
        ?InvItemAllowanceChargeRepository $aciiR = null,
        ?InvItemRepository $repository = null,
        ?InvRepository $iR = null,
        ?TaxRateRepository $trR = null,
        ?ProductRepository $pR = null,
        ?TaskRepository $taskR = null,
    ): InvItemService {
        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = $aciiR ?? m::mock(InvItemAllowanceChargeRepository::class);
        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(InvItemRepository::class);
        /** @var InvRepository&m\MockInterface $iR */
        $iR = $iR ?? m::mock(InvRepository::class);
        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = $trR ?? m::mock(TaxRateRepository::class);
        /** @var ProductRepository&m\MockInterface $pR */
        $pR = $pR ?? m::mock(ProductRepository::class);
        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = $taskR ?? m::mock(TaskRepository::class);
        return new InvItemService($aciiR, $repository, $iR, $trR, $pR, $taskR);
    }

    public function addInvItemProductHappyPathAppliesAllRelationsAndCreatesAmount(): void
    {
        $model = new InvItem();

        /** @var Inv&m\MockInterface $invEntity */
        $invEntity = m::mock(Inv::class);
        /** @var TaxRate&m\MockInterface $taxRateCtor */
        $taxRateCtor = m::mock(TaxRate::class);
        /** @var Product&m\MockInterface $productCtor */
        $productCtor = m::mock(Product::class);
        /** @var Task&m\MockInterface $taskCtor */
        $taskCtor = m::mock(Task::class);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(100)->andReturn($invEntity);

        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(3)->andReturn($taxRateCtor);

        /** @var ProductRepository&m\MockInterface $pR */
        $pR = m::mock(ProductRepository::class);
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(7)->andReturn($productCtor);

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $e4 = $taskR->shouldReceive('repoTaskquery');
        $e4->once()->with(9)->andReturn($taskCtor);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with(m::type(InvItem::class))->andReturnUsing(
            static function (InvItem $item): void {
                $item->setId(55);
            }
        );

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e6 = $aciiR->shouldReceive('repoInvItemquery');
        $e6->once()->with(55)->andReturn($this->readerYielding([]));

        $service = $this->makeService($aciiR, $repository, $iR, $trR, $pR, $taskR);

        /** @var Product&m\MockInterface $depsProduct */
        $depsProduct = m::mock(Product::class);
        $e7 = $depsProduct->shouldReceive('getProductName');
        $e7->once()->andReturn('Widget');
        $e8 = $depsProduct->shouldReceive('getProductDescription');
        $e8->once()->andReturn('A great widget');

        /** @var ProductRepository&m\MockInterface $depsPR */
        $depsPR = m::mock(ProductRepository::class);
        $e9 = $depsPR->shouldReceive('repoProductquery');
        $e9->once()->with(7)->andReturn($depsProduct);
        $e10 = $depsPR->shouldReceive('repoCount');
        $e10->once()->with(7)->andReturn(1);

        /** @var TaxRate&m\MockInterface $depsTaxRate */
        $depsTaxRate = m::mock(TaxRate::class);
        $e11 = $depsTaxRate->shouldReceive('getTaxRatePercent');
        $e11->once()->andReturn(20.0);

        /** @var TaxRateRepository&m\MockInterface $depsTRR */
        $depsTRR = m::mock(TaxRateRepository::class);
        $e12 = $depsTRR->shouldReceive('repoTaxRatequery');
        $e12->once()->with(3)->andReturn($depsTaxRate);

        /** @var Unit&m\MockInterface $depsUnit */
        $depsUnit = m::mock(Unit::class);
        $e13 = $depsUnit->shouldReceive('getUnitName');
        $e13->once()->andReturn('Hour');

        /** @var UnitRepository&m\MockInterface $depsUNR */
        $depsUNR = m::mock(UnitRepository::class);
        $e14 = $depsUNR->shouldReceive('repoUnitquery');
        $e14->once()->with(4)->andReturn($depsUnit);

        /** @var SettingRepository&m\MockInterface $depsSR */
        $depsSR = m::mock(SettingRepository::class);
        $e15 = $depsSR->shouldReceive('getSetting');
        $e15->once()->with('enable_vat_registration')->andReturn('1');

        /** @var InvItemAmountService&m\MockInterface $depsIIAS */
        $depsIIAS = m::mock(InvItemAmountService::class);
        $e16 = $depsIIAS->shouldReceive('saveInvItemAmountNoForm');
        $e16->once()->with(m::type(InvItemAmount::class), m::on(
            static function (array $arr): bool {
                return $arr['inv_item_id'] === 55
                    && $arr['charge'] === 0.00
                    && $arr['allowance'] === 0.00
                    && $arr['discount'] === 2.0
                    && $arr['subtotal'] === 20.0
                    && $arr['taxtotal'] === (20.0 - 2.0) * (20.0 / 100.00)
                    && $arr['total'] === 20.0 - 2.0 + (20.0 - 2.0) * (20.0 / 100.00);
            }
        ));

        /** @var InvItemAmountRepository&m\MockInterface $depsIIAR */
        $depsIIAR = m::mock(InvItemAmountRepository::class);
        $e17 = $depsIIAR->shouldReceive('repoInvItemAmountquery');
        $e17->once()->with(55)->andReturn(null);
        $e18 = $depsIIAR->shouldReceive('repoCount');
        $e18->once()->with(55)->andReturn(0);

        $deps = new IiAddProductDeps($depsPR, $depsTRR, $depsIIAS, $depsIIAR, $depsSR, $depsUNR);

        $array = [
            'tax_rate_id' => '3',
            'product_id' => '7',
            'task_id' => '9',
            'so_item_id' => '5',
            'peppol_po_itemid' => 'X1',
            'peppol_po_lineid' => 'L1',
            'note' => 'note text',
            'quantity' => '2',
            'price' => '10.00',
            'discount_amount' => '1.00',
            'order' => '1',
            'product_unit_id' => '4',
        ];

        $result = $service->addInvItemProduct($model, $array, '100', $deps);

        Assert::same(55, $result);
        Assert::same('Widget', $model->getName());
        Assert::same('A great widget', $model->getDescription());
        Assert::same('Hour', $model->getProductUnit());
        Assert::same(4, $model->getProductUnitId());
        Assert::same('note text', $model->getNote());
        Assert::same(2.0, $model->getQuantity());
        Assert::same(10.0, $model->getPrice());
        Assert::same(1.0, $model->getDiscountAmount());
        Assert::same(1, $model->getOrder());
        Assert::same(5, $model->getSoItemId());
        Assert::same(100, $model->reqInvId());
        Assert::same('X1', $model->getPeppolPoItemid());
        Assert::same('L1', $model->getPeppolPoLineid());
        Assert::same('1', $model->getBelongsToVatInvoice());
        Assert::same($taxRateCtor, $model->getTaxRate());
        Assert::same($productCtor, $model->getProduct());
        Assert::same($taskCtor, $model->getTask());
        Assert::same($invEntity, $model->getInv());
    }

    public function addInvItemProductWithoutProductIdSkipsSaveAndRelations(): void
    {
        $model = new InvItem(id: 77);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(200)->andReturn(null);

        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        /** @var ProductRepository&m\MockInterface $pR */
        $pR = m::mock(ProductRepository::class);
        $pR->shouldNotReceive('repoProductquery');

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $taskR->shouldNotReceive('repoTaskquery');

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $repository->shouldNotReceive('save');

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $aciiR->shouldNotReceive('repoInvItemquery');

        $service = $this->makeService($aciiR, $repository, $iR, $trR, $pR, $taskR);

        /** @var ProductRepository&m\MockInterface $depsPR */
        $depsPR = m::mock(ProductRepository::class);
        $e2 = $depsPR->shouldReceive('repoProductquery');
        $e2->once()->with(0)->andReturn(null);

        /** @var TaxRateRepository&m\MockInterface $depsTRR */
        $depsTRR = m::mock(TaxRateRepository::class);
        $e3 = $depsTRR->shouldReceive('repoTaxRatequery');
        $e3->once()->with(0)->andReturn(null);

        /** @var UnitRepository&m\MockInterface $depsUNR */
        $depsUNR = m::mock(UnitRepository::class);
        $e4 = $depsUNR->shouldReceive('repoUnitquery');
        $e4->once()->with(0)->andReturn(null);

        /** @var SettingRepository&m\MockInterface $depsSR */
        $depsSR = m::mock(SettingRepository::class);
        $e5 = $depsSR->shouldReceive('getSetting');
        $e5->once()->with('enable_vat_registration')->andReturn('0');

        /** @var InvItemAmountService&m\MockInterface $depsIIAS */
        $depsIIAS = m::mock(InvItemAmountService::class);
        $depsIIAS->shouldNotReceive('saveInvItemAmountNoForm');

        /** @var InvItemAmountRepository&m\MockInterface $depsIIAR */
        $depsIIAR = m::mock(InvItemAmountRepository::class);
        $depsIIAR->shouldNotReceive('repoInvItemAmountquery');
        $depsIIAR->shouldNotReceive('repoCount');

        $deps = new IiAddProductDeps($depsPR, $depsTRR, $depsIIAS, $depsIIAR, $depsSR, $depsUNR);

        $array = ['product_unit_id' => '0'];

        $result = $service->addInvItemProduct($model, $array, '200', $deps);

        Assert::same(77, $result);
        Assert::same(0, $model->getProductId());
        Assert::null($model->getProduct());
        Assert::null($model->getInv());
    }

    public function accumulativeChargeTotalSumsOnlyChargeEntries(): void
    {
        /** @var AllowanceCharge&m\MockInterface $chargeType */
        $chargeType = m::mock(AllowanceCharge::class);
        $e = $chargeType->shouldReceive('getIdentifier');
        $e->once()->andReturn(true);

        /** @var InvItemAllowanceCharge&m\MockInterface $chargeItem */
        $chargeItem = m::mock(InvItemAllowanceCharge::class);
        $e2 = $chargeItem->shouldReceive('getAllowanceCharge');
        $e2->once()->andReturn($chargeType);
        $e3 = $chargeItem->shouldReceive('getAmount');
        $e3->once()->andReturn('5.00');

        /** @var AllowanceCharge&m\MockInterface $allowanceType */
        $allowanceType = m::mock(AllowanceCharge::class);
        $e4 = $allowanceType->shouldReceive('getIdentifier');
        $e4->once()->andReturn(false);

        /** @var InvItemAllowanceCharge&m\MockInterface $allowanceItem */
        $allowanceItem = m::mock(InvItemAllowanceCharge::class);
        $e5 = $allowanceItem->shouldReceive('getAllowanceCharge');
        $e5->once()->andReturn($allowanceType);
        $allowanceItem->shouldNotReceive('getAmount');

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e6 = $aciiR->shouldReceive('repoInvItemquery');
        $e6->once()->with(10)->andReturn($this->readerYielding([$chargeItem, $allowanceItem]));

        $service = $this->makeService();

        Assert::same(5.00, $service->accumulativeChargeTotal(10, $aciiR));
    }

    public function accumulativeAllowanceTotalSumsOnlyAllowanceEntries(): void
    {
        /** @var AllowanceCharge&m\MockInterface $chargeType */
        $chargeType = m::mock(AllowanceCharge::class);
        $e = $chargeType->shouldReceive('getIdentifier');
        $e->once()->andReturn(true);

        /** @var InvItemAllowanceCharge&m\MockInterface $chargeItem */
        $chargeItem = m::mock(InvItemAllowanceCharge::class);
        $e2 = $chargeItem->shouldReceive('getAllowanceCharge');
        $e2->once()->andReturn($chargeType);
        $chargeItem->shouldNotReceive('getAmount');

        /** @var AllowanceCharge&m\MockInterface $allowanceType */
        $allowanceType = m::mock(AllowanceCharge::class);
        $e3 = $allowanceType->shouldReceive('getIdentifier');
        $e3->once()->andReturn(false);

        /** @var InvItemAllowanceCharge&m\MockInterface $allowanceItem */
        $allowanceItem = m::mock(InvItemAllowanceCharge::class);
        $e4 = $allowanceItem->shouldReceive('getAllowanceCharge');
        $e4->once()->andReturn($allowanceType);
        $e5 = $allowanceItem->shouldReceive('getAmount');
        $e5->once()->andReturn('2.00');

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e6 = $aciiR->shouldReceive('repoInvItemquery');
        $e6->once()->with(10)->andReturn($this->readerYielding([$chargeItem, $allowanceItem]));

        $service = $this->makeService();

        Assert::same(2.00, $service->accumulativeAllowanceTotal(10, $aciiR));
    }

    public function addInvItemAllowanceChargesCopiesEachOriginalEntry(): void
    {
        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $e = $allowanceCharge->shouldReceive('reqId');
        $e->once()->andReturn(77);

        /** @var InvItemAllowanceCharge&m\MockInterface $originalAC */
        $originalAC = m::mock(InvItemAllowanceCharge::class);
        $e2 = $originalAC->shouldReceive('getAllowanceCharge');
        $e2->once()->andReturn($allowanceCharge);
        $e3 = $originalAC->shouldReceive('getAmount');
        $e3->once()->andReturn('15');
        $e4 = $originalAC->shouldReceive('getVatOrTax');
        $e4->once()->andReturn('3');

        /** @var list<InvItemAllowanceCharge> $captured */
        $captured = [];

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e5 = $aciiR->shouldReceive('repoInvItemquery');
        $e5->once()->with(10)->andReturn($this->readerYielding([$originalAC]));
        $e6 = $aciiR->shouldReceive('save');
        $e6->once()->with(m::on(
            static function (InvItemAllowanceCharge $iiac) use (&$captured): bool {
                $captured[] = $iiac;
                return true;
            }
        ));

        $service = $this->makeService();
        $service->addInvItemAllowanceCharges('900', 10, 650, $aciiR);

        Assert::count($captured, 1);
        Assert::same(77, $captured[0]->reqAllowanceChargeId());
        Assert::same(900, $captured[0]->reqInvId());
        Assert::same(650, $captured[0]->reqInvItemId());
        Assert::same('15', $captured[0]->getAmount());
        Assert::same('3', $captured[0]->getVatOrTax());
    }

    public function addInvItemAllowanceChargesFromQuoteCopiesEachOriginalEntry(): void
    {
        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $e = $allowanceCharge->shouldReceive('reqId');
        $e->once()->andReturn(88);

        /** @var QuoteItemAllowanceCharge&m\MockInterface $originalAC */
        $originalAC = m::mock(QuoteItemAllowanceCharge::class);
        $e2 = $originalAC->shouldReceive('getAllowanceCharge');
        $e2->once()->andReturn($allowanceCharge);
        $e3 = $originalAC->shouldReceive('getAmount');
        $e3->once()->andReturn('25');
        $e4 = $originalAC->shouldReceive('getVatOrTax');
        $e4->once()->andReturn('5');

        /** @var list<InvItemAllowanceCharge> $captured */
        $captured = [];

        /** @var QuoteItemAllowanceChargeRepository&m\MockInterface $acqiR */
        $acqiR = m::mock(QuoteItemAllowanceChargeRepository::class);
        $e5 = $acqiR->shouldReceive('repoQuoteItemquery');
        $e5->once()->with(20)->andReturn($this->readerYielding([$originalAC]));

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e6 = $aciiR->shouldReceive('save');
        $e6->once()->with(m::on(
            static function (InvItemAllowanceCharge $iiac) use (&$captured): bool {
                $captured[] = $iiac;
                return true;
            }
        ));

        $service = $this->makeService();
        $service->addInvItemAllowanceChargesFromQuote('901', 20, 651, $acqiR, $aciiR);

        Assert::count($captured, 1);
        Assert::same(88, $captured[0]->reqAllowanceChargeId());
        Assert::same(901, $captured[0]->reqInvId());
        Assert::same(651, $captured[0]->reqInvItemId());
        Assert::same('25', $captured[0]->getAmount());
        Assert::same('5', $captured[0]->getVatOrTax());
    }

    public function saveInvItemProductWithProductFoundAppliesNameDescAndSaves(): void
    {
        $model = new InvItem();

        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        $e = $product->shouldReceive('getProductName');
        $e->once()->andReturn('Gadget');
        $e2 = $product->shouldReceive('getProductDescription');
        $e2->once()->andReturn('Desc D');

        /** @var ProductRepository&m\MockInterface $pr */
        $pr = m::mock(ProductRepository::class);
        $e3 = $pr->shouldReceive('repoProductquery');
        $e3->once()->with(12)->andReturn($product);
        $e4 = $pr->shouldReceive('repoCount');
        $e4->once()->with(12)->andReturn(2);

        /** @var Unit&m\MockInterface $unit */
        $unit = m::mock(Unit::class);
        $e5 = $unit->shouldReceive('getUnitName');
        $e5->once()->andReturn('Box');

        /** @var UnitRepository&m\MockInterface $unR */
        $unR = m::mock(UnitRepository::class);
        $e6 = $unR->shouldReceive('repoUnitquery');
        $e6->once()->with(6)->andReturn($unit);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e7 = $repository->shouldReceive('save');
        $e7->once()->with(m::type(InvItem::class));

        $service = $this->makeService(repository: $repository);

        $array = [
            'tax_rate_id' => '2',
            'so_item_id' => '8',
            'product_id' => '12',
            'note' => 'N2',
            'quantity' => '3',
            'price' => '5.00',
            'discount_amount' => '0.50',
            'order' => '2',
            'product_unit_id' => '6',
        ];

        $result = $service->saveInvItemProduct($model, $array, '300', $pr, $unR);

        Assert::same(2, $result);
        Assert::same('Gadget', $model->getName());
        Assert::same('Desc D', $model->getDescription());
        Assert::same('Box', $model->getProductUnit());
        Assert::same(12, $model->getProductId());
        Assert::same('N2', $model->getNote());
        Assert::same(3.0, $model->getQuantity());
        Assert::same(5.0, $model->getPrice());
        Assert::same(0.5, $model->getDiscountAmount());
        Assert::same(2, $model->getOrder());
        Assert::same(300, $model->reqInvId());
    }

    /**
     * A caller-supplied 'name' (e.g. invToInvInvItems() copying a template
     * invoice's own item verbatim, as HomeCare auto-invoicing does) must win
     * over the current Product's own name — previously only 'description'
     * had this precedence; 'name' always used the live Product regardless,
     * which could silently diverge name from description on a copied
     * invoice item if the product was ever renamed after the fact.
     */
    public function saveInvItemProductPrefersSuppliedNameOverLiveProductName(): void
    {
        $model = new InvItem();

        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        $e = $product->shouldReceive('getProductName');
        $e->never();
        $e2 = $product->shouldReceive('getProductDescription');
        $e2->once()->andReturn('Live Desc');

        /** @var ProductRepository&m\MockInterface $pr */
        $pr = m::mock(ProductRepository::class);
        $e3 = $pr->shouldReceive('repoProductquery');
        $e3->once()->with(12)->andReturn($product);
        $pr->shouldNotReceive('repoCount');

        /** @var UnitRepository&m\MockInterface $unR */
        $unR = m::mock(UnitRepository::class);
        $e4 = $unR->shouldReceive('repoUnitquery');
        $e4->once()->andReturn(null);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once();

        $service = $this->makeService(repository: $repository);

        $array = [
            'product_id' => '12',
            'name' => '14 Elm Street',
            'description' => 'Explicit copied description',
        ];

        $service->saveInvItemProduct($model, $array, '300', $pr, $unR);

        Assert::same('14 Elm Street', $model->getName());
        Assert::same('Explicit copied description', $model->getDescription());
    }

    public function saveInvItemProductWithoutProductSkipsSave(): void
    {
        $model = new InvItem();

        /** @var ProductRepository&m\MockInterface $pr */
        $pr = m::mock(ProductRepository::class);
        $e = $pr->shouldReceive('repoProductquery');
        $e->once()->with(0)->andReturn(null);
        $pr->shouldNotReceive('repoCount');

        /** @var UnitRepository&m\MockInterface $unR */
        $unR = m::mock(UnitRepository::class);
        $e2 = $unR->shouldReceive('repoUnitquery');
        $e2->once()->with(0)->andReturn(null);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $repository->shouldNotReceive('save');

        $service = $this->makeService(repository: $repository);

        $array = ['product_unit_id' => '0'];

        $result = $service->saveInvItemProduct($model, $array, '301', $pr, $unR);

        Assert::same(0, $result);
        Assert::same(0, $model->getProductId());
        Assert::same('', $model->getName());
    }

    public function addInvItemTaskWithPositiveTaskIdSavesAndCreatesAmount(): void
    {
        $model = new InvItem();

        /** @var Task&m\MockInterface $task */
        $task = m::mock(Task::class);
        $e = $task->shouldReceive('getName');
        $e->once()->andReturn('TaskName');

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $e2 = $taskR->shouldReceive('repoTaskquery');
        $e2->once()->with(15)->andReturn($task);

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $e3 = $taxRate->shouldReceive('getTaxRatePercent');
        $e3->once()->andReturn(15.0);

        /** @var TaxRateRepository&m\MockInterface $trr */
        $trr = m::mock(TaxRateRepository::class);
        $e4 = $trr->shouldReceive('repoTaxRatequery');
        $e4->once()->with(4)->andReturn($taxRate);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with(m::type(InvItem::class))->andReturnUsing(
            static function (InvItem $item): void {
                $item->setId(91);
            }
        );

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e6 = $aciiR->shouldReceive('repoInvItemquery');
        $e6->once()->with(91)->andReturn($this->readerYielding([]));

        $service = $this->makeService(aciiR: $aciiR, repository: $repository);

        /** @var InvItemAmountService&m\MockInterface $iias */
        $iias = m::mock(InvItemAmountService::class);
        $e7 = $iias->shouldReceive('saveInvItemAmountNoForm');
        $e7->once()->with(m::type(InvItemAmount::class), m::type('array'));

        /** @var InvItemAmountRepository&m\MockInterface $iiar */
        $iiar = m::mock(InvItemAmountRepository::class);
        $e8 = $iiar->shouldReceive('repoInvItemAmountquery');
        $e8->once()->with(91)->andReturn(null);
        $e9 = $iiar->shouldReceive('repoCount');
        $e9->once()->with(91)->andReturn(0);

        $array = [
            'tax_rate_id' => '4',
            'task_id' => '15',
            'description' => 'Custom Desc',
            'note' => 'Note X',
            'quantity' => '1',
            'price' => '20.00',
            'discount_amount' => '2.00',
            'order' => '3',
        ];

        $result = $service->addInvItemTask($model, $array, '400', $taskR, $trr, $iias, $iiar);

        Assert::same(91, $result);
        Assert::same('TaskName', $model->getName());
        Assert::same('Custom Desc', $model->getDescription());
        Assert::same('Note X', $model->getNote());
        Assert::same(1.0, $model->getQuantity());
        Assert::same(20.0, $model->getPrice());
        Assert::same(2.0, $model->getDiscountAmount());
        Assert::same(3, $model->getOrder());
        Assert::same(15, $model->getTaskId());
        Assert::same(400, $model->reqInvId());
        Assert::same(4, $model->reqTaxRateId());
    }

    public function addInvItemTaskWithZeroTaskIdSkipsSave(): void
    {
        $model = new InvItem(id: 123);

        /** @var Task&m\MockInterface $task */
        $task = m::mock(Task::class);
        $e = $task->shouldReceive('getName');
        $e->once()->andReturn('X');

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $e2 = $taskR->shouldReceive('repoTaskquery');
        $e2->once()->with(0)->andReturn($task);

        /** @var TaxRateRepository&m\MockInterface $trr */
        $trr = m::mock(TaxRateRepository::class);
        $e3 = $trr->shouldReceive('repoTaxRatequery');
        $e3->once()->with(0)->andReturn(null);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $repository->shouldNotReceive('save');

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $aciiR->shouldNotReceive('repoInvItemquery');

        $service = $this->makeService(aciiR: $aciiR, repository: $repository);

        /** @var InvItemAmountService&m\MockInterface $iias */
        $iias = m::mock(InvItemAmountService::class);
        $iias->shouldNotReceive('saveInvItemAmountNoForm');

        /** @var InvItemAmountRepository&m\MockInterface $iiar */
        $iiar = m::mock(InvItemAmountRepository::class);
        $iiar->shouldNotReceive('repoInvItemAmountquery');
        $iiar->shouldNotReceive('repoCount');

        $array = [
            'tax_rate_id' => '0',
            'task_id' => '0',
            'description' => 'Skip',
            'quantity' => '1',
            'price' => '0',
            'discount_amount' => '0',
            'order' => '0',
        ];

        $result = $service->addInvItemTask($model, $array, '401', $taskR, $trr, $iias, $iiar);

        Assert::same(123, $result);
        Assert::same('Skip', $model->getDescription());
        Assert::same(0, $model->getTaskId());
    }

    public function saveInvItemTaskKeepsMatchingRelationsAndSaves(): void
    {
        $model = new InvItem();

        /** @var TaxRate&m\MockInterface $existingTaxRate */
        $existingTaxRate = m::mock(TaxRate::class);
        $e = $existingTaxRate->shouldReceive('reqId');
        $e->once()->andReturn(4);
        $model->setTaxRate($existingTaxRate);

        /** @var Task&m\MockInterface $existingTask */
        $existingTask = m::mock(Task::class);
        $e2 = $existingTask->shouldReceive('reqId');
        $e2->once()->andReturn(15);
        $model->setTask($existingTask);

        /** @var Task&m\MockInterface $fetchedTask */
        $fetchedTask = m::mock(Task::class);
        $e3 = $fetchedTask->shouldReceive('getDescription');
        $e3->once()->andReturn('Fetched Desc');
        $fetchedTask->shouldNotReceive('getName');

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $e4 = $taskR->shouldReceive('repoTaskquery');
        $e4->once()->with(15)->andReturn($fetchedTask);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService(repository: $repository);

        $array = [
            'tax_rate_id' => '4',
            'task_id' => '15',
            'quantity' => '2',
            'price' => '8',
            'discount_amount' => '1',
            'order' => '0',
        ];

        $result = $service->saveInvItemTask($model, $array, '600', $taskR);

        Assert::same(4, $result);
        Assert::same($existingTaxRate, $model->getTaxRate());
        Assert::same($existingTask, $model->getTask());
        Assert::same('Fetched Desc', $model->getDescription());
        Assert::same('', $model->getNote());
        Assert::same(2.0, $model->getQuantity());
    }

    public function saveInvItemTaskClearsMismatchedRelationsAndSkipsSaveWhenZero(): void
    {
        $model = new InvItem();

        /** @var Task&m\MockInterface $existingTask */
        $existingTask = m::mock(Task::class);
        $e = $existingTask->shouldReceive('reqId');
        $e->once()->andReturn(15);
        $model->setTask($existingTask);

        /** @var Task&m\MockInterface $fetchedTask */
        $fetchedTask = m::mock(Task::class);
        $e2 = $fetchedTask->shouldReceive('getName');
        $e2->once()->andReturn('Z');
        $fetchedTask->shouldNotReceive('getDescription');

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $e3 = $taskR->shouldReceive('repoTaskquery');
        $e3->once()->with(0)->andReturn($fetchedTask);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $repository->shouldNotReceive('save');

        $service = $this->makeService(repository: $repository);

        $array = [
            'tax_rate_id' => '9',
            'task_id' => '0',
            'name' => 'y',
            'description' => 'Explicit',
            'note' => 'N',
            'quantity' => '0',
            'price' => '0',
            'discount_amount' => '0',
            'order' => '0',
        ];

        $result = $service->saveInvItemTask($model, $array, '601', $taskR);

        Assert::same(9, $result);
        Assert::null($model->getTask());
        Assert::null($model->getTaxRate());
        Assert::same('Z', $model->getName());
        Assert::same('Explicit', $model->getDescription());
        Assert::same('N', $model->getNote());
    }

    public function addInvItemProductTaskAppliesBothBlocksAndAlwaysSaves(): void
    {
        $model = new InvItem();

        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        $e = $product->shouldReceive('getProductName');
        $e->once()->andReturn('Prod21');

        /** @var ProductRepository&m\MockInterface $pr */
        $pr = m::mock(ProductRepository::class);
        $e2 = $pr->shouldReceive('repoProductquery');
        $e2->once()->with(21)->andReturn($product);
        $e3 = $pr->shouldReceive('repoCount');
        $e3->once()->with(21)->andReturn(3);

        /** @var Task&m\MockInterface $task */
        $task = m::mock(Task::class);
        $e4 = $task->shouldReceive('getName');
        $e4->once()->andReturn('Task31');

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $e5 = $taskR->shouldReceive('repoTaskquery');
        $e5->once()->with(31)->andReturn($task);
        $e6 = $taskR->shouldReceive('repoCount');
        $e6->once()->with(31)->andReturn(2);

        /** @var Unit&m\MockInterface $unit */
        $unit = m::mock(Unit::class);
        $e7 = $unit->shouldReceive('getUnitName');
        $e7->once()->andReturn('Litre');

        /** @var UnitRepository&m\MockInterface $uR */
        $uR = m::mock(UnitRepository::class);
        $e8 = $uR->shouldReceive('repoUnitquery');
        $e8->once()->with(8)->andReturn($unit);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldNotReceive('translate');

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e9 = $repository->shouldReceive('save');
        $e9->once()->with($model);

        $service = $this->makeService(repository: $repository);

        $array = [
            'tax_rate_id' => '6',
            'product_id' => '21',
            'task_id' => '31',
            'so_item_id' => '41',
            'description' => 'Line Desc',
            'quantity' => '4',
            'price' => '9.00',
            'discount_amount' => '0.00',
            'order' => '5',
            'product_unit_id' => '8',
        ];

        $result = $service->addInvItemProductTask($model, $array, '500', $pr, $taskR, $uR, $translator);

        Assert::same($model, $result);
        Assert::same(21, $model->getProductId());
        Assert::same(31, $model->getTaskId());
        Assert::same('Task31', $model->getName());
        Assert::same('Line Desc', $model->getDescription());
        Assert::same('Litre', $model->getProductUnit());
        Assert::same(8, $model->getProductUnitId());
        Assert::same(4.0, $model->getQuantity());
        Assert::same(9.0, $model->getPrice());
        Assert::same(0.0, $model->getDiscountAmount());
        Assert::same(5, $model->getOrder());
        Assert::same(500, $model->reqInvId());
        Assert::same(41, $model->getSoItemId());
        Assert::same(6, $model->reqTaxRateId());
    }

    public function addInvItemProductTaskWithNeitherFoundKeepsDefaultsAndStillSaves(): void
    {
        $model = new InvItem();

        /** @var ProductRepository&m\MockInterface $pr */
        $pr = m::mock(ProductRepository::class);
        $e = $pr->shouldReceive('repoProductquery');
        $e->once()->with(0)->andReturn(null);

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $e2 = $taskR->shouldReceive('repoTaskquery');
        $e2->once()->with(0)->andReturn(null);

        /** @var UnitRepository&m\MockInterface $uR */
        $uR = m::mock(UnitRepository::class);
        $e3 = $uR->shouldReceive('repoUnitquery');
        $e3->once()->with(0)->andReturn(null);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldNotReceive('translate');

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e4 = $repository->shouldReceive('save');
        $e4->once()->with($model);

        $service = $this->makeService(repository: $repository);

        $array = ['product_unit_id' => '0'];

        $result = $service->addInvItemProductTask($model, $array, '501', $pr, $taskR, $uR, $translator);

        Assert::same($model, $result);
        Assert::same('', $model->getName());
        Assert::same('', $model->getDescription());
        Assert::null($model->getProductId());
        Assert::null($model->getTaskId());
        Assert::same(0.0, $model->getQuantity());
        Assert::same(0.00, $model->getPrice());
        Assert::same(0.00, $model->getDiscountAmount());
        Assert::same(0, $model->getOrder());
        Assert::same(0, $model->getProductUnitId());
    }

    public function saveInvItemAmountCreatesNewAmountWhenNoneExists(): void
    {
        /** @var AllowanceCharge&m\MockInterface $chargeType */
        $chargeType = m::mock(AllowanceCharge::class);
        $e = $chargeType->shouldReceive('getIdentifier');
        $e->once()->andReturn(true);

        /** @var InvItemAllowanceCharge&m\MockInterface $chargeItem */
        $chargeItem = m::mock(InvItemAllowanceCharge::class);
        $e2 = $chargeItem->shouldReceive('getAllowanceCharge');
        $e2->once()->andReturn($chargeType);
        $e3 = $chargeItem->shouldReceive('getAmount');
        $e3->once()->andReturn('5');
        $e3b = $chargeItem->shouldReceive('getVatOrTax');
        $e3b->once()->andReturn('1.5');

        /** @var AllowanceCharge&m\MockInterface $allowanceType */
        $allowanceType = m::mock(AllowanceCharge::class);
        $e4 = $allowanceType->shouldReceive('getIdentifier');
        $e4->once()->andReturn(false);

        /** @var InvItemAllowanceCharge&m\MockInterface $allowanceItem */
        $allowanceItem = m::mock(InvItemAllowanceCharge::class);
        $e5 = $allowanceItem->shouldReceive('getAllowanceCharge');
        $e5->once()->andReturn($allowanceType);
        $e6 = $allowanceItem->shouldReceive('getAmount');
        $e6->once()->andReturn('2');
        $e6b = $allowanceItem->shouldReceive('getVatOrTax');
        $e6b->once()->andReturn('0.5');

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e7 = $aciiR->shouldReceive('repoInvItemquery');
        $e7->once()->with(50)->andReturn($this->readerYielding([$chargeItem, $allowanceItem]));

        /** @var InvItemAmountRepository&m\MockInterface $iiar */
        $iiar = m::mock(InvItemAmountRepository::class);
        $e8 = $iiar->shouldReceive('repoInvItemAmountquery');
        $e8->once()->with(50)->andReturn(null);
        $e9 = $iiar->shouldReceive('repoCount');
        $e9->once()->with(50)->andReturn(0);

        $subTotal = 2.0 * 10.0;
        $discountTotal = 2.0 * 1.0;
        $ipInvAc = $subTotal + 5.0 - 2.0;
        // Matches InvItemService::saveInvItemAmount: the item's own tax rate
        // applies to its charge/allowance-exclusive subtotal, and each
        // allowance/charge separately contributes its own getVatOrTax().
        $taxTotal = ($subTotal - $discountTotal) * (20.0 / 100.00) + (1.5 - 0.5);

        /** @var InvItemAmountService&m\MockInterface $iias */
        $iias = m::mock(InvItemAmountService::class);
        $e10 = $iias->shouldReceive('saveInvItemAmountNoForm');
        $e10->once()->with(m::type(InvItemAmount::class), m::on(
            static function (array $arr) use ($ipInvAc, $discountTotal, $taxTotal): bool {
                return $arr['inv_item_id'] === 50
                    && $arr['charge'] === 5.0
                    && $arr['allowance'] === 2.0
                    && $arr['discount'] === $discountTotal
                    && $arr['subtotal'] === $ipInvAc
                    && $arr['taxtotal'] === $taxTotal
                    && $arr['total'] === $ipInvAc - $discountTotal + $taxTotal;
            }
        ));

        $service = $this->makeService(aciiR: $aciiR);

        $result = $service->saveInvItemAmount(50, 2.0, 10.0, 1.0, 20.0, $iias, $iiar);

        Assert::null($result);
    }

    public function saveInvItemAmountUpdatesExistingAmountAndReturnsIt(): void
    {
        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e = $aciiR->shouldReceive('repoInvItemquery');
        $e->once()->with(51)->andReturn($this->readerYielding([]));

        /** @var InvItemAmount&m\MockInterface $existingAmount */
        $existingAmount = m::mock(InvItemAmount::class);

        /** @var InvItemAmountRepository&m\MockInterface $iiar */
        $iiar = m::mock(InvItemAmountRepository::class);
        $e2 = $iiar->shouldReceive('repoInvItemAmountquery');
        $e2->once()->with(51)->andReturn($existingAmount);
        $e3 = $iiar->shouldReceive('repoCount');
        $e3->once()->with(51)->andReturn(5);

        /** @var InvItemAmountService&m\MockInterface $iias */
        $iias = m::mock(InvItemAmountService::class);
        $e4 = $iias->shouldReceive('saveInvItemAmountNoForm');
        $e4->once()->with($existingAmount, m::on(
            static function (array $arr): bool {
                return $arr['inv_item_id'] === 51
                    && $arr['charge'] === 0.00
                    && $arr['allowance'] === 0.00
                    && $arr['discount'] === 0.0
                    && $arr['subtotal'] === 12.0
                    && $arr['taxtotal'] === 0.00
                    && $arr['total'] === 12.0;
            }
        ));

        $service = $this->makeService(aciiR: $aciiR);

        $result = $service->saveInvItemAmount(51, 3.0, 4.0, 0.0, -1.0, $iias, $iiar);

        Assert::same($existingAmount, $result);
    }

    public function deleteInvItemCallsRepositoryDelete(): void
    {
        /** @var InvItem&m\MockInterface $model */
        $model = m::mock(InvItem::class);

        /** @var InvItemRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService(repository: $repository);
        $service->deleteInvItem($model);
    }

    public function taxratePercentageReturnsPercentWhenFound(): void
    {
        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $e = $taxRate->shouldReceive('getTaxRatePercent');
        $e->once()->andReturn(17.5);

        /** @var TaxRateRepository&m\MockInterface $trr */
        $trr = m::mock(TaxRateRepository::class);
        $e2 = $trr->shouldReceive('repoTaxRatequery');
        $e2->once()->with(4)->andReturn($taxRate);

        $service = $this->makeService();

        Assert::same(17.5, $service->taxratePercentage(4, $trr));
    }

    public function taxratePercentageReturnsNullWhenNotFound(): void
    {
        /** @var TaxRateRepository&m\MockInterface $trr */
        $trr = m::mock(TaxRateRepository::class);
        $e = $trr->shouldReceive('repoTaxRatequery');
        $e->once()->with(999)->andReturn(null);

        $service = $this->makeService();

        Assert::null($service->taxratePercentage(999, $trr));
    }

    public function initializeCreditInvItemsCopiesProductItemAndNegatesAmount(): void
    {
        $dateAdded = new DateTimeImmutable('2026-01-01');

        /** @var InvItem&m\MockInterface $basisItem */
        $basisItem = m::mock(InvItem::class);
        $e = $basisItem->shouldReceive('reqTaxRateId');
        $e->once()->andReturn(6);
        $e2 = $basisItem->shouldReceive('getProductId');
        $e2->twice()->andReturn(99);
        $e3 = $basisItem->shouldReceive('getName');
        $e3->once()->andReturn('Basis Name');
        $e4 = $basisItem->shouldReceive('getDescription');
        $e4->once()->andReturn('Basis Desc');
        $e5 = $basisItem->shouldReceive('getNote');
        $e5->once()->andReturn('Basis Note');
        $e6 = $basisItem->shouldReceive('getQuantity');
        $e6->once()->andReturn(2.0);
        $e7 = $basisItem->shouldReceive('getPrice');
        $e7->once()->andReturn(15.0);
        $e8 = $basisItem->shouldReceive('getDiscountAmount');
        $e8->once()->andReturn(1.0);
        $e9 = $basisItem->shouldReceive('getIsRecurring');
        $e9->once()->andReturn(true);
        $e10 = $basisItem->shouldReceive('getProductUnit');
        $e10->once()->andReturn('Kg');
        $e11 = $basisItem->shouldReceive('getProductUnitId');
        $e11->once()->andReturn(3);
        $e12 = $basisItem->shouldReceive('getDateAdded');
        $e12->once()->andReturn($dateAdded);
        $e13 = $basisItem->shouldReceive('reqId');
        // Called twice: once by addInvItemAllowanceCharges() (as the
        // original item id to reverse charges from) and once by
        // repoInvItemAmountquery() (to find the basis item's amount row).
        $e13->twice()->andReturn(500);

        /** @var InvItem|null $capturedItem */
        $capturedItem = null;

        /** @var InvItemAllowanceChargeRepository&m\MockInterface $aciiR */
        $aciiR = m::mock(InvItemAllowanceChargeRepository::class);
        $e13b = $aciiR->shouldReceive('repoInvItemquery');
        $e13b->once()->with(500)->andReturn($this->readerYielding([]));

        /** @var InvItemRepository&m\MockInterface $iiR */
        $iiR = m::mock(InvItemRepository::class);
        $e14 = $iiR->shouldReceive('repoInvquery');
        $e14->once()->with(70)->andReturn($this->readerYielding([$basisItem]));
        $e15 = $iiR->shouldReceive('save');
        $e15->once()->with(m::on(
            static function (InvItem $item) use (&$capturedItem): bool {
                $item->setId(900);
                $capturedItem = $item;
                return true;
            }
        ));

        /** @var InvItemAmount&m\MockInterface $basisAmount */
        $basisAmount = m::mock(InvItemAmount::class);
        $e16 = $basisAmount->shouldReceive('getSubtotal');
        $e16->once()->andReturn(10.0);
        $e17 = $basisAmount->shouldReceive('getTaxTotal');
        $e17->once()->andReturn(2.0);
        $e18 = $basisAmount->shouldReceive('getDiscount');
        $e18->once()->andReturn(1.0);
        $e19 = $basisAmount->shouldReceive('getTotal');
        $e19->once()->andReturn(11.0);

        /** @var InvItemAmount|null $capturedAmount */
        $capturedAmount = null;

        /** @var InvItemAmountRepository&m\MockInterface $iiaR */
        $iiaR = m::mock(InvItemAmountRepository::class);
        $e20 = $iiaR->shouldReceive('repoInvItemAmountquery');
        $e20->once()->with(500)->andReturn($basisAmount);
        $e21 = $iiaR->shouldReceive('save');
        $e21->once()->with(m::on(
            static function (InvItemAmount $amount) use (&$capturedAmount): bool {
                $capturedAmount = $amount;
                return true;
            }
        ));

        $service = $this->makeService(aciiR: $aciiR);
        $service->initializeCreditInvItems(70, '80', $iiR, $iiaR);

        Assert::notNull($capturedItem);
        Assert::same(80, $capturedItem->reqInvId());
        Assert::same(6, $capturedItem->reqTaxRateId());
        Assert::same(99, $capturedItem->getProductId());
        Assert::null($capturedItem->getTaskId());
        Assert::same('Basis Name', $capturedItem->getName());
        Assert::same('Basis Desc', $capturedItem->getDescription());
        Assert::same('Basis Note', $capturedItem->getNote());
        Assert::same(-2.0, $capturedItem->getQuantity());
        Assert::same(15.0, $capturedItem->getPrice());
        Assert::same(1.0, $capturedItem->getDiscountAmount());
        Assert::same(0, $capturedItem->getOrder());
        Assert::true($capturedItem->getIsRecurring());
        Assert::same('Kg', $capturedItem->getProductUnit());
        Assert::same(3, $capturedItem->getProductUnitId());
        Assert::same($dateAdded, $capturedItem->getDate());

        Assert::notNull($capturedAmount);
        Assert::same(900, $capturedAmount->reqInvItemId());
        Assert::same(-10.0, $capturedAmount->getSubtotal());
        Assert::same(-2.0, $capturedAmount->getTaxTotal());
        Assert::same(-1.0, $capturedAmount->getDiscount());
        Assert::same(-11.0, $capturedAmount->getTotal());
    }
}
