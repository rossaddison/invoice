<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use DateTimeImmutable;

interface InvRepositoryInterface
{
    public function save(array|Inv|null $inv): void;

    public function repoClientInvoiceCountquery(int $client_id): int;

    /**
     * @return array<int, Inv>
     */
    public function repoClientPaidInvoicesquery(int $client_id): array;

    public function repoInvUnLoadedquery(int $id): ?Inv;

    /**
     * @return array<int, Inv>
     */
    public function repoForHomeCareRunquery(int $categorySecondaryId, DateTimeImmutable $date): array;
}
