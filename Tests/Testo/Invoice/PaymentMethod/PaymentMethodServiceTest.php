<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentMethod;

use App\Infrastructure\Persistence\PaymentMethod\PaymentMethod;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\PaymentMethod\PaymentMethodService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers PaymentMethodService: savePaymentMethod's field assignment
 * (including the always-set active boolean flag) and deletePaymentMethod.
 */
#[Test]
final class PaymentMethodServiceTest
{
    private function makeService(?PaymentMethodRepository $repository = null): PaymentMethodService
    {
        /** @var PaymentMethodRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(PaymentMethodRepository::class);
        return new PaymentMethodService($repository);
    }

    public function savePaymentMethodSetsAllFieldsAndSaves(): void
    {
        $model = new PaymentMethod();
        $array = ['name' => 'Bank Transfer', 'active' => '1'];

        /** @var PaymentMethodRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentMethodRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->savePaymentMethod($model, $array);

        Assert::same('Bank Transfer', $model->getName());
        Assert::true($model->getActive());
    }

    public function savePaymentMethodSetsActiveToFalseWhenNotOne(): void
    {
        $model = new PaymentMethod();
        $array = ['active' => '0'];

        /** @var PaymentMethodRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentMethodRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->savePaymentMethod($model, $array);

        Assert::same('', $model->getName());
        Assert::false($model->getActive());
    }

    public function deletePaymentMethodCallsRepositoryDelete(): void
    {
        $model = new PaymentMethod();

        /** @var PaymentMethodRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentMethodRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deletePaymentMethod($model);
    }
}
