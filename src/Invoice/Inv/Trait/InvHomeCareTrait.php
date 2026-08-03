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
     * The client's most recently paid invoices (status_id = 4), most recent
     * first — used by the home-care auto-invoice facility as the item
     * template source, searching backwards until one with a Service item
     * is found (see HomeCareCleaningEligibilityService). Ordered by `id`
     * rather than `date_created`: the id is immutable and assigned in true
     * insertion order, so it can't be shifted by an admin backdating an
     * invoice's date after the fact. Capped at the most recent 50 to bound
     * the backward search for a client whose invoices never contain one.
     *
     * @param int $client_id
     * @return array<int, Inv>
     */
    #[\Override]
    public function repoClientPaidInvoicesquery(int $client_id): array
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->where(['status_id' => 4])
                      ->where('deleted_at', null)
                      ->orderBy('id', 'DESC')
                      ->limit(50)
                      ->fetchAll();
    }
}
