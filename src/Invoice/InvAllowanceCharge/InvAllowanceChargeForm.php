<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\Entity\InvAllowanceCharge;
use Yiisoft\Validator\Rule\GreaterThan;
use Yiisoft\FormModel\FormModel;

final class InvAllowanceChargeForm extends FormModel
{
    private ?int $id = null;

    private ?int $allowance_charge_id = null;

    #[GreaterThan(0)]
    private ?int $amount = null;

    private ?int $vat = null;

    public function __construct(InvAllowanceCharge $invAllowanceCharge, private readonly ?int $inv_id)
    {
        $this->allowance_charge_id = (int)$invAllowanceCharge->getAllowance_charge_id();
        $this->amount = (int)$invAllowanceCharge->getAmount();
        $this->vat = (int)$invAllowanceCharge->getVat();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getInv_id(): int|null
    {
        return $this->inv_id;
    }

    public function getAllowance_charge_id(): int|null
    {
        return $this->allowance_charge_id;
    }

    public function getAmount(): int|null
    {
        return $this->amount;
    }

    public function getVat(): int|null
    {
        return $this->vat;
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
