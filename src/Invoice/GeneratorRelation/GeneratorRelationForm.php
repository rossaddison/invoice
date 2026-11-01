<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Infrastructure\Persistence\GentorRelation\GentorRelation;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class GeneratorRelationForm extends FormModel
{
    #[Required]
    private ?string $lowercasename = '';

    #[Required]
    private ?string $camelcasename = '';

    #[Required]
    private ?string $view_field_name = '';

    #[Required]
    private ?int $gentor_id = null;

    // Optional: see GentorRelation::getForeignKeyColumn() -- only needed
    // when the target entity's primary key isn't an auto-increment int,
    // so unlike the other fields here it's genuinely not required.
    private ?string $foreign_key_column = '';

    public static function show(GentorRelation $gentorRelation): self
    {
        $form = new self();
        $form->lowercasename = $gentorRelation->getLowercaseName();
        $form->camelcasename = $gentorRelation->getCamelcaseName();
        $form->view_field_name = $gentorRelation->getViewFieldName();
        $form->gentor_id = $gentorRelation->reqGentorId();
        $form->foreign_key_column = $gentorRelation->getForeignKeyColumn();
        return $form;
    }

    public function getLowercaseName(): ?string
    {
        return $this->lowercasename;
    }

    public function getCamelcaseName(): ?string
    {
        return $this->camelcasename;
    }

    public function getViewFieldName(): ?string
    {
        return $this->view_field_name;
    }

    public function reqGentorId(): ?int
    {
        return $this->gentor_id;
    }

    public function getForeignKeyColumn(): ?string
    {
        return $this->foreign_key_column;
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
