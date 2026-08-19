<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvTaxRate;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvTaxRate\InvTaxRateRepository;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Covers InvTaxRateService: saveInvTaxRate's relation persistence,
 * initializeCreditInvTaxRate's credit-note mirroring (including the
 * include_item_tax edge cases of 0 vs 1 vs null), and deleteInvTaxRate.
 */
#[Test]
final class InvTaxRateServiceTest
{
    /** @param list<InvTaxRate> $items */
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
        ?InvTaxRateRepository $repository = null,
        ?IR $iR = null,
        ?TRR $trR = null,
    ): InvTaxRateService {
        /** @var InvTaxRateRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(InvTaxRateRepository::class);
        /** @var IR&m\MockInterface $iR */
        $iR = $iR ?? m::mock(IR::class);
        /** @var TRR&m\MockInterface $trR */
        $trR = $trR ?? m::mock(TRR::class);
        return new InvTaxRateService($repository, $iR, $trR);
    }

    public function saveInvTaxRateSetsAllFieldsAndSaves(): void
    {
        $model = new InvTaxRate();
        $array = [
            'inv_id' => 1,
            'tax_rate_id' => 2,
            'include_item_tax' => 1,
            'inv_tax_rate_amount' => 12.5,
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(1)->andReturn($inv);

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(2)->andReturn($taxRate);

        /** @var InvTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(InvTaxRateRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $iR, $trR);
        $service->saveInvTaxRate($model, $array);

        Assert::same($inv, $model->getInv());
        Assert::same($taxRate, $model->getTaxRate());
        Assert::same(1, $model->reqInvId());
        Assert::same(2, $model->reqTaxRateId());
        Assert::same(1, $model->getIncludeItemTax());
        Assert::same(12.5, $model->getInvTaxRateAmount());
    }

    public function saveInvTaxRateSkipsRelationsWhenIdsMissing(): void
    {
        $model = new InvTaxRate();
        $array = [];

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        /** @var InvTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(InvTaxRateRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $iR, $trR);
        $service->saveInvTaxRate($model, $array);

        Assert::null($model->getInv());
        Assert::null($model->getTaxRate());
    }

    public function initializeCreditInvTaxRateMirrorsAndNegatesEachBasisRecord(): void
    {
        $basis1 = new InvTaxRate(inv_id: 1, tax_rate_id: 5, include_item_tax: 1, inv_tax_rate_amount: 10.00);
        $basis2 = new InvTaxRate(inv_id: 1, tax_rate_id: 6, include_item_tax: 0, inv_tax_rate_amount: 20.00);

        /** @var InvTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(InvTaxRateRepository::class);
        $e = $repository->shouldReceive('repoInvquery');
        $e->once()->with(1)->andReturn($this->readerYielding([$basis1, $basis2]));
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with(m::on(
            fn (mixed $arg): bool => $arg instanceof InvTaxRate
                && $arg->reqInvId() === 2
                && $arg->reqTaxRateId() === 5
                && $arg->getIncludeItemTax() === 1
                && $arg->getInvTaxRateAmount() === -10.00
        ));
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with(m::on(
            fn (mixed $arg): bool => $arg instanceof InvTaxRate
                && $arg->reqInvId() === 2
                && $arg->reqTaxRateId() === 6
                && $arg->getIncludeItemTax() === 0
                && $arg->getInvTaxRateAmount() === -20.00
        ));

        $service = $this->makeService($repository);
        $service->initializeCreditInvTaxRate(1, '2');
    }

    public function deleteInvTaxRateCallsRepositoryDelete(): void
    {
        $model = new InvTaxRate();

        /** @var InvTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(InvTaxRateRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteInvTaxRate($model);
    }
}
