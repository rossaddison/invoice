<?php

declare(strict_types=1); 

namespace App\Invoice\Sumex;

use App\Invoice\Entity\Sumex;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Sumex\SumexRepository;

final class SumexService
{

    private SumexRepository $repository;

    public function __construct(SumexRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param Sumex $model
     * @param array $array
     * @param SettingRepository $s
     * @return void
     */
    public function saveSumex(Sumex $model, array $array): void
    {
       // invoice is an id
       isset($array['invoice']) ? $model->setInvoice((int)$array['invoice']) : '';
       isset($array['reason']) ? $model->setReason((int)$array['reason']) : '';
       isset($array['diagnosis']) ? $model->setDiagnosis((string)$array['diagnosis']) : '';
       isset($array['observations']) ? $model->setObservations((string)$array['observations']) : '';
              
       $datetime_ts = new \DateTime();
       isset($array['treatmentstart']) ? $model->setTreatmentstart(
               $datetime_ts::createFromFormat('Y-m-d', (string)$array['treatmentstart'])) : '';
       
       $datetime_te = new \DateTime();
       isset($array['treatmentend']) ? $model->setTreatmentend(
               $datetime_te::createFromFormat('Y-m-d', (string)$array['treatmentend'])) : '';
       
       $datetime_cd = new \DateTime();
       isset($array['casedate']) ? $model->setCasedate(
               $datetime_cd::createFromFormat('Y-m-d', (string)$array['casedate'])) : '';
       
       isset($array['casenumber']) ? $model->setCasenumber((string)$array['casenumber']) : '';
 
       $this->repository->save($model);
    }
    
    /**
     * @param Sumex $model
     * @return void
     */
    public function deleteSumex(Sumex $model): void
    {
        $this->repository->delete($model);
    }
}