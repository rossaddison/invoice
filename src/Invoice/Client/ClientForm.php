<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\Helpers\DateHelper;
use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Email;

final class ClientForm extends FormModel
{
    private ?string $client_name='';
    private ?string $client_number='';
    private ?string $client_address_1='';
    private ?string $client_address_2='';
    private ?string $client_building_number='';
    private ?string $client_city='';
    private ?string $client_state='';
    private ?string $client_zip='';
    private ?string $client_country='';
    private ?string $client_phone='';
    private ?string $client_fax='';
    private ?string $client_mobile='';
    private ?string $client_email='';
    private ?string $client_web='';
    private ?string $client_vat_id='';
    private ?string $client_tax_code='';
    private ?string $client_language='';
    private ?bool $client_active=false;
    private ?string $client_surname='';
    private ?string $client_avs='';
    private ?string $client_insurednumber='';
    private ?string $client_veka='';    
    private ?string $client_birthdate='';
    private ?int $client_gender=0;
    private ?int $postaladdress_id=null;
    
    public function getClient_name() : string|null
    {
      return $this->client_name;
    }
    
    public function getClient_number() : string|null
    {
      return $this->client_number;
    }

    public function getClient_address_1() : string|null
    {
      return $this->client_address_1;
    }

    public function getClient_address_2() : string|null
    {
      return $this->client_address_2;
    }
    
    public function getClient_building_number() : string|null
    {
      return $this->client_building_number;
    }

    public function getClient_city() : string|null
    {
      return $this->client_city;
    }

    public function getClient_state() : string|null
    {
      return $this->client_state;
    }

    public function getClient_zip() : string|null
    {
      return $this->client_zip;
    }

    public function getClient_country() : string|null
    {
      return $this->client_country;
    }

    public function getClient_phone() : string|null
    {
      return $this->client_phone;
    }

    public function getClient_fax() : string|null
    {
      return $this->client_fax;
    }

    public function getClient_mobile() : string|null
    {
      return $this->client_mobile;
    }

    public function getClient_email() : string|null
    {
      return $this->client_email;
    }

    public function getClient_web() : string|null
    {
      return $this->client_web;
    }

    public function getClient_vat_id() : string|null
    {
      return $this->client_vat_id;
    }

    public function getClient_tax_code() : string|null
    {
      return $this->client_tax_code;
    }

    public function getClient_language() : string|null
    {
      return $this->client_language;
    }

    public function getClient_active() : bool|null
    {
      return $this->client_active;
    }

    public function getClient_surname() : string|null
    {
      return $this->client_surname;
    }

    public function getClient_avs() : string|null
    {
      return $this->client_avs;
    }

    public function getClient_insurednumber() : string|null
    {
      return $this->client_insurednumber;
    }

    public function getClient_veka() : string|null
    {
      return $this->client_veka;
    }
    
    public function getClient_birthdate(\App\Invoice\Setting\SettingRepository $s) : \DateTime|null
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        if (!empty($this->client_birthdate)) { 
            $date = $datehelper->date_to_mysql($this->client_birthdate);
            $str_replace = str_replace($datehelper->separator(), '-', $date);
            $datetime->modify($str_replace);
            return $datetime;        
        }
        return null;
    }

    public function getClient_gender() : int|null
    {
      return $this->client_gender;
    }
    
    public function getClient_postaladdress_id() : int|null
    {
      return $this->postaladdress_id;
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
     * @return (Email|Required)[][]
     *
     * @psalm-return array{client_name: list{Required}, client_email: list{Required, Email}}
     */
    public function getRules(): array    {
      return [
        'client_name' => [new Required()],
        'client_email' => [new Required(),new Email()],
    ];
}
}
