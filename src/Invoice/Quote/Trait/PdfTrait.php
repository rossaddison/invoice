<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Quote\QuotePdfService;
use Yiisoft\{Json\Json, Router\HydratorAttribute\RouteArgument};
use Psr\Http\Message\ResponseInterface as Response;

trait PdfTrait
{
    // Called from quote.js quote_to_pdf_confirm_with_custom_fields

    public function pdf(
        #[RouteArgument('include')] int $include,
        QuotePdfService $quotePdfService,
    ): Response {
        $quoteId = (int) $this->session->get('quote_id');
        $stream = true;
        $path = $quotePdfService->generate($quoteId, $stream, $include === 1);
        $parameters = $path !== '' ? ['success' => 1] : ['success' => 0];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    public function pdfDashboardIncludeCf(
        #[RouteArgument('id')] int $quoteId,
        QuotePdfService $quotePdfService,
    ): void {
        if ($quoteId) {
            $quotePdfService->generate($quoteId, true, true);
        }
    }

    public function pdfDashboardExcludeCf(
        #[RouteArgument('id')] int $quoteId,
        QuotePdfService $quotePdfService,
    ): void {
        if ($quoteId) {
            $quotePdfService->generate($quoteId, true, false);
        }
    }
}
