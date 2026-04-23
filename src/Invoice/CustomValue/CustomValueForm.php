<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use App\Infrastructure\Persistence\{
    CustomField\CustomField,
    CustomValue\CustomValue
};
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomValueForm extends FormModel
{
    #[Required]
    private ?int $custom_field_id = null;
    #[Required]
    private ?string $value = '';
    /**
     * Related logic: see App\Infrastructure\Persistence\CustomValue\CustomValue
            #[BelongsTo(target: CustomField::class, nullable: false, fkAction:'NO ACTION')]
            private ?CustomField $custom_field = null;
     */
    private ?CustomField $customfield = null;

    public static function show(CustomValue $custom_value): self
    {
        $form = new self();
        $form->custom_field_id = $custom_value->getCustomFieldId();
        $form->value = $custom_value->getValue();
        $form->customfield = $custom_value->getCustomField();
        return $form;
    }
    
    public function getCustomFieldId(): ?int
    {
        return $this->custom_field_id;
    }

    /**
     * Related logic: see The above construct retrieves the relation getCustomField() from the entity CustomValue
     * Related logic: see resources/views/invoice/customvalue/_view.php search Field::text($form, 'custom_field_id')
     * Related logic: see Use this function as a relation to get to the Custom Field label value
     */
    public function getCustomField(): ?CustomField
    {
        return $this->customfield;
    }

    public function getValue(): ?string
    {
        return $this->value;
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
