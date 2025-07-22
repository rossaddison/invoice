<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Invoice\Entity\UserClient;

final readonly class UserClientService
{
    public function __construct(private UserClientRepository $repository)
    {
    }

    public function saveUserClient(UserClient $model, array $array): void
    {
        isset($array['user_id']) ? $model->setUser_id((int) $array['user_id']) : '';
        isset($array['client_id']) ? $model->setClient_id((int) $array['client_id']) : '';
        $this->repository->save($model);
    }

    public function deleteUserClient(UserClient $model): void
    {
        $this->repository->delete($model);
    }
}
