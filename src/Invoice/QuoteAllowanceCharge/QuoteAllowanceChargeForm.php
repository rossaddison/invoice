<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAllowanceCharge;

use App\Invoice\Entity\QuoteAllowanceCharge;
use Yiisoft\Validator\Rule\GreaterThan;
use Yiisoft\FormModel\FormModel;

final class QuoteAllowanceChargeForm extends FormModel
{
    private ?int $id = null;

    private ?int $allowance_charge_id = null;

    #[GreaterThan(0)]
    private ?int $amount = null;

    private ?int $vat_or_tax = null;

    public function __construct(QuoteAllowanceCharge $quoteAllowanceCharge, private readonly ?int $quote_id)
    {
        $this->allowance_charge_id = (int) $quoteAllowanceCharge->getAllowance_charge_id();
        $this->amount = (int) $quoteAllowanceCharge->getAmount();
        $this->vat_or_tax = (int) $quoteAllowanceCharge->getVatOrTax();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuote_id(): ?int
    {
        return $this->quote_id;
    }

    public function getAllowance_charge_id(): ?int
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
