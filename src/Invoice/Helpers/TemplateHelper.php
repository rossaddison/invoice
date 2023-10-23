<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Entity\ClientCustom;
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\CustomValue;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\UserInv;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\Client\ClientRepository as CR;
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

Class TemplateHelper {

    private SRepo $s;
    private DHelp $d;
    private NHelp $n;
    private ccR $ccR;
    private qcR $qcR;
    private icR $icR;
    private pcR $pcR;
    private socR $socR;
    private cfR $cfR;
    private cvR $cvR;

    public function __construct(SRepo $s, ccR $ccR, qcR $qcR, icR $icR, pcR $pcR, socR $socR, cfR $cfR, cvR $cvR) {
        $this->s = $s;
        $this->d = new DHelp($s);
        $this->n = new NHelp($s);
        $this->ccR = $ccR;
        $this->qcR = $qcR;
        $this->icR = $icR;
        $this->cfR = $cfR;
        $this->cvR = $cvR;
        $this->pcR = $pcR;
        $this->socR = $socR;
    }
    
    /**
     * 
     * @param string $pk
     * @param bool $isInvoice
     * @param string $body
     * @param CR $cR
     * &param CVR $cvR
     * @param IR $iR
     * @param IAR $iaR
     * @param QR $qR
     * @param QAR $qaR
     * @param SOR $soR
     * @param uiR $uiR
     * @return string
     */
    
    public function parse_template(string $pk, bool $isInvoice, string $body, CR $cR, CVR $cvR, IR $iR, IAR $iaR, QR $qR,  QAR $qaR, SOR $soR, uiR $uiR)
    {
    $template_vars = [];
    $var = '';            
    if ((preg_match_all('/{{{([^{|}]*)}}}/', $body, $template_vars))) {
        foreach ($template_vars[1] as $var) {
                $userinv = new UserInv();
                $replace = '';
                switch ($var) {
                    // client
                    case 'client_name':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) { $replace = $client->getClient()?->getClient_name();}
                        break;    
                    case 'client_surname':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_surname();}
                        break;    
                    case 'client_address_1':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_address_1();}
                        break;    
                    case 'client_address_2':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_address_2();}
                        break;    
                    case 'client_city':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_city();}
                        break;    
                    case 'client_zip':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_zip();}
                        break;    
                    case 'client_state':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_state();}
                        break;    
                    case 'client_country':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_country();}
                        break;    
                    case 'client_phone':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_phone();}
                        break;    
                    case 'client_fax':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_fax();}
                        break;    
                    case 'client_mobile':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_mobile();}
                        break;    
                    case 'client_email':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_email();}
                        break;    
                    case 'client_web':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_web();}
                        break;    
                    case 'client_vat_id':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_vat_id();}
                        break;    
                    case 'client_tax_code':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_tax_code();}
                        break;    
                    case 'client_avs':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_avs();}
                        break;    
                    case 'client_insurednumber':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_insurednumber();}
                        break;    
                    case 'client_veka':
                        $client = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                        if ($client) {$replace = $client->getClient()?->getClient_veka();}
                        break;
                    // user
                    case 'user_company':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getCompany();}
                        break;    
                    case 'user_address_1':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getAddress_1();}
                        break;    
                    case 'user_address_2':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getAddress_2();}
                        break;    
                    case 'user_city':                        
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getCity();}
                        break;    
                    case 'user_state':                        
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getState();}
                        break;    
                    case 'user_zip':                        
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getZip();}
                        break;    
                    case 'user_country':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getCountry();}
                        break;    
                    case 'user_phone':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getPhone();}
                        break;    
                    case 'user_fax':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getFax();}
                        break;    
                    case 'user_mobile':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getMobile();}
                        break;    
                    case 'user_web':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getWeb();}                        
                        break;    
                    case 'user_vat_id':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getVat_id();}
                        break;    
                    case 'user_tax_code':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getTax_code();}
                    break;    
                    case 'user_subscribernumber':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getSubscribernumber();}
                    break;    
                    case 'user_iban':                        
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getIban();}
                    break;    
                    case 'user_gln':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getGln();}
                        break;    
                    case 'user_rcc':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$userinv = $uiR->repoUserInvUserIdCount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null;}
                        if ($userinv) {$replace = $userinv->getRcc();}
                        break;
                    // quote
                    case 'quote_item_subtotal':
                        $quote_amount = $qaR->repoQuoteAmountCount($pk) > 0 ? $qaR->repoQuotequery($pk) : null;                    
                        if ($quote_amount) {$replace = $this->n->format_currency($quote_amount->getItem_subtotal());}
                        break;
                    case 'quote_tax_total':
                        $quote_amount = $qaR->repoQuoteAmountCount($pk) > 0 ? $qaR->repoQuotequery($pk) : null;                    
                        if ($quote_amount) {$replace = $this->n->format_currency($quote_amount->getTax_total());}
                        break;
                    case 'quote_item_discount':
                        $quote = $qR->repoCount($pk) > 0 ? $qR->repoQuoteUnloadedquery($pk) : null;
                        if ($quote) {$replace = $this->n->format_currency($quote->getDiscount_amount());}
                        break;
                    case 'quote_total':
                        $quote_amount = $qaR->repoQuoteAmountCount($pk) > 0 ? $qaR->repoQuotequery($pk) : null;                    
                        if ($quote_amount) {$replace = $this->n->format_currency($quote_amount->getTotal());}
                        break;
                    case 'quote_date_created':
                        $quote = $qR->repoCount($pk) > 0 ? $qR->repoQuoteUnloadedquery($pk) : null;
                        if ($quote) {$replace = $quote->getDate_created()->format($this->d->style());}
                        break;
                    case 'quote_date_expires':
                        $quote = $qR->repoCount($pk) > 0 ? $qR->repoQuoteUnloadedquery($pk) : null;
                        if ($quote) {$replace = $quote->getDate_expires()->format($this->d->style());}
                        break;
                    case 'quote_guest_url':
                        $quote = $qR->repoCount($pk) > 0 ? $qR->repoQuoteUnloadedquery($pk) : null;
                        if ($quote) {$replace = '/invoice/quote/url_key/'. $quote->getUrl_key();}
                        break;
                    case 'quote_number':
                        $quote = $qR->repoCount($pk) > 0 ? $qR->repoQuoteUnloadedquery($pk) : null;
                        if ($quote) {$replace = $quote->getNumber() ?? '';}
                        break;
                    // salesorder    
                    case 'salesorder_notes':
                        $so = $soR->repoCount($pk) > 0 ? $soR->repoSalesOrderUnloadedquery($pk) : null;
                        if ($so) {$replace = $so->getNotes() ?? '';}
                        break;
                    
                    // invoice    
                    case 'invoice_guest_url':                        
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$replace = '/invoice/inv/url_key/'. $invoice->getUrl_key();}
                        break;
                    case 'invoice_date_due':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$replace = $this->d->date_from_mysql($invoice->getDate_due());}
                        break;
                    case 'invoice_date_created':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$replace = $invoice->getDate_created()->format($this->d->style());}
                        break;
                    case 'invoice_item_subtotal':
                        $invoice_amount = $iaR->repoInvAmountCount((int)$pk) > 0 ? $iaR->repoInvquery((int)$pk) : null;                    
                        if ($invoice_amount) {$replace = $this->n->format_currency($invoice_amount->getItem_subtotal());}
                        break;
                    case 'invoice_item_tax_total':
                        $invoice_amount = $iaR->repoInvAmountCount((int)$pk) > 0 ? $iaR->repoInvquery((int)$pk) : null;                    
                        if ($invoice_amount) {$replace = $this->n->format_currency($invoice_amount->getItem_tax_total());}
                        break;
                    case 'invoice_total':
                        $invoice_amount = $iaR->repoInvAmountCount((int)$pk) > 0 ? $iaR->repoInvquery((int)$pk) : null;                    
                        if ($invoice_amount) {$replace = $this->n->format_currency($invoice_amount->getTotal());}
                        break;
                    case 'invoice_number':
                        $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvUnloadedquery($pk) : null;
                        if ($invoice) {$replace = $invoice->getNumber();}
                        break;
                    case 'invoice_paid':
                        $invoice_amount = $iaR->repoInvAmountCount((int)$pk) > 0 ? $iaR->repoInvquery((int)$pk) : null;                    
                        if ($invoice_amount) {$replace = $this->n->format_currency($invoice_amount->getPaid());}
                        break;
                    case 'invoice_balance':
                        $invoice_amount = $iaR->repoInvAmountCount((int)$pk) > 0 ? $iaR->repoInvquery((int)$pk) : null;                    
                        if ($invoice_amount) {$replace = $this->n->format_currency($invoice_amount->getBalance());}
                        break;
                    default:
                    // Derive the custom_field_id from $var eg. 'cf_1' implies custom_field_id is 1.
                    //  $cf_id = [];
                    //$cf = '';
                    $replace_custom = null;
                    if (preg_match('/cf_([0-9].*)/', $var, $cf_id)) {
                        // Get the custom field
                        /** @var CustomField $cf */
                        $cf = $this->cfR->repoCustomFieldquery($cf_id[1]);
                        // Get the table from the custom field table
                        $table =  $cf->getTable();
                        //$custom_fields = $this->cfR->repoTablequery($table) ?: null;

                        // If the table is eg. 'quote_custom' search the table with the custom_field_id 
                        // and retrieve the value for this particularly designed field
                        switch ($table)  {
                            case 'quote_custom': 
                                // $pk = quote id;
                                $quote = $qR->repoCount($pk) > 0 ? $qR->repoQuoteLoadedquery($pk) : null;
                                if ($quote) {$replace_custom = $this->qcR->repoFormValuequery((string)$quote->getId(), $cf_id[1]);}
                                break;
                            case 'salesorder_custom': 
                                // $pk = so id;
                                $so = $soR->repoCount($pk) > 0 ? $soR->repoSalesOrderLoadedquery($pk) : null;
                                if ($so) {$replace_custom = $this->socR->repoFormValuequery((string)$so->getId(), $cf_id[1]);}
                                break;    
                            case 'inv_custom':
                                // $pk = inv id; 
                                $invoice = $iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) : null;
                                if ($invoice) {$replace_custom = $this->icR->repoFormValuequery((string)$invoice->getId(), $cf_id[1]);}
                                break;                                 
                            case 'client_custom':
                                // Client custom fields can be included on either an invoice or a quote
                                $entity = $isInvoice ? ($iR->repoCount($pk) > 0 ? $iR->repoInvLoadedquery($pk) :  null) 
                                                     : ($qR->repoCount($pk) > 0 ? $qR->repoQuoteLoadedquery($pk) :  null);
                                /** @var ClientCustom $replace_custom */
                                if ($entity) {$replace_custom = $this->ccR->repoFormValuequery($entity->getClient_id(), $cf_id[1]);}
                                break; 
                        }                            
                        // All the different entities are represented by $replace_custom
                        
                        $custom_value_id = (null!==($replace_custom) ? $replace_custom->getValue() : '');
                        // Now search the custom value table that holds the real value
                        if (null!==$custom_value_id) {
                            $custom_value = $cvR->repoCount($custom_value_id) > 0 ? $cvR->repoCustomValuequery($custom_value_id) : null;
                        } else {
                            $custom_value = null;
                        }
                        /** 
                         * @var array<array-key, float|int|string>|string $replace 
                         * @var CustomValue|null $custom_value 
                         */
                        $replace = null!==$custom_value ? $custom_value->getValue() : '';
                    } // if preg_match
            } //  switch ($var) 
            /** @var array<array-key, float|int|string>|string $replace */
            $body = str_replace('{{{' . $var . '}}}', $replace, $body);
        } // foreach ($template_vars[1] as $var) 
    } // if ((preg_match_all('/{{{([^{|}]*)}}}/', $body, $template_vars))) 
    return $body;
    }
    
    /**
     * 
     * @param Inv $invoice
     * @return string
     */
    function select_pdf_invoice_template(Inv $invoice) : string
    {
        if ($invoice->isOverdue()) {
            // Use the overdue template
            return $this->s->get_setting('pdf_invoice_template_overdue');
        } elseif ($invoice->getStatus_id() === 4) {
            // Use the paid template
            return $this->s->get_setting('pdf_invoice_template_paid');
        } else {
            // Use the default template
            return $this->s->get_setting('pdf_invoice_template');
        }
    }

    /**
     * 
     * @param Inv $invoice
     * @return string
     */
    function select_email_invoice_template(Inv $invoice) : string
    {
        // If Setting..View...Invoice...Invoice Templates have been set, use these to determine
        // what pdf template will naturally be selected when the email template is selected using
        // mailer_invoice form
        // Refer to:   $('#mailerinvform-email_template').change(function ()
        // Controller: inv/email_stage_0
        // View: views/invoice/inv/mailer_invoice
        if ($invoice->isOverdue()) {
            // Use the overdue template
            return $this->s->get_setting('email_invoice_template_overdue');
        } elseif ($invoice->getStatus_id() === 4) {
            // Use the paid template
            return $this->s->get_setting('email_invoice_template_paid');
        } else {
            // Use the default template
            return $this->s->get_setting('email_invoice_template');
        }
    }
    
    /**
     * 
     * @return string
     */
    function select_pdf_quote_template() : string
    {
        // Use the default template
        return $this->s->get_setting('pdf_quote_template');       
    }

    /**
     * 
     * @return string
     */
    function select_email_quote_template() : string
    {
        return $this->s->get_setting('email_quote_template');
    }
}