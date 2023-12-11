<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use App\Invoice\Helpers\DateHelper;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ClientNoteForm extends FormModel
{   
    private ?int $client_id=null;
    private mixed $date;
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

    /**
     * @return Required[][]
     *
     * @psalm-return array{client_id: list{Required}, date: list{Required}, note: list{Required}}
     */
    public function getRules(): array    {
      return [
        'client_id' => [new Required()],  
        'date' => [new Required()],
        'note' => [new Required()],
    ];
}
}
