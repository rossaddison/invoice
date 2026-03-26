<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;

final readonly class GeneratorRelationService
{
    public function __construct(private GeneratorRelationRepository $repository)
    {
    }

    /**
     * @param GentorRelation $model
     * @param array $array
     */
    public function saveGeneratorRelation(GentorRelation $model, array $array): void
    {
        isset($array['lowercasename']) ? $model->setLowercaseName((string) $array['lowercasename']) : '';
        isset($array['camelcasename']) ? $model->setCamelcaseName((string) $array['camelcasename']) : '';
        isset($array['view_field_name']) ? $model->setViewFieldName((string) $array['view_field_name']) : '';
        isset($array['gentor_id']) ? $model->setGentorId((int) $array['gentor_id']) : '';
        $this->repository->save($model);
    }

    /**
     * @param GentorRelation $model
     */
    public function deleteGeneratorRelation(GentorRelation $model): void
    {
        $this->repository->delete($model);
    }
}
