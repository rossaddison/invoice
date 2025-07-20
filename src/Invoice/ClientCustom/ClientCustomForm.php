<?php

declare(strict_types=1);

namespace App\Invoice\ClientCustom;

use App\Invoice\Entity\ClientCustom;
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

    public function __construct(ClientCustom $clientCustom)
    {
        $this->client_id = (int) $clientCustom->getClient_id();
        $this->custom_field_id = (int) $clientCustom->getCustom_field_id();
        $this->value = (string) $clientCustom->getValue();
    }

    public function getClient_id(): int|null
    {
        return $this->client_id;
    }

    public function getCustom_field_id(): int|null
    {
        return $this->custom_field_id;
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
