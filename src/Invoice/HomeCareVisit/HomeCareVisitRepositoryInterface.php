<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareVisit;

use App\Infrastructure\Persistence\HomeCareVisit\HomeCareVisit;
use DateTimeImmutable;

interface HomeCareVisitRepositoryInterface
{
    public function save(array|HomeCareVisit|null $visit): void;

    /**
     * Finds the (client_id, visited_at) row if one already exists —
     * whichever request created it "wins"; every other concurrent/repeat
     * scan for the same client on the same day reads this back instead of
     * attempting its own invoice generation.
     */
    public function repoFindByClientAndDatequery(int $clientId, DateTimeImmutable $visitedAt): ?HomeCareVisit;

    /**
     * Attempts to atomically claim today's visit slot for this client. Only
     * ever returns a genuinely new row — if one already exists (a concurrent
     * request won the race, or this is a repeat same-day scan), returns
     * null, and the caller should re-fetch via repoFindByClientAndDatequery()
     * to render the previously-recorded outcome instead of re-deciding.
     */
    public function tryCreatePendingVisit(int $clientId, DateTimeImmutable $visitedAt): ?HomeCareVisit;

    /**
     * The client's most recently *generated* (invoiced) visit, if any —
     * used as the eligibility anchor instead of scanning the client's whole
     * invoice history, so an unrelated invoice/credit note an admin raises
     * for other reasons can never block this.
     */
    public function repoLatestGeneratedVisitquery(int $clientId): ?HomeCareVisit;

    /**
     * Most recent scan attempts across all clients, for the admin-visible
     * log — every outcome (generated/not_eligible/contact_us) is recorded
     * here, unlike the deliberately generic public scan-result page.
     *
     * @return iterable<HomeCareVisit>
     */
    public function repoRecentquery(int $limit): iterable;
}
