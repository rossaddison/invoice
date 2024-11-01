<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;

final class AllowanceChargeForm extends FormModel
{
    private string $id;

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

    #[Integer(min:1)]
    private ?int $tax_rate_id = null;

    public function __construct(AllowanceCharge $allowanceCharge)
    {
        $this->id = $allowanceCharge->getId();
        $this->identifier = $allowanceCharge->getIdentifier();
        $this->reason_code = $allowanceCharge->getReasonCode();
        $this->reason = $allowanceCharge->getReason();
        $this->multiplier_factor_numeric = $allowanceCharge->getMultiplierFactorNumeric();
        $this->amount = $allowanceCharge->getAmount();
        $this->base_amount = $allowanceCharge->getBaseAmount();
        $this->tax_rate_id = (int)$allowanceCharge->getTaxRateId();
    }

    public function getIdentifier(): bool|null
    {
        return $this->identifier;
    }

    public function getReasonCode(): string|null
    {
        return $this->reason_code;
    }

    public function getReason(): string|null
    {
        return $this->reason;
    }

    public function getMultiplierFactorNumeric(): int|null
    {
        return $this->multiplier_factor_numeric;
    }

    public function getAmount(): int|null
    {
        return $this->amount;
    }

    public function getBaseAmount(): int|null
    {
        return $this->base_amount;
    }

    public function getTaxRateId(): int|null
    {
        return $this->tax_rate_id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }
}
