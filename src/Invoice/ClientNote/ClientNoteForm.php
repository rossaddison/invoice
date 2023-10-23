<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;


use App\Invoice\Helpers\DateHelper;
use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ClientNoteForm extends FormModel
{   
    private ?int $client_id=null;
    private ?string $date='';
    private ?string $note='';

    public function getClient_id() : int|null
    {
      return $this->client_id;
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
