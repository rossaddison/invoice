<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\Entity\QuoteItemAllowanceCharge;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteItemAllowanceChargeForm extends FormModel
{
    private ?int $quote_id = null;
    #[Required]
    private ?int $allowance_charge_id = null;
    #[Required]
    private ?float $amount = null;
    #[Required]
    private ?float $vat_or_tax = null;

    public function __construct(
        QuoteItemAllowanceCharge $quoteItemAllowanceCharge,
        private readonly ?int $quote_item_id)
    {
        $this->quote_id = (int) $quoteItemAllowanceCharge->getQuoteId();
        $this->allowance_charge_id = (int) $quoteItemAllowanceCharge->getAllowanceChargeId();
        $this->amount = (float) $quoteItemAllowanceCharge->getAmount();
        $this->vat_or_tax = (float) $quoteItemAllowanceCharge->getVatOrTax();
    }

    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function getQuoteItemId(): ?int
    {
        return $this->quote_item_id;
    }

    public function getAllowanceChargeId(): ?int
    {
        return $this->allowance_charge_id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getVatOrTax(): ?float
    {
        return $this->vat_or_tax;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
