<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class UnitPeppolForm extends FormModel
{
    #[Required]
    private ?string $unit_id = '';
    #[Required]
    #[Length(min: 0, max: 3)]
    private ?string $code = '';
    #[Required]
    #[Length(min: 0, max: 120)]
    private ?string $name = '';
    #[Required]
    private ?string $description = '';

    public function __construct(UnitPeppol $unitPeppol)
    {
        $this->unit_id = $unitPeppol->getUnitId();
        $this->code = $unitPeppol->getCode();
        $this->name = $unitPeppol->getName();
        $this->description = $unitPeppol->getDescription();
    }
    
    public function getUnitId(): ?string
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
