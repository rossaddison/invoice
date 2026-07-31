<?php

declare(strict_types=1);

namespace App\Invoice\Worker;

use App\Infrastructure\Persistence\Worker\Worker;

final readonly class WorkerService
{
    public function __construct(private WorkerRepository $repository)
    {
    }

    /**
     * @param Worker $model
     * @param array $array
     */
    public function saveWorker(Worker $model, array $array): void
    {
        isset($array['firstname']) ? $model->setFirstname((string) $array['firstname']) : '';
        isset($array['lastname']) ? $model->setLastname((string) $array['lastname']) : '';
        $model->setActive(isset($array['active']) && $array['active'] !== '' && $array['active'] !== '0');
        $this->repository->save($model);
    }

    /**
     * @param Worker $model
     */
    public function deleteWorker(Worker $model): void
    {
        $this->repository->delete($model);
    }
}
