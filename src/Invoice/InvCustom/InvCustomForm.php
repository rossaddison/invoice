<?php

declare(strict_types=1);

namespace App\Invoice\InvCustom;

use App\Invoice\Entity\InvCustom;
use Yiisoft\FormModel\FormModel;

final class InvCustomForm extends FormModel
{
    private ?int $inv_id = null;
    private ?int $custom_field_id = null;
    private ?string $value = '';

    public function __construct(InvCustom $invCustom)
    {
        $this->inv_id = (int) $invCustom->getInvId();
        $this->custom_field_id = (int) $invCustom->getCustomFieldId();
        $this->value = $invCustom->getValue();
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
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
