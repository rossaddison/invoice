<?php 

declare(strict_types=1);

namespace App\Invoice\Generator;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class GeneratorForm extends FormModel
{
    private ?int $id = null;
    private ?string $route_prefix = '';
    private ?string $route_suffix = '';
    private ?string $camelcase_capital_name = '';
    private ?string $small_singular_name = '';
    private ?string $small_plural_name = '';
    private ?string $namespace_path = '';
    private ?string $controller_layout_dir = '';
    private ?string $controller_layout_dir_dot_path = '';  
    private ?string $repo_extra_camelcase_name = '';
    private ?string $paginator_next_page_attribute = '';
    private ?string $pre_entity_table = '';
    private ?string $constrain_index_field = '';
    private ?string $filter_field = '';
    private ?int $filter_field_start_position = null;
    private ?int $filter_field_end_position = null;
    private bool $created_include = false;
    private bool $updated_include = false;
    private bool $modified_include = false;
    private bool $deleted_include = false;
    private bool $keyset_paginator_include = false;
    private bool $offset_paginator_include = false;
    private bool $flash_include = true;
    private bool $headerline_include = false;
        
    public function getRoute_prefix(): string|null
    {
        return $this->route_prefix;
    }

    public function getRoute_suffix(): string|null
    {
        return $this->route_suffix;
    }
    
    public function getCamelcase_capital_name(): string|null
    {
        return $this->camelcase_capital_name;
    }
    
    public function getSmall_singular_name(): string|null
    {
        return $this->small_singular_name;
    }
    
    public function getSmall_plural_name(): string|null
    {
        return $this->small_plural_name;
    }
    
    public function getNamespace_path(): string|null
    {
        return $this->namespace_path;
    }
    
    public function getController_layout_dir(): string|null
    {
        return $this->controller_layout_dir;
    }
    
    public function getController_layout_dir_dot_path(): string|null
    {
        return $this->controller_layout_dir_dot_path;
    }
    
    public function getRepo_extra_camelcase_name(): string|null
    {
        return $this->repo_extra_camelcase_name;
    }
    
    public function getPaginator_next_page_attribute(): string|null
    {
        return $this->paginator_next_page_attribute;
    }
    
    public function getPre_entity_table(): string|null
    {
        return $this->pre_entity_table;
    }
    
    public function getConstrain_index_field(): string|null
    {
        return $this->constrain_index_field;
    }
    
    public function getFilter_field(): string|null
    {
        return $this->filter_field;
    }
    
    public function getFilter_field_start_position(): int|null
    {
        return $this->filter_field_start_position;
    }
    
    public function getFilter_field_end_position(): int|null
    {
        return $this->filter_field_end_position;
    }
    
    public function getCreated_include(): bool
    {
        return $this->created_include;
    }
    
    public function getUpdated_include(): bool
    {
        return $this->updated_include;
    }
    
    public function getModified_include(): bool
    {
        return $this->modified_include;
    }
    
    public function getDeleted_include(): bool
    {
        return $this->deleted_include;
    }
    
    public function getKeyset_paginator_include(): bool
    {
        return $this->keyset_paginator_include;
    }
    
    public function getOffset_paginator_include(): bool
    {
        return $this->offset_paginator_include;
    }
    
    public function getFlash_include(): bool
    {
        return $this->flash_include;
    }
    
    public function getHeaderline_include(): bool
    {
        return $this->headerline_include;
    }
    
    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }
    
    /**
     * @return Required[][]
     *
     * @psalm-return array{route_prefix: list{Required}, route_suffix: list{Required}, camelcase_capital_name: list{Required}, small_singular_name: list{Required}, small_plural_name: list{Required}, namespace_path: list{Required}, controller_layout_dir: list{Required}, controller_layout_dir_dot_path: list{Required}, pre_entity_table: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'route_prefix' => [new Required()],
            'route_suffix' =>[new Required()],
            'camelcase_capital_name' =>[new Required()],
            'small_singular_name' => [new Required()],
            'small_plural_name' => [new Required()],
            'namespace_path' => [new Required()],
            'controller_layout_dir' => [new Required()],
            'controller_layout_dir_dot_path' => [new Required()],
            'pre_entity_table' => [new Required()],
        ];
    }
}
