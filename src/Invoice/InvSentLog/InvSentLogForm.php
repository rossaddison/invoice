<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\InvSentLog;
use App\Invoice\Entity\Inv;
use Yiisoft\FormModel\FormModel;

use \DateTimeImmutable;

final class InvSentLogForm extends FormModel
{    
    private ?int $id = null;
    private ?int $inv_id = null;
    private mixed $date_sent = '';
    private ?Inv $inv = null;
    
    public function __construct(InvSentLog $invsentlog) 
    {
        $this->id = $invsentlog->getId();
        $this->inv = $invsentlog->getInv();
        $this->inv_id = $invsentlog->getInv_id();
        $this->date_sent = $invsentlog->getDate_sent();
    }
    
    public function getId() : int|null
    {
        return $this->id;
    }
    
    public function getInv_id() : int|null
    {
        return $this->inv_id;
    }
    
    public function getInv() : Inv|null
    {
        return $this->inv; 
    }    

    public function getDate_sent() : string|null|DateTimeImmutable
    {
        /**
         * @var string|DateTimeImmutable $this->date_sent 
         */
        return $this->date_sent;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }

}
