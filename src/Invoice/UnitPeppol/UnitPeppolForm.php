<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class UnitPeppolForm extends FormModel
{
    private ?string $id = '';
    #[Required]
    private ?string $unit_id = '';
    #[Required]
    private ?string $code = '';
    #[Required]
    private ?string $name = '';
    #[Required]
    private ?string $description = '';

    public function __construct(UnitPeppol $unitPeppol)
    {
        $this->id = $unitPeppol->getId();
        $this->unit_id = $unitPeppol->getUnit_id();
        $this->code = $unitPeppol->getCode();
        $this->name = $unitPeppol->getName();
        $this->description = $unitPeppol->getDescription();
    }

    public function getId(): string|null
    {
        return $this->id;
    }

    public function getUnit_id(): string|null
    {
        return $this->unit_id;
    }

    public function getCode(): string|null
    {
        return $this->code;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getDescription(): string|null
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
