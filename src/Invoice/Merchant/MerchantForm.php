<?php
declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\Merchant;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

use DateTimeImmutable;

final class MerchantForm extends FormModel
{    
    private ?int $inv_id=null;
    #[Required]
    private ?bool $successful=true;
    
    private mixed $date='';
    #[Required]
    private ?string $driver='';
    #[Required]
    private ?string $response='';
    #[Required]
    private ?string $reference='';
    private ?Inv $inv;
    
    public function __construct(Merchant $merchant)
    {
          $this->inv_id = (int)$merchant->getInv_id();
          $this->successful = $merchant->getSuccessful();
          $this->date = $merchant->getDate();
          $this->driver = $merchant->getDriver();
          $this->response = $merchant->getResponse();
          $this->reference = $merchant->getReference();
          $this->inv = $merchant->getInv();
    } 
    
    public function getInv() : Inv|null
    {
        return $this->inv;
    }        

    public function getInv_id() : int|null
    {
      return $this->inv_id;
    }

    public function getSuccessful() : bool|null
    {
      return $this->successful;
    }
    
    public function getDate() : string|DateTimeImmutable
    {
      /**
       * @var string|DateTimeImmutable $this->date 
       */
      return $this->date;
    }    

    public function getDriver() : string|null
    {
      return $this->driver;
    }

    public function getResponse() : string|null
    {
      return $this->response;
    }

    public function getReference() : string|null
    {
      return $this->reference;
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
