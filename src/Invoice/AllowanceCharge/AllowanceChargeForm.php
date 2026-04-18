<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;

final class AllowanceChargeForm extends FormModel
{
    #[Required]
    private ?bool $identifier = false;

    #[Required]
    private int $level = 0;

    /**
     * Related logic: see Not required because determined from value 'reason' in array using AllowanceChargeContoller
     */
    private ?string $reason_code = '';

    #[Required]
    private ?string $reason = '';

    #[Required]
    private ?int $multiplier_factor_numeric = null;

    #[Required]
    private ?int $amount = null;

    #[Required]
    private ?int $base_amount = null;

    #[Integer(min: 1)]
    private ?int $tax_rate_id = null;

    public static function show(AllowanceCharge $allowanceCharge) : self
    {
        $form = new self();
        $form->identifier = $allowanceCharge->getIdentifier();
        $form->level = $allowanceCharge->getLevel();
        $form->reason_code = $allowanceCharge->getReasonCode();
        $form->reason = $allowanceCharge->getReason();
        $form->multiplier_factor_numeric = $allowanceCharge->getMultiplierFactorNumeric();
        $form->amount = $allowanceCharge->getAmount();
        $form->base_amount = $allowanceCharge->getBaseAmount();
        $form->tax_rate_id = $allowanceCharge->getTaxRateId();
        return $form;
    }

    public function getIdentifier(): ?bool
    {
        return $this->identifier;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getReasonCode(): ?string
    {
        return $this->reason_code;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getMultiplierFactorNumeric(): ?int
    {
        return $this->multiplier_factor_numeric;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getBaseAmount(): ?int
    {
        return $this->base_amount;
    }

    public function getTaxRateId(): ?int
    {
        return $this->tax_rate_id;
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
