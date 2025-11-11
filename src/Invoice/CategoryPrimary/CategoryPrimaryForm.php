<?php

declare(strict_types=1);

namespace App\Invoice\CategoryPrimary;

use App\Invoice\Entity\CategoryPrimary;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CategoryPrimaryForm extends FormModel
{
    #[Required]
    private ?string $name = '';

    public function __construct(CategoryPrimary $categoryPrimary)
    {
        $this->name = $categoryPrimary->getName();
    }

    public function getName(): ?string
    {
        return $this->name;
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
