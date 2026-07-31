<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserClient;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserClient\UserClientService;
use App\User\UserRepository as UR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers UserClientService: saveUserClient's user/client relation
 * persistence and field assignment, and deleteUserClient.
 */
#[Test]
final class UserClientServiceTest
{
    private function makeService(
        ?UserClientRepository $repository = null,
        ?UR $userR = null,
        ?CR $cR = null,
    ): UserClientService {
        $repository = $repository ?? m::mock(UserClientRepository::class);
        $userR = $userR ?? m::mock(UR::class);
        $cR = $cR ?? m::mock(CR::class);
        return new UserClientService($repository, $userR, $cR);
    }

    public function saveUserClientSetsUserAndClientRelationsAndSaves(): void
    {
        $model = new UserClient();
        $array = ['user_id' => 1, 'client_id' => 2];

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->once()->andReturn(1);

        /** @var UR&m\MockInterface $userR */
        $userR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $userR->expects('findById');
        $e2->once()->with(1)->andReturn($user);

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $client->shouldReceive('reqId');
        $e3->once()->andReturn(2);

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $cR->expects('repoClientquery');
        $e4->once()->with(2)->andReturn($client);

        /** @var UserClientRepository&m\MockInterface $repository */
        $repository = m::mock(UserClientRepository::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $repository->expects('save');
        $e5->once()->with($model);

        $service = $this->makeService($repository, $userR, $cR);
        $service->saveUserClient($model, $array);

        Assert::same($user, $model->getUser());
        Assert::same(1, $model->reqUserId());
        Assert::same($client, $model->getClient());
        Assert::same(2, $model->reqClientId());
    }

    public function deleteUserClientCallsRepositoryDelete(): void
    {
        $model = new UserClient();

        /** @var UserClientRepository&m\MockInterface $repository */
        $repository = m::mock(UserClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteUserClient($model);
    }
}
