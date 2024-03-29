<?php
declare(strict_types=1); 

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use App\Invoice\Setting\SettingRepository;


final class ClientNoteService
{
    private ClientNoteRepository $repository;

    public function __construct(ClientNoteRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function addClientNote(ClientNote $model, array $array): void
    {
       isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
       
       $datetime = new \DateTime();
       /**
        * @var string $array['date_note']
        */
       $date = $array['date_note'] ?? '';
       $model->setDate_note($datetime::createFromFormat('Y-m-d', $date));
       
       isset($array['note']) ? $model->setNote((string)$array['note']) : '';
       $this->repository->save($model);
    }
    
    /**
     * @param ClientNote $model
     * @param array $array
     * @return void
     */
    public function saveClientNote(ClientNote $model, array $array,): void
    {
       isset($array['client_id']) 
       && $model->getClient()?->getClient_id() == $array['client_id']
       ? $model->setClient($model->getClient()) : $model->setClient(null);
       
       isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
       
       $datetime = new \DateTime();
       /**
        * @var string $array['date_note']
        */
       $date = $array['date_note'] ?? '';
       $model->setDate_note($datetime::createFromFormat('Y-m-d' , $date));
       
       isset($array['note']) ? $model->setNote((string)$array['note']) : '';
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