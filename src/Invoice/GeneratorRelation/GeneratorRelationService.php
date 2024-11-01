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
     * @param array $array
     * @return void
     */
    public function saveGeneratorRelation(GentorRelation $model, array $array): void
    {
        isset($array['lowercasename']) ? $model->setLowercase_name((string)$array['lowercasename']) : '';
        isset($array['camelcasename']) ? $model->setCamelcase_name((string)$array['camelcasename']) : '';
        isset($array['view_field_name']) ? $model->setView_field_name((string)$array['view_field_name']) : '';
        isset($array['gentor_id']) ? $model->setGentor_id((int)$array['gentor_id']) : '';
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
