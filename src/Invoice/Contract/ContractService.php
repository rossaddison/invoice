<?php

declare(strict_types=1); 

namespace App\Invoice\Contract;

use App\Invoice\Entity\Contract;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Contract\ContractRepository;

final class ContractService
{   
    private ContractRepository $repository;

    public function __construct(ContractRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param Contract $model
     * @param ContractForm $form
     * @param SR $s
     * @return void
     */
    public function bothContract(Contract $model, ContractForm $form, SR $s) : void {
        $model->nullifyRelationOnChange((int)$form->getClient_id());
        $datehelper = new DateHelper($s);
        $datetime_period_start = $datehelper->get_or_set_with_style(null!==$form->getPeriod_start()? $form->getPeriod_start() : new \DateTime());
        $datetimeimmutable_period_start = new \DateTimeImmutable($datetime_period_start instanceof \DateTime ? $datetime_period_start->format('Y-m-d H:i:s') : 'now');
        $model->setPeriod_start($datetimeimmutable_period_start);
        
        $datetime_period_end = $datehelper->get_or_set_with_style(null!==$form->getPeriod_end()? $form->getPeriod_end() : new \DateTime());
        $datetimeimmutable_period_end = new \DateTimeImmutable($datetime_period_end instanceof \DateTime ? $datetime_period_end->format('Y-m-d H:i:s') : 'now');
        $model->setPeriod_end($datetimeimmutable_period_end);
        
        null!==$form->getClient_id() ? $model->setClient_id((int)$form->getClient_id()) : '';
        null!==$form->getName() ? $model->setName($form->getName()): '';
        null!==$form->getReference() ? $model->setReference($form->getReference()): '';
        
        if ($model->isNewRecord()) {
            $model->setPeriod_start(new \DateTimeImmutable('now'));
            $model->setPeriod_end(new \DateTimeImmutable('now'));
        }
        $this->repository->save($model);
    }
    
    public function deleteContract(Contract $model): void
    {
        $this->repository->delete($model);
    }
}