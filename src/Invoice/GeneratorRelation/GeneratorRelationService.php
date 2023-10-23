<?php
declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;

final class GeneratorRelationService
{
    private GeneratorRelationRepository $repository;

    public function __construct(GeneratorRelationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param GentorRelation $model
     * @param GeneratorRelationForm $form
     * @return void
     */
    public function saveGeneratorRelation(GentorRelation $model, GeneratorRelationForm $form): void
    {
        null!==$form->getLowercase_name() ? $model->setLowercase_name($form->getLowercase_name()) : '';
        null!==$form->getCamelcase_name() ? $model->setCamelcase_name($form->getCamelcase_name()) : '';
        null!==$form->getView_field_name() ? $model->setView_field_name($form->getView_field_name()) : '';
        null!==$form->getgentor_id() ? $model->setGentor_id($form->getGentor_id()) : '';
        $this->repository->save($model);
    }
    
    /**
     * 
     * @param GentorRelation $model
     * @return void
     */
    public function deleteGeneratorRelation(GentorRelation $model): void
    {
        $this->repository->delete($model);
    }
}
