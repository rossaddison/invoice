<?php

declare(strict_types=1);

namespace App\Invoice\ItemLookup;

use App\Invoice\Entity\ItemLookup;
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

    public function __construct(ItemLookup $itemLookup)
    {
        $this->name = $itemLookup->getName();
        $this->description = $itemLookup->getDescription();
        $this->price = $itemLookup->getPrice();
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function getPrice(): float|null
    {
        return $this->price;
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
