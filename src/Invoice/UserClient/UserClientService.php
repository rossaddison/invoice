<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Invoice\Client\ClientRepository as CR;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\User\UserRepository as UR;

final readonly class UserClientService
{
    public function __construct(
        private UserClientRepository $repository,
        private UR $userR,
        private CR $cR,
    ) {
    }

    private function persist(UserClient $model, array $array): void
    {
        $user = $this->userR->findById(
            (int) $array['user_id']
        );
        $model->setUser($user);
        $model->setUserId($user->reqId());
        $client = $this->cR->repoClientquery((int) $array['client_id']);
        $model->setClient($client);
        $model->setClientId($client->reqId());
    }

    /**
     * @param UserClient $model
     * @param array $array
     */
    public function saveUserClient(
        UserClient $model,
        array $array
    ): void {
        $this->persist($model, $array);
        $this->repository->save($model);
    }

    /**
     * @param UserClient $model
     */
    public function deleteUserClient(UserClient $model): void
    {
        $this->repository->delete($model);
    }
}
