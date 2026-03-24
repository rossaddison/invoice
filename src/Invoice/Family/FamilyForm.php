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

    public ?string $family_commalist = '';
    
    public ?string $family_productprefix = '';
    
    public ?string $category_primary_id = null;

    public ?string $category_secondary_id = null;
    
    public function __construct(Family $family)
    {
        $this->family_name = $family->getFamilyName();
        $this->family_commalist = $family->getFamilyCommalist();
        $this->family_productprefix = $family->getFamilyProductprefix();
        $this->category_primary_id = $family->getCategoryPrimaryId();
        $this->category_secondary_id = $family->getCategorySecondaryId();        
     }

    public function getFamilyName(): ?string
    {
        return $this->family_name;
    }

    public function getCategoryPrimaryId(): string
    {
        return (string) $this->category_primary_id;
    }

    public function getCategorySecondaryId(): string
    {
        return (string) $this->category_secondary_id;
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
