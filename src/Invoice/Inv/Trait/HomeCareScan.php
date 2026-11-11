<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\HomeCareVisit\HomeCareVisit;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Client\ClientRepository as ClientR;
use App\Invoice\HomeCareVisit\HomeCareVisitRepositoryInterface;
use App\Invoice\Inv\HomeCareScanConfirmer;
use App\Invoice\Inv\InvCopyDeps;
use App\Invoice\Inv\HomeCareCleaningEligibilityService;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

trait HomeCareScan
{
    /**
     * Public, unauthenticated home-care-cleaning QR scan endpoint. Knowing the
     * printed token is sufficient to trigger this check — a deliberate, scoped
     * exception to this app's usual guest-access model, where a url_key alone
     * never grants access without an authenticated session.
     *
     * A pending HomeCareVisit row is claimed for (client, today) *before*
     * the eligibility decision — its unique index is what actually makes
     * concurrent/repeat same-day scans safe, not application-level locking.
     * Losing that claim only blocks a retry permanently once the existing
     * row's outcome is 'generated' (a real invoice exists — must never
     * double-generate). A stale 'not_eligible'/'contact_us' row means
     * nothing was created yet, so it's safe to delete and re-claim: staff
     * fixing a data issue (e.g. a product's type) mid-day can have the
     * customer rescan and actually succeed, rather than being stuck
     * replaying the first attempt's outcome until midnight. See
     * docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md.
     *
     * A client with a planned draft still waiting on its allocations is NOT given a second
     * invoice: the page asks the customer to confirm the visit instead (no side effect on
     * this GET), and homeCareScanConfirm() acts on the button.
     *
     * Related logic: see Route::get('/scan/{token}')
     */
    public function homeCareScan(
        #[RouteArgument('token')]
        string $token,
        ClientR $clientR,
        HomeCareCleaningEligibilityService $eligibilityService,
        HomeCareVisitRepositoryInterface $visitRepository,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
        HomeCareScanConfirmer $confirmer,
    ): Response {
        $client = $clientR->repoClientByQrTokenquery($token);
        if (null === $client) {
            return $this->webService->getNotFoundResponse();
        }

        $pending = $eligibilityService->isFeatureEnabledForClient($client->reqId())
            ? $confirmer->pendingDraft($client->reqId(), new DateTimeImmutable('today'))
            : null;

        if (null === $pending) {
            return $this->claimAndDecideHomeCareScan($client->reqId(), $eligibilityService, $visitRepository, $d, $formHydrator);
        }
        $confirmation = ['outcome' => 'confirm', 'token' => $token, 'visitDate' => $pending->getDateCreated()->format('Y-m-d')];
        return $this->webViewRenderer->renderPartial('homecare_scan_result', $confirmation);
    }

    /**
     * Claims today's visit slot (a repeat scan gets the earlier outcome instead) and, when the claim
     * is new, decides and records what the scan does.
     */
    private function claimAndDecideHomeCareScan(
        int $clientId,
        HomeCareCleaningEligibilityService $eligibilityService,
        HomeCareVisitRepositoryInterface $visitRepository,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): Response {
        $claim = $this->claimTodaysHomeCareVisit($clientId, $visitRepository);
        if ($claim instanceof Response) {
            return $claim;
        }
        [$claimedClientId, $visit] = $claim;

        return $this->decideAndRecordHomeCareScanOutcome(
            $claimedClientId,
            $visit,
            $eligibilityService,
            $visitRepository,
            $d,
            $formHydrator,
        );
    }

    /**
     * The customer pressed "Yes, it has been cleaned" on the confirm page. Claims today's
     * visit slot (so a repeat press or a second device is a no-op), re-checks that a planned
     * draft is still pending, then completes its sides and marks it Sent (no email).
     *
     * Related logic: see Route::post('/scan/{token}')
     */
    public function homeCareScanConfirm(
        #[RouteArgument('token')]
        string $token,
        ClientR $clientR,
        HomeCareCleaningEligibilityService $eligibilityService,
        HomeCareVisitRepositoryInterface $visitRepository,
        HomeCareScanConfirmer $confirmer,
    ): Response {
        $client = $clientR->repoClientByQrTokenquery($token);
        if (null === $client) {
            return $this->webService->getNotFoundResponse();
        }
        $claim = $this->claimTodaysHomeCareVisit($client->reqId(), $visitRepository);
        if ($claim instanceof Response) {
            return $claim;
        }

        return $this->confirmClaimedHomeCareVisit($claim[0], $claim[1], $eligibilityService, $visitRepository, $confirmer);
    }

