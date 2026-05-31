<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Client\ClientRepository;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvTaxRate\InvTaxRateRepository;
use App\Invoice\QuoteItem\QuoteItemRepository;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Infrastructure\Persistence\{Inv\Inv, InvAmount\InvAmount,
    InvItem\InvItem, InvAllowanceCharge\InvAllowanceCharge,
    QuoteAmount\QuoteAmount, QuoteItem\QuoteItem,
    SalesOrder\SalesOrder, SalesOrderItem\SalesOrderItem
};
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

class PdfHelper
{
    private readonly CountryHelper $countryhelper;

    public function __construct(
        private readonly SR $s,
        private readonly Session $session,
        private readonly Translator $translator)
    {
        $this->countryhelper = new CountryHelper();
    }

    /**
     * @return string|null
     */
    private function localeToLanguage(): ?string
    {
        $dropdown_locale = (string) $this->session->get('_language');
        $session_list = $this->s->localeLanguageArray();
        /** @var string $session_list[$dropdown_locale] */
        return $session_list[$dropdown_locale] ?? null;
    }

    /**
     * @param array|object $quote_or_inv
     * @return mixed
     */
    private function getPrintLanguage(array|object $quote_or_inv): mixed
    {
        $locale_lang = $this->localeToLanguage();
        // Get the client language if set : otherwise use the locale as basis
        if ($quote_or_inv instanceof \App\Infrastructure\Persistence\Quote\Quote
            || $quote_or_inv instanceof Inv) {
            return $quote_or_inv->getClient()?->getClientLanguage() ?? $locale_lang;
        }
        return 'English';
    }

    public function generateQuotePdf(
        int $quote_id,
        int $user_id,
        bool $stream,
        bool $custom,
        ?object $quote_amount,
        array $quote_custom_values,
        ClientRepository $cR,
        CustomValueRepository $cvR,
        CustomFieldRepository $cfR,
        DeliveryLocationRepository $dlR,
        QuoteItemRepository $qiR,
        QuoteItemAmountRepository $qiaR,
        QuoteItemAllowanceChargeRepository $acqiR,
        QuoteRepository $qR,
        QuoteTaxRateRepository $qtrR,
        UserInvRepository $uiR,
        WebViewRenderer $webViewRenderer,
    ): ?string {
        
        $quote = $qR->repoCount($quote_id) > 0 ?
                $qR->repoQuoteLoadedquery($quote_id) : null;

        if (null !== $quote) {
            $userinv = ($uiR->repoUserInvcount($user_id) > 0 ?
                    $uiR->repoUserInvquery($user_id) : null);
            $quote_template = (!empty($this->s->getSetting('pdf_quote_template')) ?
                $this->s->getSetting('pdf_quote_template') : 'quote');
            $_language = $this->session->get('_language');
            $items = ($qiR->repoCount($quote_id) > 0 ?
                    $qiR->repoQuoteItemIdquery($quote_id) : null);
            $client_number = (string) $quote->getClient()?->getClientNumber();
            $show_item_discounts = false;
            if (null !== $items) {
                /** @var QuoteItem $item */
                foreach ($items as $item) {
                    if ($item->getDiscountAmount() !== 0.00) {
                        $show_item_discounts = true;
                    }
                }
            }
            $data = [
                'quote' => $quote,
                'quote_tax_rates' => (($qtrR->repoCount(
                    (int) $this->session->get('quote_id')) > 0) ?
                    $qtrR->repoQuotequery(
                        (int) $this->session->get('quote_id')) : null),
                'items' => $items,
                'qiaR' => $qiaR,
                'acqiR' => $acqiR,
                'output_type' => 'pdf',
                'show_item_discounts' => $show_item_discounts,
                'show_custom_fields' => $custom,
                'custom_fields' => $cfR->repoTablequery('quote_custom'),
                'custom_values' => $cvR->fixCfValueToCf(
                    $cfR->repoTablequery('quote_custom')),
                'cvH' => new CVH($this->s, $cvR),
                'cvR' => $cvR,
                'quote_custom_values' => $quote_custom_values,
                'top_custom_fields' => $webViewRenderer->renderPartialAsString(
                '//invoice/template/quote/pdf/top_custom_fields', [
                    'custom_fields' => $cfR->repoTablequery('quote_custom'),
                    'cvR' => $cvR,
                    'quote_custom_values' => $quote_custom_values,
                    'cvH' => new CVH($this->s, $cvR),
                ]),
                'view_custom_fields' => $webViewRenderer->renderPartialAsString(
                '//invoice/template/quote/pdf/view_custom_fields', [
                    'custom_fields' => $cfR->repoTablequery('quote_custom'),
                    'cvR' => $cvR,
                    'quote_custom_values' => $quote_custom_values,
                    'cvH' => new CVH($this->s, $cvR),
                ]),
                'company_logo_and_address' => $webViewRenderer->renderPartialAsString(
                '//invoice/setting/company_logo_and_address.php',
                    ['company' => $this->s->getConfigCompanyDetails(),
                        'document_number' => $quote->getNumber(),
                        'client_number' => $client_number,
                        'isInvoice' => false,
                        'isQuote' => true,
                        'isSalesOrder' => false,
                    ],
                ),
                'delivery_location' =>
                     $this->viewPartialDeliveryLocation(
                        (string) $_language,
                            $dlR, $quote->getDeliveryLocationId(),
                                $webViewRenderer),
                'userInv' => $userinv,
                'client' => $cR->repoClientqueryOrig($quote->getClient()?->reqId() ?? 0),
                'quote_amount' => $quote_amount,
                'cldr' => array_search($this->getPrintLanguage($quote),
                    $this->s->localeLanguageArray()),
            ];
            $html = $webViewRenderer->renderPartialAsString(
                '//invoice/template/quote/pdf/' . $quote_template, $data);
            if ($this->s->getSetting('pdf_html_quote') === '1') {
                return $html;
            }
            // Set the print language to null for future use
            $this->session->set('print_language', '');
            $mpdfhelper = new MpdfHelper($this->translator);
            $filename = $this->s->getSetting('quote')
                . '_'
                . str_replace(['\\', '/'], '_', $quote->getNumber() ??
                    (string) random_int(0, 10));
            return $mpdfhelper->pdfCreate($html, $filename, $stream,
                $quote->getPassword(), $this->s, null, null, false,
                false, [], $quote);
        }
        return null;
    }
    
