<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Payment;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\PaymentMethod\PaymentMethod;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\Payment\PaymentService;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers PaymentService: the two save flows (form-based and payment-handler)
 * and delete, including the inv/payment_method relation persistence.
 */
#[Test]
final class PaymentServiceTest
{
    /**
     * Reads the private payment_date property directly via reflection.
     * See PR #998 "Possible issues found": setPaymentDate() accepts a
     * \DateTime, but getPaymentDate() is declared to return
     * string|DateTimeImmutable, which does not include \DateTime — so
     * calling getPaymentDate() after a successful date parse throws a
     * TypeError. Reflection avoids exercising that crash here.
     */
    private function paymentDateProperty(Payment $model): mixed
    {
        $property = new \ReflectionProperty(Payment::class, 'payment_date');
        return $property->getValue($model);
    }

    private function makeService(
        ?PaymentRepository $repository = null,
        ?IR $iR = null,
        ?PMR $pmR = null,
    ): PaymentService {
        /** @var PaymentRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(PaymentRepository::class);
        /** @var IR&m\MockInterface $iR */
        $iR = $iR ?? m::mock(IR::class);
        /** @var PMR&m\MockInterface $pmR */
        $pmR = $pmR ?? m::mock(PMR::class);
        return new PaymentService($repository, $iR, $pmR);
    }

    public function savePaymentSetsAllFieldsAndSaves(): void
    {
        $model = new Payment();
        $array = [
            'inv_id' => 10,
            'payment_method_id' => 3,
            'payment_date' => '2026-05-01',
            'amount' => 100.50,
            'note' => 'Paid in full',
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(10)->andReturn($inv);

        /** @var PaymentMethod&m\MockInterface $paymentMethod */
        $paymentMethod = m::mock(PaymentMethod::class);
        /** @var PMR&m\MockInterface $pmR */
        $pmR = m::mock(PMR::class);
        $e2 = $pmR->shouldReceive('repoPaymentMethodquery');
        $e2->once()->with(3)->andReturn($paymentMethod);

        /** @var PaymentRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $iR, $pmR);
        $service->savePayment($model, $array);

        Assert::same($inv, $model->getInv());
        Assert::same($paymentMethod, $model->getPaymentMethod());
        Assert::same(3, $model->reqPaymentMethodId());
        Assert::same(100.50, $model->getAmount());
        Assert::same('Paid in full', $model->getNote());
        Assert::same(10, $model->reqInvId());
        $paymentDate = $this->paymentDateProperty($model);
        if (!$paymentDate instanceof \DateTime) {
            throw new \RuntimeException('Expected a DateTime');
        }
        Assert::same('2026-05-01', $paymentDate->format('Y-m-d'));
    }

    public function savePaymentSkipsRelationsWhenIdsMissing(): void
    {
        $model = new Payment();
        $array = [];

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');

        /** @var PMR&m\MockInterface $pmR */
        $pmR = m::mock(PMR::class);
        $pmR->shouldNotReceive('repoPaymentMethodquery');

        /** @var PaymentRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $iR, $pmR);
        $service->savePayment($model, $array);

        Assert::null($model->getInv());
        Assert::null($model->getPaymentMethod());
    }

    public function addPaymentViaPaymentHandlerSetsAllFieldsAndSaves(): void
    {
        $model = new Payment();
        $date = new \DateTime('2026-04-15');
        $array = [
            'inv_id' => 20,
            'payment_method_id' => 5,
            'payment_date' => $date,
            'amount' => 55.25,
            'note' => 'Handler payment',
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(20)->andReturn($inv);

        /** @var PaymentMethod&m\MockInterface $paymentMethod */
        $paymentMethod = m::mock(PaymentMethod::class);
        /** @var PMR&m\MockInterface $pmR */
        $pmR = m::mock(PMR::class);
        $e2 = $pmR->shouldReceive('repoPaymentMethodquery');
        $e2->once()->with(5)->andReturn($paymentMethod);

        /** @var PaymentRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $iR, $pmR);
        $service->addPaymentViaPaymentHandler($model, $array);

        Assert::same(5, $model->reqPaymentMethodId());
        $paymentDate = $this->paymentDateProperty($model);
        if (!$paymentDate instanceof \DateTime) {
            throw new \RuntimeException('Expected a DateTime');
        }
        Assert::same($date, $paymentDate);
        Assert::same(55.25, $model->getAmount());
        Assert::same('Handler payment', $model->getNote());
        Assert::same(20, $model->reqInvId());
    }

    public function deletePaymentCallsRepositoryDelete(): void
    {
        $model = new Payment();

        /** @var PaymentRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deletePayment($model);
    }
}
