<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\InvItem;
//use App\Invoice\Entity\UserInv;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
//use App\Invoice\Libraries\Sumex;
//use App\Invoice\InvAmount\InvAmountRepository;
//use App\Invoice\Inv\InvRepository;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Sumex\SumexRepository;
//use App\Invoice\UserInv\UserInvRepository;
//use setasign\Fpdi\Fpdi;
//use Yiisoft\Aliases\Aliases;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface as Translator;

class PdfHelper
{
    private readonly CountryHelper $countryhelper;

    public function __construct(private readonly SR $s, private readonly Session $session)
    {
        $this->countryhelper = new CountryHelper();
    }

    /**
     * @return string|null
     */
    private function locale_to_language(): string|null
    {
        $dropdown_locale = (string) $this->session->get('_language');
        /** @var array $session_list */
        $session_list = $this->s->locale_language_array();
        /** @var string $session_list[$dropdown_locale] */
        return $session_list[$dropdown_locale] ?? null;
    }

    /**
     * @param array|object $quote_or_inv
     * @return mixed
     */
    private function get_print_language(array|object $quote_or_inv): mixed
    {
        $locale_lang = $this->locale_to_language();
        // Get the client language if set : otherwise use the locale as basis
        if ($quote_or_inv instanceof \App\Invoice\Entity\Quote ||
            $quote_or_inv instanceof Inv) {
            return $quote_or_inv->getClient()?->getClient_language() ?? $locale_lang;
        }
        return '';
    }

