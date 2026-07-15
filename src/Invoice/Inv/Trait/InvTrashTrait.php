<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use Yiisoft\Data\Cycle\Reader\EntityReader;

trait InvTrashTrait
{
    public function findTrashed(): EntityReader
    {
        $query = $this->select()
            ->scope(null)
            ->where('deleted_at', '!=', null);
        return $this->prepareDataReader($query);
    }

    public function findTrashedById(int $id): ?Inv
    {
        /** @var Inv|null */
        return $this->select()
            ->scope(null)
            ->where(['id' => $id])
            ->where('deleted_at', '!=', null)
            ->fetchOne();
    }
}
