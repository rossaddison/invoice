<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\FromDropDown\FromDropDownRepository as FDR;
use App\Invoice\Inv\InvBatchEmailService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Json\Json;

trait BatchEmail
{
    /**
     * Returns per-client email resolution preview so the confirmation modal
     * can show exactly where each email will go before the user confirms.
     */
    public function batchEmailPreview(
        Request $request,
        InvBatchEmailService $batchEmailService,
    ): Response {
        $params  = $request->getQueryParams();
        /** @var list<string> $raw */
        $raw     = (array) ($params['keylist'] ?? []);
        $invIds  = array_map('intval', $raw);
        return $this->factory->createResponse(
            Json::encode($batchEmailService->preview($invIds))
        );
    }

    /**
     * Groups selected invoices by client, sends one email per client with all
     * PDFs attached, marks every invoice as Sent (status 2), and logs each send.
     */
    public function batchEmail(
        Request $request,
        InvBatchEmailService $batchEmailService,
        ETR $etR,
        FDR $fdR,
    ): Response {
        $params          = $request->getQueryParams();
        /** @var list<string> $raw */
        $raw             = (array) ($params['keylist'] ?? []);
        $invIds          = array_map('intval', $raw);
        $emailTemplateId = (int) ($params['email_template_id'] ?? 0);

        if ($invIds === [] || $emailTemplateId === 0) {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }

        $fromDropDownId    = (int) ($params['from_dropdown_id'] ?? 0);
        $selectedFromEmail = $fromDropDownId > 0
            ? ($fdR->repoFromDropDownLoadedquery($fromDropDownId)?->getEmail() ?? '')
            : '';

        $success = $batchEmailService->sendBatch($invIds, $emailTemplateId, $etR, $selectedFromEmail);
        return $this->factory->createResponse(Json::encode(['success' => $success ? 1 : 0]));
    }
}
