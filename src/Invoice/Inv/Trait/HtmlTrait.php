<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Inv\InvPdfService;
use Yiisoft\{Html\Html, Router\HydratorAttribute\RouteArgument};
use Psr\Http\Message\ResponseInterface as Response;

trait HtmlTrait
{
    public function html(
        #[RouteArgument('include')] int $include,
        InvPdfService $invPdfService,
    ): Response {
        $invId = (int) $this->session->get('inv_id');
        $html = $invPdfService->generateHtml($invId, $include === 1);
        if ($html !== '') {
            // Same bug/fix as PdfTrait::pdfPlaywrightDocument()'s own
            // getHtmlResponse() call and Inv\Trait\Peppol::peppolStreamOutput()
            // (PR #1168): $this->factory is DataResponseFactoryInterface --
            // DataResponseMiddleware + JsonFormatter JSON-encode whatever's
            // passed to createResponse(), turning this already-HTML-encoded
            // <pre> string into a JSON string body with the wrong
            // Content-Type. Found live on yii3i.online via the "Html
            // Preview" button, 2026-09-01 -- same recurring pattern
            // documented in feedback_factory_html_encoding memory.
            return $this->webService->getHtmlResponse('<pre>' . Html::encode($html) . '</pre>');
        }
        // Same memory's third documented variant: Json::encode() already
        // produces a JSON string; passing that string to createResponse()
        // JSON-encodes it AGAIN, wrapping valid JSON in an extra layer of
        // string escaping instead of returning a real JSON object. Pass
        // the plain array instead -- JsonFormatter encodes it once.
        return $this->factory->createResponse(['success' => 0]);
    }
}
