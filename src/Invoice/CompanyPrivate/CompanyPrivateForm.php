<?php

declare(strict_types=1);

namespace App\Invoice\CompanyPrivate;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;
use App\Invoice\Helpers\DateHelper;

final class CompanyPrivateForm extends FormModel
{        
    private ?int $company_id=null;
    private ?string $vat_id='';
    private ?string $tax_code='';
    private ?string $iban='';
    private ?int $gln=null;
    private ?string $rcc='';    
    private ?string $logo_filename='';
    private ?string $start_date='';
    private ?string $end_date='';
        
    public function getCompany_id() : int|null
    {
      return $this->company_id;
    }

    public function getVat_id() : string|null
    {
      return $this->vat_id;
    }

    public function getTax_code() : string|null
    {
      return $this->tax_code;
    }

    public function getIban() : string|null
    {
      return $this->iban;
    }

    public function getGln() : int|null
    {
      return $this->gln;
    }

    public function getLogo_filename() : string|null
    {
      return $this->logo_filename;
    }
    
    public function getRcc() : string|null
    {
      return $this->rcc;
    }

    public function getStart_date(\App\Invoice\Setting\SettingRepository $s) : \DateTime
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $date = $datehelper->date_to_mysql(null!==$this->start_date ? $this->start_date : date('Y-m-d'));
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
    }
    
    public function getEnd_date(\App\Invoice\Setting\SettingRepository $s) : \DateTime
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $date = $datehelper->date_to_mysql(null!==$this->end_date ? $this->end_date : date('Y-m-d'));
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
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
     * @psalm-return array{company_id: list{Required}}
     */
    public function getRules(): array 
    {
      return [ 
        'company_id' => [new Required()],
      ];
}
}
