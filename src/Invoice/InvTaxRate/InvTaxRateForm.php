<?php

declare(strict_types=1);

namespace App\Invoice\InvTaxRate;

use App\Invoice\Entity\InvTaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvTaxRateForm extends FormModel
{
    private ?string $inv_id      = '';
    private ?string $tax_rate_id = '';
    #[Required]
    private ?int $include_item_tax = null;
    #[Required]
    private ?float $inv_tax_rate_amount = null;

    public function __construct(InvTaxRate $invTaxRate)
    {
        $this->inv_id              = $invTaxRate->getInv_id();
        $this->tax_rate_id         = $invTaxRate->getTax_rate_id();
        $this->include_item_tax    = $invTaxRate->getInclude_item_tax();
        $this->inv_tax_rate_amount = $invTaxRate->getInv_tax_rate_amount();
    }

    public function getInv_id(): ?string
    {
        return $this->inv_id;
    }

    public function getTax_rate_id(): ?string
    {
        return $this->tax_rate_id;
    }

    public function getInclude_item_tax(): ?int
    {
        return $this->include_item_tax;
    }

    public function getInv_tax_rate_amount(): ?float
    {
        return $this->inv_tax_rate_amount;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
