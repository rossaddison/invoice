<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Client\ClientRepository as ClientR;
use App\Invoice\Inv\InvCopyDeps;
use App\Invoice\Inv\HomeCareCleaningEligibilityService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\FormModel\FormHydrator;

trait HomeCareScan
{
    /**
     * Public, unauthenticated home-care-cleaning QR scan endpoint. Knowing the
     * printed token is sufficient to trigger this check — a deliberate,
     * scoped exception to this app's usual guest-access model, where a
     * url_key alone never grants access without an authenticated session.
     *
     * Related logic: see Route::get('/scan/{token}')
     */
    public function homeCareScan(
        string $token,
        ClientR $clientR,
        HomeCareCleaningEligibilityService $eligibilityService,
        InvCopyDeps $d,
        FormHydrator $formHydrator,
    ): Response {
        $client = $clientR->repoClientByQrTokenquery($token);
        if (null === $client) {
            return $this->webService->getNotFoundResponse();
        }

        $templateInvoice = $eligibilityService->findInvoiceToCopyIfEligible($client->reqId());
        if (null === $templateInvoice) {
            return $this->renderHomeCareScanResult('not_eligible');
        }

        $newInvoiceId = $this->generateHomeCareCleaningInvoice(
            $client->reqId(), $templateInvoice, $d, $formHydrator);

        return $this->renderHomeCareScanResult($newInvoiceId > 0 ? 'generated' : 'contact_us');
    }

    private function renderHomeCareScanResult(string $outcome): Response
    {
        return $this->webViewRenderer->renderPartial('homecare_scan_result', [
            'outcome' => $outcome,
        ]);
    }
}
