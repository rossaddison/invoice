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

    private ?int $id = null;

    #[Required]
    private ?int $gentor_id = null;

    public function __construct(GentorRelation $gentorRelation)
    {
        $this->lowercasename   = $gentorRelation->getLowercase_name();
        $this->camelcasename   = $gentorRelation->getCamelcase_name();
        $this->view_field_name = $gentorRelation->getView_field_name();
        $this->gentor_id       = $gentorRelation->getGentor_id();
    }

    public function getLowercase_name(): ?string
    {
        return $this->lowercasename;
    }

    public function getCamelcase_name(): ?string
    {
        return $this->camelcasename;
    }

    public function getView_field_name(): ?string
    {
        return $this->view_field_name;
    }

    public function getGentor_id(): ?int
    {
        return $this->gentor_id;
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
