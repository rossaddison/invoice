<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use App\Infrastructure\Persistence\UnitPeppol\UnitPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class UnitPeppolForm extends FormModel
{
    #[Required]
    private ?int $unit_id = null;
    #[Required]
    #[Length(min: 0, max: 3)]
    private ?string $code = '';
    #[Required]
    #[Length(min: 0, max: 120)]
    private ?string $name = '';
    #[Required]
    private ?string $description = '';

    public static function show(UnitPeppol $unitPeppol): self
    {
        $form = new self();
        $form->unit_id = $unitPeppol->reqUnitId();
        $form->code = $unitPeppol->getCode();
        $form->name = $unitPeppol->getName();
        $form->description = $unitPeppol->getDescription();
        return $form;
    }
    
    public function getUnitId(): ?int
    {
        return $this->unit_id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
