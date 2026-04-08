<?php

declare(strict_types=1);

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;
use App\Invoice\Client\ClientRepository as CR;

final readonly class ProjectService
{
    public function __construct(
        private ProjectRepository $repository,
        private CR $cR,
    ) {
    }

    /**
     * @param Project $model
     * @param array $array
     */
    public function saveProject(
        Project $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['client_id']) ?
            $model->setClientId((int) $array['client_id']) : '';
        isset($array['name']) ?
            $model->setName((string) $array['name']) : '';
        $this->repository->save($model);
    }

    private function persist(
        Project $model,
        array $array
    ): void {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery(
                    (string) $array[$client]));
        }
    }

    /**
     * @param Project $model
     */
    public function deleteProject(Project $model): void
    {
        $this->repository->delete($model);
    }
}
