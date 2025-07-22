<?php

declare(strict_types=1);

namespace App\Invoice\InvCustom;

use App\Invoice\Entity\InvCustom;
use Yiisoft\FormModel\FormModel;

final class InvCustomForm extends FormModel
{
    private ?int $inv_id          = null;
    private ?int $custom_field_id = null;
    private ?string $value        = '';

    public function __construct(InvCustom $invCustom)
    {
        $this->inv_id          = (int) $invCustom->getInv_id();
        $this->custom_field_id = (int) $invCustom->getCustom_field_id();
        $this->value           = $invCustom->getValue();
    }

    public function getInv_id(): ?int
    {
        return $this->inv_id;
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
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
