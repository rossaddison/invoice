<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\{Inv\Inv};
use App\Invoice\Inv\Exception\PdfNotFoundException;
use App\Invoice\Inv\InvPdfService;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Widget\Bootstrap5ModalPdf;

use Yiisoft\{Json\Json, Router\HydratorAttribute\RouteArgument};
use Psr\Http\Message\ResponseInterface as Response;

trait PdfTrait
{
    // Called from
    // ..src\Invoice\Asset\rebuild\js\inv.js inv_to_pdf_confirm_with_custom_fields

    private function viewModalPdf(): string
    {
        $bootstrap5ModalPdf = new Bootstrap5ModalPdf(
            $this->translator,
            $this->webViewRenderer,
            'inv',
        );
        return $bootstrap5ModalPdf->renderPartialLayoutWithPdfAsString();
    }

    public function pdf(
        #[RouteArgument('include')] int $include,
        InvPdfService $invPdfService,
    ): Response {
        try {
            $invId = (int) ($this->session->get('inv_id') ?? '0');
            $stream = $this->sR->getSetting('pdf_stream_inv') !== '0';
            $path = $invPdfService->generate($invId, $stream, $include === 1);
            if ($path !== '') {
                return $this->pdfArchiveMessage();
            }
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        } catch (\Yiisoft\ErrorHandler\Exception\ErrorException) {
            throw new PdfNotFoundException($this->translator);
        }
    }

    public function pdfArchiveMessage(): Response
    {
        if ($this->sR->getSetting('pdf_archive_inv') == '1') {
            return $this->factory->createResponse(
                    $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/pdf_close',
                ['heading' => '',
                    'message' => $this->translator->translate('pdf.archived.yes')],
            ));
        }
        return $this->factory->createResponse(
                $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/pdf_close',
            ['heading' => '',
                'message' => $this->translator->translate('pdf.archived.no')],
        ));
    }

    public function pdfDashboardIncludeCf(
        #[RouteArgument('id')] int $inv_id,
        InvPdfService $invPdfService,
    ): void {
        $inv = $invPdfService->findInv($inv_id);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $invPdfService);
        }
        if ($inv_id) {
            $invPdfService->generate($inv_id, true, true);
        }
    }

    public function pdfDashboardExcludeCf(
        #[RouteArgument('id')] int $inv_id,
        InvPdfService $invPdfService,
    ): void {
        $inv = $invPdfService->findInv($inv_id);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $invPdfService);
        }
        if ($inv_id) {
            $invPdfService->generate($inv_id, true, false);
        }
    }

    public function pdfDownloadIncludeCf(
        #[RouteArgument('url_key')] string $url_key,
        InvPdfService $invPdfService,
        UPR $upR,
    ): mixed {
        $inv = $invPdfService->loadGuestInv($url_key);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $invPdfService);
        }
        if ($url_key) {
            $inv_guest = $invPdfService->loadGuestInv($url_key);
            if ($inv_guest) {
                $inv_id = $inv_guest->reqId();
                $temp_aliase = $invPdfService->generate($inv_id, false, true);
                if ($temp_aliase) {
                    $this->sendFileDownload($temp_aliase, $upR);
                }
            }
        }
        exit;
    }

    public function pdfDownloadExcludeCf(
        #[RouteArgument('url_key')] string $urlKey,
        InvPdfService $invPdfService,
        UPR $upR,
    ): mixed {
        $inv = $invPdfService->loadGuestInv($urlKey);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $invPdfService);
        }
        if ($urlKey) {
            $inv_guest = $invPdfService->loadGuestInv($urlKey);
            if ($inv_guest) {
                $inv_id = $inv_guest->reqId();
                $temp_aliase = $invPdfService->generate($inv_id, false, false);
                if ($temp_aliase) {
                    $this->sendFileDownload($temp_aliase, $upR);
                }
            }
        }
        exit;
    }

    private function sendFileDownload(string $filePath, UPR $upR): void
    {
        if (!file_exists($filePath)) {
            return;
        }
        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            return;
        }
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $originalFileName = pathinfo($filePath, PATHINFO_BASENAME);
        $contentTypes = $upR->getContentTypes();
        $ctype = (string) (isset($contentTypes[$ext])
            ? $contentTypes[$ext]
            : $upR->getContentTypeDefaultOctetStream());
        // https://www.php.net/manual/en/function.header.php
        // header() must be called before any actual output is sent
        header('Expires: -1');
        header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
        header("Content-Disposition: attachment; filename=\"{$originalFileName}\"");
        header('Content-Type: ' . $ctype);
        header('Content-Length: ' . (string) $fileSize);
        echo file_get_contents($filePath, true);
    }

    private function pdfNotFoundException(Inv $inv, InvPdfService $invPdfService): void
    {
        if (($this->rbacObserver($inv, $invPdfService->ucR(), $invPdfService->uiR()))
                ||
            ($this->rbacAdmin())
                ||
            ($this->rbacAccountant())
        ) {
            // Do nothing
        } else {
            throw new PdfNotFoundException($this->translator);
        }
    }
}
