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
    
    #[Column(type: 'string(20)')]
    private ?string $route_prefix = '';
    
    #[Column(type: 'string(20)')]
    private ?string $route_suffix = '';
    
    #[Column(type: 'string(50)')]
    private ?string $camelcase_capital_name = '';
    
    #[Column(type: 'string(20)')]
    private ?string $small_singular_name = '';
    
    #[Column(type: 'string(20)')]
    private ?string $small_plural_name = '';
    
    #[Column(type: 'string(100)')]
    private ?string $namespace_path = '';
    
    #[Column(type: 'string(100)')]
    private ?string $controller_layout_dir = '';
    
    #[Column(type: 'string(100)')]
    private ?string $controller_layout_dir_dot_path = '';  
        
    #[Column(type: 'string(50)')]
    private ?string $pre_entity_table = '';
    
    #[Column(type: 'bool', default: false)]
    private bool $modified_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $created_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $updated_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $deleted_include = false;
   
    #[Column(type: 'bool', default: true)]
    private bool $flash_include = true;
    
    public function __construct(
      string $route_prefix='',
      string $route_suffix='',
      string $camelcase_capital_name = '',
      string $small_singular_name ='',
      string $small_plural_name ='',
      string $namespace_path ='',
      string $controller_layout_dir = 'dirname(dirname(__DIR__)',
      string $controller_layout_dir_dot_path = '/Invoice/Layout/main.php',
      string $pre_entity_table = '',      
      bool $created_include = false,
      bool $updated_include = false,
      bool $modified_include = false,
      bool $deleted_include = false,      
      bool $flash_include = false
    )
    {
      $this->route_prefix = $route_prefix;
      $this->route_suffix = $route_suffix;
      $this->camelcase_capital_name = $camelcase_capital_name;
      $this->small_singular_name = $small_singular_name;
      $this->small_plural_name = $small_plural_name;
      $this->namespace_path = $namespace_path;
      $this->controller_layout_dir = $controller_layout_dir;
      $this->controller_layout_dir_dot_path = $controller_layout_dir_dot_path;
      $this->pre_entity_table = $pre_entity_table;
      $this->created_include = $created_include;
      $this->updated_include = $updated_include;
      $this->modified_include = $modified_include;
      $this->deleted_include = $deleted_include;
      $this->flash_include = $flash_include;
    }
    public function getGentor_id(): string
    {
        return (string)$this->id;
    }    
    
    public function getRoute_prefix(): string|null
    {
        return $this->route_prefix;
    }
    
    public function setRoute_prefix(string $route_prefix): void
    {
        $this->route_prefix = $route_prefix;
    }
    
    public function getRoute_suffix(): string|null
    {
        return $this->route_suffix;
    }
    
    public function setRoute_suffix(string $route_suffix): void
    {
        $this->route_suffix = $route_suffix;
    }
    
    public function getCamelcase_capital_name(): string|null
    {
        return $this->camelcase_capital_name;
    }
    
    public function setCamelcase_capital_name(string $camelcase_capital_name): void
    {
        $this->camelcase_capital_name = $camelcase_capital_name;     
    }
    
    public function getSmall_singular_name(): string|null
    {
        return $this->small_singular_name;
    }
    
    public function setSmall_singular_name(string $small_singular_name): void
    {
        $this->small_singular_name = $small_singular_name;
    }
    
    public function getSmall_plural_name(): string|null
    {
        return $this->small_plural_name;
    }
    
    public function setSmall_plural_name(string $small_plural_name): void
    {
        $this->small_plural_name = $small_plural_name;
    }
    
    public function getNamespace_path(): string|null
    {
        return $this->namespace_path;
    }
    
    public function setNamespace_path(string $namespace_path): void
    {
        $this->namespace_path = $namespace_path;
    }
    
    public function getController_layout_dir(): string|null
    {
        return $this->controller_layout_dir;
    }
    
    public function setController_layout_dir(string $controller_layout_dir): void
    {
        $this->controller_layout_dir = $controller_layout_dir;
    }
    
    public function getController_layout_dir_dot_path(): string|null
    {
        return $this->controller_layout_dir_dot_path;
    }
    
    public function setController_layout_dir_dot_path(string $controller_layout_dir_dot_path): void
    {
        $this->controller_layout_dir_dot_path = $controller_layout_dir_dot_path;
    }
            
    public function getPre_entity_table(): string|null
    {
        return $this->pre_entity_table;
    }
    
    public function setPre_entity_table(string $pre_entity_table): void
    {
        $this->pre_entity_table = $pre_entity_table;
    }
        
    public function isCreated_include(): bool
    {
        return $this->created_include;
    }
    
    public function setCreated_include(bool $created_include): void
    {
        $this->created_include = $created_include;
    }
    
    public function isUpdated_include(): bool
    {
        return $this->updated_include;
    }
    
    public function setUpdated_include(bool $updated_include): void
    {
        $this->updated_include = $updated_include;
    }
    
    public function isModified_include(): bool
    {
        return $this->modified_include;
    }
    
    public function setModified_include(bool $modified_include): void
    {
        $this->modified_include = $modified_include;
    }
    
    public function isDeleted_include(): bool
    {
        return $this->deleted_include;
    }
    
    public function setDeleted_include(bool $deleted_include): void
    {
        $this->deleted_include = $deleted_include;
    }
    
    public function isFlash_include(): bool
    {
        return $this->flash_include;
    }
    
    public function setFlash_include(bool $flash_include): void
    {
        $this->flash_include = $flash_include;
    }
}
