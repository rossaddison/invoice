<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Gentor\Trait;

/**
 * @method int requireId(?int $id, string $context)
 */
trait GentorTrait1
{

    public function reqGentorId(): int
    {
        return $this->requireId($this->id, 'Gentor');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
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
}
