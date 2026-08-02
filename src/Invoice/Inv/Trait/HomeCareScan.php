<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Client\ClientRepository as ClientR;
use App\Invoice\HomeCareVisit\HomeCareVisitRepositoryInterface;
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
     * Losing that claim (a concurrent request won it, or this is a repeat
     * scan) replays whatever outcome the winning request recorded rather
     * than re-deciding, so a scan can never generate a second invoice for
     * the same client on the same day. See
     * docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md.
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
    ): Response {
        $client = $clientR->repoClientByQrTokenquery($token);
        if (null === $client) {
            return $this->webService->getNotFoundResponse();
        }
        $clientId = $client->reqId();
        $today = new DateTimeImmutable('today');

        $visit = $visitRepository->tryCreatePendingVisit($clientId, $today);
        if (null === $visit) {
            $existing = $visitRepository->repoFindByClientAndDatequery($clientId, $today);
            return $this->renderHomeCareScanResult($existing?->getOutcome() ?? 'not_eligible');
        }

        $templateInvoice = $eligibilityService->findInvoiceToCopyIfEligible($clientId);
        if (null === $templateInvoice) {
            $visit->setOutcome('not_eligible');
            $visitRepository->save($visit);
            return $this->renderHomeCareScanResult('not_eligible');
        }

        $newInvoiceId = $this->generateHomeCareCleaningInvoice(
            $clientId, $templateInvoice, $d, $formHydrator);

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
        $visitRepository->save($visit);

        return $this->renderHomeCareScanResult($newInvoiceId > 0 ? 'generated' : 'contact_us');
    }

    private function renderHomeCareScanResult(string $outcome): Response
    {
        return $this->webViewRenderer->renderPartial('homecare_scan_result', [
            'outcome' => $outcome,
        ]);
    }
}
