<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\{Inv\Inv};
use App\Invoice\Inv\Exception\PdfNotFoundException;
use App\Invoice\Inv\InvPdfDeps;
use App\Invoice\Helpers\PdfHelper;
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
        InvPdfDeps $d,
    ): Response {
        try {
            $inv_id = (int) ($this->session->get('inv_id') ?? '0');
            $inv_amount = (($d->iaR->repoInvAmountCount($inv_id) > 0) ?
                $d->iaR->repoInvquery($inv_id) : null);
            if ($inv_amount) {
                $custom = ($include === 1);
                $inv_custom_values = $this->invCustomValues($inv_id, $d->icR);
                $pdfhelper = new PdfHelper($this->sR, $this->session,
                        $this->translator);
                $stream = ($this->sR->getSetting('pdf_stream_inv') == '0') ?
                    false : true;
                if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                    $this->generateInvNumberIfApplicable($inv_id, $d->iR,
                        $this->sR, $d->gR);
                    $this->sR->invoiceMarkSent($inv_id, $d->iR);
                }
                $inv = $d->iR->repoInvUnloadedquery($inv_id);
                if (null !== $inv) {
                    $so = (null !== ($soId = $inv->getSoId())) ?
                        $d->soR->repoSalesOrderUnloadedquery($soId) : null;
                    $pdfhelper->generateInvPdf($inv_id, $inv->reqUserId(),
                            $stream, $custom, $so, $inv_amount,
                            $inv_custom_values, $d->cR, $d->cvR, $d->cfR, $d->dlR, $d->aciR,
                            $d->iiR, $d->aciiR, $d->iiaR, $d->iR, $d->itrR, $d->uiR,
                            $this->webViewRenderer);
                    return $this->pdfArchiveMessage();
                }
                return $this->factory->createResponse(
                    Json::encode(['success' => 0]));
            }
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        } catch (\Yiisoft\ErrorHandler\Exception\ErrorException $e) {
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
        InvPdfDeps $d,
    ): void {
        $inv = $d->iR->repoInvUnLoadedquery($inv_id);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $d);
        }
        if ($inv_id) {
            $inv_amount = (($d->iaR->repoInvAmountCount($inv_id) > 0) ?
                    $d->iaR->repoInvquery($inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->invCustomValues($inv_id, $d->icR);
                $pdfhelper = new PdfHelper($this->sR, $this->session,
                        $this->translator);
                $stream = true;
                if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                    $this->generateInvNumberIfApplicable($inv_id,
                        $d->iR, $this->sR, $d->gR);
                    $this->sR->invoiceMarkSent($inv_id, $d->iR);
                }
                $inv = $d->iR->repoInvUnloadedquery($inv_id);
                if ($inv) {
                    $so = (!empty((int) $inv->getSoId()) ?
                        $d->soR->repoSalesOrderLoadedquery((int) $inv->getSoId()) : null);
                    $pdfhelper->generateInvPdf(
                            $inv_id, $inv->reqUserId(), $stream, true,
                            $so, $inv_amount, $inv_custom_values, $d->cR, $d->cvR,
                                $d->cfR, $d->dlR, $d->aciR, $d->iiR, $d->aciiR, $d->iiaR, $d->iR,
                                    $d->itrR, $d->uiR, $this->webViewRenderer);
                }
            }
        }
    }

    public function pdfDashboardExcludeCf(
        #[RouteArgument('id')] int $inv_id,
        InvPdfDeps $d,
    ): void {
        $inv = $d->iR->repoInvUnLoadedquery($inv_id);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $d);
        }
        if ($inv_id) {
            $inv_amount = (($d->iaR->repoInvAmountCount($inv_id) > 0) ?
                    $d->iaR->repoInvquery($inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->invCustomValues($inv_id, $d->icR);
                $pdfhelper = new PdfHelper($this->sR, $this->session,
                    $this->translator);
                $stream = true;
                if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                    $this->generateInvNumberIfApplicable(
                        $inv_id, $d->iR, $this->sR, $d->gR);
                    $this->sR->invoiceMarkSent($inv_id, $d->iR);
                }
                $inv = $d->iR->repoInvUnloadedquery($inv_id);
                if ($inv) {
                    $so = (!empty((int) $inv->getSoId()) ?
                        $d->soR->repoSalesOrderLoadedquery((int) $inv->getSoId()) : null);
                    $pdfhelper->generateInvPdf(
                            $inv_id, $inv->reqUserId(), $stream, false,
                            $so, $inv_amount, $inv_custom_values, $d->cR, $d->cvR,
                                $d->cfR, $d->dlR, $d->aciR, $d->iiR, $d->aciiR, $d->iiaR, $d->iR,
                                    $d->itrR, $d->uiR, $this->webViewRenderer);
                }
            }
        }
    }

    public function pdfDownloadIncludeCf(
        #[RouteArgument('url_key')] string $url_key,
        InvPdfDeps $d,
    ): mixed {
        $inv = $d->iR->repoUrlKeyGuestLoaded($url_key);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $d);
        }
        if ($url_key) {
            if ($d->iR->repoUrlKeyGuestCount($url_key) < 1) {
                return $this->webService->getNotFoundResponse();
            }
            $inv_guest = $d->iR->repoUrlKeyGuestCount($url_key) ?
                    $d->iR->repoUrlKeyGuestLoaded($url_key) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->reqId();
                $inv_amount = (($d->iaR->repoInvAmountCount($inv_id) > 0) ?
                        $d->iaR->repoInvquery($inv_id) : null);
                if ($inv_amount) {
                    $inv_custom_values = $this->invCustomValues($inv_id, $d->icR);
                    $pdfhelper = new PdfHelper(
                            $this->sR, $this->session, $this->translator);
                    $stream = false;
                    $c_f = true;
                    if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                        $this->generateInvNumberIfApplicable($inv_id, $d->iR,
                                $this->sR, $d->gR);
                        $this->sR->invoiceMarkSent($inv_id, $d->iR);
                    }
                    $inv = $d->iR->repoInvUnloadedquery($inv_id);
                    if ($inv) {
                        $so = (($inv->getSoId() > 0) ?
                            $d->soR->repoSalesOrderLoadedquery((int) $inv->getSoId()) :
                            null);
                        $temp_aliase = $pdfhelper->generateInvPdf(
                            $inv_id, $inv->reqUserId(), $stream, $c_f, $so,
                                $inv_amount, $inv_custom_values, $d->cR, $d->cvR,
                                    $d->cfR, $d->dlR, $d->aciR, $d->iiR, $d->aciiR, $d->iiaR,
                                        $d->iR, $d->itrR, $d->uiR, $this->webViewRenderer);
                        if ($temp_aliase) {
                            $path_parts = pathinfo($temp_aliase);
                            /**
                             * @var string $path_parts['extension']
                             */
                            $file_ext = $path_parts['extension'];
                            $original_file_name = $path_parts['basename'];
                            if (file_exists($temp_aliase)) {
                                $file_size = filesize($temp_aliase);
                                if ($file_size != false) {
                                    $allowed_content_type_array =
                                        $d->upR->getContentTypes();
                                    $save_ctype = isset(
                                        $allowed_content_type_array[$file_ext]);
                                    /**
                                     * @var string $ctype
                                     */
                                    $ctype = $save_ctype ?
                                        $allowed_content_type_array[$file_ext] :
                                        $d->upR->getContentTypeDefaultOctetStream();
    // https://www.php.net/manual/en/function.header.php
    // Remember that header() must be called before any actual output
    // is sent, either by normal HTML tags, blank lines in a file,
    // or from PHP.
    header('Expires: -1');
    header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
    header("Content-Disposition: attachment; filename=\"$original_file_name\"");
    header('Content-Type: ' . $ctype);
    header('Content-Length: ' . (string) $file_size);
    echo file_get_contents($temp_aliase, true);
                                }
                                exit;
                            }
                        }
                    }
                }
            }
        }
        exit;
    }

    public function pdfDownloadExcludeCf(
        #[RouteArgument('url_key')] string $urlKey,
        InvPdfDeps $d,
    ): mixed {
        $inv = $d->iR->repoUrlKeyGuestLoaded($urlKey);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $d);
        }
        if ($urlKey) {
            if ($d->iR->repoUrlKeyGuestCount($urlKey) < 1) {
                return $this->webService->getNotFoundResponse();
            }
            $inv_guest = $d->iR->repoUrlKeyGuestCount($urlKey) ?
                $d->iR->repoUrlKeyGuestLoaded($urlKey) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->reqId();
                $inv_amount = (($d->iaR->repoInvAmountCount($inv_id) > 0) ?
                    $d->iaR->repoInvquery($inv_id) : null);
                if ($inv_amount) {
                    $inv_custom_values = $this->invCustomValues($inv_id, $d->icR);
                    $pdfhelper = new PdfHelper($this->sR, $this->session,
                        $this->translator);
                    $stream = false;
                    $c_f = false;
                    if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                        $this->generateInvNumberIfApplicable($inv_id, $d->iR,
                            $this->sR, $d->gR);
                        $this->sR->invoiceMarkSent($inv_id, $d->iR);
                    }
                    $inv = $d->iR->repoInvUnloadedquery($inv_id);
                    if ($inv) {
                        $so = $d->soR->repoSalesOrderLoadedquery((int) $inv->getSoId());
                        $temp_aliase = $pdfhelper->generateInvPdf(
                            $inv_id, $inv->reqUserId(), $stream, $c_f, $so,
                                $inv_amount, $inv_custom_values, $d->cR, $d->cvR,
                                    $d->cfR, $d->dlR, $d->aciR, $d->iiR, $d->aciiR, $d->iiaR, $d->iR,
                                        $d->itrR, $d->uiR, $this->webViewRenderer);
                        if ($temp_aliase) {
                            $path_parts = pathinfo($temp_aliase);
                            /**
                             * @var string $path_parts['extension']
                             */
                            $file_ext = $path_parts['extension'];
                            $original_file_name = $path_parts['filename'];
                            if (file_exists($temp_aliase)) {
                                $file_size = filesize($temp_aliase);
                                if ($file_size != false) {
                                    $allowed_content_type_array =
                                        $d->upR->getContentTypes();
                                    $save_ctype = isset(
                                        $allowed_content_type_array[$file_ext]);
                                    /** @var string $ctype */
                                    $ctype = $save_ctype ?
                                        $allowed_content_type_array[$file_ext] :
                                        $d->upR->getContentTypeDefaultOctetStream();
// https://www.php.net/manual/en/function.header.php
// Remember that header() must be called before any actual output is sent,
// either by normal HTML tags, blank lines in a file, or from PHP.
header('Expires: -1');
header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
header("Content-Disposition: attachment; filename=\"$original_file_name\"");
header('Content-Type: ' . $ctype);
header('Content-Length: ' . (string) $file_size);
echo file_get_contents($temp_aliase, true);
                                }
                                exit;
                            }
                        }
                    }
                }
            }
        }
        exit;
    }

    private function pdfNotFoundException(Inv $inv, InvPdfDeps $d): void
    {
        if (($this->rbacObserver($inv, $d->ucR, $d->uiR))
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
