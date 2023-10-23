<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Invoice\Helpers\DateHelper;
use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class MerchantForm extends FormModel
{    
    
    private ?int $inv_id=null;
    private ?bool $successful=true;
    private ?string $date='';
    private ?string $driver='';
    private ?string $response='';
    private ?string $reference='';

    public function getInv_id() : int|null
    {
      return $this->inv_id;
    }

    public function getSuccessful() : bool|null
    {
      return $this->successful;
    }
    
    public function getDate(\App\Invoice\Setting\SettingRepository $s) : \DateTime
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $date = $datehelper->date_to_mysql(null!==$this->date ? $this->date : date('Y-m-d'));
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
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

    /**
     * @return Required[][]
     *
     * @psalm-return array{successful: list{Required}, date: list{Required}, driver: list{Required}, response: list{Required}, reference: list{Required}}
     */
    public function getRules(): array    {
      return [
        'successful' => [new Required()],
        'date' => [new Required()],
        'driver' => [new Required()],
        'response' => [new Required()],
        'reference' => [new Required()],
    ];
}
}
