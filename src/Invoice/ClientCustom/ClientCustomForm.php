<?php

declare(strict_types=1);

namespace App\Invoice\ClientCustom;

use App\Infrastructure\Persistence\ClientCustom\ClientCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StringValue;

final class ClientCustomForm extends FormModel
{
    #[Integer]
    #[Required]
    private ?int $client_id = null;

    #[Integer]
    #[Required]
    private ?int $custom_field_id = null;

    #[StringValue()]
    #[Required]
    private ?string $value = '';

    public static function show(ClientCustom $clientCustom): self
    {
        $form = new self();
        $form->client_id = $clientCustom->reqClientId();
        $form->custom_field_id = $clientCustom->reqCustomFieldId();
        $form->value = (string) $clientCustom->getValue();
        return $form;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
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
