<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderCustomForm extends FormModel
{
    private ?int $so_id = null;
    private ?int $custom_field_id = null;

    #[Required]
    private ?string $value = '';

    public function __construct(SalesOrderCustom $salesOrderCustom)
    {
        $this->so_id = (int) $salesOrderCustom->getSo_id();
        $this->custom_field_id = (int) $salesOrderCustom->getCustom_field_id();
        $this->value = $salesOrderCustom->getValue();
    }

    public function getSo_id(): int|null
    {
        return $this->so_id;
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