    /**
     * @param string|null $quote_id
     * @param string $user_id
     * @param bool $stream
     * @param bool $custom
     * @param QuoteAmount|null $quote_amount
     * @param array $quote_custom_values
     * @param \App\Invoice\Client\ClientRepository $cR
     * @param \App\Invoice\CustomValue\CustomValueRepository $cvR
     * @param \App\Invoice\CustomField\CustomFieldRepository $cfR
     * @param \App\Invoice\QuoteItem\QuoteItemRepository $qiR
     * @param \App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
     * @param \App\Invoice\Quote\QuoteRepository $qR
     * @param \App\Invoice\QuoteTaxRate\QuoteTaxRateRepository $qtrR
     * @param \App\Invoice\UserInv\UserInvRepository $uiR
     * @param \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer
     * @return string
     */
    public function generate_quote_pdf(
        string|null $quote_id,
        string $user_id,
        bool $stream,
        bool $custom,
        object|null $quote_amount,
        array $quote_custom_values,
        \App\Invoice\Client\ClientRepository $cR,
        \App\Invoice\CustomValue\CustomValueRepository $cvR,
        \App\Invoice\CustomField\CustomFieldRepository $cfR,
        \App\Invoice\QuoteItem\QuoteItemRepository $qiR,
        \App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR,
        \App\Invoice\Quote\QuoteRepository $qR,
        \App\Invoice\QuoteTaxRate\QuoteTaxRateRepository $qtrR,
        \App\Invoice\UserInv\UserInvRepository $uiR,
        \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer,
    ) {
        if (null !== $quote_id) {
            $quote = $qR->repoCount($quote_id) > 0 ? $qR->repoQuoteLoadedquery($quote_id) : null;

            if (null !== $quote) {
                // If userinv details have been filled, use these details
                $userinv = ($uiR->repoUserInvcount($user_id) > 0 ? $uiR->repoUserInvquery($user_id) : null);
                // If a template has been selected in the dropdown use it otherwise use the default 'quote' template under
                // views/invoice/template/quote/pdf/quote.pdf
                $quote_template = (!empty($this->s->getSetting('pdf_quote_template')) ? $this->s->getSetting('pdf_quote_template') : 'quote');

                // Determine if discounts should be displayed if there are items on the quote
                $items = ($qiR->repoCount($quote_id) > 0 ? $qiR->repoQuoteItemIdquery($quote_id) : null);

                // e-invoicing requirement
                /** @var string $client_number */
                $client_number = $quote->getClient()?->getClient_number();
                $show_item_discounts = false;
                // Determine if any of the items have a discount, if so then the discount amount row will have to be shown.
                if (null !== $items) {
                    /** @var QuoteItem $item */
                    foreach ($items as $item) {
                        if ($item->getDiscount_amount() !== 0.00) {
                            $show_item_discounts = true;
                        }
                    }
                }
                // Get all data related to building the quote including custom fields
                $data = [
                    'quote' => $quote,
                    'quote_tax_rates' => (($qtrR->repoCount((string) $this->session->get('quote_id')) > 0) ? $qtrR->repoQuotequery((string) $this->session->get('quote_id')) : null),
                    'items' => $items,
                    'qiaR' => $qiaR,
                    'output_type' => 'pdf',
                    'show_item_discounts' => $show_item_discounts,
                    // Show the custom fields if the user has answered yes on the modal ie $custom = true
                    'show_custom_fields' => $custom,
                    // Custom fields appearing near the top of the quote
                    'custom_fields' => $cfR->repoTablequery('quote_custom'),
                    'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                    'cvH' => new CVH($this->s),
                    'cvR' => $cvR,
                    'quote_custom_values' => $quote_custom_values,
                    'top_custom_fields' => $viewrenderer->renderPartialAsString('//invoice/template/quote/pdf/top_custom_fields', [
                        'custom_fields' => $cfR->repoTablequery('quote_custom'),
                        'cvR' => $cvR,
                        'quote_custom_values' => $quote_custom_values,
                        'cvH' => new CVH($this->s),
                    ]),
                    // Custom fields appearing at the bottom of the quote
                    'view_custom_fields' => $viewrenderer->renderPartialAsString('//invoice/template/quote/pdf/view_custom_fields', [
                        'custom_fields' => $cfR->repoTablequery('quote_custom'),
                        'cvR' => $cvR,
                        'quote_custom_values' => $quote_custom_values,
                        'cvH' => new CVH($this->s),
                    ]),
                    'company_logo_and_address' => $viewrenderer->renderPartialAsString(
                        '//invoice/setting/company_logo_and_address.php',
                        ['company' => $company = $this->s->get_config_company_details(),
                            'document_number' => $quote->getNumber(),
                            'client_number' => $client_number,
                            'isInvoice' => false,
                            'isQuote' => true,
                            'isSalesOrder' => false,
                        ],
                    ),
                    'userInv' => $userinv,
                    'client' => $cR->repoClientquery((string) $quote->getClient()?->getClient_id()),
                    'quote_amount' => $quote_amount,
                    // Use the temporary print language to define cldr
                    'cldr' => array_search($this->get_print_language($quote), $this->s->locale_language_array()),
                ];
                // Quote Template will be either 'quote' or a custom designed quote in the folder.
                $html = $viewrenderer->renderPartialAsString('//invoice/template/quote/pdf/' . $quote_template, $data);
                if ($this->s->getSetting('pdf_html_quote') === '1') {
                    return $html;
                }
                // Set the print language to null for future use
                $this->session->set('print_language', '');
                $mpdfhelper = new MpdfHelper();
                $filename = $this->s->getSetting('i.quote') . '_' . str_replace(['\\', '/'], '_', $quote->getNumber() ?? (string) random_int(0, 10));
                return $mpdfhelper->pdf_create($html, $filename, $stream, $quote->getPassword(), $this->s, null, null, false, false, [], $quote);
            }
        }
        return '';
    }   //generate_quote_pdf

