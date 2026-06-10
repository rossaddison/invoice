<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Client\ClientRepository;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\CustomField\CustomFieldRepository;

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
use App\Infrastructure\Persistence\{
    Inv\Inv,
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
            return $mpdfhelper->pdfCreate($html, $filename, $stream, $this->s,
                new PdfCreateContext($quote->getPassword(), null, null, false, false, [], $quote));
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
                return $mpdfhelper->pdfCreate($html, $filename, $stream, $this->s,
                    new PdfCreateContext($so->getPassword(), null, null, false, false, [], $so));
            }
        }
        return '';
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
