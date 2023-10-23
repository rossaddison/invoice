<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

use App\Invoice\Entity\Client;
  
 #[Entity(repository: \App\Invoice\PostalAddress\PostalAddressRepository::class)]
 
 class PostalAddress
 {   
     #[Column(type: 'primary')]
     public ?int $id = null;
          
     #[Column(type: 'integer(11)', nullable: false)]
     private ?int $client_id = null;
     
     #[Column(type:'string(50)', nullable: false)]
     private string $street_name =  '';
     
     #[Column(type:'string(50)', nullable: false)]
     private string $additional_street_name =  '';
     
     #[Column(type:'string(4)', nullable: false)]
     private string $building_number =  '';
     
     #[Column(type:'string(50)', nullable: false)]
     private string $city_name =  '';
     
     #[Column(type:'string(7)', nullable: false)]
     private string $postalzone =  '';
     
     #[Column(type:'string(50)', nullable: false)]
     private string $countrysubentity =  '';
     
     #[Column(type:'string(50)', nullable: false)]
     private string $country =  '';
     
     public function __construct(
         int $id = null,
         int $client_id = null,
         string $street_name = '',
         string $additional_street_name = '',
         string $building_number = '',
         string $city_name = '',
         string $postalzone = '',
         string $countrysubentity = '',
         string $country = ''
     )
     {
         $this->id=$id;
         $this->client_id=$client_id;
         $this->street_name=$street_name;
         $this->additional_street_name=$additional_street_name;
         $this->building_number=$building_number;
         $this->city_name=$city_name;
         $this->postalzone=$postalzone;
         $this->countrysubentity=$countrysubentity;
         $this->country=$country;
     }
    
    public function getId(): string
    {
     return (string)$this->id;
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
    
    public function getStreet_name(): string
    {
       return $this->street_name;
    }
    
    public function setStreet_name(string $street_name) : void
    {
      $this->street_name =  $street_name;
    }
    
    public function getAdditional_street_name(): string
    {
       return $this->additional_street_name;
    }
    
    public function setAdditional_street_name(string $additional_street_name) : void
    {
      $this->additional_street_name =  $additional_street_name;
    }
    
    public function getBuilding_number(): string
    {
       return $this->building_number;
    }
    
    public function setBuilding_number(string $building_number) : void
    {
      $this->building_number =  $building_number;
    }
    
    public function getCity_name(): string
    {
       return $this->city_name;
    }
    
    public function setCity_name(string $city_name) : void
    {
      $this->city_name =  $city_name;
    }
    
    public function getPostalzone(): string
    {
       return $this->postalzone;
    }
    
    public function setPostalzone(string $postalzone) : void
    {
      $this->postalzone =  $postalzone;
    }
    
    public function getCountrysubentity(): string
    {
       return $this->countrysubentity;
    }
    
    public function setCountrysubentity(string $countrysubentity) : void
    {
      $this->countrysubentity =  $countrysubentity;
    }
    
    public function getCountry(): string
    {
       return $this->country;
    }
    
    public function setCountry(string $country) : void
    {
      $this->country =  $country;
    }
}