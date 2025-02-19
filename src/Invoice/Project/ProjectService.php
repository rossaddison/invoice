<?php

declare(strict_types=1);

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;

final readonly class ProjectService
{
    public function __construct(private ProjectRepository $repository)
    {
    }

    /**
     * @param Project $model
     * @param array $array
     */
    public function saveProject(Project $model, array $array): void
    {
        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        $this->repository->save($model);
    }

    /**
     * @param Project $model
     */
    public function deleteProject(Project $model): void
    {
        $this->repository->delete($model);
    }
}
