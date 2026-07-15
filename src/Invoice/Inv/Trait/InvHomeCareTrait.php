<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;

trait InvHomeCareTrait
{
    /**
     * Total invoice count for a client, used by the home-care
     * auto-invoice facility to distinguish "no invoice ever" (nothing to
     * template from) from "invoices exist but none paid".
     *
     * @param int $client_id
     * @return int
     */
    #[\Override]
    public function repoClientInvoiceCountquery(int $client_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->where('deleted_at', null)
                      ->count();
    }

    /**
     * The client's most recently paid invoice (status_id = 4), used as the
     * eligibility anchor and item template for the home-care
     * auto-invoice facility.
     *
     * @param int $client_id
     * @return Inv|null
     */
    #[\Override]
    public function repoClientLatestPaidInvoicequery(int $client_id): ?Inv
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->where(['status_id' => 4])
                      ->where('deleted_at', null)
                      ->orderBy('date_created', 'DESC')
                      ->fetchOne() ?: null;
    }

    /**
     * Counts invoices for a client dated after a given date, regardless of
     * status. Used by the home-care auto-invoice facility to detect
     * an "interim invoice" (paid or not) that should block a new one.
     *
     * @param int $client_id
     * @param string $afterDate Y-m-d date string
     * @return int
     */
    #[\Override]
    public function repoClientInvoiceCountAfterDatequery(int $client_id, string $afterDate): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->where('date_created', '>', $afterDate)
                      ->where('deleted_at', null)
                      ->count();
    }
}
