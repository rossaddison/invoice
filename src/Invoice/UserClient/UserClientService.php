<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Entity\UserClient;
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
            (string) $array['user_id']
        );
        if ($user) {
            $model->setUser($user);
            $model->setUser_id((int) $user->getId());
        }
        $client = $this->cR->repoClientquery(
            (string) $array['client_id']
        );
        $model->setClient($client);
        $model->setClient_id((int) $client->getClient_id());
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
