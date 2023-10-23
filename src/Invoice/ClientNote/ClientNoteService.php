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
    
    public function addClientNote(ClientNote $model, ClientNoteForm $form, SettingRepository $s): void
    {
       null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       $model->setDate($form->getDate($s));
       null!==$form->getNote() ? $model->setNote($form->getNote()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param ClientNote $model
     * @param ClientNoteForm $form
     * @param SettingRepository $s
     * @return void
     */
    public function saveClientNote(ClientNote $model, ClientNoteForm $form, SettingRepository $s): void
    {
       //Psalm Level 3: ERROR: PossiblyNullReference - src/Invoice/ClientNote/ClientNoteService.php:58:33 - Cannot call method getClient_id on possibly null value (see https://psalm.dev/083)
       //&& $model->getClient()->getClient_id() == $form->getClient_id()
       // https://stackoverflow.com/questions/12351737/is-there-a-nullsafe-operator-in-php
       // Use the null safe operator to remove this error instead of psalm-suppress

       // if the dropdown value has changed,
       // reset the relation to null before using setter of individual field
        
       null!==$form->getClient_id() 
       && $model->getClient()?->getClient_id() == $form->getClient_id()
       ? $model->setClient($model->getClient()) : $model->setClient(null);
       
       null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       
       $model->setDate($form->getDate($s));
       null!==$form->getNote() ? $model->setNote($form->getNote()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|ClientNote|null $model
     * @return void
     */
    public function deleteClientNote(array|ClientNote|null $model): void
    {
        $this->repository->delete($model);
    }
}