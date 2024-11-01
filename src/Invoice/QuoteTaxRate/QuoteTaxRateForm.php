<?php

declare(strict_types=1);

namespace App\Invoice\QuoteTaxRate;

use App\Invoice\Entity\QuoteTaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteTaxRateForm extends FormModel
{
    private ?int $quote_id = null;
    #[Required]
    private ?int $tax_rate_id = null;
    private ?int $include_item_tax = null;
    private ?float $quote_tax_rate_amount = null;

    public function __construct(QuoteTaxRate $quoteTaxRate)
    {
        $this->quote_id = (int)$quoteTaxRate->getQuote_id();
        $this->tax_rate_id = (int)$quoteTaxRate->getTax_rate_id();
        $this->include_item_tax = $quoteTaxRate->getInclude_item_tax();
        $this->quote_tax_rate_amount = $quoteTaxRate->getQuote_tax_rate_amount();
    }

    public function getQuote_id(): int|null
    {
        return $this->quote_id;
    }

    public function getTax_rate_id(): int|null
    {
        return $this->tax_rate_id;
    }

    public function getInclude_item_tax(): int|null
    {
        return $this->include_item_tax;
    }

    public function getQuote_tax_rate_amount(): float
    {
        return $this->quote_tax_rate_amount ?? 0.00;
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
