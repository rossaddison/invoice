<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentCustom;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\PaymentCustom\PaymentCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Payment\PaymentRepository as PR;
use App\Invoice\PaymentCustom\PaymentCustomRepository;
use App\Invoice\PaymentCustom\PaymentCustomService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers PaymentCustomService: savePaymentCustom's payment/custom-field
 * relation persistence and field assignment, and deletePaymentCustom.
 */
#[Test]
final class PaymentCustomServiceTest
{
    private function makeService(
        ?PaymentCustomRepository $repository = null,
        ?PR $pR = null,
        ?CFR $cfR = null,
    ): PaymentCustomService {
        /** @var PaymentCustomRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(PaymentCustomRepository::class);
        /** @var PR&m\MockInterface $pR */
        $pR = $pR ?? m::mock(PR::class);
        /** @var CFR&m\MockInterface $cfR */
        $cfR = $cfR ?? m::mock(CFR::class);
        return new PaymentCustomService($repository, $pR, $cfR);
    }

    public function savePaymentCustomSetsAllFieldsAndSaves(): void
    {
        $model = new PaymentCustom();
        $array = [
            'payment_id' => 1,
            'custom_field_id' => 2,
            'value' => 'Reference',
        ];

        /** @var Payment&m\MockInterface $payment */
        $payment = m::mock(Payment::class);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e = $pR->shouldReceive('repoPaymentquery');
        $e->once()->with(1)->andReturn($payment);

        /** @var CustomField&m\MockInterface $customField */
        $customField = m::mock(CustomField::class);
        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(2)->andReturn($customField);

        /** @var PaymentCustomRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentCustomRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $pR, $cfR);
        $service->savePaymentCustom($model, $array);

        Assert::same($payment, $model->getPayment());
        Assert::same($customField, $model->getCustomField());
        Assert::same(1, $model->reqPaymentId());
        Assert::same(2, $model->reqCustomFieldId());
        Assert::same('Reference', $model->getValue());
    }

    public function savePaymentCustomSkipsPaymentRelationWhenLookupReturnsNull(): void
    {
        $model = new PaymentCustom();
        $array = ['payment_id' => 99, 'value' => 'Reference'];

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e = $pR->shouldReceive('repoPaymentquery');
        $e->once()->with(99)->andReturn(null);

        /** @var PaymentCustomRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentCustomRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $pR);
        $service->savePaymentCustom($model, $array);

        Assert::null($model->getPayment());
    }

    public function savePaymentCustomSkipsRelationsWhenIdsMissing(): void
    {
        $model = new PaymentCustom();
        $array = [];

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoPaymentquery');

        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        /** @var PaymentCustomRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentCustomRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $pR, $cfR);
        $service->savePaymentCustom($model, $array);

        Assert::null($model->getPayment());
        Assert::null($model->getCustomField());
    }

    public function deletePaymentCustomCallsRepositoryDelete(): void
    {
        $model = new PaymentCustom();

        /** @var PaymentCustomRepository&m\MockInterface $repository */
        $repository = m::mock(PaymentCustomRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deletePaymentCustom($model);
    }
}
