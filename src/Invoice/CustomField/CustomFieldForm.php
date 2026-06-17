<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Infrastructure\Persistence\CustomField\CustomField;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomFieldForm extends FormModel
{
    public ?string $table = '';
    public ?string $label = '';
    #[Required]
    public ?string $type = '';
    #[Required]
    public ?int $location = null;
    #[Required]
    public ?int $order = null;
    #[Required]
    public ?bool $required = false;
    public ?int $email_min_length = null;
    public ?int $email_max_length = null;
    public ?bool $email_multiple = false;
    public ?int $url_min_length = null;
    public ?int $url_max_length = null;
    public ?int $text_min_length = null;
    public ?int $text_max_length = null;
    public ?int $text_area_min_length = null;
    public ?int $text_area_max_length = null;
    public ?int $text_area_cols = null;
    public ?int $text_area_rows = null;
    public ?string $text_area_wrap = '';
    public ?int $number_min = null;
    public ?int $number_max = null;

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
