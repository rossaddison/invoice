<?php

declare(strict_types=1);

namespace App\Invoice\FamilyCustom;

use App\Infrastructure\Persistence\FamilyCustom\FamilyCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StringValue;

final class FamilyCustomForm extends FormModel
{
    #[Integer]
    #[Required]
    private ?int $family_id = null;

    #[Integer]
    #[Required]
    private ?int $custom_field_id = null;

    #[StringValue()]
    #[Required]
    private ?string $value = '';
    public static function show(FamilyCustom $familyCustom): self
    {
        $form = new self();
        $form->family_id = $familyCustom->reqId();
        $form->custom_field_id = $familyCustom->reqCustomFieldId();
        $form->value = (string) $familyCustom->getValue();
        return $form;
    }

    public function reqId(): ?int
    {
        return $this->family_id;
    }

    public function getCustomFieldId(): ?int
    {
        return $this->custom_field_id;
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
