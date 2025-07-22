<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;

final readonly class GeneratorRelationService
{
    public function __construct(private GeneratorRelationRepository $repository)
    {
    }

    public function saveGeneratorRelation(GentorRelation $model, array $array): void
    {
        isset($array['lowercasename']) ? $model->setLowercase_name((string) $array['lowercasename']) : '';
        isset($array['camelcasename']) ? $model->setCamelcase_name((string) $array['camelcasename']) : '';
        isset($array['view_field_name']) ? $model->setView_field_name((string) $array['view_field_name']) : '';
        isset($array['gentor_id']) ? $model->setGentor_id((int) $array['gentor_id']) : '';
        $this->repository->save($model);
    }

    public function deleteGeneratorRelation(GentorRelation $model): void
    {
        $this->repository->delete($model);
    }
}
