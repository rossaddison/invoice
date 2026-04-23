<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Infrastructure\Persistence\CustomField\CustomField;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomFieldForm extends FormModel
{
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
    public static function show(CustomField $custom_field): self
    {
        $form = new self();
        $form->table = $custom_field->getTable();
        $form->label = $custom_field->getLabel();
        $form->type = $custom_field->getType();
        $form->location = $custom_field->getLocation();
        $form->order = $custom_field->getOrder();
        $form->required = $custom_field->getRequired();
        $form->email_min_length = $custom_field->getEmailMinLength();
        $form->email_max_length = $custom_field->getEmailMaxLength();
        $form->email_multiple = $custom_field->getEmailMultiple();
        $form->url_min_length = $custom_field->getUrlMinLength();
        $form->url_max_length = $custom_field->getUrlMaxLength();
        $form->text_min_length = $custom_field->getTextMinLength();
        $form->text_max_length = $custom_field->getTextMaxLength();
        $form->text_area_min_length = $custom_field->getTextAreaMinLength();
        $form->text_area_max_length = $custom_field->getTextAreaMaxLength();
        $form->text_area_cols = $custom_field->getTextAreaCols();
        $form->text_area_rows = $custom_field->getTextAreaRows();
        $form->text_area_wrap = $custom_field->getTextAreaWrap();
        $form->number_min = $custom_field->getNumberMin();
        $form->number_max = $custom_field->getNumberMax();
        return $form;
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
