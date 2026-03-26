<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\Generator\GeneratorRepository::class)]
class Gentor
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(#[Column(type: 'string(20)')]
        private string $route_prefix = '', #[Column(type: 'string(20)')]
        private string $route_suffix = '', #[Column(type: 'string(50)')]
        private string $camelcase_capital_name = '', #[Column(type: 'string(20)')]
        private string $small_singular_name = '', #[Column(type: 'string(20)')]
        private string $small_plural_name = '', #[Column(type: 'string(100)')]
        private string $namespace_path = '', #[Column(type: 'string(100)')]
        private string $controller_layout_dir = 'dirname(dirname(__DIR__)', #[Column(type: 'string(100)')]
        private string $controller_layout_dir_dot_path = '@invoice/layout/main.php', #[Column(type: 'string(50)')]
        private string $pre_entity_table = '', #[Column(type: 'bool', default: false)]
        private bool $created_include = false, #[Column(type: 'bool', default: false)]
        private bool $updated_include = false, #[Column(type: 'bool', default: false)]
        private bool $modified_include = false, #[Column(type: 'bool', default: false)]
        private bool $deleted_include = false, #[Column(type: 'bool', default: true)]
        private bool $flash_include = false) {}

    public function getGentorId(): string
    {
        return (string) $this->id;
    }

    public function getRoutePrefix(): string
    {
        return $this->route_prefix;
    }

    public function setRoutePrefix(string $route_prefix): void
    {
        $this->route_prefix = $route_prefix;
    }

    public function getRouteSuffix(): string
    {
        return $this->route_suffix;
    }

    public function setRouteSuffix(string $route_suffix): void
    {
        $this->route_suffix = $route_suffix;
    }

    public function getCamelcaseCapitalName(): string
    {
        return $this->camelcase_capital_name;
    }

    public function setCamelcaseCapitalName(string $camelcase_capital_name): void
    {
        $this->camelcase_capital_name = $camelcase_capital_name;
    }

    public function getSmallSingularName(): string
    {
        return $this->small_singular_name;
    }

    public function setSmallSingularName(string $small_singular_name): void
    {
        $this->small_singular_name = $small_singular_name;
    }

    public function getSmallPluralName(): string
    {
        return $this->small_plural_name;
    }

    public function setSmallPluralName(string $small_plural_name): void
    {
        $this->small_plural_name = $small_plural_name;
    }

    public function getNamespacePath(): string
    {
        return $this->namespace_path;
    }

    public function setNamespacePath(string $namespace_path): void
    {
        $this->namespace_path = $namespace_path;
    }

    public function getControllerLayoutDir(): string
    {
        return $this->controller_layout_dir;
    }

    public function setControllerLayoutDir(string $controller_layout_dir): void
    {
        $this->controller_layout_dir = $controller_layout_dir;
    }

    public function getControllerLayoutDirDotPath(): string
    {
        return $this->controller_layout_dir_dot_path;
    }

    public function setControllerLayoutDirDotPath(string $controller_layout_dir_dot_path): void
    {
        $this->controller_layout_dir_dot_path = $controller_layout_dir_dot_path;
    }

    public function getPreEntityTable(): string
    {
        return $this->pre_entity_table;
    }

    public function setPreEntityTable(string $pre_entity_table): void
    {
        $this->pre_entity_table = $pre_entity_table;
    }

    public function isCreatedInclude(): bool
    {
        return $this->created_include;
    }

    public function setCreatedInclude(bool $created_include): void
    {
        $this->created_include = $created_include;
    }

    public function isUpdatedInclude(): bool
    {
        return $this->updated_include;
    }

    public function setUpdatedInclude(bool $updated_include): void
    {
        $this->updated_include = $updated_include;
    }

    public function isModifiedInclude(): bool
    {
        return $this->modified_include;
    }

    public function setModifiedInclude(bool $modified_include): void
    {
        $this->modified_include = $modified_include;
    }

    public function isDeletedInclude(): bool
    {
        return $this->deleted_include;
    }

    public function setDeletedInclude(bool $deleted_include): void
    {
        $this->deleted_include = $deleted_include;
    }

    public function isFlashInclude(): bool
    {
        return $this->flash_include;
    }

    public function setFlashInclude(bool $flash_include): void
    {
        $this->flash_include = $flash_include;
    }
}
