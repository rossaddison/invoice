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
        isset($array['route_prefix']) ? $model->setRoute_prefix((string) $array['route_prefix']) : '';
        isset($array['route_suffix']) ? $model->setRoute_suffix((string) $array['route_suffix']) : '';
        isset($array['camelcase_capital_name']) ? $model->setCamelcase_capital_name((string) $array['camelcase_capital_name']) : '';
        isset($array['small_singular_name']) ? $model->setSmall_singular_name((string) $array['small_singular_name']) : '';
        isset($array['small_plural_name']) ? $model->setSmall_plural_name((string) $array['small_plural_name']) : '';
        isset($array['namespace_path']) ? $model->setNamespace_path((string) $array['namespace_path']) : '';
        isset($array['controller_layout_dir']) ? $model->setController_layout_dir((string) $array['controller_layout_dir']) : '';
        isset($array['controller_layout_dir_dot_path']) ? $model->setController_layout_dir_dot_path((string) $array['controller_layout_dir_dot_path']) : '';
        isset($array['pre_entity_table']) ? $model->setPre_entity_table((string) $array['pre_entity_table']) : '';
        $model->setFlash_include($array['flash_include'] === '1' ? true : false);
        $model->setCreated_include($array['created_include'] === '1' ? true : false);
        $model->setModified_include($array['modified_include'] === '1' ? true : false);
        $model->setUpdated_include($array['updated_include'] === '1' ? true : false);
        $model->setDeleted_include($array['deleted_include'] === '1' ? true : false);
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
