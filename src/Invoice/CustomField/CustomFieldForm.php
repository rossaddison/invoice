<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Invoice\Entity\CustomField;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomFieldForm extends FormModel
{
    private ?int $id = null;
    private ?string $table = '';
    private ?string $label = '';
    #[Required]
    private ?string $type = '';
    #[Required]
    private ?int $location = null;
    #[Required]
    private ?int $order = null;
    #[Required]
    private ?bool $required = false;
    private ?int $email_min_length = null;
    private ?int $email_max_length = null;
    private ?bool $email_multiple = false;
    private ?int $url_min_length = null;
    private ?int $url_max_length = null;
    private ?int $text_min_length = null;
    private ?int $text_max_length = null;
    private ?int $text_area_min_length = null;
    private ?int $text_area_max_length = null;
    private ?int $text_area_cols = null;
    private ?int $text_area_rows = null;
    private ?string $text_area_wrap = '';
    private ?int $number_min = null;
    private ?int $number_max = null;
    public function __construct(CustomField $custom_field)
    {
        $this->id = (int) $custom_field->getId();
        $this->table = $custom_field->getTable();
        $this->label = $custom_field->getLabel();
        $this->type = $custom_field->getType();
        $this->location = $custom_field->getLocation();
        $this->order = $custom_field->getOrder();
        $this->required = $custom_field->getRequired();
        $this->email_min_length = $custom_field->getEmailMinLength();
        $this->email_max_length = $custom_field->getEmailMaxLength();
        $this->email_multiple = $custom_field->getEmailMultiple();
        $this->url_min_length = $custom_field->getUrlMinLength();
        $this->url_max_length = $custom_field->getUrlMaxLength();
        $this->text_min_length = $custom_field->getTextMinLength();
        $this->text_max_length = $custom_field->getTextMaxLength();
        $this->text_area_min_length = $custom_field->getTextAreaMinLength();
        $this->text_area_max_length = $custom_field->getTextAreaMaxLength();
        $this->text_area_cols = $custom_field->getTextAreaCols();
        $this->text_area_rows = $custom_field->getTextAreaRows();
        $this->text_area_wrap = $custom_field->getTextAreaWrap();
        $this->number_min = $custom_field->getNumberMin();
        $this->number_max = $custom_field->getNumberMax();
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

    public function getEmailMinLength(): ?int
    {
        return $this->email_min_length;
    }

    public function getEmailMaxLength(): ?int
    {
        return $this->email_max_length;
    }

    public function getEmailMultiple(): ?bool
    {
        return $this->email_multiple;
    }

    public function getTextMinLength(): ?int
    {
        return $this->text_min_length;
    }

    public function getTextMaxLength(): ?int
    {
        return $this->text_max_length;
    }

    public function getTextAreaMinLength(): ?int
    {
        return $this->text_area_min_length;
    }

    public function getTextAreaMaxLength(): ?int
    {
        return $this->text_area_max_length;
    }

    public function getTextAreaCols(): ?int
    {
        return $this->text_area_cols;
    }

    public function getTextAreaRows(): ?int
    {
        return $this->text_area_rows;
    }

    public function getTextAreaWrap(): ?string
    {
        return $this->text_area_wrap;
    }

    public function getNumberMin(): ?int
    {
        return $this->number_min;
    }

    public function getNumberMax(): ?int
    {
        return $this->number_max;
    }

    public function getUrlMinLength(): ?int
    {
        return $this->url_min_length;
    }

    public function getUrlMaxLength(): ?int
    {
        return $this->url_max_length;
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
