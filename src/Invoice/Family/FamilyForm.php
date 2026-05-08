<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Infrastructure\Persistence\Family\Family;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class FamilyForm extends FormModel
{
    #[Required]
    private ?string $family_name = '';

    public ?string $family_commalist = '';

    public ?string $family_productprefix = '';

    public ?int $category_primary_id = null;

    public ?int $category_secondary_id = null;

    public static function show(Family $family): self
    {
        $form = new self();
        $form->family_name = $family->getFamilyName();
        $form->family_commalist = $family->getFamilyCommalist();
        $form->family_productprefix = $family->getFamilyProductprefix();
        $form->category_primary_id = $family->reqCategoryPrimaryId();
        $form->category_secondary_id = $family->reqCategorySecondaryId();
        return $form;
    }

    public function getFamilyName(): ?string
    {
        return $this->family_name;
    }

    public function getCategoryPrimaryId(): ?int
    {
        return $this->category_primary_id;
    }

    public function getCategorySecondaryId(): ?int
    {
        return $this->category_secondary_id;
    }

    public function getFamilyCommalist(): ?string
    {
        return $this->family_commalist;
    }

    public function getFamilyProductprefix(): ?string
    {
        return $this->family_productprefix;
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
