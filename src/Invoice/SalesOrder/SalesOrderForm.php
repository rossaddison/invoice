<?php
declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderForm extends FormModel
{    
    private string $number ='';
    private ?string $quote_id=null;
    private ?string $inv_id=null;
    private ?int $group_id=null;
    private ?int $client_id=null;
    private ?string $client_po_number=null;
     private ?string $client_po_line_number=null;
    private ?string $client_po_person=null;
    private ?int $status_id=1;
    private ?float $discount_amount=0;
    private ?float $discount_percent=0;
    private ?string $url_key='';
    private ?string $password='';
    private ?string $notes='';    
    private ?string $payment_term='';
    
    // The Entities ie. Entity/SalesOrder.php have return type string => return type strings in the form 
    // get => string ; 
    
    public function getQuote_id() : string|null
    {
      return $this->quote_id;
    }
    
    public function getInv_id() : string|null
    {
      return $this->inv_id;
    }
    
    public function getClient_po_number() : string|null
    {
      return $this->client_po_number;  
    }
    
    public function getClient_po_line_number() : string|null
    {
      return $this->client_po_line_number;  
    }

    public function getClient_po_person() : string|null
    {
      return $this->client_po_person;  
    }

    public function getClient_id() : int|null
    {
      return $this->client_id;
    }

    public function getGroup_id() : int|null
    {
      return $this->group_id;
    }

    public function getStatus_id() : int|null
    {
      return $this->status_id;
    }
        
    public function getNumber() : string
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

    public function getNotes() : string|null
    {
      return $this->notes;
    }
    
    public function getPaymentTerm() : string|null
    {
      return $this->payment_term;
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
     * @psalm-return array{quote_id: list{Required}, client_id: list{Required}, group_id: list{Required}}
     */
    public function getRules(): array {
      return [
         'quote_id'=> [new Required()],
         'client_id'=> [new Required()],
         'group_id'=> [new Required()],
      ];
    }
}
