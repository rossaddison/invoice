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
    
    public function __construct(Family $family)
    {
        $this->family_name = $family->getFamily_name();
    }        
    
    public function getFamily_name(): ?string
    {
        return $this->family_name;
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
