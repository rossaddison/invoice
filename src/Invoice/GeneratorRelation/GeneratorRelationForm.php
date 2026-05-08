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

    public static function show(GentorRelation $gentorRelation): self
    {
        $form = new self();
        $form->lowercasename = $gentorRelation->getLowercaseName();
        $form->camelcasename = $gentorRelation->getCamelcaseName();
        $form->view_field_name = $gentorRelation->getViewFieldName();
        $form->gentor_id = $gentorRelation->reqGentorId();
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
