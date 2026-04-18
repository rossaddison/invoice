<?php

declare(strict_types=1);

namespace App\Invoice\CategoryPrimary;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CategoryPrimaryForm extends FormModel
{
    #[Required]
    private string $name = '';

    public function getName(): string
    {
        return $this->name;
    }

    public static function show(CategoryPrimary $categoryPrimary): self
    {
        $form = new self();
        
        $form->name = $categoryPrimary->getName() ?? '';

        return $form;
    }

    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
