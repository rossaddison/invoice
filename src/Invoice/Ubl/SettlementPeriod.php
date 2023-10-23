<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use DateTime;

class SettlementPeriod implements XmlSerializable
{
    private DateTime $startDate;
    private DateTime $endDate;
    
    private function __construct(DateTime $startDate, DateTime $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    } 
    
    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'StartDate' => $this->startDate->format('Y-m-d') ?: '',
            Schema::CBC . 'EndDate' => $this->endDate->format('Y-m-d') ?: '',
        ]);

        $writer->write([
            [
                'name' => Schema::CBC . 'DurationMeasure',
                'value' => $this->endDate->diff($this->startDate)->format('%d'),
                'attributes' => [
                    'unitCode' => 'DAY'
                ]
            ]
        ]);
    }
}
