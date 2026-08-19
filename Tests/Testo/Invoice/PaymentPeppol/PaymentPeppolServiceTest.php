<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentPeppol;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\PaymentPeppol\PaymentPeppol;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\PaymentPeppol\PaymentPeppolRepository;
use App\Invoice\PaymentPeppol\PaymentPeppolService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers PaymentPeppolService: savePaymentPeppol's invoice-relation
 * persistence, field assignment, auto_reference timestamp round-trip, and
 * deletePaymentPeppol.
 */
#[Test]
final class PaymentPeppolServiceTest
{
    private function makeService(
        ?PaymentPeppolRepository $repository = null,
        ?IR $iR = null,
    ): PaymentPeppolService {
        /** @var PaymentPeppolRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(PaymentPeppolRepository::class);
        /** @var IR&m\MockInterface $iR */
        $iR = $iR ?? m::mock(IR::class);
        return new PaymentPeppolService($repository, $iR);
    }

    public function savePaymentPeppolSetsAllFieldsAndSaves(): void
    {
        $model = new PaymentPeppol();
        $timestamp = (new \DateTimeImmutable('2026-05-01 12:00:00'))->getTimestamp();
        $array = [
            'inv_id' => 6,
            'id' => 12,
            'provider' => 'storecove',
            'auto_reference' => $timestamp,
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(6)->andReturn($inv);

        /** @var PaymentPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentPeppolRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->savePaymentPeppol($model, $array);

        Assert::same($inv, $model->getInv());
        Assert::same(6, $model->reqInvId());
        Assert::same(12, $model->reqId());
        Assert::same('storecove', $model->getProvider());
        Assert::same($timestamp, $model->getAutoReference());
    }

    public function savePaymentPeppolSkipsRelationWhenInvIdMissing(): void
    {
        $model = new PaymentPeppol();
        $array = ['auto_reference' => 0];

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');

        /** @var PaymentPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentPeppolRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->savePaymentPeppol($model, $array);

        Assert::null($model->getInv());
        Assert::same('', $model->getProvider());
    }

    public function deletePaymentPeppolCallsRepositoryDelete(): void
    {
        $model = new PaymentPeppol();

        /** @var PaymentPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentPeppolRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deletePaymentPeppol($model);
    }
}
