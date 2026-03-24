<?php

declare(strict_types=1);

namespace App\Invoice\InvTaxRate;

use App\Invoice\Entity\InvTaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvTaxRateForm extends FormModel
{
    private ?string $inv_id = '';
    private ?string $tax_rate_id = '';
    #[Required]
    private ?int $include_item_tax = null;
    #[Required]
    private ?float $inv_tax_rate_amount = null;

    public function __construct(InvTaxRate $invTaxRate)
    {
        $this->inv_id = $invTaxRate->getInvId();
        $this->tax_rate_id = $invTaxRate->getTaxRateId();
        $this->include_item_tax = $invTaxRate->getIncludeItemTax();
        $this->inv_tax_rate_amount = $invTaxRate->getInvTaxRateAmount();
    }

    public function getInvId(): ?string
    {
        return $this->inv_id;
    }

    public function getTaxRateId(): ?string
    {
        return $this->tax_rate_id;
    }

    public function getIncludeItemTax(): ?int
    {
        return $this->include_item_tax;
    }

    public function getInvTaxRateAmount(): ?float
    {
        return $this->inv_tax_rate_amount;
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
