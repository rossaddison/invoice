<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Merchant;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Merchant\Merchant;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\Merchant\MerchantService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers MerchantService: saveMerchant and saveMerchantViaPaymentHandler's
 * inv relation persistence and field assignment, and deleteMerchant.
 */
#[Test]
final class MerchantServiceTest
{
    /**
     * Reads the private date property directly via reflection. See PR #998
     * "Possible issues found": setDate() accepts a \DateTime, but getDate()
     * is declared to return string|DateTimeImmutable, which does not
     * include \DateTime — so calling getDate() after a successful date
     * parse throws a TypeError. Reflection avoids exercising that crash
     * here.
     */
    private function dateProperty(Merchant $model): mixed
    {
        $property = new \ReflectionProperty(Merchant::class, 'date');
        return $property->getValue($model);
    }

    private function makeService(
        ?MerchantRepository $repository = null,
        ?IR $iR = null,
    ): MerchantService {
        $repository = $repository ?? m::mock(MerchantRepository::class);
        $iR = $iR ?? m::mock(IR::class);
        return new MerchantService($repository, $iR);
    }

    public function saveMerchantSetsAllFieldsAndSaves(): void
    {
        $model = new Merchant();
        $array = [
            'inv_id' => 11,
            'successful' => true,
            'date' => '2026-05-12',
            'driver' => 'stripe',
            'response' => 'OK',
            'reference' => 'ref-123',
        ];

        $inv = m::mock(Inv::class);
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e */
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(11)->andReturn($inv);

        $repository = m::mock(MerchantRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->saveMerchant($model, $array);

        Assert::same($inv, $model->getInv());
        Assert::same(11, $model->reqInvId());
        Assert::true($model->getSuccessful());
        $date = $this->dateProperty($model);
        if (!$date instanceof \DateTime) {
            throw new \RuntimeException('Expected a DateTime');
        }
        Assert::same('2026-05-12', $date->format('Y-m-d'));
        Assert::same('stripe', $model->getDriver());
        Assert::same('OK', $model->getResponse());
        Assert::same('ref-123', $model->getReference());
    }

    public function saveMerchantSkipsInvRelationWhenIdMissing(): void
    {
        $model = new Merchant();
        $array = ['successful' => false, 'date' => '2026-05-12'];

        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');

        $repository = m::mock(MerchantRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->saveMerchant($model, $array);

        Assert::null($model->getInv());
        Assert::false($model->getSuccessful());
    }

    public function saveMerchantViaPaymentHandlerSetsAllFieldsAndSaves(): void
    {
        $model = new Merchant();
        $date = new \DateTime('2026-05-13');
        $array = [
            'inv_id' => 22,
            'merchant_response_successful' => true,
            'merchant_response_date' => $date,
            'merchant_response_driver' => 'braintree',
            'merchant_response' => 'Approved',
            'merchant_response_reference' => 'ref-456',
            'merchant_response_provider_reference' => 'provider-789',
        ];

        $inv = m::mock(Inv::class);
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e */
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(22)->andReturn($inv);

        $repository = m::mock(MerchantRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->saveMerchantViaPaymentHandler($model, $array);

        Assert::same(22, $model->reqInvId());
        Assert::true($model->getSuccessful());
        $storedDate = $this->dateProperty($model);
        if (!$storedDate instanceof \DateTime) {
            throw new \RuntimeException('Expected a DateTime');
        }
        Assert::same($date, $storedDate);
        Assert::same('braintree', $model->getDriver());
        Assert::same('Approved', $model->getResponse());
        Assert::same('ref-456', $model->getReference());
        Assert::same('provider-789', $model->getProviderReference());
    }

    public function saveMerchantViaPaymentHandlerAllowsNullProviderReference(): void
    {
        $model = new Merchant();
        $date = new \DateTime('2026-05-14');
        $array = [
            'inv_id' => 33,
            'successful' => true,
            'merchant_response_successful' => false,
            'merchant_response_date' => $date,
            'merchant_response_driver' => 'adyen',
            'merchant_response' => 'Declined',
            'merchant_response_reference' => 'ref-000',
        ];

        $inv = m::mock(Inv::class);
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e */
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(33)->andReturn($inv);

        $repository = m::mock(MerchantRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iR);
        $service->saveMerchantViaPaymentHandler($model, $array);

        Assert::null($model->getProviderReference());
        Assert::false($model->getSuccessful());
    }

    public function deleteMerchantCallsRepositoryDelete(): void
    {
        $model = new Merchant();

        $repository = m::mock(MerchantRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteMerchant($model);
    }
}
