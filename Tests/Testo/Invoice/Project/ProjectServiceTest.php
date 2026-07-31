<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Project;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Project\Project;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Project\ProjectRepository;
use App\Invoice\Project\ProjectService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ProjectService: saveProject's client-relation persistence and
 * field assignment, and deleteProject.
 */
#[Test]
final class ProjectServiceTest
{
    private function makeService(
        ?ProjectRepository $repository = null,
        ?CR $cR = null,
    ): ProjectService {
        $repository = $repository ?? m::mock(ProjectRepository::class);
        $cR = $cR ?? m::mock(CR::class);
        return new ProjectService($repository, $cR);
    }

    public function saveProjectSetsAllFieldsAndSaves(): void
    {
        $model = new Project();
        $array = [
            'client_id' => 9,
            'name' => 'Website Redesign',
        ];

        $client = m::mock(Client::class);
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(9)->andReturn($client);

        $repository = m::mock(ProjectRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveProject($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same(9, $model->getClientId());
        Assert::same('Website Redesign', $model->getName());
    }

    public function saveProjectSkipsFieldsWhenMissing(): void
    {
        $model = new Project();

        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        $repository = m::mock(ProjectRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveProject($model, []);

        Assert::null($model->getClient());
        Assert::same('', $model->getName());
    }

    public function deleteProjectCallsRepositoryDelete(): void
    {
        $model = new Project();

        /** @var ProjectRepository&m\MockInterface $repository */
        $repository = m::mock(ProjectRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteProject($model);
    }
}
