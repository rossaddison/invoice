<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Gentor\Trait;

/**
 * @method int requireId(?int $id, string $context)
 */
trait GentorTrait2
{

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
