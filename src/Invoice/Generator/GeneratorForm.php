<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class GeneratorForm extends FormModel
{
    #[Required]
    private string $route_prefix = '';
    #[Required]
    private string $route_suffix = '';
    #[Required]
    private string $camelcase_capital_name = '';
    #[Required]
    private string $small_singular_name = '';
    #[Required]
    private string $small_plural_name = '';
    #[Required]
    private string $namespace_path = '';
    #[Required]
    private string $controller_layout_dir = '';
    #[Required]
    private string $controller_layout_dir_dot_path = '';
    #[Required]
    private string $pre_entity_table = '';

    private bool $flash_include = true;
    private bool $created_include = true;
    private bool $modified_include = true;
    private bool $updated_include = true;
    private bool $deleted_include = true;

    public function __construct(Gentor $generator)
    {
        $this->route_prefix = $generator->getRoutePrefix();
        $this->route_suffix = $generator->getRouteSuffix();
        $this->camelcase_capital_name = $generator->getCamelcaseCapitalName();
        $this->small_singular_name = $generator->getSmallSingularName();
        $this->small_plural_name = $generator->getSmallPluralName();
        $this->namespace_path = $generator->getNamespacePath();
        $this->controller_layout_dir = $generator->getControllerLayoutDir();
        $this->controller_layout_dir_dot_path = $generator->getControllerLayoutDirDotPath();
        $this->pre_entity_table = $generator->getPreEntityTable();
        $this->flash_include = $generator->isFlashInclude();
        $this->created_include = $generator->isCreatedInclude();
        $this->modified_include = $generator->isModifiedInclude();
        $this->updated_include = $generator->isUpdatedInclude();
        $this->deleted_include = $generator->isDeletedInclude();
    }

    public function getRoutePrefix(): string
    {
        return $this->route_prefix;
    }

    public function getRouteSuffix(): string
    {
        return $this->route_suffix;
    }

    public function getCamelcaseCapitalName(): string
    {
        return $this->camelcase_capital_name;
    }

    public function getSmallSingularName(): string
    {
        return $this->small_singular_name;
    }

    public function getSmallPluralName(): string
    {
        return $this->small_plural_name;
    }

    public function getNamespacePath(): string
    {
        return $this->namespace_path;
    }

    public function getControllerLayoutDir(): string
    {
        return $this->controller_layout_dir;
    }

    public function getControllerLayoutDirDotPath(): string
    {
        return $this->controller_layout_dir_dot_path;
    }

    public function getPreEntityTable(): string
    {
        return $this->pre_entity_table;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
