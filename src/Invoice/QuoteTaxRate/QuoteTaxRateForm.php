<?php

declare(strict_types=1);

namespace App\Invoice\QuoteTaxRate;

use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteTaxRateForm extends FormModel
{
    private ?int $quote_id = null;
    #[Required]
    private ?int $tax_rate_id = null;
    private ?int $include_item_tax = null;
    private ?float $quote_tax_rate_amount = null;

    public static function show(QuoteTaxRate $quoteTaxRate): self
    {
        $form = new self();
        $form->quote_id = $quoteTaxRate->reqQuoteId();
        $form->tax_rate_id = $quoteTaxRate->reqTaxRateId();
        $form->include_item_tax = $quoteTaxRate->getIncludeItemTax();
        $form->quote_tax_rate_amount = $quoteTaxRate->getQuoteTaxRateAmount();
        return $form;
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