    /**
     * @param string|null $so_id
     * @param string $user_id
     * @param bool $stream
     * @param bool $custom
     * @param object|null $so_amount
     * @param array $so_custom_values
     * @param \App\Invoice\Client\ClientRepository $cR
     * @param \App\Invoice\CustomValue\CustomValueRepository $cvR
     * @param \App\Invoice\CustomField\CustomFieldRepository $cfR
     * @param \App\Invoice\SalesOrderItem\SalesOrderItemRepository $soiR
     * @param \App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
     * @param \App\Invoice\SalesOrder\SalesOrderRepository $soR
     * @param \App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository $sotrR
     * @param \App\Invoice\UserInv\UserInvRepository $uiR
     * @param \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer
     * @param Translator $translator
     * @return string
     */
    public function generate_salesorder_pdf(
        string|null $so_id,
        string $user_id,
        bool $stream,
        bool $custom,
        object|null $so_amount,
        array $so_custom_values,
        \App\Invoice\Client\ClientRepository $cR,
        \App\Invoice\CustomValue\CustomValueRepository $cvR,
        \App\Invoice\CustomField\CustomFieldRepository $cfR,
        \App\Invoice\SalesOrderItem\SalesOrderItemRepository $soiR,
        \App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR,
        \App\Invoice\SalesOrder\SalesOrderRepository $soR,
        \App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository $sotrR,
        \App\Invoice\UserInv\UserInvRepository $uiR,
        \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer,
        Translator $translator,
    ): string {
        if (null !== $so_id) {
            $so = $soR->repoCount($so_id) > 0 ? $soR->repoSalesOrderLoadedquery($so_id) : null;

            if (null !== $so) {
                // If userinv details have been filled, use these details
                $userinv = ($uiR->repoUserInvcount($user_id) > 0 ? $uiR->repoUserInvquery($user_id) : null);
                // If a template has been selected in the dropdown use it otherwise use the default 'salesorder' template under
                // views/invoice/template/salesorder/pdf/salesorder.pdf
                $salesorder_template = (!empty($this->s->getSetting('pdf_salesorder_template')) ? $this->s->getSetting('pdf_salesorder_template') : 'salesorder');

                // Determine if discounts should be displayed if there are items on the salesorder
                $items = ($soiR->repoCount($so_id) > 0 ? $soiR->repoSalesOrderItemIdquery($so_id) : null);
                // e-invoicing requirement
                /** @var string $client_number */
                $client_number = $so->getClient()?->getClient_number();
                $show_item_discounts = false;
                // Determine if any of the items have a discount, if so then the discount amount row will have to be shown.
                if (null !== $items) {
                    /** @var SalesOrderItem $item */
                    foreach ($items as $item) {
                        if ($item->getDiscount_amount() !== 0.00) {
                            $show_item_discounts = true;
                        }
                    }
                }
                // Get all data related to building the quote including custom fields
                $data = [
                    'salesorder' => $so,
                    'salesorder_tax_rates' => (($sotrR->repoCount((string) $this->session->get('so_id')) > 0) ? $sotrR->repoSalesOrderquery((string) $this->session->get('so_id')) : null),
                    'items' => $items,
                    'soiaR' => $soiaR,
                    'output_type' => 'pdf',
                    'show_item_discounts' => $show_item_discounts,
                    // Show the custom fields if the user has answered yes on the modal ie $custom = true
                    'show_custom_fields' => $custom,
                    // Custom fields appearing near the top of the quote
                    'custom_fields' => $cfR->repoTablequery('salesorder_custom'),
                    'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('salesorder_custom')),
                    'salesorder_custom_values' => $so_custom_values,
                    'top_custom_fields' => $viewrenderer->renderPartialAsString('//invoice/template/salesorder/pdf/top_custom_fields', [
                        'custom_fields' => $cfR->repoTablequery('salesorder_custom'),
                        'cvR' => $cvR,
                        'salesorder_custom_values' => $so_custom_values,
                        'cvH' => new CVH($this->s),
                    ]),
                    // Custom fields appearing at the bottom of the salesorder
                    'view_custom_fields' => $viewrenderer->renderPartialAsString('//invoice/template/salesorder/pdf/view_custom_fields', [
                        'custom_fields' => $cfR->repoTablequery('salesorder_custom'),
                        'cvR' => $cvR,
                        'salesorder_custom_values' => $so_custom_values,
                        'cvH' => new CVH($this->s),
                    ]),
                    'company_logo_and_address' => $viewrenderer->renderPartialAsString(
                        '//invoice/setting/company_logo_and_address.php',
                        ['company' => $company = $this->s->get_config_company_details(),
                            'document_number' => $so->getNumber(),
                            'client_number' => $client_number,
                            'isInvoice' => false,
                            'isQuote' => false,
                            'isSalesOrder' => true,
                        ],
                    ),
                    'userInv' => $userinv,
                    'client' => $cR->repoClientquery((string) $so->getClient()?->getClient_id()),
                    'so_amount' => $so_amount,
                    // Use the temporary print language to define cldr
                    'cldr' => array_search($this->get_print_language($so), $this->s->locale_language_array()),
                ];
                // Sales Order Template will be either 'salesorder' or a custom designed salesorder in the folder.
                $html = $viewrenderer->renderPartialAsString('//invoice/template/salesorder/pdf/' . $salesorder_template, $data);
                if ($this->s->getSetting('pdf_html_salesorder') === '1') {
                    return $html;
                }
                // Set the print language to null for future use
                $this->session->set('print_language', '');
                $mpdfhelper = new MpdfHelper();
                $filename = $translator->translate('salesorder') . '_' . str_replace(['\\', '/'], '_', $so->getNumber() ?? (string) random_int(0, 10));
                return $mpdfhelper->pdf_create($html, $filename, $stream, $so->getPassword(), $this->s, null, null, false, false, [], $so);
            }
        }
        return '';
    }   //generate_quote_pdf

