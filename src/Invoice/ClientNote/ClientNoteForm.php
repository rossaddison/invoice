<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StringValue;

use DateTimeImmutable;

final class ClientNoteForm extends FormModel
{   
    #[Integer]
    #[Required]
    private ?int $client_id=null;
    
    #[Required]
    private mixed $date;
    
    #[StringValue]
    #[Required]
    private ?string $note='';
    
    public function __construct(ClientNote $clientNote) 
    {
        $this->client_id = (int)$clientNote->getClient_id();
        $this->date = $clientNote->getDate();
        $this->note = $clientNote->getNote();
    }

    public function getClient_id() : int|null
    {
      return $this->client_id;
    }
    
    public function getDate() : string|DateTimeImmutable
    {
      /**
       * @var string|DateTimeImmutable $this->date 
       */
      return $this->date;
    }    
    
    public function getNote() : string|null
    {
      return $this->note;
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
