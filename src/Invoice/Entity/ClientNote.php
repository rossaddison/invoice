<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\Client;
use \DateTime;
use \DateTimeImmutable;

 #[Entity(repository: \App\Invoice\ClientNote\ClientNoteRepository::class)]
 class ClientNote
 {
    #[BelongsTo(target: Client::class, nullable: false, fkAction:'NO ACTION')]
    private ?Client $client = null;
    
    #[Column(type:'primary')]
    private ?int $id =  null;
    
    #[Column(type:'integer(11)', nullable: false)]
    private ?int $client_id =  null;
    
    #[Column(type: 'date', nullable: false)]
    private mixed $date_note;
    
    #[Column(type: 'longText', nullable:false)]
    private string $note =  '';
     
    public function __construct(
         int $client_id = null,
         string $note = '',
         mixed $date_note = '',   
    )
    {
         $this->client_id=$client_id;
         $this->note=$note;
         $this->date_note=$date_note;
    }
    
    public function getClient() : ?Client
    {
      return $this->client;
    }
    
    public function setClient(?Client $client) : void
    {
      $this->client =  $client;
    }
    
    /**
     * @return null|numeric-string
     */
    public function getId(): string|null
    {
        return $this->id === null ? null : (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getClient_id(): string
    {
     return (string)$this->client_id;
    }
    
    public function setClient_id(int $client_id) : void
    {
      $this->client_id =  $client_id;
    }
    
    public function getDate_note(): string|DateTimeImmutable
    { 
      /**
       * @var string|DateTimeImmutable $this->date_note
       */
      return $this->date_note;
    }
    
    public function setDate_note(DateTime $date_note) : void
    {
      $this->date_note =  $date_note;
    }
    
    public function getNote(): string
    {
       return $this->note;
    }
    
    public function setNote(string $note) : void
    {
      $this->note =  $note;
    }
    
    public function isNewRecord(): bool
    {
        return null===$this->getId() ? true : false;
    }
}