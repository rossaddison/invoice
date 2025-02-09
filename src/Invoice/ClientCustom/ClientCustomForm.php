<?php

declare(strict_types=1);

namespace App\Invoice\ClientCustom;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use App\Invoice\Entity\ClientCustom;

final class ClientCustomForm extends FormModel
{
    private ?int $client_id = null;
    private ?int $custom_field_id = null;
    private ?string $value = '';

    public function __construct(ClientCustom $clientCustom)
    {
        $this->client_id = (int)$clientCustom->getClient_id();
        $this->custom_field_id = (int)$clientCustom->getCustom_field_id();
        $this->value = (string)$clientCustom->getValue();
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
    public function getFormName(): string
    {
        return '';
    }

    /**
     * @return Required[][]
     *
     * @psalm-return array{client_id: list{Required}, custom_field_id: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'client_id' => [new Required()],
            'custom_field_id' => [new Required()],
        ];
    }
}
