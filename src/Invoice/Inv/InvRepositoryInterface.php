<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;

interface InvRepositoryInterface
{
    public function save(array|Inv|null $inv): void;

    public function repoClientInvoiceCountquery(int $client_id): int;

    public function repoClientLatestPaidInvoicequery(int $client_id): ?Inv;

    public function repoClientInvoiceCountAfterDatequery(int $client_id, string $afterDate): int;

    public function repoInvUnLoadedquery(int $id): ?Inv;
}
