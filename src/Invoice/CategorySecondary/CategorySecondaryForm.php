<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Regex;
use Yiisoft\Validator\Rule\Required;

final class CategorySecondaryForm extends FormModel
{
    private ?int $category_primary_id = null;

    #[Required]
    private ?string $name = '';

    /** A HomeCare run's starting postcode: optional, but a UK postcode when given. */
    #[Regex(pattern: '/^\\s*([A-Za-z]{1,2}\\d[A-Za-z\\d]?)\\s*(\\d[A-Za-z]{2})\\s*$/', message: 'Enter a UK postcode such as AB1 2CD.', skipOnEmpty: true)]
    private ?string $start_postcode = '';

    public static function show(CategorySecondary $categorySecondary): self
    {
        $form = new self();

        $form->category_primary_id = $categorySecondary->reqCategoryPrimaryId();

        $form->name = $categorySecondary->getName() ?? '';

        $form->start_postcode = $categorySecondary->getStartPostcode() ?? '';

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

    public function getStartPostcode(): ?string
    {
        return $this->start_postcode;
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
