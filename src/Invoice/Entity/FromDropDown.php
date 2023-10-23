<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
  
 #[Entity(repository: \App\Invoice\FromDropDown\FromDropDownRepository::class)]
 
 class FromDropDown
 {
     #[Column(type: 'primary')]
     private ?int $id =  null;
     
     #[Column(type:'text)', nullable: false)]
     private string $email =  '';
     
     #[Column(type:'bool',default:false,nullable: false)]
     private bool $include =  false;
     
     #[Column(type:'bool',default:false,nullable: false)]
     private bool $default_email =  false;
     
     public function __construct(
         int $id = null,
         string $email = '',
         bool $include = false,
         bool $default_email = false
     )
     {
         $this->id=$id;
         $this->email=$email;
         $this->include=$include;
         $this->default_email=$default_email;
     }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getEmail(): string
    {
       return $this->email;
    }
    
    public function setEmail(string $email) : void
    {
      $this->email =  $email;
    }
    
    public function getInclude(): bool
    {
       return $this->include;
    }
    
    public function setInclude(bool $include) : void
    {
      $this->include =  $include;
    }
    
    public function getDefault_email(): bool
    {
       return $this->default_email;
    }
    
    public function setDefault_email(bool $default_email) : void
    {
      $this->default_email =  $default_email;
    }
}