<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Service;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Task\TaskRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Credit notes are a reversed copy of the basis invoice (see
 * InvController::processCreditConfirm / Trait\Credit). These tests cover
 * initializeCreditInvItems() carrying the basis item's InvItemAllowanceCharge
 * rows onto the credit note item, negated in step with the already-negated
 * quantity/subtotal, so a UBL export of the credit note still has an
 * allowance/charge breakdown to draw from.
 *
 * Every test method here mixes real assertions (expects()->method(...))
 * on some mocks with other mocks used as plain stubs (willReturn() only,
 * e.g. unrelated repository dependencies a given test doesn't care about)
 * — PHPUnit 13's stub-without-expectations check flags those per mock
 * instance regardless of what other mocks in the same test do. This
 * class-level attribute silences that check; it does not weaken any
 * existing expects() assertion, which still runs and still fails the test
 * if unmet.
 */
#[AllowMockObjectsWithoutExpectations]
final class InvItemServiceCreditTest extends TestCase
{
    /**
     * @return array{
     *     aciiR: MockObject&InvItemAllowanceChargeRepository,
     *     repository: MockObject&InvItemRepository,
     *     iR: MockObject&InvRepository,
     *     trR: MockObject&TaxRateRepository,
     *     pR: MockObject&ProductRepository,
     *     taskR: MockObject&TaskRepository,
     *     service: InvItemService,
     * }
     */
    private function makeService(): array
    {
        $aciiR = $this->createMock(InvItemAllowanceChargeRepository::class);
        $repository = $this->createMock(InvItemRepository::class);
        $iR = $this->createMock(InvRepository::class);
        $trR = $this->createMock(TaxRateRepository::class);
        $pR = $this->createMock(ProductRepository::class);
        $taskR = $this->createMock(TaskRepository::class);

        return [
            'aciiR' => $aciiR,
            'repository' => $repository,
            'iR' => $iR,
            'trR' => $trR,
            'pR' => $pR,
            'taskR' => $taskR,
            'service' => new InvItemService($aciiR, $repository, $iR, $trR, $pR, $taskR),
        ];
    }

    private function reader(array $items): MockObject
    {
        $reader = $this->createMock(EntityReader::class);
        $reader->method('getIterator')->willReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    private function invItemAllowanceCharge(bool $isCharge, int $allowanceChargeId, float $amount, float $vatOrTax): InvItemAllowanceCharge
    {
        $ac = new AllowanceCharge(id: $allowanceChargeId, identifier: $isCharge);
        $iiac = new InvItemAllowanceCharge();
        $iiac->setAllowanceCharge($ac);
        $iiac->setAmount($amount);
        $iiac->setVatOrTax($vatOrTax);
        return $iiac;
    }

    // --- initializeCreditInvItems: item-level allowance/charge propagation ---

    public function testInitializeCreditInvItemsCopiesNegatedItemAllowanceCharge(): void
    {
        ['aciiR' => $aciiR, 'service' => $service] = $this->makeService();

        $basisItem = new InvItem(
            id: 719,
            name: 'Cleen Screans',
            quantity: 2.00,
            price: 10.00,
            discount_amount: 0.00,
            inv_id: 289,
            tax_rate_id: 3,
            product_id: 7,
        );
        $iiR = $this->createMock(InvItemRepository::class);
        $iiR->expects($this->once())->method('repoInvquery')->with(289)->willReturn($this->reader([$basisItem]));
        $iiR->expects($this->once())->method('save')
            ->willReturnCallback(function (InvItem $newItem): void {
                $newItem->setId(900);
            });

        $iiaR = $this->createMock(InvItemAmountRepository::class);
        $iiaR->expects($this->once())->method('repoInvItemAmountquery')->with(719)->willReturn(null);

        $aciiR->expects($this->once())->method('repoInvItemquery')->with(719)
            ->willReturn($this->reader([$this->invItemAllowanceCharge(true, 5, 5.00, 1.00)]));
        $aciiR->expects($this->once())->method('save')->with(
            $this->callback(function (InvItemAllowanceCharge $saved): bool {
                return $saved->reqInvId() === 500
                    && $saved->reqInvItemId() === 900
                    && $saved->reqAllowanceChargeId() === 5
                    && $saved->getAmount() === '-5'
                    && $saved->getVatOrTax() === '-1'
                ;
            })
        );

        $service->initializeCreditInvItems(289, '500', $iiR, $iiaR);
    }

    public function testInitializeCreditInvItemsCopiesEachItemAllowanceChargeInSet(): void
    {
        ['aciiR' => $aciiR, 'service' => $service] = $this->makeService();

        $basisItem = new InvItem(id: 720, name: 'Tuch Padd', quantity: 1.00, price: 15.00, inv_id: 289, tax_rate_id: 3, product_id: 8);
        $iiR = $this->createMock(InvItemRepository::class);
        $iiR->method('repoInvquery')->willReturn($this->reader([$basisItem]));
        $iiR->method('save')->willReturnCallback(function (InvItem $newItem): void {
            $newItem->setId(901);
        });

        $iiaR = $this->createMock(InvItemAmountRepository::class);
        $iiaR->method('repoInvItemAmountquery')->willReturn(null);

        $aciiR->expects($this->once())->method('repoInvItemquery')->with(720)->willReturn($this->reader([
            $this->invItemAllowanceCharge(true, 5, 5.00, 1.00),
            $this->invItemAllowanceCharge(false, 4, 10.00, 2.00),
        ]));
        $aciiR->expects($this->exactly(2))->method('save');

        $service->initializeCreditInvItems(289, '500', $iiR, $iiaR);
    }

    public function testInitializeCreditInvItemsNegatesItemAmountTotalsIndependentlyOfAllowanceCharges(): void
    {
        ['aciiR' => $aciiR, 'service' => $service] = $this->makeService();

        $basisItem = new InvItem(id: 719, name: 'Cleen Screans', quantity: 1.00, price: 20.00, inv_id: 289, tax_rate_id: 3, product_id: 7);
        $iiR = $this->createMock(InvItemRepository::class);
        $iiR->method('repoInvquery')->willReturn($this->reader([$basisItem]));
        $iiR->method('save')->willReturnCallback(function (InvItem $newItem): void {
            $newItem->setId(900);
        });

        $aciiR->method('repoInvItemquery')->willReturn($this->reader([]));

        $basisAmount = new InvItemAmount();
        $basisAmount->setSubtotal(32.00);
        $basisAmount->setTaxTotal(4.80);
        $basisAmount->setDiscount(0.00);
        $basisAmount->setTotal(36.80);
        $iiaR = $this->createMock(InvItemAmountRepository::class);
        $iiaR->expects($this->once())->method('repoInvItemAmountquery')->with(719)->willReturn($basisAmount);
        $iiaR->expects($this->once())->method('save')->with(
            $this->callback(function (InvItemAmount $saved): bool {
                return $saved->getSubtotal() === -32.00
                    && $saved->getTaxTotal() === -4.80
                    && $saved->getDiscount() === -0.00
                    && $saved->getTotal() === -36.80;
            })
        );

        $service->initializeCreditInvItems(289, '500', $iiR, $iiaR);
    }

    // --- addInvItemAllowanceCharges: multiplier ---

    public function testAddInvItemAllowanceChargesDefaultMultiplierPreservesSign(): void
    {
        ['aciiR' => $aciiR, 'service' => $service] = $this->makeService();
        $aciiR->expects($this->once())->method('repoInvItemquery')->with(719)
            ->willReturn($this->reader([$this->invItemAllowanceCharge(true, 5, 5.00, 1.00)]));
        $aciiR->expects($this->once())->method('save')->with(
            $this->callback(fn (InvItemAllowanceCharge $s): bool => $s->getAmount() === '5' && $s->getVatOrTax() === '1')
        );

        $service->addInvItemAllowanceCharges('500', 719, 900, $aciiR);
    }

    public function testAddInvItemAllowanceChargesNegativeMultiplierReversesSign(): void
    {
        ['aciiR' => $aciiR, 'service' => $service] = $this->makeService();
        $aciiR->expects($this->once())->method('repoInvItemquery')->with(719)
            ->willReturn($this->reader([$this->invItemAllowanceCharge(true, 5, 5.00, 1.00)]));
        $aciiR->expects($this->once())->method('save')->with(
            $this->callback(fn (InvItemAllowanceCharge $s): bool => $s->getAmount() === '-5' && $s->getVatOrTax() === '-1')
        );

        $service->addInvItemAllowanceCharges('500', 719, 900, $aciiR, -1.0);
    }
}
