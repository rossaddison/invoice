<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Infrastructure\Persistence\{
    CustomField\CustomField, Inv\Inv
};
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\ClientCustom\ClientCustomRepository as ccR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as qcR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvCustom\InvCustomRepository as icR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as pcR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as socR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Invoice\Helpers\DateHelper as DHelp;
use App\Invoice\Helpers\NumberHelper as NHelp;

final readonly class TemplateHelper
{
    private DHelp $d;
    private NHelp $n;

    public function __construct(private SRepo $s, private ccR $ccR, private qcR $qcR, private icR $icR, private pcR $pcR, private socR $socR, private cfR $cfR, private cvR $cvR)
    {
        $this->d = new DHelp($this->s);
        $this->n = new NHelp($this->s);
    }

    public function parseTemplate(int $pk, bool $isInvoice, string $body, ParseTemplateDeps $deps): string
    {
        $cvR = $deps->cvR;
        $iR  = $deps->iR;
        $iaR = $deps->iaR;
        $qR  = $deps->qR;
        $qaR = $deps->qaR;
        $soR = $deps->soR;
        $uiR = $deps->uiR;
        $template_vars = [];
        if (preg_match_all('/{{{([^{|}]*)}}}/', $body, $template_vars) > 0) {
            foreach ($template_vars[1] as $var) {
                $prefix  = explode('_', $var, 2)[0];
                $replace = match ($prefix) {
                    'client'     => $this->resolveClientVar($var, $pk, $iR),
                    'user'       => $this->resolveUserVar($var, $pk, $iR, $uiR),
                    'quote'      => $this->resolveQuoteVar($var, $pk, $qR, $qaR),
                    'salesorder' => $this->resolveSalesOrderVar($var, $pk, $soR),
                    'invoice'    => $this->resolveInvoiceVar($var, $pk, $iR, $iaR),
                    default      => $this->resolveCustomFieldVar($var, $pk, $isInvoice, $iR, $qR, $soR, $cvR),
                };
                $body = str_replace('{{{' . $var . '}}}', $replace, $body);
            }
        }
        return $body;
    }

    private function resolveClientVar(string $var, int $pk, IR $iR): string
    {
        $inv    = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
        $client = $inv?->getClient();
        if ($client === null) {
            return '';
        }
        return match ($var) {
            'client_name'      => $client->getClientName(),
            'client_surname'   => $client->getClientSurname()  ?? '',
            'client_address_1' => $client->getClientAddress1() ?? '',
            'client_address_2' => $client->getClientAddress2() ?? '',
            'client_city'      => $client->getClientCity()     ?? '',
            'client_zip'       => $client->getClientZip()      ?? '',
            'client_state'     => $client->getClientState()    ?? '',
            'client_country'   => $client->getClientCountry()  ?? '',
            'client_phone'     => $client->getClientPhone()    ?? '',
            'client_fax'       => $client->getClientFax()      ?? '',
            'client_mobile'    => $client->getClientMobile()   ?? '',
            'client_email'     => $client->getClientEmail(),
            'client_web'       => $client->getClientWeb()      ?? '',
            'client_vat_id'    => $client->getClientVatId(),
            'client_tax_code'  => $client->getClientTaxCode()  ?? '',
            default            => '',
        };
    }

    private function resolveUserVar(string $var, int $pk, IR $iR, uiR $uiR): string
    {
        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
        if ($invoice === null) {
            return '';
        }
        $userId  = $invoice->reqUserId();
        $userinv = $uiR->repoUserInvUserIdCount($userId) > 0
            ? $uiR->repoUserInvUserIdquery($userId)
            : null;
        if ($userinv === null) {
            return '';
        }
        return (string) match ($var) {
            'user_company'          => $userinv->getCompany()          ?? '',
            'user_address_1'        => $userinv->getAddress1()         ?? '',
            'user_address_2'        => $userinv->getAddress2()         ?? '',
            'user_city'             => $userinv->getCity()             ?? '',
            'user_state'            => $userinv->getState()            ?? '',
            'user_zip'              => $userinv->getZip()              ?? '',
            'user_country'          => $userinv->getCountry()          ?? '',
            'user_phone'            => $userinv->getPhone()            ?? '',
            'user_fax'              => $userinv->getFax()              ?? '',
            'user_mobile'           => $userinv->getMobile()           ?? '',
            'user_web'              => $userinv->getWeb()              ?? '',
            'user_vat_id'           => $userinv->getVatId(),
            'user_tax_code'         => $userinv->getTaxCode()          ?? '',
            'user_subscribernumber' => $userinv->getSubscribernumber() ?? '',
            'user_iban'             => $userinv->getIban()             ?? '',
            'user_gln'              => $userinv->getGln()              ?? '',
            'user_rcc'              => $userinv->getRcc()              ?? '',
            default                 => '',
        };
    }

    private function resolveQuoteVar(string $var, int $pk, QR $qR, QAR $qaR): string
    {
        $quote  = $qR->repoCount($pk) > 0            ? $qR->repoQuoteUnloadedquery($pk) : null;
        $amount = $qaR->repoQuoteAmountCount($pk) > 0 ? $qaR->repoQuotequery($pk)        : null;
        return match ($var) {
            'quote_item_subtotal' => $amount !== null ? $this->n->formatCurrency($amount->getItemSubtotal())         : '',
            'quote_tax_total'     => $amount !== null ? $this->n->formatCurrency($amount->getTaxTotal())             : '',
            'quote_total'         => $amount !== null ? $this->n->formatCurrency($amount->getTotal())                : '',
            'quote_item_discount' => $quote  !== null ? $this->n->formatCurrency($quote->getDiscountAmount())        : '',
            'quote_date_created'  => $quote  !== null ? $quote->getDateCreated()->format($this->d->style())          : '',
            'quote_date_expires'  => $quote  !== null ? $quote->getDateExpires()->format($this->d->style())          : '',
            'quote_guest_url'     => $quote  !== null ? 'quote/url_key/' . $quote->getUrlKey()                      : '',
            'quote_number'        => $quote  !== null ? ($quote->getNumber() ?? '')                                  : '',
            default               => '',
        };
    }

    private function resolveSalesOrderVar(string $var, int $pk, SOR $soR): string
    {
        if ($var === 'salesorder_notes') {
            $so = $soR->repoCount($pk) > 0 ? $soR->repoSalesOrderUnloadedquery($pk) : null;
            return $so?->getNotes() ?? '';
        }
        return '';
    }

    private function resolveInvoiceVar(string $var, int $pk, IR $iR, IAR $iaR): string
    {
        $invoice = $iR->repoCount($pk) > 0            ? $iR->repoInvUnloadedquery($pk) : null;
        $amount  = $iaR->repoInvAmountCount($pk) > 0  ? $iaR->repoInvquery($pk)        : null;
        return match ($var) {
            'invoice_guest_url'      => $invoice !== null ? 'inv/url_key/' . $invoice->getUrlKey()                : '',
            'invoice_date_due'       => $invoice !== null ? $this->d->dateFromMysql($invoice->getDateDue())       : '',
            'invoice_date_created'   => $invoice !== null ? $invoice->getDateCreated()->format($this->d->style()) : '',
            'invoice_number'         => $invoice !== null ? ($invoice->getNumber() ?? '')                         : '',
            'invoice_item_subtotal'  => $amount  !== null ? $this->n->formatCurrency($amount->getItemSubtotal())  : '',
            'invoice_item_tax_total' => $amount  !== null ? $this->n->formatCurrency($amount->getItemTaxTotal())  : '',
            'invoice_total'          => $amount  !== null ? $this->n->formatCurrency($amount->getTotal())         : '',
            'invoice_paid'           => $amount  !== null ? $this->n->formatCurrency($amount->getPaid())          : '',
            'invoice_balance'        => $amount  !== null ? $this->n->formatCurrency($amount->getBalance())       : '',
            default                  => '',
        };
    }

    private function resolveCustomFieldVar(
        string $var,
        int $pk,
        bool $isInvoice,
        IR $iR,
        QR $qR,
        SOR $soR,
        cvR $cvR,
    ): string {
        if (!preg_match('/cf_(\d.*)/', $var, $cf_id)) {
            return '';
        }
        /** @var CustomField $cf */
        $cf    = $this->cfR->repoCustomFieldquery((int) $cf_id[1]);
        $table = $cf->getTable();
        $replace_custom = null;
        switch ($table) {
            case 'quote_custom':
                $quote = $qR->repoCount($pk) > 0 ? $qR->repoQuoteLoadedquery($pk) : null;
                if ($quote) {
                    $replace_custom = $this->qcR->repoFormValuequery($quote->reqId(), (int) $cf_id[1]);
                }
                break;
            case 'salesorder_custom':
                $so = $soR->repoCount($pk) > 0 ? $soR->repoSalesOrderLoadedquery($pk) : null;
                if ($so) {
                    $replace_custom = $this->socR->repoFormValuequery($so->reqId(), $cf_id[1]);
                }
                break;
            case 'inv_custom':
                $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                if ($invoice) {
                    $replace_custom = $this->icR->repoFormValuequery($invoice->reqId(), (int) $cf_id[1]);
                }
                break;
            case 'client_custom':
                $entity = null;
                if ($isInvoice && $iR->repoCount($pk) > 0) {
                    $entity = $iR->repoInvLoadedquery($pk);
                } elseif (!$isInvoice && $qR->repoCount($pk) > 0) {
                    $entity = $qR->repoQuoteLoadedquery($pk);
                }
                if ($entity) {
                    /** @var \App\Infrastructure\Persistence\ClientCustom\ClientCustom $replace_custom */
                    $replace_custom = $this->ccR->repoFormValuequery($entity->reqClientId(), (int) $cf_id[1]);
                }
                break;
            default:
                break;
        }
        $custom_value_id = null !== $replace_custom ? (int) $replace_custom->getValue() : 0;
        $custom_value    = $cvR->repoCount($custom_value_id) > 0
            ? $cvR->repoCustomValuequery($custom_value_id)
            : null;
        if ($custom_value === null) {
            return '';
        }
        return $custom_value->getValue();
    }

    /**
     * @param Inv $invoice
     * @return string
     */
    public function selectPdfInvoiceTemplate(Inv $invoice): string
    {
        if ($invoice->isOverdue()) {
            // Use the overdue template
            return $this->s->getSetting('pdf_invoice_template_overdue');
        }
        if ($invoice->reqStatusId() === 4) {
            // Use the paid template
            return $this->s->getSetting('pdf_invoice_template_paid');
        }
        // Use the default template
        return $this->s->getSetting('pdf_invoice_template');
    }

    /**
     * @param Inv $invoice
     * @return string
     */
    public function selectEmailInvoiceTemplate(Inv $invoice): string
    {
        // If Setting..View...Invoice...Invoice Templates have been set, use these to determine
        // what pdf template will naturally be selected when the email template is selected using
        // mailer_invoice form
        // Refer to:   $('#mailerinvform-email_template').change(function ()
        // Controller: inv/email_stage_0
        // View: views/invoice/inv/mailer_invoice
        if ($invoice->isOverdue()) {
            // Use the overdue template
            return $this->s->getSetting('email_invoice_template_overdue');
        }
        if ($invoice->reqStatusId() === 4) {
            // Use the paid template
            return $this->s->getSetting('email_invoice_template_paid');
        }
        // Use the default template
        return $this->s->getSetting('email_invoice_template');
    }

    /**
     * @return string
     */
    public function selectPdfQuoteTemplate(): string
    {
        // Use the default template
        return $this->s->getSetting('pdf_quote_template');
    }

    /**
     * @return string
     */
    public function selectEmailQuoteTemplate(): string
    {
        return $this->s->getSetting('email_quote_template');
    }
}
