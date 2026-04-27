<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge;
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
    private ?int $quote_item_id = null;

    public static function show(
        QuoteItemAllowanceCharge $quoteItemAllowanceCharge,
        ?int $quote_item_id
    ): self {
        $form = new self();
        $form->quote_id = $quoteItemAllowanceCharge->isPersisted()
            ? $quoteItemAllowanceCharge->reqQuoteId()
            : null;
        $form->allowance_charge_id = $quoteItemAllowanceCharge->isPersisted()
            ? $quoteItemAllowanceCharge->reqAllowanceChargeId()
            : null;
        $form->amount = (float) $quoteItemAllowanceCharge->getAmount();
        $form->vat_or_tax = (float) $quoteItemAllowanceCharge->getVatOrTax();
        $form->quote_item_id = $quote_item_id;
        return $form;
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
