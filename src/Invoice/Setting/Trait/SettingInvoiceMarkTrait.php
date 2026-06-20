<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Quote\QuoteRepository as QR;

trait SettingInvoiceMarkTrait
{

    /**
     * @param int $invoice_id
     * @param IR $iR
     */
    public function invoiceMarkViewed(int $invoice_id, IR $iR): void
    {
        $invoice = $iR->repoInvUnloadedquery($invoice_id);
        if ($invoice) {
            //mark as viewed if status is 2
            if (($iR->repoCount($invoice_id) > 0)
                    && $invoice->reqStatusId() === 2) {
                //set the invoice to viewed status ie 3
                $invoice->setStatusId(3);
                $iR->save($invoice);
            }

//set the invoice to 'read only' only once it has been viewed according
//to 'Other settings' 2 sent, 3 viewed, 4 paid,
            if ($this->getSetting('read_only_toggle') == 3) {
                $invoice = $iR->repoInvUnloadedquery($invoice_id);
                if ($invoice) {
                    $invoice->setIsReadOnly(true);
                    $iR->save($invoice);
                }
            }
        }
    }

    /**
     * @param int $quote_id
     * @param QR $qR
     */
    public function quoteMarkViewed(int $quote_id, QR $qR): void
    {
        $quote = $qR->repoQuoteStatusquery($quote_id, 2);
        if ($quote && $qR->repoCount($quote_id) > 0) {
            //mark as viewed if status is 2
            //set the quote to viewed status ie 3
            $quote->setStatusId(3);
            $qR->save($quote);
        }
    }

    /**
     * @param int $invoice_id
     */
    public function invoiceMarkSent(int $invoice_id, IR $iR): void
    {
        $invoice = $iR->repoInvUnloadedquery($invoice_id);
        if ($invoice) {
            //draft->sent->view->paid
            //set the invoice to sent ie. 2
            if ($invoice->reqStatusId() === 1) {
                $invoice->setStatusId(2);
            }
            //set the invoice to read only ie. not updateable,
            // if invoice_status_id is 2
            if (null !== $this->withKey('read_only_toggle')
                && $this->withKey(
                        'read_only_toggle')?->getSettingValue() === '2') {
                $invoice->setIsReadOnly(true);
            }
            $iR->save($invoice);
        }
    }

    /**
     * @param int $quote_id
     * @param QR $qR
     */
    public function quoteMarkSent(int $quote_id, QR $qR): void
    {
        // Quote exists and has a status of 1 ie. draft
        if ($qR->repoQuoteStatuscount($quote_id, 1) > 0) {
            $quote = $qR->repoQuoteStatusquery($quote_id, 1);
            if ($quote) {
                $quote->setStatusId(2);
                $qR->save($quote);
            }
        }
    }

    /**
     * @param mixed $amount
     * @return string
     */
    public function formatCurrency(mixed $amount): string
    {
        $this->loadSettings();
        $currency_symbol = $this->getSetting('currency_symbol');
        $currency_symbol_placement = $this->getSetting('currency_symbol_placement');
        $thousands_separator = $this->getSetting('thousands_separator');
        $decimal_point = $this->getSetting('decimal_point');

        if ($currency_symbol_placement == 'before') {
            return $currency_symbol . number_format(
                    (float) $amount, ($decimal_point) ? 2 : 0,
                    $decimal_point, $thousands_separator);
        }
        if ($currency_symbol_placement == 'afterspace') {
            return number_format((float) $amount, ($decimal_point) ? 2 : 0,
                    $decimal_point, $thousands_separator) . '&nbsp;'
                    . $currency_symbol;
        }
        return number_format((float) $amount, ($decimal_point) ? 2 : 0,
                $decimal_point, $thousands_separator) . $currency_symbol;
    }

    /**
     * @param float|null $amount
     * @return string|null
     */
    public function formatAmount(?float $amount = null): ?string
    {
        $this->loadSettings();
        if (null !== $amount) {
            $thousands_separator = $this->getSetting('thousands_separator');
            $decimal_point = $this->getSetting('decimal_point');
            //force the rounding of amounts to 2 decimal points if the decimal
            // point setting is filled.
            return number_format($amount, ($decimal_point) ? 2 : 0,
                    $decimal_point, $thousands_separator);
        }
        return null;
    }

    /**
     * @param float $amount
     * @return string
     */
    public function standardizeAmount(float $amount): string
    {
        $this->loadSettings();
        $thousands_separator = $this->getSetting('thousands_separator');
        $decimal_point = $this->getSetting('decimal_point');
        $amt = str_replace($thousands_separator, '', (string) $amount);
        return str_replace($decimal_point, '.', $amt);
    }
}
