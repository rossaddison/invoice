<?php

declare(strict_types=1); 

namespace App\Invoice\ItemLookup;

use App\Invoice\Entity\ItemLookup;


final class ItemLookupService
{

    private ItemLookupRepository $repository;

    public function __construct(ItemLookupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param ItemLookup $model
     * @param ItemLookupForm $form
     * @return void
     */
    public function saveItemLookup(ItemLookup $model, ItemLookupForm $form): void
    {
        
       $model->setName($form->getName() ?? '');
       $model->setDescription($form->getDescription() ?? '');
       $model->setPrice($form->getPrice() ?? 0.00);
 
        $this->repository->save($model);
    }
    
    /**
     * 
     * @param ItemLookup $model
     * @return void
     */
    public function deleteItemLookup(ItemLookup $model): void
    {
        $this->repository->delete($model);
    }
}