    /**
     * With today's visit slot claimed: confirms the planned draft still pending for the client, or
     * records the visit as not eligible when there is none.
     */
    private function confirmClaimedHomeCareVisit(
        int $clientId,
        HomeCareVisit $visit,
        HomeCareCleaningEligibilityService $eligibilityService,
        HomeCareVisitRepositoryInterface $visitRepository,
        HomeCareScanConfirmer $confirmer,
    ): Response {
        $pending = $eligibilityService->isFeatureEnabledForClient($clientId)
            ? $confirmer->pendingDraft($clientId, new DateTimeImmutable('today'))
            : null;
        if (null === $pending) {
            $visit->setOutcome('not_eligible');
            $visitRepository->save($visit);
            return $this->renderHomeCareScanResult('not_eligible');
        }
        $confirmer->confirm($pending, $visit);
        return $this->renderHomeCareScanResult('confirmed');
    }

    /**
     * Attempts to claim (or reclaim, after deleting a stale non-terminal
     * row) today's HomeCareVisit slot for this client.
     *
     * @return Response|array{0: int, 1: HomeCareVisit}
     */
    private function claimTodaysHomeCareVisit(
        int $clientId,
        HomeCareVisitRepositoryInterface $visitRepository,
    ): Response|array {
        $today = new DateTimeImmutable('today');
        $visit = $visitRepository->tryCreatePendingVisit($clientId, $today);
        if (null === $visit) {
            $existing = $visitRepository->repoFindByClientAndDatequery($clientId, $today);
            // 'generated' and 'confirmed' are terminal: an invoice was created, or a planned one
            // was confirmed and sent, so neither may ever be repeated
            if ($existing !== null && !in_array($existing->getOutcome(), ['generated', 'confirmed'], true)) {
                $visitRepository->delete($existing);
                $visit = $visitRepository->tryCreatePendingVisit($clientId, $today);
            }
            if (null === $visit) {
                return $this->renderHomeCareScanResult($existing?->getOutcome() ?? 'not_eligible');
            }
        }
        return [$clientId, $visit];
    }

    private function decideAndRecordHomeCareScanOutcome(
        int $clientId,
        HomeCareVisit $visit,
        HomeCareCleaningEligibilityService $eligibilityService,
        HomeCareVisitRepositoryInterface $visitRepository,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): Response {
        $templateInvoice = $eligibilityService->findInvoiceToCopyIfEligible($clientId);
        if (null === $templateInvoice) {
            $visit->setOutcome('not_eligible');
            $visitRepository->save($visit);
            return $this->renderHomeCareScanResult('not_eligible');
        }

        $newInvoiceId = $this->generateHomeCareCleaningInvoice(
            $clientId,
            $templateInvoice,
            $d,
            $formHydrator
        );
        $this->applyHomeCareScanOutcome($visit, $newInvoiceId, $clientId, $templateInvoice);
        $visitRepository->save($visit);

        return $this->renderHomeCareScanResult($newInvoiceId > 0 ? 'generated' : 'contact_us');
    }

    private function applyHomeCareScanOutcome(
        HomeCareVisit $visit,
        int $newInvoiceId,
        int $clientId,
        Inv $templateInvoice,
    ): void {
        if ($newInvoiceId > 0) {
            $visit->setOutcome('generated');
            $visit->setInvoiceId($newInvoiceId);
        } else {
            $visit->setOutcome('contact_us');
            $visit->setReason(
                'generateHomeCareCleaningInvoice() returned 0 — form validation'
                . ' failed, or the client has no single active linked user account',
            );
            $this->logger->warning('HomeCare auto-invoice generation failed.', [
                'client_id' => $clientId,
                'template_invoice_id' => $templateInvoice->reqId(),
            ]);
        }
    }

    private function renderHomeCareScanResult(string $outcome): Response
    {
        return $this->webViewRenderer->renderPartial('homecare_scan_result', [
            'outcome' => $outcome,
        ]);
    }
}