    /**
     * @param string|null $inv_id
     * @param string $user_id
     * @param bool $custom
     * @param SalesOrder|null $so
     * @param InvAmount|null $inv_amount
     * @param array $inv_custom_values
     * @param \App\Invoice\Client\ClientRepository $cR
     * @param \App\Invoice\CustomValue\CustomValueRepository $cvR
     * @param \App\Invoice\CustomField\CustomFieldRepository $cfR
     * @param \App\Invoice\InvItem\InvItemRepository $iiR
     * @param \App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR
     * @param Inv $inv
     * @param \App\Invoice\InvTaxRate\InvTaxRateRepository $itrR
     * @param \App\Invoice\UserInv\UserInvRepository $uiR
     * @param SumexRepository $sumexR
     * @param \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer
     * @return string
     */
    public function generate_inv_html(
        string|null $inv_id,
        string $user_id,
        bool $custom,
        SalesOrder|null $so,
        InvAmount|null $inv_amount,
        array $inv_custom_values,
        \App\Invoice\Client\ClientRepository $cR,
        \App\Invoice\CustomValue\CustomValueRepository $cvR,
        \App\Invoice\CustomField\CustomFieldRepository $cfR,
        \App\Invoice\InvItem\InvItemRepository $iiR,
        \App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR,
        Inv $inv,
        \App\Invoice\InvTaxRate\InvTaxRateRepository $itrR,
        \App\Invoice\UserInv\UserInvRepository $uiR,
        SumexRepository $sumexR,
        \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer,
    ): string {
        if (null !== $inv_id) {
            // If userinv details have been filled, use these details
            $userinv = ($uiR->repoUserInvcount($user_id) > 0 ? $uiR->repoUserInvquery($user_id) : null);
            // 'draft' => status_id => 1
            $inv_template = $this->generate_inv_pdf_template_normal_paid_overdue_watermark($inv->getStatus_id() ?? 1);
            // Determine if discounts should be displayed if there are items on the invoice
            $items = ($iiR->repoCount($inv_id) > 0 ? $iiR->repoInvItemIdquery($inv_id) : null);
            /** @var \App\Invoice\Entity\Sumex $sumex */
            $sumex = $sumexR->repoSumexInvoicequery($inv_id);
            // e-invoicing requirement
            //$client_number = $inv->getClient()?->getClient_number();
            $client_purchase_order_number = ($so ? $so->getClient_po_number() : '');
            $date_helper = new DateHelper($this->s);
            $_language = $this->session->get('_language');
            $show_item_discounts = false;
            // Determine if any of the items have a discount, if so then the discount amount row will have to be shown.
            if (null !== $items) {
                /** @var InvItem $item */
                foreach ($items as $item) {
                    if ($item->getDiscount_amount() !== 0.00) {
                        $show_item_discounts = true;
                    }
                }
            }
            // Get all data related to building the inv including custom fields
            $data = [
                'inv' => $inv,
                'inv_tax_rates' => (($itrR->repoCount((string) $this->session->get('inv_id')) > 0) ? $itrR->repoInvquery((string) $this->session->get('inv_id')) : null),
                'items' => $items,
                'iiaR' => $iiaR,
                'output_type' => 'pdf',
                'show_item_discounts' => $show_item_discounts,
                // Show the custom fields if the user has answered yes on the modal ie $custom = true
                'show_custom_fields' => $custom,
                // Custom fields appearing near the top of the quote
                'custom_fields' => $cfR->repoTablequery('inv_custom'),
                'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                'cvH' => new CVH($this->s),
                'inv_custom_values' => $inv_custom_values,
                'top_custom_fields' => $viewrenderer->renderPartialAsString('//invoice/template/invoice/pdf/top_custom_fields', [
                    'custom_fields' => $cfR->repoTablequery('inv_custom'),
                    'cvR' => $cvR,
                    'inv_custom_values' => $inv_custom_values,
                    'cvH' => new CVH($this->s),
                ]),
                // Custom fields appearing at the bottom of the invoice
                'view_custom_fields' => $viewrenderer->renderPartialAsString('//invoice/template/invoice/pdf/view_custom_fields', [
                    'custom_fields' => $cfR->repoTablequery('inv_custom'),
                    'cvR' => $cvR,
                    'inv_custom_values' => $inv_custom_values,
                    'cvH' => new CVH($this->s),
                ]),
                'sumex' => $sumex,
                'userinv' => $userinv,
                'company_logo_and_address' => $viewrenderer->renderPartialAsString(
                    '//invoice/setting/company_logo_and_address.php',
                    [
                        // if there is no active company with private details, use the config params company details
                        'company' => !$this->s->get_private_company_details() == []
                                    ? $this->s->get_private_company_details()
                                    : $this->s->get_config_company_details(),
                        'document_number' => $inv->getNumber(),
                        //'client_number'=> $client_number,
                        'client_purchase_order_number' => $client_purchase_order_number,
                        'date_tax_point' => $date_helper->date_from_mysql($inv->getDate_tax_point()),
                        '_language' => $_language,
                        'inv_id' => $inv_id,
                        'isInvoice' => true,
                        'isQuote' => false,
                        'isSalesOrder' => false,
                    ],
                ),
                'client' => $cR->repoClientquery((string) $inv->getClient()?->getClient_id()),
                'inv_amount' => $inv_amount,
                'cldr' => array_search($this->get_print_language($inv), $this->s->locale_language_array()),
            ];
            // Inv Template will be either 'inv' or a custom designed inv in the folder.
            return $viewrenderer->renderPartialAsString('//invoice/template/invoice/pdf/' . $inv_template, $data);
        }
        return '';
    }

