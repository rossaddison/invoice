<?php

declare(strict_types=1);

namespace App\Invoice\InvCustom;

use App\Infrastructure\Persistence\{
    InvCustom\InvCustom
};
use Yiisoft\FormModel\FormModel;

final class InvCustomForm extends FormModel
{
    private ?int $inv_id = null;
    private ?int $custom_field_id = null;
    private ?string $value = '';

    public static function show(InvCustom $invCustom, int $inv_id): self
    {
        $form = new self();
        $form->inv_id = $inv_id;
        $form->custom_field_id = $invCustom->reqCustomFieldId();
        $form->value = $invCustom->getValue();
        return $form;
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
