<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Generator\GeneratorForm;

final class GeneratorService
{
    private GeneratorRepository $repository;

    public function __construct(GeneratorRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param Gentor $model
     * @param GeneratorForm $form
     * @return void
     */
    public function saveGenerator(Gentor $model, GeneratorForm $form): void
    {
        $form->getRoute_prefix() ? $model->setRoute_prefix($form->getRoute_prefix()) : '';
        $form->getRoute_suffix() ? $model->setRoute_suffix($form->getRoute_suffix()) : '';
        $form->getCamelcase_capital_name() ? $model->setCamelcase_capital_name($form->getCamelcase_capital_name()) : '';
        $form->getSmall_singular_name() ? $model->setSmall_singular_name($form->getSmall_singular_name()) : '';
        $form->getSmall_plural_name() ? $model->setSmall_plural_name($form->getSmall_plural_name()) : '';
        $form->getNamespace_path() ? $model->setNamespace_path($form->getNamespace_path()) : '';
        $form->getController_layout_dir() ? $model->setController_layout_dir($form->getController_layout_dir()) : '';
        $form->getController_layout_dir_dot_path() ? $model->setController_layout_dir_dot_path($form->getController_layout_dir_dot_path()) : '';
        $form->getRepo_extra_camelcase_name() ? $model->setRepo_extra_camelcase_name($form->getRepo_extra_camelcase_name()) : '';
        $form->getPaginator_next_page_attribute() ? $model->setPaginator_next_page_attribute($form->getPaginator_next_page_attribute()) : '';
        $form->getPre_entity_table() ? $model->setPre_entity_table($form->getPre_entity_table()) : '';
        $form->getConstrain_index_field() ? $model->setConstrain_index_field($form->getConstrain_index_field()) : '';
        $form->getCreated_include() ? $model->setCreated_include($form->getCreated_include()) : '';
        $form->getUpdated_include() ? $model->setUpdated_include($form->getUpdated_include()) : '';
        $form->getModified_include() ? $model->setModified_include($form->getModified_include()) : '';
        $form->getDeleted_include() ? $model->setDeleted_include($form->getDeleted_include()) : '';
        $form->getKeyset_paginator_include() ? $model->setKeyset_paginator_include($form->getKeyset_paginator_include()) : '';
        $form->getOffset_paginator_include() ? $model->setOffset_paginator_include($form->getOffset_paginator_include()) : '';
        $form->getFilter_field() ? $model->setFilter_field($form->getFilter_field()) : '';
        $form->getFilter_field_start_position() ? $model->setFilter_field_start_position($form->getFilter_field_start_position()) : '';
        $form->getFilter_field_end_position() ? $model->setFilter_field_end_position($form->getFilter_field_end_position()) : '';
        $form->getFlash_include() ? $model->setFlash_include($form->getFlash_include()) : '';
        $form->getHeaderline_include() ? $model->setHeaderline_include($form->getHeaderline_include()) : '';
        $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|Gentor|null $model
     * @return void
     */
    public function deleteGenerator(array|Gentor|null $model): void
    {
        $this->repository->delete($model);
    }
}
