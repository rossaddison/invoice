<?php

declare(strict_types=1);

namespace App\Invoice\ItemLookup;

use App\Infrastructure\Persistence\ItemLookup\ItemLookup;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\GreaterThan;

final class ItemLookupForm extends FormModel
{
    #[Required]
    private ?string $name = '';

    #[Required]
    private ?string $description = '';

    #[GreaterThan(0.00)]
    private ?float $price = null;

    public static function show(ItemLookup $itemLookup): self
    {
        $form = new self();
        $form->name = $itemLookup->getName();
        $form->description = $itemLookup->getDescription();
        $form->price = $itemLookup->getPrice();
        return $form;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): ?float
    {
        return $this->price;
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
