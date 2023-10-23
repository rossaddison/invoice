<?php

declare(strict_types=1);

namespace App\Invoice\Sumex;

use App\Invoice\Helpers\DateHelper;
use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SumexForm extends FormModel
{    
    private ?int $invoice=null;
    private ?int $reason=null;
    private ?string $diagnosis='';
    private ?string $observations='';
    private ?string $treatmentstart='';
    private ?string $treatmentend='';
    private ?string $casedate='';
    private ?string $casenumber='';

    public function getInvoice() : int|null
    {
      return $this->invoice;
    }

    public function getReason() : int|null
    {
      return $this->reason;
    }

    public function getDiagnosis() : string|null
    {
      return $this->diagnosis;
    }

    public function getObservations() : string|null
    {
      return $this->observations;
    }
    
    public function getTreatmentstart(\App\Invoice\Setting\SettingRepository $s) : \DateTime
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $date = $datehelper->date_to_mysql(null!==$this->treatmentstart ? $this->treatmentstart : date('Y-m-d'));
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
    }
    
    public function getTreatmentend(\App\Invoice\Setting\SettingRepository $s) : \DateTime
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $date = $datehelper->date_to_mysql(null!==$this->treatmentend ? $this->treatmentend : date('Y-m-d'));
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
    }
    
    public function getCasedate(\App\Invoice\Setting\SettingRepository $s) : \DateTime
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ? $s->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $date = $datehelper->date_to_mysql(null!==$this->casedate ? $this->casedate : date('Y-m-d'));
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
    }

    public function getCasenumber() : string|null
    {
      return $this->casenumber;
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
     * @psalm-return array{invoice: list{Required}, reason: list{Required}, diagnosis: list{Required}, observations: list{Required}, treatmentstart: list{Required}, treatmentend: list{Required}, casedate: list{Required}, casenumber: list{Required}}
     */
    public function getRules(): array    {
      return [
        'invoice' => [new Required()],
        'reason' => [new Required()],
        'diagnosis' => [new Required()],
        'observations' => [new Required()],
        'treatmentstart' => [new Required()],
        'treatmentend' => [new Required()],
        'casedate' => [new Required()],
        'casenumber' => [new Required()],
    ];
}
}
