<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;

final readonly class GeneratorService
{
    public function __construct(private GeneratorRepository $repository)
    {
    }

    /**
     * @param Gentor $model
     * @param array $array
     */
    public function saveGenerator(Gentor $model, array $array): void
    {
        isset($array['route_prefix']) ? $model->setRoutePrefix((string) $array['route_prefix']) : '';
        isset($array['route_suffix']) ? $model->setRouteSuffix((string) $array['route_suffix']) : '';
        isset($array['camelcase_capital_name']) ? $model->setCamelcaseCapitalName((string) $array['camelcase_capital_name']) : '';
        isset($array['small_singular_name']) ? $model->setSmallSingularName((string) $array['small_singular_name']) : '';
        isset($array['small_plural_name']) ? $model->setSmallPluralName((string) $array['small_plural_name']) : '';
        isset($array['namespace_path']) ? $model->setNamespacePath((string) $array['namespace_path']) : '';
        isset($array['controller_layout_dir']) ? $model->setControllerLayoutDir((string) $array['controller_layout_dir']) : '';
        isset($array['controller_layout_dir_dot_path']) ? $model->setControllerLayoutDirDotPath((string) $array['controller_layout_dir_dot_path']) : '';
        isset($array['pre_entity_table']) ? $model->setPreEntityTable((string) $array['pre_entity_table']) : '';
        $model->setFlashInclude($array['flash_include'] === '1' ? true : false);
        $model->setCreatedInclude($array['created_include'] === '1' ? true : false);
        $model->setModifiedInclude($array['modified_include'] === '1' ? true : false);
        $model->setUpdatedInclude($array['updated_include'] === '1' ? true : false);
        $model->setDeletedInclude($array['deleted_include'] === '1' ? true : false);
        $this->repository->save($model);
    }

    /**
     * @param array|Gentor|null $model
     */
    public function deleteGenerator(array|Gentor|null $model): void
    {
        $this->repository->delete($model);
    }
}
