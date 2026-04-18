<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CategorySecondaryForm extends FormModel
{
    private ?int $category_primary_id = null;

    #[Required]
    private ?string $name = '';
    
    public static function show(CategorySecondary $categorySecondary): self
    {
        $form = new self();
        
        $form->category_primary_id = $categorySecondary->getCategoryPrimaryId();
        
        $form->name = $categorySecondary->getName() ?? '';
        
        return $form;
    }

    public function getCategoryPrimaryId(): ?int
    {
        return $this->category_primary_id;
    }

    public function getName(): ?string
    {
        return $this->name;
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
