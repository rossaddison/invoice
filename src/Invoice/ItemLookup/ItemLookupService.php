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
     * @param array $array
     * @return void
     */
    public function saveItemLookup(ItemLookup $model, array $array): void
    {
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        isset($array['description']) ? $model->setDescription((string)$array['description']) : '';
        isset($array['price']) ? $model->setPrice((float)$array['price']) : 0.00;
        $this->repository->save($model);
    }

    /**
     * @param ItemLookup $model
     * @return void
     */
    public function deleteItemLookup(ItemLookup $model): void
    {
        $this->repository->delete($model);
    }
}
