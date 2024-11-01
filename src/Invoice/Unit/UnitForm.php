<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\Entity\Unit;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class UnitForm extends FormModel
{
    private ?int $unit_id = null;

    #[Required]
    private ?string $unit_name = null;
    #[Required]
    private ?string $unit_name_plrl = null;

    public function __construct(Unit $unit)
    {
        $this->unit_id = $unit->getUnit_id();
        $this->unit_name = $unit->getUnit_name();
        $this->unit_name_plrl = $unit->getUnit_name_plrl();
    }

    public function getUnit_id(): int|null
    {
        return $this->unit_id;
    }

    public function getUnit_name(): string|null
    {
        return $this->unit_name;
    }

    public function getUnit_name_plrl(): string|null
    {
        return $this->unit_name_plrl;
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
}
