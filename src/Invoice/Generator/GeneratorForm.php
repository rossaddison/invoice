<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class GeneratorForm extends FormModel
{
    private ?int $id = null;
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

    private bool $flash_include    = true;
    private bool $created_include  = true;
    private bool $modified_include = true;
    private bool $updated_include  = true;
    private bool $deleted_include  = true;

    public function __construct(Gentor $generator)
    {
        $this->route_prefix                   = $generator->getRoute_prefix();
        $this->route_suffix                   = $generator->getRoute_suffix();
        $this->camelcase_capital_name         = $generator->getCamelcase_capital_name();
        $this->small_singular_name            = $generator->getSmall_singular_name();
        $this->small_plural_name              = $generator->getSmall_plural_name();
        $this->namespace_path                 = $generator->getNamespace_path();
        $this->controller_layout_dir          = $generator->getController_layout_dir();
        $this->controller_layout_dir_dot_path = $generator->getController_layout_dir_dot_path();
        $this->pre_entity_table               = $generator->getPre_entity_table();
        $this->flash_include                  = $generator->isFlash_include();
        $this->created_include                = $generator->isCreated_include();
        $this->modified_include               = $generator->isModified_include();
        $this->updated_include                = $generator->isUpdated_include();
        $this->deleted_include                = $generator->isDeleted_include();
    }

    public function getRoute_prefix(): string
    {
        return $this->route_prefix;
    }

    public function getRoute_suffix(): string
    {
        return $this->route_suffix;
    }

    public function getCamelcase_capital_name(): string
    {
        return $this->camelcase_capital_name;
    }

    public function getSmall_singular_name(): string
    {
        return $this->small_singular_name;
    }

    public function getSmall_plural_name(): string
    {
        return $this->small_plural_name;
    }

    public function getNamespace_path(): string
    {
        return $this->namespace_path;
    }

    public function getController_layout_dir(): string
    {
        return $this->controller_layout_dir;
    }

    public function getController_layout_dir_dot_path(): string
    {
        return $this->controller_layout_dir_dot_path;
    }

    public function getPre_entity_table(): string
    {
        return $this->pre_entity_table;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
