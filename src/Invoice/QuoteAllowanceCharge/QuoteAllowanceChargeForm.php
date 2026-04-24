<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAllowanceCharge;

use App\Infrastructure\Persistence\QuoteAllowanceCharge\{
    QuoteAllowanceCharge,
};
use Yiisoft\Validator\Rule\GreaterThan;
use Yiisoft\FormModel\FormModel;

final class QuoteAllowanceChargeForm extends FormModel
{
    private ?int $allowance_charge_id = null;

    #[GreaterThan(0)]
    private ?int $amount = null;

    private ?int $quote_id = null;

    private ?int $vat_or_tax = null;

    public static function show(
        QuoteAllowanceCharge $quoteAllowanceCharge,
        ?int $quote_id): self
    {
        $form = new self();
        $form->allowance_charge_id =
            (int) $quoteAllowanceCharge->getAllowanceChargeId();
        $form->amount = (int) $quoteAllowanceCharge->getAmount();
        $form->quote_id = $quote_id;
        $form->vat_or_tax = (int) $quoteAllowanceCharge->getVatOrTax();
        return $form;
    }

    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function getAllowanceChargeId(): ?int
    {
        return $this->allowance_charge_id;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getVatOrTax(): ?int
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
