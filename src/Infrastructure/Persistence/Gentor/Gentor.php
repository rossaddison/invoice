<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Gentor;

use App\Infrastructure\Persistence\Trait\RequireId;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use App\Infrastructure\Persistence\Gentor\Trait\GentorTrait1;
use App\Infrastructure\Persistence\Gentor\Trait\GentorTrait2;

#[Entity(repository: \App\Invoice\Generator\GeneratorRepository::class)]
class Gentor
{
    use RequireId;
    use GentorTrait1;
    use GentorTrait2;
 
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
}
