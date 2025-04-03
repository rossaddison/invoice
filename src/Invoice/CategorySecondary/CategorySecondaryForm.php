<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Invoice\Entity\CategorySecondary;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CategorySecondaryForm extends FormModel
{    
    private ?int $category_primary_id = null;
    
    #[Required]
    private ?string $name = '';
    private ?int $id = null;

    public function __construct(CategorySecondary $categorySecondary) 
    {
        $this->category_primary_id = $categorySecondary->getCategory_primary_id();
        $this->name = $categorySecondary->getName();
        $this->id = $categorySecondary->getId();
    }
    
    public function getCategory_primary_id() : int|null
    {
        return $this->category_primary_id;
    }

    public function getName() : string|null
    {
        return $this->name;
    }

    public function getId() : int|null
    {
        return $this->id;
    }

    /**
     * @return string
     * @psalm-return ''
     */#[\Override]
    public function getFormName(): string
    {
        return '';
    }

}
