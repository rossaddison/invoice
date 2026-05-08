<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Infrastructure\Persistence\Unit\Unit;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class UnitForm extends FormModel
{
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $unit_name = null;
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $unit_name_plrl = null;

    public static function show(Unit $unit): self
    {
        $form = new self();
        $form->unit_name = $unit->getUnitName();
        $form->unit_name_plrl = $unit->getUnitNamePlrl();
        return $form;
    }

    public function getUnitName(): ?string
    {
        return $this->unit_name;
    }

    public function getUnitNamePlrl(): ?string
    {
        return $this->unit_name_plrl;
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
