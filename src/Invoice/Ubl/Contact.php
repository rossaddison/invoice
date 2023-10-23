<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Contact implements XmlSerializable
{
    private ?string $name;
    private ?string $firstname;
    private ?string $lastname;
    private ?string $telephone;
    private ?string $telefax;
    private ?string $electronicMail;
    
    public function __construct(?string $name, ?string $firstname, ?string $lastname, ?string $telephone, ?string $telefax, ?string $electronicMail) {
        $this->name = $name;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->telephone = $telephone;
        $this->telefax = $telefax;
        $this->electronicMail = $electronicMail;
    }
    
    /**
     * @see StoreCoveHelper validate_supplier_contact
     * @return string|null
     */
    public function getName() : ?string 
    {
        return $this->name;
    }
    
    public function getFirstName() : ?string 
    {
        return $this->firstname;
    }
    
    public function getLastName() : ?string 
    {
        return $this->lastname;
    }
    
    public function getTelephone() : ?string 
    {
        return $this->telephone;
    }
    
    public function getTelefax() : ?string 
    {
        return $this->telefax;
    }
    
    public function getElectronicMail() : ?string 
    {
        return $this->electronicMail;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->name !== null) {
            $writer->write([
                Schema::CBC . 'Name' => $this->name
            ]);
        }
        
        if ($this->name == null && ($this->firstname !== null || $this->lastname !== null)) {
            $writer->write([
                Schema::CBC . 'Name' => ($this->firstname ?? '').' '. ($this->lastname ?? '')
            ]);
        }

        if ($this->telephone !== null) {
            $writer->write([
                Schema::CBC . 'Telephone' => $this->telephone
            ]);
        }

        if ($this->telefax !== null) {
            $writer->write([
                Schema::CBC . 'Telefax' => $this->telefax
            ]);
        }

        if ($this->electronicMail !== null) {
            $writer->write([
                Schema::CBC . 'ElectronicMail' => $this->electronicMail
            ]);
        }
    }
}