    /**
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function generateSalesorderPdf(
        ?string $so_id,
        int $user_id,
        bool $stream,
        bool $custom,
        ?object $so_amount,
        array $so_custom_values,
        ClientRepository $cR,
        CustomValueRepository $cvR,
        CustomFieldRepository $cfR,
        SalesOrderItemRepository $soiR,
        SalesOrderItemAmountRepository $soiaR,
        SalesOrderItemAllowanceChargeRepository $acsoiR,
        SalesOrderRepository $soR,
        SalesOrderTaxRateRepository $sotrR,
        UserInvRepository $uiR,
        WebViewRenderer $webViewRenderer,
        Translator $translator,
    ): string {
        if (null !== $so_id) {
            $so = $soR->repoCount((int) $so_id) > 0 ?
                $soR->repoSalesOrderLoadedquery((int) $so_id) : null;

            if (null !== $so) {
                $userinv = ($uiR->repoUserInvcount($user_id) > 0 ?
                    $uiR->repoUserInvquery($user_id) : null);
                $salesorder_template =
                    (!empty($this->s->getSetting('pdf_salesorder_template')) ?
                        $this->s->getSetting('pdf_salesorder_template') :
                            'salesorder');
                $items = ($soiR->repoCount((int) $so_id) > 0 ?
                    $soiR->repoSalesOrderItemIdquery((int) $so_id) : null);
                $client_number = (string) $so->getClient()?->getClientNumber();
                $show_item_discounts = false;
                if (null !== $items) {
                    /** @var SalesOrderItem $item */
                    foreach ($items as $item) {
                        if ($item->getDiscountAmount() !== 0.00) {
                            $show_item_discounts = true;
                        }
                    }
                }
                $data = [
                    'salesorder' => $so,
                    'salesorder_tax_rates' =>
                        (($sotrR->repoCount(
                            (int) $this->session->get('so_id')) > 0) ?
                        $sotrR->repoSalesOrderquery(
                            (int) $this->session->get('so_id')) : null),
                    'items' => $items,
                    'soiaR' => $soiaR,
                    'acsoiR' => $acsoiR,
                    'output_type' => 'pdf',
                    'show_item_discounts' => $show_item_discounts,
                    'show_custom_fields' => $custom,
                    'custom_fields' => $cfR->repoTablequery('salesorder_custom'),
                    'custom_values' =>
                        $cvR->fixCfValueToCf($cfR->repoTablequery('salesorder_custom')),
                    'salesorder_custom_values' => $so_custom_values,
                    'top_custom_fields' => $webViewRenderer->renderPartialAsString(
                    '//invoice/template/salesorder/pdf/top_custom_fields', [
                        'custom_fields' => $cfR->repoTablequery('salesorder_custom'),
                        'cvR' => $cvR,
                        'salesorder_custom_values' => $so_custom_values,
                        'cvH' => new CVH($this->s, $cvR),
                    ]),
                    'view_custom_fields' => $webViewRenderer->renderPartialAsString(
                    '//invoice/template/salesorder/pdf/view_custom_fields', [
                        'custom_fields' => $cfR->repoTablequery('salesorder_custom'),
                        'cvR' => $cvR,
                        'salesorder_custom_values' => $so_custom_values,
                        'cvH' => new CVH($this->s, $cvR),
                    ]),
                    'company_logo_and_address' => $webViewRenderer->renderPartialAsString(
                    '//invoice/setting/company_logo_and_address.php',
                        ['company' => $this->s->getConfigCompanyDetails(),
                            'document_number' => $so->getNumber(),
                            'client_number' => $client_number,
                            'isInvoice' => false,
                            'isQuote' => false,
                            'isSalesOrder' => true,
                        ],
                    ),
                    'userInv' => $userinv,
                    'client' => $cR->repoClientqueryOrig($so->getClient()?->reqId() ?? 0),
                    'so_amount' => $so_amount,
                    'cldr' => array_search($this->getPrintLanguage($so),
                        $this->s->localeLanguageArray()),
                ];
                $html = $webViewRenderer->renderPartialAsString(
                '//invoice/template/salesorder/pdf/' . $salesorder_template, $data);
                if ($this->s->getSetting('pdf_html_salesorder') === '1') {
                    return $html;
                }
                // Set the print language to null for future use
                $this->session->set('print_language', '');
                $mpdfhelper = new MpdfHelper($this->translator);
                $filename = $translator->translate('salesorder')
                    . '_'
                    . str_replace(['\\', '/'], '_', $so->getNumber() ??
                        (string) random_int(0, 10));
                return $mpdfhelper->pdfCreate($html, $filename, $stream,
                    $so->getPassword(), $this->s, null, null, false, false,
                    [], $so);
            }
        }
        return '';
    }

    public function generateInvHtml(
        int $inv_id,
        int $user_id,
        bool $custom,
        ?SalesOrder $so,
        ?InvAmount $inv_amount,
        array $inv_custom_values,
        ClientRepository $cR,
        CustomValueRepository $cvR,
        CustomFieldRepository $cfR,
        DeliveryLocationRepository $dlR,
        InvAllowanceChargeRepository $aciR,
        InvItemRepository $iiR,
        InvItemAllowanceChargeRepository $aciiR,
        InvItemAmountRepository $iiaR,
        Inv $inv,
        InvTaxRateRepository $itrR,
        UserInvRepository $uiR,
        WebViewRenderer $webViewRenderer,
    ): string {
        $invId = (int) $this->session->get('inv_id');
        $userinv = ($uiR->repoUserInvcount($user_id) > 0 ?
            $uiR->repoUserInvquery($user_id) : null);
        $inv_template = $this->generateInvPdfTemplateNormalPaidOverdueWatermark(
            $inv->reqStatusId() ?: 1);
        $items = ($iiR->repoCount($inv_id) > 0 ?
            $iiR->repoInvItemIdquery($inv_id) : null);
        $client_purchase_order_number = ($so ? $so->getClientPoNumber() : '');
        $date_helper = new DateHelper($this->s);
        $_language = $this->session->get('_language');
        $show_item_discounts = false;
        $vat = $this->s->getSetting('enable_vat_registration');
        if (null !== $items) {
            /** @var InvItem $item */
            foreach ($items as $item) {
                if ($item->getDiscountAmount() !== 0.00) {
                    $show_item_discounts = true;
                }
            }
        }
        $data = [
            'aciiR' => $aciiR,
            'inv' => $inv,
            'inv_tax_rates' =>
            (($itrR->repoCount($invId) > 0) ?
              $itrR->repoInvquery($invId) : []),
            'items' => $items,
            'iiaR' => $iiaR,
            'output_type' => 'pdf',
            'show_item_discounts' => $show_item_discounts,
            'show_custom_fields' => $custom,
            'custom_fields' => $cfR->repoTablequery('inv_custom'),
            'custom_values' => $cvR->fixCfValueToCf(
                $cfR->repoTablequery('inv_custom')),
            'cvH' => new CVH($this->s, $cvR),
            'inv_custom_values' => $inv_custom_values,
            'top_custom_fields' => $webViewRenderer->renderPartialAsString(
                    '//invoice/template/invoice/pdf/top_custom_fields', [
                'custom_fields' => $cfR->repoTablequery('inv_custom'),
                'cvR' => $cvR,
                'inv_custom_values' => $inv_custom_values,
                'cvH' => new CVH($this->s, $cvR),
            ]),
            'view_custom_fields' => $webViewRenderer->renderPartialAsString(
                    '//invoice/template/invoice/pdf/view_custom_fields', [
                'custom_fields' => $cfR->repoTablequery('inv_custom'),
                'cvR' => $cvR,
                'inv_custom_values' => $inv_custom_values,
                'cvH' => new CVH($this->s, $cvR),
            ]),
            'userinv' => $userinv,
            'company_logo_and_address' => $webViewRenderer->renderPartialAsString(
                '//invoice/setting/company_logo_and_address.php',
                [
                    'company' => !$this->s->getPrivateCompanyDetails() == []
                                ? $this->s->getPrivateCompanyDetails()
                                : $this->s->getConfigCompanyDetails(),
                    'document_number' => $inv->getNumber(),
                    //'client_number'=> $client_number,
                    'client_purchase_order_number' => $client_purchase_order_number,
                    'date_tax_point' =>
                        $date_helper->dateFromMysql($inv->getDateTaxPoint()),
                    '_language' => $_language,
                    'inv_id' => $inv_id,
                    'isInvoice' => true,
                    'isQuote' => false,
                    'isSalesOrder' => false,
                ],
            ),
            'inv_allowance_charges' => $this->viewPartialInvAllowanceCharges(
                $inv_id, $vat, $aciR, $webViewRenderer),
            'delivery_location' => $this->viewPartialDeliveryLocation(
                (string) $_language,
                $dlR,
                (int) $inv->getDeliveryLocationId(), $webViewRenderer),
            'client' => $cR->repoClientqueryOrig($inv->getClient()?->reqId() ?? 0),
            'inv_amount' => $inv_amount,
            'cldr' => array_search($this->getPrintLanguage($inv),
                $this->s->localeLanguageArray()),
        ];
        return $webViewRenderer->renderPartialAsString(
            '//invoice/template/invoice/pdf/' . $inv_template, $data);
    }

    public function generateInvPdf(
        int $inv_id,
        int $user_id,
        bool $stream,
        bool $custom,
        ?SalesOrder $so,
        ?InvAmount $inv_amount,
        array $inv_custom_values,
        ClientRepository $cR,
        CustomValueRepository $cvR,
        CustomFieldRepository $cfR,
        DeliveryLocationRepository $dlR,
        InvAllowanceChargeRepository $aciR,
        InvItemRepository $iiR,
        InvItemAllowanceChargeRepository $aciiR,
        InvItemAmountRepository $iiaR,
        InvRepository $iR,
        InvTaxRateRepository $itrR,
        UserInvRepository $uiR,
        WebViewRenderer $webViewRenderer,
    ): string {
        $inv = $iR->repoCount($inv_id) > 0 ?
            $iR->repoInvLoadedquery($inv_id) : null;
        if ($inv) {
            $html = $this->generateInvHtml($inv_id, $user_id, $custom,
                $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR,
                $dlR, $aciR, $iiR, $aciiR, $iiaR, $inv, $itrR, $uiR,
                $webViewRenderer);
// Set the print language to null for future use
            $this->session->set('print_language', '');
            $mpdfhelper = new MpdfHelper($this->translator);
            $include_zugferd = $this->s->getSetting('include_zugferd')
                === '0' ? false : true;
            if ($include_zugferd && null !== $inv_amount) {
                $z = new ZugFerdHelper($this->s, $iiaR, $inv_amount,
                    $this->translator);
                $associatedFiles = [
                    [
                        'name' => 'ZUGFeRD-invoice.xml',
                        'description' => 'ZUGFeRD Invoice',
                        'AFRelationship' => 'Alternative',
                        'mime' => 'text/xml',
                        'path' => $z->generateInvoiceZugferdXmlTempFile($inv),
                    ],
                ];
            } else {
                $associatedFiles = [];
            }
            $filename = $this->translator->translate('invoice')
                . '_'
                . str_replace(['\\', '/'], '_', $inv->getNumber() ??
                    (string) random_int(0, 10));
//$isInvoice is assigned to true as it is an invoice
// If stream is true return the pdf as a string using mpdf otherwise save to
// local file and return the filename inclusive target_path to be used to
// attach to email attachments
            return $mpdfhelper->pdfCreate($html,
                $filename, $stream, $inv->getPassword(), $this->s,
                $iiaR, $inv_amount, true, $include_zugferd,
                $associatedFiles, $inv);
        } // if $inv
        return '';
    }

    /**
     * Determines what watermark words eg. paid, and overdue that will
     * be shown diagonally across an invoice
     * @param int $status_id
     * @return string
     */
    public function generateInvPdfTemplateNormalPaidOverdueWatermark(int $status_id):
        string
    {
        return match (true) {
            $status_id == 4 &&
                !empty($this->s->getSetting('pdf_invoice_template_paid')) =>
                    $this->s->getSetting('pdf_invoice_template_paid'),
            $status_id == 4 &&
                empty($this->s->getSetting('pdf_invoice_template_paid')) =>
                    'paid',
            $status_id == 5 &&
                !empty($this->s->getSetting('pdf_invoice_template_overdue')) =>
                    $this->s->getSetting('pdf_invoice_template_overdue'),
            $status_id == 5 &&
                empty($this->s->getSetting('pdf_invoice_template_overdue')) =>
                    'overdue',
            default => strlen($this->s->getSetting('pdf_invoice_template')) > 0 ?
                $this->s->getSetting('pdf_invoice_template') : 'invoice',
        };
    }

    private function viewPartialInvAllowanceCharges(
        int $inv_id,
        string $vat,
        InvAllowanceChargeRepository $aciR,
        WebViewRenderer $webViewRenderer,
    ): string {
        $identifier = 0;
        $print = '';
        if ($inv_id) {
            $inv_allowance_charges = $aciR->repoACIquery($inv_id);
            $aOrC = 'allowance.or.charge.';
            /**
             * @var InvAllowanceCharge $inv_allowance_charge
             */
            foreach ($inv_allowance_charges as $inv_allowance_charge) {
                $allowanceCharge = $inv_allowance_charge->getAllowanceCharge();
                $allowanceOrCharge = '';
                if ($allowanceCharge) {
                    $identifier = $allowanceCharge->getIdentifier();
                    $allowanceOrCharge = $identifier ?
                        $this->translator->translate($aOrC . 'allowance') :
                        $this->translator->translate($aOrC . 'charge');
                }

                $amount = $inv_allowance_charge->getAmount();
                $vatOrTax = $inv_allowance_charge->getVatOrTax();
                $amountTitle = $this->translator->translate($aOrC . 'amount');
                $key = match (true) {
                    $identifier && $vat  => $aOrC . 'allowance.vat',
                    $identifier          => $aOrC . 'tax',
                    !$identifier && $vat => $aOrC . 'charge.vat',
                    default              => $aOrC . 'charge.tax',
                };

                $vatOrHeadingTitle = $this->translator->translate($key);
                
                $print .=  "{$allowanceOrCharge}: "
                . "{$amountTitle} {$amount}, "
                . "$vatOrHeadingTitle}: {$vatOrTax}<br>";
            }
            return $webViewRenderer->renderPartialAsString(
                '//invoice/inv/partial_inv_allowance_charges', [
                'title' => $this->translator->translate($aOrC . 'inv'),
                'inv_allowance_charges' => $print,
            ]);
        } else {
            return '';
        }
    }

    private function viewPartialDeliveryLocation(
            string $_language,
            DLR $dlr,
            ?int $delivery_location_id,
            WebViewRenderer $webViewRenderer): string
    {
        if ($delivery_location_id > 0) {
            $del = $dlr->repoDeliveryLocationquery($delivery_location_id);
            if (null !== $del) {
                return $webViewRenderer->renderPartialAsString(
                '//invoice/inv/partial_inv_delivery_location', [
                    'actionName' => 'del/view',
                    'actionArguments' => ['_language' => $_language,
                        'id' => $delivery_location_id],
                    'title' => $this->translator->translate('delivery.location'),
                    'building_number' => $del->getBuildingNumber(),
                    'address_1' => $del->getAddress1(),
                    'address_2' => $del->getAddress2(),
                    'city' => $del->getCity(),
                    'state' => $del->getZip(),
                    'country' => $del->getCountry(),
                    'global_location_number' => $del->getGlobalLocationNumber(),
                ]);
            } //null!==$del
        } else {
            return '';
        }
        return '';
    }
}
