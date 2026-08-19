<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\StockMovement\StockMovement;
use App\Invoice\Inv\InvPaymentSettlementDeps;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Inv\InvService as IS;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\StockMovement\StockMovementRepository as SMR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Covers InvPaymentSettlementService — the single place a payment
 * confirmation settles an already-loaded, already-confirmed-paid Inv
 * (status, payment_method, InvAmount balance/paid) and records the
 * resulting StockMovement rows, rather than each of the ~17 payment-gateway
 * webhook handlers duplicating that whole block themselves. Not yet wired
 * into any of them.
 *
 * Deliberately takes already-loaded Inv/InvAmount objects rather than an
 * id — see InvPaymentSettlementService's own docblock for why re-fetching
 * would be a real bug against how these handlers actually work.
 *
 * All repositories/services here are `final`, mockable only because
 * Tests/testo.php enables DG\BypassFinals — same established pattern as
 * InvRecurringCronServiceTest, including its EntityReader iterator stub.
 */
#[Test]
final class InvPaymentSettlementServiceTest
{
    /**
     * @param list<InvItem> $items
     */
    private function fakeReader(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $generator = (static function () use ($items) {
            yield from $items;
        })();
        $reader->shouldReceive('getIterator')->andReturn($generator);
        return $reader;
    }

    private function trackedProduct(int $id, float $startingStock): Product
    {
        $product = new Product(stock_quantity: $startingStock);
        $product->setId($id);
        $product->setTrackStock(true);
        return $product;
    }

    private function invItem(Product $product, float $quantity): InvItem
    {
        $item = new InvItem(quantity: $quantity);
        $item->setProduct($product);
        return $item;
    }

    public function settlesAPaidInvoiceAndRecordsStockMovementsForTrackedProducts(): void
    {
        $invoice = new Inv();
        $invoice->setId(101);
        $invoice->setStatusId(1);
        $invoiceAmountRecord = new InvAmount(inv_id: 101, total: 30.00, balance: 30.00);

        $product = $this->trackedProduct(5, 44.00);
        $item = $this->invItem($product, 3.00);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('save')->once()->with($invoice);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('save')->once()->with($invoiceAmountRecord);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvquery')->once()->with(101)->andReturn($this->fakeReader([$item]));

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldReceive('save')->once()->with($product);

        /** @var SMR&m\MockInterface $smR */
        $smR = m::mock(SMR::class);
        $smR->shouldReceive('save')->once()->with(m::type(StockMovement::class));

        $service = $this->makeService($iR, $iaR, $iiR, $pR, $smR);
        $service->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);

        Assert::same(4, $invoice->reqStatusId());
        Assert::same(4, $invoice->getPaymentMethod());
        Assert::same(0.00, $invoiceAmountRecord->getBalance());
        Assert::same(30.00, $invoiceAmountRecord->getPaid());
        Assert::same(41.00, $product->getStockQuantity());
    }

    public function honoursAnExplicitPaymentMethod(): void
    {
        $invoice = new Inv();
        $invoice->setId(104);
        $invoice->setStatusId(1);
        $invoiceAmountRecord = new InvAmount(inv_id: 104, total: 10.00, balance: 10.00);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('save')->once()->with($invoice);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('save')->once()->with($invoiceAmountRecord);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvquery')->once()->with(104)->andReturn($this->fakeReader([]));

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        /** @var SMR&m\MockInterface $smR */
        $smR = m::mock(SMR::class);

        $service = $this->makeService($iR, $iaR, $iiR, $pR, $smR);
        $service->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord, paymentMethod: 6);

        Assert::same(6, $invoice->getPaymentMethod());
    }

    public function skipsProductsThatDoNotTrackStock(): void
    {
        $invoice = new Inv();
        $invoice->setId(102);
        $invoice->setStatusId(1);
        $invoiceAmountRecord = new InvAmount(inv_id: 102, total: 20.00, balance: 20.00);

        $product = new Product();
        $product->setId(6);
        $product->setTrackStock(false);
        $item = $this->invItem($product, 2.00);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('save')->once()->with($invoice);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('save')->once()->with($invoiceAmountRecord);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvquery')->once()->with(102)->andReturn($this->fakeReader([$item]));

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('save');

        /** @var SMR&m\MockInterface $smR */
        $smR = m::mock(SMR::class);
        $smR->shouldNotReceive('save');

        $service = $this->makeService($iR, $iaR, $iiR, $pR, $smR);
        $service->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);

        Assert::same(4, $invoice->reqStatusId());
    }

    public function isIdempotentWhenTheInvoiceIsAlreadyPaid(): void
    {
        $invoice = new Inv();
        $invoice->setId(103);
        $invoice->setStatusId(4);
        $invoiceAmountRecord = new InvAmount(inv_id: 103, total: 15.00, balance: 0.00);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('save');

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldNotReceive('save');

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldNotReceive('repoInvquery');

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('save');

        /** @var SMR&m\MockInterface $smR */
        $smR = m::mock(SMR::class);
        $smR->shouldNotReceive('save');

        $service = $this->makeService($iR, $iaR, $iiR, $pR, $smR);
        $service->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }

    private function makeService(IR $iR, IAR $iaR, IIR $iiR, PR $pR, SMR $smR): InvPaymentSettlementService
    {
        /** @var IS&m\MockInterface $invService */
        $invService = m::mock(IS::class);
        /**
         * @param callable(): void $fn
         */
        $invService->shouldReceive('withTransaction')
            ->andReturnUsing(static function (callable $fn): void {
                $fn();
            });

        return new InvPaymentSettlementService(
            new InvPaymentSettlementDeps($iR, $iaR, $iiR, $pR, $smR, $invService),
        );
    }
}
