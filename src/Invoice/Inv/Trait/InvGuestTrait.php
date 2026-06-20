<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;

trait InvGuestTrait
{
    /**
     * @return Inv|null
     */
    public function repoUrlKeyGuestLoaded(string $url_key): ?Inv
    {
        $query = $this->select()
                       ->load('client')
                       ->where(['url_key' => $url_key])
                       ->andWhere(
                               ['status_id' => ['in' => new Parameter([2,3,4])]])
                       ->where('deleted_at', null);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $url_key
     * @return int
     */
    public function repoUrlKeyGuestCount(string $url_key): int
    {
        return $this->select()
                      ->where(['url_key' => $url_key])
                      ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->where('deleted_at', null)
                      ->count();
    }

    public function repoClientGuestCount(int $inv_id, array $user_client = []):
        Select
    {
        return $this->select()
                      ->where(['id' => $inv_id])
                      ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->andWhere(['client_id' => ['in' => new Parameter(
                              $user_client)]])
                      ->where('deleted_at', null);
    }

    /**
     * @param int $status_id
     * @param array $user_client
     * @return EntityReader
     */
    public function repoGuestClientsPostDraft(int $status_id, array $user_client = []): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id' => $status_id])
                    ->where(['client_id' => ['in' => new Parameter($user_client)]])
                    ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                    ->where('deleted_at', null);
            return $this->prepareDataReader($query);
        }
        $query = $this->select()
                     ->where(['client_id' => ['in' => new Parameter($user_client)]])
                     ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                     ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * Returns sent/viewed (unpaid) invoices for a set of client IDs.
     * @param array $clientIds
     * @return Inv[]
     */
    public function repoUnpaidByClientIds(array $clientIds): array
    {
        if (empty($clientIds)) {
            return [];
        }
        return $this->select()
                    ->load('invAmount')
                    ->where(['client_id' => ['in' => new Parameter($clientIds)]])
                    ->andWhere(['status_id' => ['in' => new Parameter([2, 3])]])
                    ->where('deleted_at', null)
                    ->fetchAll();
    }

    public function guestVisible(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }
}
