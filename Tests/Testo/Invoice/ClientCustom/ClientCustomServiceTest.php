<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ClientCustom;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientCustom\ClientCustom;
use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientCustom\ClientCustomRepository;
use App\Invoice\ClientCustom\ClientCustomService;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ClientCustomService: saveClientCustom's client/custom-field
 * relation persistence and field assignment, and deleteClientCustom.
 */
#[Test]
final class ClientCustomServiceTest
{
    private function makeService(
        ?ClientCustomRepository $repository = null,
        ?CR $cR = null,
        ?CFR $cfR = null,
    ): ClientCustomService {
        /** @var ClientCustomRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(ClientCustomRepository::class);
        /** @var CR&m\MockInterface $cR */
        $cR = $cR ?? m::mock(CR::class);
        /** @var CFR&m\MockInterface $cfR */
        $cfR = $cfR ?? m::mock(CFR::class);
        return new ClientCustomService($repository, $cR, $cfR);
    }

    public function saveClientCustomSetsAllFieldsAndSaves(): void
    {
        $model = new ClientCustom();
        $array = [
            'client_id' => 1,
            'custom_field_id' => 2,
            'value' => 'Reference',
        ];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(1)->andReturn($client);

        /** @var CustomField&m\MockInterface $customField */
        $customField = m::mock(CustomField::class);
        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(2)->andReturn($customField);

        /** @var ClientCustomRepository&m\MockInterface $repository */
        $repository = m::mock(ClientCustomRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $cR, $cfR);
        $service->saveClientCustom($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same($customField, $model->getCustomField());
        Assert::same(1, $model->reqClientId());
        Assert::same(2, $model->reqCustomFieldId());
        Assert::same('Reference', $model->getValue());
    }

    public function saveClientCustomSkipsRelationsWhenIdsMissing(): void
    {
        $model = new ClientCustom();
        $array = [];

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        /** @var ClientCustomRepository&m\MockInterface $repository */
        $repository = m::mock(ClientCustomRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR, $cfR);
        $service->saveClientCustom($model, $array);

        Assert::null($model->getClient());
        Assert::null($model->getCustomField());
    }

    public function deleteClientCustomCallsRepositoryDelete(): void
    {
        $model = new ClientCustom();

        /** @var ClientCustomRepository&m\MockInterface $repository */
        $repository = m::mock(ClientCustomRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteClientCustom($model);
    }
}
