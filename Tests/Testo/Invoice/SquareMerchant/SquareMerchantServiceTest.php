<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SquareMerchant;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\SquareMerchant\SquareMerchant;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\SquareMerchant\SquareMerchantRepository;
use App\Invoice\SquareMerchant\SquareMerchantService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers SquareMerchantService::saveSquareMerchantViaPaymentHandler() —
 * mirrors MerchantServiceTest's saveMerchantViaPaymentHandler coverage,
 * plus order_id/payment_id in place of the generic provider_reference.
 */
#[Test]
final class SquareMerchantServiceTest
{
    private function dateProperty(SquareMerchant $model): mixed
    {
        $property = new \ReflectionProperty(SquareMerchant::class, 'date');
        return $property->getValue($model);
    }

    private function makeService(
        ?SquareMerchantRepository $repository = null,
        ?IR $iR = null,
    ): SquareMerchantService {
        $repository = $repository ?? m::mock(SquareMerchantRepository::class);
        $iR = $iR ?? m::mock(IR::class);
        return new SquareMerchantService($repository, $iR);
    }

    public function saveSquareMerchantViaPaymentHandlerSetsBothReferences(): void
    {
        $model = new SquareMerchant();
        $date = new \DateTime('2026-08-07');
        $array = [
            'inv_id' => 22,
            'merchant_response_successful' => true,
            'merchant_response_date' => $date,
            'merchant_response' => 'Payment confirmed',
            'merchant_response_reference' => 'INV-0022-square-payment-xyz',
            'merchant_response_order_id' => 'order-abc',
            'merchant_response_payment_id' => 'payment-xyz',
        ];

        $inv = m::mock(Inv::class);
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e */
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(22)->andReturn($inv);

        $repository = m::mock(SquareMerchantRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->saveSquareMerchantViaPaymentHandler($model, $array);

        Assert::same($inv, $model->getInv());
        Assert::same(22, $model->reqInvId());
        Assert::true($model->getSuccessful());
        $storedDate = $this->dateProperty($model);
        if (!$storedDate instanceof \DateTime) {
            throw new \RuntimeException('Expected a DateTime');
        }
        Assert::same($date, $storedDate);
        Assert::same('Payment confirmed', $model->getResponse());
        Assert::same('INV-0022-square-payment-xyz', $model->getReference());
        Assert::same('order-abc', $model->getOrderId());
        Assert::same('payment-xyz', $model->getPaymentId());
    }

    public function saveSquareMerchantViaPaymentHandlerAllowsNullReferencesOnFailure(): void
    {
        $model = new SquareMerchant();
        $date = new \DateTime('2026-08-07');
        $array = [
            'inv_id' => 33,
            'merchant_response_successful' => false,
            'merchant_response_date' => $date,
            'merchant_response' => 'Payment declined',
            'merchant_response_reference' => 'INV-0033-square',
        ];

        $inv = m::mock(Inv::class);
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e */
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(33)->andReturn($inv);

        $repository = m::mock(SquareMerchantRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->saveSquareMerchantViaPaymentHandler($model, $array);

        Assert::false($model->getSuccessful());
        Assert::null($model->getOrderId());
        Assert::null($model->getPaymentId());
    }
}