    /**
     * @param string|null $inv_id
     * @param string $user_id
     * @param bool $stream
     * @param bool $custom
     * @param SalesOrder|null $so
     * @param InvAmount|null $inv_amount
     * @param array $inv_custom_values
     * @param \App\Invoice\Client\ClientRepository $cR
     * @param \App\Invoice\CustomValue\CustomValueRepository $cvR
     * @param \App\Invoice\CustomField\CustomFieldRepository $cfR
     * @param \App\Invoice\InvItem\InvItemRepository $iiR
     * @param \App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR
     * @param \App\Invoice\Inv\InvRepository $iR
     * @param \App\Invoice\InvTaxRate\InvTaxRateRepository $itrR
     * @param \App\Invoice\UserInv\UserInvRepository $uiR
     * @param \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer
     * @return string
     */
    public function generate_inv_pdf(
        string|null $inv_id,
        string $user_id,
        bool $stream,
        bool $custom,
        SalesOrder|null $so,
        InvAmount|null $inv_amount,
        array $inv_custom_values,
        \App\Invoice\Client\ClientRepository $cR,
        \App\Invoice\CustomValue\CustomValueRepository $cvR,
        \App\Invoice\CustomField\CustomFieldRepository $cfR,
        \App\Invoice\InvItem\InvItemRepository $iiR,
        \App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR,
        \App\Invoice\Inv\InvRepository $iR,
        \App\Invoice\InvTaxRate\InvTaxRateRepository $itrR,
        \App\Invoice\UserInv\UserInvRepository $uiR,
        SumexRepository $sumexR,
        \Yiisoft\Yii\View\Renderer\ViewRenderer $viewrenderer,
    ): string {
        if (null !== $inv_id) {
            $inv = $iR->repoCount($inv_id) > 0 ? $iR->repoInvLoadedquery($inv_id) : null;
            if ($inv) {
                $html = $this->generate_inv_html($inv_id, $user_id, $custom, $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $inv, $itrR, $uiR, $sumexR, $viewrenderer);
                // Set the print language to null for future use
                $this->session->set('print_language', '');
                $mpdfhelper = new MpdfHelper();
                $associatedFiles = [];
                $include_zugferd = $this->s->getSetting('include_zugferd') === '0' ? false : true;
                if ($include_zugferd && null !== $inv_amount) {
                    $z = new ZugFerdHelper($this->s, $iiaR, $inv_amount);
                    $associatedFiles = [
                        [
                            'name' => 'ZUGFeRD-invoice.xml',
                            'description' => 'ZUGFeRD Invoice',
                            'AFRelationship' => 'Alternative',
                            'mime' => 'text/xml',
                            'path' => $z->generate_invoice_zugferd_xml_temp_file($inv, $iiaR, $inv_amount),
                        ],
                    ];
                } else {
                    $associatedFiles = [];
                }
                $filename = $this->s->trans('invoice') . '_' . str_replace(['\\', '/'], '_', $inv->getNumber() ?? (string) random_int(0, 10));
                //$isInvoice is assigned to true as it is an invoice
                // If stream is true return the pdf as a string using mpdf otherwise save to local file and
                // return the filename inclusive target_path to be used to attach to email attachments
                return $mpdfhelper->pdf_create($html, $filename, $stream, $inv->getPassword(), $this->s, $iiaR, $inv_amount, true, $include_zugferd, $associatedFiles, $inv);
            } // if $inv
        }
        return '';
    } //generate_inv_pdf

