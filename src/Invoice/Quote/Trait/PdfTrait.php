<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\{
    Client\ClientRepository as CR,
    CustomField\CustomFieldRepository as CFR,
    CustomValue\CustomValueRepository as CVR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Group\GroupRepository as GR,
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    Setting\SettingRepository as SR,
    UserInv\UserInvRepository as UIR,
};
use App\Invoice\Helpers\PdfHelper;
use Yiisoft\{
    Json\Json,
    Router\HydratorAttribute\RouteArgument,
};
use Psr\Http\Message\ResponseInterface as Response;

trait PdfTrait
{
    // Called from quote.js quote_to_pdf_confirm_with_custom_fields

    public function pdf(#[RouteArgument('include')] int $include, CR $cR,
        CVR $cvR, CFR $cfR, DLR $dlR, GR $gR, QAR $qaR, ACQIR $acqiR,
        QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR,
        UIR $uiR): Response
    {
        // include is a value of 0 or 1 passed from quote.js
        // function quoteToPdfWith(out)_custom_fields indicating whether
        // the user wants custom fields included on the quote or not.
        $quote_id = (string) $this->session->get('quote_id');
        $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ?
            $qaR->repoQuotequery($quote_id) : null);
        if ($quote_amount) {
            $custom = (($include === 1) ? true : false);
            $quote_custom_values = $this->quoteCustomValues(
                (string) $this->session->get('quote_id'), $qcR);
            // session is passed to the pdfHelper and will be used for the
            // locale ie. $session->get('_language') or the print_language
            // ie $session->get('print_language')
            $pdfhelper = new PdfHelper($sR, $this->session, $this->translator);
            // The quote will be streamed ie. shown, and not archived
            $stream = true;
            // If we are required to mark quotes as 'sent' when sent.
            if ($sR->getSetting('mark_quotes_sent_pdf') == 1) {
                $this->generateQuoteNumberIfApplicable(
                    $quote_id, $qR, $sR, $gR);
                $sR->quoteMarkSent($quote_id, $qR);
            }
            $quote = $qR->repoQuoteUnloadedquery($quote_id);
            if ($quote) {
                $pdfhelper->generateQuotePdf($quote_id,
                    $quote->getUserId(), $stream, $custom, $quote_amount,
                        $quote_custom_values, $cR, $cvR, $cfR, $dlR, $qiR,
                            $qiaR, $acqiR, $qR, $qtrR, $uiR,
                                $this->webViewRenderer);
                $parameters = ($include == '1' ?
                    ['success' => 1] : ['success' => 0]);
                return $this->factory->createResponse(
                    Json::encode($parameters));
            } // $inv
            return $this->factory->createResponse(
                Json::encode(['success' => 0]));
        } // quote_amount
        return $this->webService->getNotFoundResponse();
    }

    public function pdfDashboardIncludeCf(
        #[RouteArgument('id')] int $quote_id, CR $cR, CVR $cvR, CFR $cfR,
            DLR $dlR, GR $gR, QAR $qaR, ACQIR $acqiR, QCR $qcR, QIR $qiR,
            QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR): void
    {
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount(
                (string) $quote_id) > 0) ? $qaR->repoQuotequery(
                    (string) $quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quoteCustomValues(
                    (string) $quote_id, $qcR);
                // session is passed to the pdfHelper and will be used for the
                // locale ie. $session->get('_language') or the print_language
                // ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session,
                    $this->translator);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->getSetting('mark_quotes_sent_pdf') == 1) {
                    $this->generateQuoteNumberIfApplicable(
                        (string) $quote_id, $qR, $sR, $gR);
                    $sR->quoteMarkSent((string) $quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery((string) $quote_id);
                if ($quote) {
                    $pdfhelper->generateQuotePdf(
                        (string) $quote_id, $quote->getUserId(), $stream,
                            true, $quote_amount, $quote_custom_values, $cR,
                                $cvR, $cfR, $dlR, $qiR, $qiaR, $acqiR, $qR,
                                    $qtrR, $uiR, $this->webViewRenderer);
                }
            }
        } //quote_id
    }

    public function pdfDashboardExcludeCf(
        #[RouteArgument('id')] int $quote_id, CR $cR, CVR $cvR, CFR $cfR,
            DLR $dlR, GR $gR, QAR $qaR, ACQIR $acqiR, QCR $qcR, QIR $qiR,
            QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR): void
    {
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount(
                (string) $quote_id) > 0) ? $qaR->repoQuotequery(
                    (string) $quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quoteCustomValues(
                    (string) $quote_id, $qcR);
                // session is passed to the pdfHelper and will be used for the
                // locale ie. $session->get('_language') or the
                // print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session,
                    $this->translator);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->getSetting('mark_quotes_sent_pdf') == 1) {
                    $this->generateQuoteNumberIfApplicable(
                        (string) $quote_id, $qR, $sR, $gR);
                    $sR->quoteMarkSent((string) $quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery((string) $quote_id);
                if ($quote) {
                    $pdfhelper->generateQuotePdf((string) $quote_id,
                        $quote->getUserId(), $stream, false, $quote_amount,
                            $quote_custom_values, $cR, $cvR, $cfR, $dlR, $qiR,
                                $qiaR, $acqiR, $qR, $qtrR, $uiR,
                                    $this->webViewRenderer);
                }
            }
        } // quote_id
    }
}