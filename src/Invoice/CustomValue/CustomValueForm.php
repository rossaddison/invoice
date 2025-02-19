<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\CustomValue;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomValueForm extends FormModel
{
    #[Required]
    private ?int $id = null;
    #[Required]
    private ?int $custom_field_id = null;
    #[Required]
    private ?string $value = '';
    /**
     * @see App\Invoice\Entity\CustomValue
            #[BelongsTo(target: CustomField::class, nullable: false, fkAction:'NO ACTION')]
            private ?CustomField $custom_field = null;
     */
    private ?CustomField $customfield = null;

    public function __construct(CustomValue $custom_value)
    {
        $this->id = (int)$custom_value->getId();
        $this->custom_field_id = $custom_value->getCustom_field_id();
        $this->value = $custom_value->getValue();
        $this->customfield = $custom_value->getCustomField();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getCustom_field_id(): int|null
    {
        return $this->custom_field_id;
    }

    /**
     * @see The above construct retrieves the relation getCustomField() from the entity CustomValue
     * @see resources/views/invoice/customvalue/_view.php search Field::text($form, 'custom_field_id')
     * @see Use this function as a relation to get to the Custom Field label value
     */
    public function getCustomField(): CustomField|null
    {
        return $this->customfield;
    }

    public function getValue(): string|null
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
