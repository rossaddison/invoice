<?php
declare(strict_types=1);

namespace App\Invoice\Inv;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class InvForm extends FormModel implements RulesProviderInterface
{    
    private ?string $number ='';
    private ?string $date_created='';
    // Countries with VAT systems will need these fields
    private ?string $date_supplied='';    
    private ?string $date_paid_off='';    
    private ?string $date_tax_point='';
    // stand_in_code/description_code
    private ?string $stand_in_code=''; 
    private ?string $quote_id='';
    private ?string $group_id='';
    private ?string $client_id=''; 
    private ?string $so_id='';
    private ?int $creditinvoice_parent_id=null;
    private ?int $delivery_id=null;
    private ?int $delivery_location_id=null;
    private ?int $contract_id=null;
    private ?int $status_id=1;
    private ?float $discount_amount=0.00;
    private ?float $discount_percent=0.00;
    private ?string $url_key='';
    private ?string $password='';
    private ?int $payment_method=0;
    private ?string $terms='';
    private ?string $note='';
    private ?string $document_description='';

    public function getDate_created() : string|null 
    {
        return $this->date_created;
    }
    
    public function getDate_supplied() : string|null 
    {
        return $this->date_supplied;
    }
    
    public function getDate_paid_off() : string|null 
    {
        return $this->date_paid_off;
    }
    
    public function getDate_tax_point() : string|null 
    {
        return $this->date_tax_point;
    }
    
    public function getStand_in_code() : string|null 
    {
        return $this->stand_in_code;
    }
    
    public function getQuote_id() : string|null
    {
      return $this->quote_id;
    }

    public function getClient_id() : string|null
    {
      return $this->client_id;
    }
    
    public function getSo_id() : string|null
    {
      return $this->so_id;
    }

    public function getGroup_id() : string|null
    {
      return $this->group_id;
    }
    
    public function getCreditinvoice_parent_id() : int|null
    {
      return $this->creditinvoice_parent_id;
    }
    
    public function getDelivery_id() : int|null
    {
      return $this->delivery_id;
    }
    
    public function getDelivery_location_id() : int|null
    {
      return $this->delivery_location_id;
    }
    
    public function getContract_id() : int|null
    {
      return $this->contract_id;
    }

    public function getStatus_id() : int|null
    {
      return $this->status_id;
    }
        
    public function getNumber() : string|null
    {
      return $this->number;
    }
    
    public function getDiscount_amount() : float|null
    {
      return $this->discount_amount;
    }

    public function getDiscount_percent() : float|null
    {
      return $this->discount_percent;
    }

    public function getUrl_key() : string|null
    {
      return $this->url_key;
    }

    public function getPassword() : string|null
    {
      return $this->password;
    }
    
    public function getPayment_method() : int|null
    {
      return $this->payment_method;
    }

    public function getTerms() : string|null
    {
      return $this->terms;
    }
    
    public function getNote() : string|null
    {
      return $this->note;
    }
    
    public function getDocumentDescription() : string|null
    {
      return $this->document_description;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }
    
    /**
     * @return Required[][]
     *
     * @psalm-return array{client_id: list{Required}, group_id: list{Required}}
     */
    public function getRules(): array    {
      return [
        'client_id' => [new Required()],
        'group_id' => [new Required()],
      ];
    }
}
