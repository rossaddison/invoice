<?php
declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class AllowanceChargeForm extends FormModel
{   
    private ?bool $identifier=false;
    private ?string $reason_code='';
    private ?string $reason='';
    private ?int $multiplier_factor_numeric=null;
    private ?int $amount=null;
    private ?int $base_amount=null;
    private ?int $tax_rate_id=null;

    public function getIdentifier() : bool|null
    {
      return $this->identifier;
    }

    public function getReason_code() : string|null
    {
      return $this->reason_code;
    }

    public function getReason() : string|null
    {
      return $this->reason;
    }

    public function getMultiplier_factor_numeric() : int|null
    {
      return $this->multiplier_factor_numeric;
    }

    public function getAmount() : int|null
    {
      return $this->amount;
    }

    public function getBase_amount() : int|null
    {
      return $this->base_amount;
    }

    public function getTax_rate_id() : int|null
    {
      return $this->tax_rate_id;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
        'identifier' => [new Required()],  
        'reason_code' => [new Required()],
        'reason' => [new Required()],
        'multiplier_factor_numeric' => [new Required()],        'amount' => [new Required()],        'base_amount' => [new Required()],    ];
    }
}
