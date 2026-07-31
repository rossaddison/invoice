<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\InvAllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Cycle\Reader\EntityReader;

final class InvAllowanceChargeServiceTest extends TestCase
{
    /**
     * @return array{
     *     repository: MockObject&InvAllowanceChargeRepository,
     *     acR: MockObject&AllowanceChargeRepository,
     *     service: InvAllowanceChargeService,
     * }
     */
    private function makeService(): array
    {
        $repository = $this->createMock(InvAllowanceChargeRepository::class);
        $acR = $this->createMock(AllowanceChargeRepository::class);

        return [
            'repository' => $repository,
            'acR' => $acR,
            'service' => new InvAllowanceChargeService($repository, $acR),
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

    private function invAllowanceCharge(int $allowanceChargeId, float $amount): InvAllowanceCharge
    {
        $iac = new InvAllowanceCharge();
        $iac->setAllowanceChargeId($allowanceChargeId);
        $iac->setAmount($amount);
        return $iac;
    }

    private function allowanceChargeWithTaxRate(int $id, float $taxRatePercent): AllowanceCharge
    {
        $taxRate = $this->createMock(TaxRate::class);
        $taxRate->method('getTaxRatePercent')->willReturn($taxRatePercent);
        $ac = new AllowanceCharge(id: $id);
        $ac->setTaxRate($taxRate);
        return $ac;
    }

    // --- initializeCreditInvAllowanceCharges ---

    public function testInitializeCreditInvAllowanceChargesCopiesNegatedAmountAndVat(): void
    {
        ['repository' => $repository, 'acR' => $acR, 'service' => $service] = $this->makeService();
        $repository->expects($this->once())->method('repoACIquery')->with(289)
            ->willReturn($this->reader([$this->invAllowanceCharge(9, 12.00)]));
        $acR->expects($this->once())->method('repoAllowanceChargequery')->with(9)
            ->willReturn($this->allowanceChargeWithTaxRate(9, 20.0));

        $repository->expects($this->once())->method('save')->with(
            $this->callback(function (InvAllowanceCharge $saved): bool {
                return $saved->reqInvId() === 500
                    && $saved->reqAllowanceChargeId() === 9
                    && $saved->getAmount() === -12.00
                    && $saved->getVatOrTax() === -2.40;
            })
        );

        $service->initializeCreditInvAllowanceCharges(289, 500);
    }

    public function testInitializeCreditInvAllowanceChargesCopiesEachRowInSet(): void
    {
        ['repository' => $repository, 'acR' => $acR, 'service' => $service] = $this->makeService();
        $repository->expects($this->once())->method('repoACIquery')->with(289)->willReturn($this->reader([
            $this->invAllowanceCharge(9, 12.00),
            $this->invAllowanceCharge(11, 4.00),
        ]));
        $acR->method('repoAllowanceChargequery')->willReturnMap([
            [9, $this->allowanceChargeWithTaxRate(9, 20.0)],
            [11, $this->allowanceChargeWithTaxRate(11, 0.0)],
        ]);

        $repository->expects($this->exactly(2))->method('save');

        $service->initializeCreditInvAllowanceCharges(289, 500);
    }

    public function testInitializeCreditInvAllowanceChargesDoesNothingWhenNoneExist(): void
    {
        ['repository' => $repository, 'service' => $service] = $this->makeService();
        $repository->expects($this->once())->method('repoACIquery')->with(289)->willReturn($this->reader([]));

        $repository->expects($this->never())->method('save');

        $service->initializeCreditInvAllowanceCharges(289, 500);
    }
}
