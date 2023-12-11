<?php
declare(strict_types=1); 

namespace App\Invoice\ClientNote;

use App\Invoice\Helpers\DateHelper;
use App\Invoice\Entity\ClientNote;
use App\Invoice\Setting\SettingRepository;


final class ClientNoteService
{
    private ClientNoteRepository $repository;

    public function __construct(ClientNoteRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function addClientNote(ClientNote $model, array $array, SettingRepository $s): void
    {
       $datehelper = new DateHelper($s);
       null!==$array['client_id'] ? $model->setClient_id((int)$array['client_id']) : '';
       $datetime = new \DateTime();
       $model->setDate($datetime::createFromFormat($datehelper->style(),(string)$array['date']));
       null!==$array['note'] ? $model->setNote((string)$array['note']) : '';
       $this->repository->save($model);
    }
    
    /**
     * @param ClientNote $model
     * @param array $array
     * @param SettingRepository $s
     * @return void
     */
    public function saveClientNote(ClientNote $model, array $array, SettingRepository $s): void
    {
       $datehelper = new DateHelper($s);
       null!==$array['client_id'] 
       && $model->getClient()?->getClient_id() == $array['client_id']
       ? $model->setClient($model->getClient()) : $model->setClient(null);
       
       null!==$array['client_id'] ? $model->setClient_id((int)$array['client_id']) : '';
       
       $datetime = new \DateTime();
       $model->setDate($datetime::createFromFormat($datehelper->style(),(string)$array['date']));
       null!==$array['note'] ? $model->setNote((string)$array['note']) : '';
       $this->repository->save($model);
    }
    
    /**
     * @param array|ClientNote|null $model
     * @return void
     */
    public function deleteClientNote(array|ClientNote|null $model): void
    {
        $this->repository->delete($model);
    }
}