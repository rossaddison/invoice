<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\Entity\Family;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class FamilyForm extends FormModel
{
    #[Required]
    private ?string $family_name = '';

    public ?string $category_primary_id = null;

    public ?string $category_secondary_id = null;

    public function __construct(Family $family)
    {
        $this->family_name = $family->getFamily_name();
        $this->category_primary_id = $family->getCategory_primary_id();
        $this->category_secondary_id = $family->getCategory_secondary_id();
    }

    public function getFamily_name(): ?string
    {
        return $this->family_name;
    }

    public function getCategory_primary_id(): string
    {
        return (string) $this->category_primary_id;
    }

    public function getCategory_secondary_id(): string
    {
        return (string) $this->category_secondary_id;
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
