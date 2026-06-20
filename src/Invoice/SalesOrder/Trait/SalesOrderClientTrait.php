<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Trait;

use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;

trait SalesOrderClientTrait
{
    /**
     * @param int $client_id
     */
    public function byClient(int $client_id): Select
    {
        return $this->select()
                      ->where(['client_id' => $client_id]);
    }

    /**
     * @param int $client_id
     * @param int $status_id
     * @psalm-return EntityReader
     */
    public function byClientSalesorderStatus(int $client_id, int $status_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $client_id
     * @param int $status_id
     * @return int
     */
    public function byClientSalesorderStatusCount(int $client_id, int $status_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->count();
    }

    /**
     * @param int $client_id
     * @return int
     */
    public function repoCountByClient(int $client_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->count();
    }

    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function repoClient(int $client_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }
}
