<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class GeneratorRelationForm extends FormModel
{
    private ?string $lowercasename = '';
    
    private ?string $camelcasename = '';
    
    private ?string $view_field_name = '';
    
    private ?int $id = null;
    
    private ?int $gentor_id = null;
    
    public function getLowercase_name(): string|null
    {
        return $this->lowercasename;
    }
    
    public function getCamelcase_name(): string|null
    {
        return $this->camelcasename;
    }
    
    public function getView_field_name(): string|null
    {
        return $this->view_field_name;
    }
    
    public function getGentor_id(): int|null
    {
        return $this->gentor_id;
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
     * @psalm-return array{lowercasename: list{Required}, camelcasename: list{Required}, view_field_name: list{Required}, gentor_id: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'lowercasename' => [new Required()],
            'camelcasename' => [new Required()],
            'view_field_name' => [new Required()],
            'gentor_id' => [new Required()],
        ];
    }
}
