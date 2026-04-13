<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Inv\Exception\PdfNotFoundException;

use App\Invoice\{
    Client\ClientRepository as CR,
    CustomValue\CustomValueRepository as CVR,
    CustomField\CustomFieldRepository as CFR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Entity\Inv, Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvCustom\InvCustomRepository as ICR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    SalesOrder\SalesOrderRepository as SOR,
    Upload\UploadRepository as UPR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\Invoice\Helpers\PdfHelper;
use App\Widget\Bootstrap5ModalPdf;

use Yiisoft\{Json\Json, Router\HydratorAttribute\RouteArgument};
use Psr\Http\Message\ResponseInterface as Response;

trait PdfTrait
{
    // Called from
    // ..src\Invoice\Asset\rebuild\js\inv.js inv_to_pdf_confirm_with_custom_fields

    /**
     * @return string
     */
    private function viewModalPdf(): string
    {
        $bootstrap5ModalPdf = new Bootstrap5ModalPdf(
            $this->translator,
            $this->webViewRenderer,
            'inv',
        );
        // show the pdf inside a modal when engaging with a view
        return $bootstrap5ModalPdf->renderPartialLayoutWithPdfAsString();
    }
 
    public function pdf(#[RouteArgument('include')] int $include, CR $cR,
        CVR $cvR, CFR $cfR, DLR $dlR, ACIR $aciR, GR $gR, SOR $soR, IAR $iaR,
            ICR $icR, IIR $iiR, ACIIR $aciiR, IIAR $iiaR, IR $iR, ITRR $itrR,
                UIR $uiR): Response
    {
        try {
            // include is a value of 0 or 1 passed from inv.js function
            // inv_to_pdf_with(out)_custom_fields indicating whether the user
            // wants custom fields included on the inv or not.
            $inv_id = (string) ($this->session->get('inv_id') ?? '0');
            $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ?
                $iaR->repoInvquery((int) $inv_id) : null);
            if ($inv_amount) {
                $custom = ($include === 1);
                $inv_custom_values = $this->invCustomValues($inv_id, $icR);
                // session is passed to the pdfHelper and will be used for the
                // locale ie. $session->get('_language') or the print_language
                // ie $session->get('print_language')
                $pdfhelper = new PdfHelper($this->sR, $this->session,
                        $this->translator);
                // The invoice will be streamed if set under Settings
                //  ...View
                //  ...Invoices
                //  ...Pdf Settings
                $stream = ($this->sR->getSetting('pdf_stream_inv') == '0') ?
                    false : true;
                // If we are required to mark invoices as 'sent' when sent.
                if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                    $this->generateInvNumberIfApplicable($inv_id, $iR,
                        $this->sR, $gR);
                    $this->sR->invoiceMarkSent($inv_id, $iR);
                }
                $inv = $iR->repoInvUnloadedquery($inv_id);
                if ($inv) {
                    $so = !empty($inv->getSoId()) ?
                        $soR->repoSalesOrderUnloadedquery($inv->getSoId()) :
                            null;
                    $pdfhelper->generateInvPdf($inv_id, $inv->getUserId(),
                            $stream, $custom, $so, $inv_amount,
                            $inv_custom_values, $cR, $cvR, $cfR, $dlR, $aciR,
                            $iiR, $aciiR, $iiaR, $iR, $itrR, $uiR,
                            $this->webViewRenderer);
                    return $this->pdfArchiveMessage();
                } // $inv
                return $this->factory->createResponse(
                    Json::encode(['success' => 0]));
            } // $inv_amount
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
            #[RouteArgument('id')] int $inv_id, CR $cR, CVR $cvR, CFR $cfR,
            DLR $dlR, ACIR $aciR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR,
            ACIIR $aciiR, IIAR $iiaR, IR $iR, ITRR $itrR, UCR $ucR, UIR $uiR,
            SOR $soR): void
    {
        $inv = $iR->repoInvUnLoadedquery((string) $inv_id);
        if (null!==$inv) {
            $this->pdfNotFoundException($inv, $ucR, $uiR);
        }
        if ($inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount($inv_id) > 0) ?
                    $iaR->repoInvquery($inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->invCustomValues(
                        (string) $inv_id, $icR);
                // session is passed to the pdfHelper and will be used for
                // the locale ie. $session->get('_language') or the
                // print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($this->sR, $this->session,
                        $this->translator);
                // The invoice will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark invoices as 'sent' when sent.
                if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                    $this->generateInvNumberIfApplicable((string) $inv_id,
                        $iR, $this->sR, $gR);
                    $this->sR->invoiceMarkSent((string) $inv_id, $iR);
                }
                $inv = $iR->repoInvUnloadedquery((string) $inv_id);
                if ($inv) {
                    $so = (!empty($inv->getSoId()) ?
                        $soR->repoSalesOrderLoadedquery($inv->getSoId()) : null);
                    $pdfhelper->generateInvPdf(
                        (string) $inv_id, $inv->getUserId(), $stream, true,
                            $so, $inv_amount, $inv_custom_values, $cR, $cvR,
                                $cfR, $dlR, $aciR, $iiR, $aciiR, $iiaR, $iR,
                                    $itrR, $uiR, $this->webViewRenderer);
                } //inv
            } //$inv_amount
        } //$inv_id
    }

    public function pdfDashboardExcludeCf(
        #[RouteArgument('id')] int $inv_id, CR $cR, CVR $cvR, CFR $cfR,
            DLR $dlR, ACIR $aciR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR,
            ACIIR $aciiR, IIAR $iiaR, IR $iR, ITRR $itrR, UCR $ucR, UIR $uiR,
            SOR $soR): void
    {
        $inv = $iR->repoInvUnLoadedquery((string) $inv_id);
        if (null !== $inv) {
            $this->pdfNotFoundException($inv, $ucR, $uiR);
        }
        if ($inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount($inv_id) > 0) ?
                    $iaR->repoInvquery($inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->invCustomValues(
                        (string) $inv_id, $icR);
                // session is passed to the pdfHelper and will be used for the
                // locale ie. $session->get('_language') or the print_language
                // ie $session->get('print_language')
                $pdfhelper = new PdfHelper($this->sR, $this->session,
                    $this->translator);
                // The invoice will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark invoices as 'sent' when sent.
                if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                    $this->generateInvNumberIfApplicable(
                        (string) $inv_id, $iR, $this->sR, $gR);
                    $this->sR->invoiceMarkSent((string) $inv_id, $iR);
                }
                $inv = $iR->repoInvUnloadedquery((string) $inv_id);
                if ($inv) {
                    $so = (!empty($inv->getSoId()) ?
                        $soR->repoSalesOrderLoadedquery($inv->getSoId()) : null);
                    $pdfhelper->generateInvPdf(
                        (string) $inv_id, $inv->getUserId(), $stream, false,
                            $so, $inv_amount, $inv_custom_values, $cR, $cvR,
                                $cfR, $dlR, $aciR, $iiR, $aciiR, $iiaR, $iR,
                                    $itrR, $uiR, $this->webViewRenderer);
                } //inv
            } //inv_amount
        } // inv_id
    }

    public function pdfDownloadIncludeCf(
        #[RouteArgument('url_key')] string $url_key, CR $cR, CVR $cvR, CFR $cfR,
            DLR $dlR, ACIR $aciR, GR $gR, SOR $soR, IAR $iaR, ICR $icR,
                IIR $iiR, ACIIR $aciiR, IIAR $iiaR, IR $iR, ITRR $itrR,
                    UCR $ucR, UIR $uiR, UPR $upR): mixed
    {
        $inv = $iR->repoUrlKeyGuestLoaded($url_key);
        if (null!==$inv) {
            $this->pdfNotFoundException($inv, $ucR, $uiR);
        }
        if ($url_key) {
            // If the status is sent 2, viewed 3, or paid 4 and the url key exists
            if ($iR->repoUrlKeyGuestCount($url_key) < 1) {
                return $this->webService->getNotFoundResponse();
            }
            // Retrieve the inv_id
            $inv_guest = $iR->repoUrlKeyGuestCount($url_key) ?
                    $iR->repoUrlKeyGuestLoaded($url_key) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ?
                        $iaR->repoInvquery((int) $inv_id) : null);
                if ($inv_amount) {
                    $inv_custom_values = $this->invCustomValues($inv_id, $icR);
                    // session is passed to the pdfHelper and will be used for
                    // the locale ie. $session->get('_language') or the
                    // print_language ie $session->get('print_language')
                    $pdfhelper = new PdfHelper(
                            $this->sR, $this->session, $this->translator);
                    // The invoice will be not be streamed ie. shown (in a
                    // separate tab see setting), but will be downloaded
                    $stream = false;
                    $c_f = true;
                    // If we are required to mark invoices as 'sent' when sent.
                    if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                        $this->generateInvNumberIfApplicable($inv_id, $iR,
                                $this->sR, $gR);
                        $this->sR->invoiceMarkSent($inv_id, $iR);
                    }
                    $inv = $iR->repoInvUnloadedquery((string) $inv_id);
                    if ($inv) {
                        $so = (!empty($inv->getSoId()) ?
                            $soR->repoSalesOrderLoadedquery($inv->getSoId()) :
                            null);
                        // Because the invoice is not streamed an aliase of
                        // temporary folder file location is returned
                        $temp_aliase = $pdfhelper->generateInvPdf(
                            $inv_id, $inv->getUserId(), $stream, $c_f, $so,
                                $inv_amount, $inv_custom_values, $cR, $cvR,
                                    $cfR, $dlR, $aciR, $iiR, $aciiR, $iiaR,
                                        $iR, $itrR, $uiR, $this->webViewRenderer);
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
                                        $upR->getContentTypes();
                                    // Check extension against allowed content
                                    // file types Related logic: see
                                    // UploadRepository getContentTypes
                                    $save_ctype = isset(
                                        $allowed_content_type_array[$file_ext]);
                                    /**
                                     * @var string $ctype
                                     */
                                    $ctype = $save_ctype ?
                                        $allowed_content_type_array[$file_ext] :
                                        $upR->getContentTypeDefaultOctetStream();
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
                            } // file_exists
                        } // is_string
                    } //inv
                } // inv_amount
            } // inv_guest
        } //url_key
        exit;
    }

    public function pdfDownloadExcludeCf(
        #[RouteArgument('url_key')] string $urlKey, CR $cR, CVR $cvR, CFR $cfR,
            DLR $dlR, ACIR $aciR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR,
                ACIIR $aciiR, IIAR $iiaR, IR $iR, ITRR $itrR, SOR $soR,
                    UCR $ucR, UIR $uiR, UPR $upR): mixed
    {
        $inv = $iR->repoUrlKeyGuestLoaded($urlKey);
        if (null!==$inv) {
            $this->pdfNotFoundException($inv, $ucR, $uiR);
        }
        if ($urlKey) {
            // If the status is sent 2, viewed 3, or paid 4 and the url key exists
            if ($iR->repoUrlKeyGuestCount($urlKey) < 1) {
                return $this->webService->getNotFoundResponse();
            }
            // Retrieve the inv_id
            $inv_guest = $iR->repoUrlKeyGuestCount($urlKey) ?
                $iR->repoUrlKeyGuestLoaded($urlKey) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ?
                    $iaR->repoInvquery((int) $inv_id) : null);
                if ($inv_amount) {
                    $inv_custom_values = $this->invCustomValues($inv_id, $icR);
                    // session is passed to the pdfHelper and will be used for
                    // the locale ie. $session->get('_language') or the
                    // print_language ie $session->get('print_language')
                    $pdfhelper = new PdfHelper($this->sR, $this->session,
                        $this->translator);
                    // The invoice will be not be streamed ie. shown (in a
                    // separate tab see setting), but will be downloaded
                    $stream = false;
                    $c_f = false;
                    // If we are required to mark invoices as 'sent' when sent.
                    if ($this->sR->getSetting('mark_invoices_sent_pdf') == 1) {
                        $this->generateInvNumberIfApplicable($inv_id, $iR,
                            $this->sR, $gR);
                        $this->sR->invoiceMarkSent($inv_id, $iR);
                    }
                    $inv = $iR->repoInvUnloadedquery((string) $inv_id);
                    if ($inv) {
                        $so = $soR->repoSalesOrderLoadedquery($inv->getSoId());
                        // Because the invoice is not streamed an aliase of
                        // temporary folder file location is returned
                        $temp_aliase = $pdfhelper->generateInvPdf(
                            $inv_id, $inv->getUserId(), $stream, $c_f, $so,
                                $inv_amount, $inv_custom_values, $cR, $cvR,
                                    $cfR, $dlR, $aciR, $iiR, $aciiR, $iiaR, $iR,
                                        $itrR, $uiR, $this->webViewRenderer);
                        if ($temp_aliase) {
                            $path_parts = pathinfo($temp_aliase);
                            /**
                             * @var string $path_parts['extension']
                             */
                            $file_ext = $path_parts['extension'];
                            // Do not choose 'basename' because extension pdf
                            // not necessary ie. filename is basename without
                            // extension .pdf
                            $original_file_name = $path_parts['filename'];
                            if (file_exists($temp_aliase)) {
                                $file_size = filesize($temp_aliase);
                                if ($file_size != false) {
                                    $allowed_content_type_array =
                                        $upR->getContentTypes();
                                    // Check extension against allowed content
                                    // file types Related logic: see
                                    // UploadRepository getContentTypes
                                    $save_ctype = isset(
                                        $allowed_content_type_array[$file_ext]);
                                    /** @var string $ctype */
                                    $ctype = $save_ctype ?
                                        $allowed_content_type_array[$file_ext] :
                                        $upR->getContentTypeDefaultOctetStream();
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
                            } // file_exists
                        } // $temp_aliase
                    } // $inv
                } // inv_amount
            } // inv_guest
        } // url_key
        exit;
    }

    private function pdfNotFoundException(Inv $inv, UCR $ucR, UIR $uiR) : void {
        if (($this->rbacObserver($inv, $ucR, $uiR))
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
