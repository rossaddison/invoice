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
     * 
     * @param Project $model
     * @param ProjectForm $form
     * @return void
     */
    public function saveProject(Project $model, ProjectForm $form): void
    {
       $form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       $form->getName() ? $model->setName($form->getName()) : ''; 
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