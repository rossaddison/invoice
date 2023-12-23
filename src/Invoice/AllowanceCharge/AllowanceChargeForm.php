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
    private ?bool $identifier=false;
    #[Required]
    private ?string $reason_code='';
    #[Required]
    private ?string $reason='';
    #[Required]
    private ?int $multiplier_factor_numeric=null;
    #[Required]
    private ?int $amount=null;
    #[Required]
    private ?int $base_amount=null;
    #[Integer(min:1)]
    private ?int $tax_rate_id=null;
    
    public function __construct(AllowanceCharge $allowance_charge) 
    {
        $this->id = $allowance_charge->getId();
        $this->identifier = $allowance_charge->getIdentifier();
        $this->reason_code = $allowance_charge->getReason_code();
        $this->reason = $allowance_charge->getReason();
        $this->multiplier_factor_numeric = $allowance_charge->getMultiplier_factor_numeric();
        $this->amount = $allowance_charge->getAmount();
        $this->base_amount = $allowance_charge->getBase_amount();
        $this->tax_rate_id = (int)$allowance_charge->getTax_rate_id();
    }        

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
    
    public function getId() : string
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
