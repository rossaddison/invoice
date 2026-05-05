<?php

declare(strict_types=1);

namespace App\Invoice\InvTaxRate;

use App\Infrastructure\Persistence\{
    InvTaxRate\InvTaxRate
};
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

    public static function show(InvTaxRate $invTaxRate): self
    {
        $form = new self();
        $form->inv_id = (string) $invTaxRate->reqInvId();
        $form->tax_rate_id = (string) $invTaxRate->reqTaxRateId();
        $form->include_item_tax = $invTaxRate->getIncludeItemTax();
        $form->inv_tax_rate_amount = $invTaxRate->getInvTaxRateAmount();
        return $form;
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
