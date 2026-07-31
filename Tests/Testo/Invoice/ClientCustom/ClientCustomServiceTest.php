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
        $repository = $repository ?? m::mock(ClientCustomRepository::class);
        $cR = $cR ?? m::mock(CR::class);
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

        $client = m::mock(Client::class);
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(1)->andReturn($client);

        $customField = m::mock(CustomField::class);
        $cfR = m::mock(CFR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(2)->andReturn($customField);

        $repository = m::mock(ClientCustomRepository::class);
        /** @var \Mockery\Expectation $e3 */
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

        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        $repository = m::mock(ClientCustomRepository::class);
        /** @var \Mockery\Expectation $e */
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

        $repository = m::mock(ClientCustomRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteClientCustom($model);
    }
}
