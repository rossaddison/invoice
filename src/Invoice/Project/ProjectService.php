<?php

declare(strict_types=1); 

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;


final class ProjectService
{

    private ProjectRepository $repository;

    public function __construct(ProjectRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Project $model
     * @param array $array
     * @return void
     */
    public function saveProject(Project $model, array $array): void
    {
       isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
       isset($array['name']) ? $model->setName((string)$array['name']) : ''; 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Project $model
     * @return void
     */
    public function deleteProject(Project $model): void
    {
        $this->repository->delete($model);
    }
}