<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Invoice\Entity\UserClient;

final class UserClientService
{
    private UserClientRepository $repository;

    public function __construct(UserClientRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param UserClient $model
     * @param array $array
     */
    public function saveUserClient(UserClient $model, array $array): void
    {
        isset($array['user_id']) ? $model->setUser_id((int)$array['user_id']) : '';
        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
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
