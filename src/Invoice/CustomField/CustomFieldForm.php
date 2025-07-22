<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Invoice\Entity\CustomField;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomFieldForm extends FormModel
{
    private ?int $id = null;
    #[Required]
    private ?string $table = '';
    #[Required]
    private ?string $label = '';
    #[Required]
    private ?string $type = '';
    #[Required]
    private ?int $location = null;
    #[Required]
    private ?int $order = null;
    #[Required]
    private ?bool $required = false;

    public function __construct(CustomField $custom_field)
    {
        $this->id       = (int) $custom_field->getId();
        $this->table    = $custom_field->getTable();
        $this->label    = $custom_field->getLabel();
        $this->type     = $custom_field->getType();
        $this->location = $custom_field->getLocation();
        $this->order    = $custom_field->getOrder();
        $this->required = $custom_field->getRequired();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getLocation(): ?int
    {
        return $this->location;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
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
