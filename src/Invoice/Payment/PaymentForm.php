<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\Helpers\DateHelper;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentForm extends FormModel
{    
    private ?int $payment_method_id=null;
    private ?string $payment_date='';
    private ?float $amount=null;
    private ?string $note='';
    private ?int $inv_id=null;

    public function getPayment_method_id() : int|null
    {
      return $this->payment_method_id;
    }
    
    public function getPayment_date(\App\Invoice\Setting\SettingRepository $s) : \DateTime
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $date = $datehelper->date_to_mysql(null!==$this->payment_date ? $this->payment_date : date('Y-m-d'));
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
    }
    

    public function getAmount() : float|null
    {
      return $this->amount;
    }

    public function getNote() : string|null
    {
      return $this->note;
    }

    public function getInv_id() : int|null
    {
      return $this->inv_id;
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
     * @psalm-return array{inv_id: list{Required}, payment_method_id: list{Required}, payment_date: list{Required}, amount: list{Required}, note: list{Required}}
     */
    public function getRules(): array    {
      return [
        'inv_id' => [new Required()],
        'payment_method_id' => [new Required()],
        'payment_date' => [new Required()],
        'amount' => [new Required()],
        'note' => [new Required()],
    ];
}
}
