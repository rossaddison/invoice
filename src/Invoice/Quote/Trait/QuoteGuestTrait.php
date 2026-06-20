<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\Quote\Quote;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;

trait QuoteGuestTrait
{
    /**
     * @param string $url_key
     * @return Quote|null
     */
    public function repoUrlKeyGuestLoaded(string $url_key): ?Quote
    {
        $query = $this->select()
                       ->load('client')
                       ->where(['url_key' => $url_key]);
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
                      ->count();
    }

    /**
     * @param int $quote_id
     * @param array $user_client
     * @return int
     */
    public function repoClientGuestCount(int $quote_id, array $user_client = []): int
    {
        return $this->select()
                      ->where(['id' => $quote_id])
                      ->andWhere(['client_id' => ['in' => new Parameter($user_client)]])
                      ->count();
    }

    /**
     * @param int $status_id
     * @param array $user_client
     * @return EntityReader
     */
    public function repoGuestClientsSentViewedApprovedRejectedCancelled(int $status_id, array $user_client = []): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id' => $status_id])
                    ->andWhere(['client_id' => ['in' => new Parameter($user_client)]]);
            return $this->prepareDataReader($query);
        }
        $query = $this->select()
                     // sent = 2, viewed = 3, approved = 4, rejected = 5, cancelled = 6
                     ->where(['client_id' => ['in' => new Parameter($user_client)]])
                     ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6])]]);
        return $this->prepareDataReader($query);
    }

    public function guestVisible(): Select
    {
        return $this->select()->where(['status_id' => ['in' => new Parameter([2,3,4,5])]]);
    }

    /**
     * @param string $url_key
     */
    public function approveOrRejectQuoteByKey(string $url_key): Select
    {
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5])]])
                      ->where(['url_key' => $url_key]);
    }

    /**
     * @param int $id
     * @return Select
     */
    public function approveOrRejectQuoteById(int $id): Select
    {
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5])]])
                      ->where(['id' => $id]);
    }
}
