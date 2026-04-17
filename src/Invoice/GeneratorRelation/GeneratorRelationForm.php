<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;
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

    public function __construct(GentorRelation $gentorRelation)
    {
        $this->lowercasename = $gentorRelation->getLowercaseName();
        $this->camelcasename = $gentorRelation->getCamelcaseName();
        $this->view_field_name = $gentorRelation->getViewFieldName();
        $this->gentor_id = $gentorRelation->getGentorId();
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

    public function getGentorId(): ?int
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
