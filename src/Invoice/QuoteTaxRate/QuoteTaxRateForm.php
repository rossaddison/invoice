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
        $this->quote_id = (int) $quoteTaxRate->getQuoteId();
        $this->tax_rate_id = (int) $quoteTaxRate->getTaxRateId();
        $this->include_item_tax = $quoteTaxRate->getIncludeItemTax();
        $this->quote_tax_rate_amount = $quoteTaxRate->getQuoteTaxRateAmount();
    }

    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function getTaxRateId(): ?int
    {
        return $this->tax_rate_id;
    }

    public function getIncludeItemTax(): ?int
    {
        return $this->include_item_tax;
    }

    public function getQuoteTaxRateAmount(): float
    {
        return $this->quote_tax_rate_amount ?? 0.00;
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