    /**
     * Determines what watermark words eg. paid, and overdue that will be shown diagonally across an invoice
     * @param int $status_id
     * @return string
     */
    public function generate_inv_pdf_template_normal_paid_overdue_watermark(int $status_id): string
    {
        return match (true) {
            $status_id == 4 && !empty($this->s->getSetting('pdf_invoice_template_paid')) => $this->s->getSetting('pdf_invoice_template_paid'),
            $status_id == 4 && empty($this->s->getSetting('pdf_invoice_template_paid')) => 'paid',
            $status_id == 5 && !empty($this->s->getSetting('pdf_invoice_template_overdue')) => $this->s->getSetting('pdf_invoice_template_overdue'),
            $status_id == 5 && empty($this->s->getSetting('pdf_invoice_template_overdue')) => 'overdue',
            default => strlen($this->s->getSetting('pdf_invoice_template')) > 0 ? $this->s->getSetting('pdf_invoice_template') : 'invoice',
        };
    }

    ///**
    // *
    // * @param InvRepository $iR
    // * @param InvAmountRepository $iaR
    // * @param SumexRepository $SumexR
    // * @param $uiR
    // * @param int $inv_id
    // * @param bool $stream
    // * @param bool $client
    // * @throws \Exception
    // * @psalm-suppress MissingReturnType
    // */
    //
    //public function generate_inv_sumex(
    //        InvRepository $iR,
    //        InvAmountRepository $iaR,
    //        SumexRepository $SumexR,
    //        UserInvRepository $uiR,
    //        int $inv_id,
    //        bool $stream = true,
    //        bool $client = false)
    //{
    //    if ($inv_id) {
    //        $inv = $iR->repoCount((string)$inv_id) > 0 ? $iR->repoInvLoadedquery((string)$inv_id) : null;
    //        if ($inv instanceof Inv) {
    //            /** @var InvAmount $inv_amount */
    //            $inv_amount = $iaR->repoInvAmountquery($inv_id);
    //            /** @var \App\Invoice\Entity\Sumex $sumex_treatment */
    //            $sumex_treatment = $SumexR->repoSumexInvoicequery((string)$inv_id);
    //            $user_details = $uiR->repoUserInvUserIdquery($inv->getUser_id());
    //            if ($user_details instanceof UserInv) {
    //                $sumex = new Sumex($inv, $inv_amount, $user_details, $sumex_treatment, $this->s, $this->session);
    //                $temp = tempnam("/tmp", "invsumex_");
    //                $tempCopy = tempnam("/tmp", "invsumex_");
    //
    //                /** @var \setasign\Fpdi\Fpdi $pdf */
    //                $pdf = new Fpdi();
    //
    //                $sumexPDF = $sumex->pdf($inv_id);
    //                if (is_string($sumexPDF)) {
    //                    $sha1sum = sha1($sumexPDF);
    //                    $shortsum = substr($sha1sum, 0, 8);
    //
    //                                        $filename = $this->s->trans('invoice') . '_' . ($inv->getNumber() ?: 'Number Not Provided'). '_' . $shortsum;
    //                    if (!$client) {
    //                        $f = fopen($temp, 'wb');
    //                        if (!$f) {
    //                            throw new \Exception(sprintf('Unable to create output file %s', $temp));
    //                        }
    //                        fwrite($f, $sumexPDF, strlen($sumexPDF));
    //                        fclose($f);
    //
    //                        // Hackish
    //                        $sumexPDF = str_replace(
    //                            "Giustificativo per la richiesta di rimborso",
    //                            "Copia: Giustificativo per la richiesta di rimborso",
    //                            $sumexPDF
    //                        );
    //
    //                        $fc = fopen($tempCopy, 'wb');
    //                        if (!$fc) {
    //                            throw new \Exception(sprintf('Unable to create output file %s', $tempCopy));
    //                        }
    //                        fwrite($fc, $sumexPDF, strlen($sumexPDF));
    //                        fclose($fc);
    //
    //                        $pageCount = $pdf->setSourceFile($temp);
    //
    //                          for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    //                            $templateId = $pdf->importPage($pageNo);
    //                            $size = $pdf->getTemplateSize($templateId);
    //                            /**
    //                             * Related logic: see setasign\fpdf\fpdf.php                             *
    //                             * @var int $size['w']
    //                             * @var int $size['h']
    //                             */
    //                            if ($size['w'] > $size['h']) {
    //                                $pageFormat = 'L';  //  landscape
    //                            } else {
    //                                $pageFormat = 'P';  //  portrait
    //                            }
    //                            $pdf->addPage($pageFormat, array($size['w'], $size['h']), 0);
    //                            $pdf->useTemplate($templateId);
    //                        }
    //
    //                        $pageCount = $pdf->setSourceFile($tempCopy);
    //
    //                        for ($pageNo = 2; $pageNo <= $pageCount; $pageNo++) {
    //
    //                            $templateId = $pdf->importPage($pageNo);
    //                            $size = $pdf->getTemplateSize($templateId);
    //                            /**
    //                             * Related logic: see setasign\fpdf\fpdf.php                             *
    //                             * @var int $size['w']
    //                             * @var int $size['h']
    //                             */
    //                            if ($size['w'] > $size['h']) {
    //                                $pageFormat = 'L';  //  landscape
    //                            } else {
    //                                $pageFormat = 'P';  //  portrait
    //                            }
    //                            $pdf->addPage($pageFormat, array($size['w'], $size['h']));
    //                            $pdf->useTemplate($templateId);
    //                        }
    //
    //                        unlink($temp);
    //                        unlink($tempCopy);
    //
    //                        if ($stream) {
    //                            header("Content-Type:application/pdf");
    //                            // string
    //                            return $pdf->Output($filename . '.pdf', 'I');
    //                        }
    //                        $aliases = new Aliases(['@uploads_temp_folder' => dirname(__DIR__).DIRECTORY_SEPARATOR.'Uploads'.DIRECTORY_SEPARATOR]);
    //                        $filePath = $aliases->get('@uploads_temp_folder') . $filename . '.pdf';
    //                        $pdf->Output($filePath, 'F');
    //                        // string
    //                        return $filePath;
    //                    } else {
    //                        if ($stream) {
    //                            // string
    //                            return $sumexPDF;
    //                        }
    //                        $aliases = new Aliases(['@uploads_temp_folder' => dirname(__DIR__).DIRECTORY_SEPARATOR.'Uploads'.DIRECTORY_SEPARATOR]);
    //                        $filePath = $aliases->get('@uploads_temp_folder') . $filename . '.pdf';
    //                        // string
    //                        return $filePath;
    //                    }
    //               } //is_string
    //            } // instanceof UserInv
    //        }
    //    }
    //}
}
