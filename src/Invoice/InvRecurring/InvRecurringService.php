<?php

declare(strict_types=1); 

namespace App\Invoice\InvRecurring;

use App\Invoice\Entity\InvRecurring;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Inv\InvRepository;

final class InvRecurringService
{

    private InvRecurringRepository $repository;
    private InvRepository $invR;
    private SettingRepository $s;
    /**
     * @param InvRecurringRepository $repository
     */
    public function __construct(InvRecurringRepository $repository,
                                InvRepository $invR,
                                SettingRepository $s)
    {
        $this->repository = $repository;
        $this->invR= $invR;
        $this->s = $s;
    }

    /**
     * @param InvRecurring $model
     * @param array $array 
     * @return void
     */
    public function saveInvRecurring(InvRecurring $model, array $array): void
    {
       $model->setInv_id((int)$array['inv_id']);
       
       isset($array['frequency']) ? $model->setFrequency((string)$array['frequency']) : '';
       
       $base_invoice = $this->invR->repoInvUnloadedquery((string)$array['inv_id']);
       if (null!== $base_invoice) {
            $immutable_invoice_date = $base_invoice->getDate_created();
       
            $dateHelper = new DateHelper($this->s);
            $start_date = $dateHelper->add_to_immutable($immutable_invoice_date, (string)$array['frequency']);
       
            $model->setStart(new \DateTime($start_date));
       }
       
       /**
        * @var null|string $array['next']
        */
       $next = isset($array['next']) ? new \DateTime($array['next']) : null;
       $next ? $model->setNext($next) : '';       
       
       /**
        * @var null|string $array['end']
        */
       $end = isset($array['end']) ? new \DateTime($array['end']) : null;
       $end ? $model->setEnd($end) : '';
       
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param InvRecurring $model
     * @return void
     */
    public function deleteInvRecurring(InvRecurring $model): void
    {
        $this->repository->delete($model);
    }
}