<?php
declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Entity\Inv;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

use DateTimeImmutable;

final class InvForm extends FormModel
{    
    private ?string $id='';
    private ?string $number ='';
    private mixed $date_created='';
    // Countries with VAT systems will need these fields
    private mixed $date_modified='';
    private mixed $date_supplied='';    
    private mixed $date_paid_off='';    
    private mixed $date_tax_point='';
    private mixed $date_due='';
    // stand_in_code/description_code
    private ?string $stand_in_code=''; 
    private ?string $quote_id='';
    
    #[Required]
    private ?string $group_id='';
    
    #[Required]
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
    private bool $is_read_only;
    private mixed $time_created = '';
    
    public function __construct(Inv $inv)
    {
        $this->id = $inv->getId();
        $this->date_created = $inv->getDate_created();
        $this->date_modified = $inv->getDate_modified();
        $this->client_id = $inv->getClient_id();
        $this->group_id = $inv->getGroup_id();
        $this->status_id = $inv->getStatus_id();
        $this->contract_id = (int)$inv->getContract_id();
        $this->delivery_id = (int)$inv->getDelivery_id();
        $this->delivery_location_id = (int)$inv->getDelivery_location_id();
        $this->so_id = $inv->getSo_id();
        $this->quote_id = $inv->getQuote_id();
        $this->is_read_only = $inv->getIs_read_only();
        $this->password = $inv->getPassword();
        $this->time_created = $inv->getTime_created();
        $this->date_tax_point = $inv->getDate_tax_point();
        $this->stand_in_code = $inv->getStand_in_code();
        $this->date_supplied = $inv->getDate_supplied();
        $this->date_due = $inv->getDate_due();
        $this->number = $inv->getNumber();
        $this->discount_amount = $inv->getDiscount_amount();
        $this->discount_percent = $inv->getDiscount_percent();
        $this->terms = $inv->getTerms();
        $this->note = $inv->getNote();
        $this->document_description = $inv->getDocumentDescription();
        $this->url_key = $inv->getUrl_key();
        $this->payment_method = $inv->getPayment_method();
        $this->creditinvoice_parent_id = (int)$inv->getCreditinvoice_parent_id();
    }
    
    public function getId() : string|null
    {
        return $this->id;
    }    

    public function getDate_created() : string|null|DateTimeImmutable 
    {
        /**
         * @var string|DateTimeImmutable $this->date_created 
         */
        return $this->date_created;
    }
    
    public function getDate_modified() : string|null|DateTimeImmutable 
    {
        /**
         * @var string|DateTimeImmutable $this->date_modified 
         */
        return $this->date_modified;
    }
    
    public function getDate_supplied() : string|null|DateTimeImmutable 
    {
        /**
         * @var string|DateTimeImmutable $this->date_supplied 
         */
        return $this->date_supplied;
    }
    
    public function getDate_paid_off() : string|null|DateTimeImmutable 
    {
        /**
         * @var string|DateTimeImmutable $this->date_paid_off 
         */
        return $this->date_paid_off;
    }
    
    public function getDate_tax_point() : string|null|DateTimeImmutable 
    {
        /**
         * @var string|DateTimeImmutable $this->date_tax_point 
         */
        return $this->date_tax_point;
    }
    
    public function getDate_due() : string|null|DateTimeImmutable 
    {
        /**
         * @var string|DateTimeImmutable $this->date_due 
         */
        return $this->date_due;
    }
    
    public function getTime_created() : string|null|DateTimeImmutable 
    {
        /**
         * @var string|DateTimeImmutable $this->time_created 
         */
        return $this->time_created;
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
    
    public function getIsReadOnly() : bool
    {
        return $this->is_read_only;
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
}
