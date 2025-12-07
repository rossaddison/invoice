<?php

declare(strict_types=1);

namespace App\Invoice\FamilyCustom;

use App\Invoice\Entity\FamilyCustom;
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

    public function __construct(FamilyCustom $familyCustom)
    {
        $this->family_id = (int) $familyCustom->getFamily_id();
        $this->custom_field_id = (int) $familyCustom->getCustom_field_id();
        $this->value = (string) $familyCustom->getValue();
    }

    public function getFamily_id(): ?int
    {
        return $this->family_id;
    }

    public function getCustom_field_id(): ?int
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
