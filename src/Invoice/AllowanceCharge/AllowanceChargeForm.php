<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;

final class AllowanceChargeForm extends FormModel
{
    private readonly string $id;

    #[Required]
    private ?bool $identifier = false;

    /**
     * @see Not required because determined from value 'reason' in array using AllowanceChargeContoller
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

    public function __construct(AllowanceCharge $allowanceCharge)
    {
        $this->id                        = $allowanceCharge->getId();
        $this->identifier                = $allowanceCharge->getIdentifier();
        $this->reason_code               = $allowanceCharge->getReasonCode();
        $this->reason                    = $allowanceCharge->getReason();
        $this->multiplier_factor_numeric = $allowanceCharge->getMultiplierFactorNumeric();
        $this->amount                    = $allowanceCharge->getAmount();
        $this->base_amount               = $allowanceCharge->getBaseAmount();
        $this->tax_rate_id               = (int) $allowanceCharge->getTaxRateId();
    }

    public function getIdentifier(): ?bool
    {
        return $this->identifier;
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

    public function getId(): string
    {
        return $this->id;
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
