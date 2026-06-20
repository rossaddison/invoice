<?php

declare(strict_types=1);

namespace App\Invoice\Trait;

use App\Infrastructure\Persistence\{Client\Client, Group\Group};
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\PaymentMethod\PaymentMethod;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use Yiisoft\Security\Random;

trait InvoiceInstallTrait
{

    /**
     * @param SettingRepository $sR
     */
    private function installDefaultSettingsOnFirstRun(SettingRepository $sR): void
    {
        $default_settings = [
/**
 * Remove the 'default_settings_exist' setting from the settings table by
 * manually going into the mysql database table 'settings' and deleting it.
 * This will remove & reinstall the default settings listed below. The above
 * index function will check whether this setting exists. If not THIS function
 * will be run.
 * CAUTION: THIS WILL ALSO REMOVE ALL THE SETTINGS INCLUDING SECRET KEYS
 */

            'active_only' => 0,
            'app_cdn_not_node_module' => 1,
            'bootstrap5_cdn_not_node_module' => 1,
            'bootstrap5_offcanvas_enable' => 0,
            'bootstrap5_offcanvas_placement' => 'top',
            'bootstrap5_alert_message_font_size' => '10',
            'bootstrap5_alert_close_button_font_size' => '10',
            'bootstrap5_layout_invoice_navbar_font' => 'Arial',
            'bootstrap5_layout_invoice_navbar_font_size' => '10',
            'bootstrap5_layout_guest_navbar_font' => 'Arial',
            'bootstrap5_layout_guest_navbar_font_size' => '10',
            'bootstrap5_layout_main_navbar_font' => 'Arial',
            'bootstrap5_layout_main_navbar_font_size' => '10',
            'bootstrap5_sidebar_background' => '#1a1a2e',
            'bootstrap5_sidebar_guest_background' => '#1a1a2e',
            'bootstrap5_client_form_font_size' => '14',
            'bootstrap5_form_font_size' => '14',
            'bootstrap5_form_input_height' => '56',
            'cron_key' => Random::string(32),
            'currency_symbol' => 'Ã‚Â£',
            'currency_symbol_placement' => 'before',
            // default payment gateway currency code
            'currency_code' => 'GBP',
            'currency_code_from' => 'GBP',
            'currency_code_to' => 'GBP',
            'custom_title' => 'Yii-invoice',
            // Use the mySql Y-m-d date format as  default
            'date_format' => 'Y-m-d',
            'decimal_point' => '.',
            'default_invoice_group' => 1,
            'default_quote_group' => 2,
            'default_invoice_terms' => '',
            'default_sales_order_group' => 3,
            'default_settings_exist' => '1',
            'default_language' => 'English',
            //paginator list limit
            'default_list_limit' => 120,
            'disable_flash_messages_inv' => 0,
            'disable_flash_messages_quote' => 0,
            // Prevent documents from being made non-editable.
            // By default documents are made non-editable
            // according to the read_only_toggle (listed below) which is set
            // at sent ie 2. So when a document is sent it becomes non-editable
            // i.e. read_only. By default this setting is on 0 ie. Invoices can
            // be made read-only (through the read_only_toggle)
            'disable_read_only' => 0,
            'disable_sidebar' => 1,
            'email_send_method' => 'symfony',
            // Invoice deletion by Law is not allowed. Invoices have to be
            // cancelled with a credit invoice/note.
            'enable_invoice_deletion' => true,
            'enable_peppol_client_defaults' => 1,
            'enable_telegram' => 0,
            'enable_vat_registration' => 0,
            'enable_tfa' => 0,
            // Qr code is always shown
            'enable_tfa_with_disabling' => 0,
            // Archived pdfs are automatically sent to customers from
            // view/invoice...Options...Send
            // The pdf is sent along with the attachment to the invoice on the
            // view/invoice.
            'email_pdf_attachment' => 1,
            'generate_invoice_number_for_draft' => 1,
            'generate_quote_number_for_draft' => 1,
            'generate_so_number_for_draft' => 1,
            'install_test_data' => 0,
            'inv_cdn_not_node_module' => 1,
            //1=>None, 2=>Cash, 3=>Cheque, 4=>Card/Direct Debit-Succeeded
            //5=>Card/Direct Debit-Processing 6=>Card/Direct Debit-Customer Ready
            'invoice_default_payment_method' => 6,
            'invoices_due_after' => 30,
            'invoice_logo' => 'favicon.ico',
            //This setting should be zero during Production. See inv/mark_sent
            //warning
            'mark_invoices_sent_copy' => 0,
            'mpdf_ltr' => 1,
            'mpdf_cjk' => 1,
            'mpdf_auto_script_to_lang' => 1,
            'mpdf_auto_vietnamese' => 1,
            'mpdf_auto_arabic' => 1,
            'mpdf_allow_charset_conversion' => 1,
            'mpdf_auto_language_to_font' => 1,
            'mpdf_show_image_errors' => 1,
            'no_front_about_page' => 1,
            'no_front_accreditations_page' => 1,
            'no_front_contact_details_page' => 1,
            'no_front_contact_interest_page' => 1,
            'no_front_gallery_page' => 1,
            'no_front_pricing_page' => 1,
            'no_front_privacy_policy_page' => 1,
            'no_front_terms_of_service_page' => 1,
            'no_front_site_slider_page' => 1,
            'no_front_team_page' => 1,
            'no_front_testimonial_page' => 1,
            'no_developer_sandbox_hmrc_continue_button' => 1,
            'no_facebook_continue_button' => 1,
            'no_github_continue_button' => 1,
            'no_google_continue_button' => 1,
            'no_govuk_continue_button' => 1,
            'no_linkedin_continue_button' => 1,
            'no_microsoftonline_continue_button' => 1,
            'no_openidconnect_continure_button' => 1,
            'no_x_continue_button' => 1,
            'no_yandex_continue_button' => 1,
            'no_vkontakte_continue_button' => 1,
            // Number format Default located in SettingsRepository
            'number_format' => 'number_format_us_uk',
            'payment_list_limit' => 20,
            // Show the pdf in the Browser ie. stream ...Settings...View...
            // Invoices...Pdf Settings...G
            'pdf_stream_inv' => 1,
            // Accumulate pdf's in archive folder
            // /src/Invoice/Uploads/Archive/Invoice
            // Settings...View...Invoices...Pdf Settings...Folder
            'pdf_archive_inv' => 1,
            // Preview in webpage as html instead of tabbed pdf with
            // Settings...View...Invoices...Pdf Settings...</>
            'pdf_html_inv' => 0,
            // Setting => filename ... under views/invoice/template/invoice/pdf
            'pdf_stream_quote' => 1,
            'pdf_archive_quote' => 1,
            'pdf_html_quote' => 0,
            'pdf_invoice_template' => 'invoice',
            'pdf_invoice_template_paid' => 'paid',
            'pdf_invoice_template_overdue' => 'overdue',
            // Setting => filename ... under views/invoice/template/quote/pdf
            'pdf_quote_template' => 'quote',
            // Peppol UBL2.1 Invoice: Sender or Recipients Currency Code
            'peppol_doc_currency_toggle' => 0,
            'peppol_xml_stream' => 1,
            // emojis appear in the xml to highlight the document's currency
            // and currency conversions
            'peppol_debug_with_emojis' => 1,
            // The internal PeppolValidator is used to validate the xml
            'peppol_debug_with_internal_validator' => 1,
            // Templates used for processing online payments via
            // customers/clients login portal
            'public_invoice_template' => 'Invoice_Web',
            'public_quote_template' => 'Quote_Web',
            'qr_height_and_width' => 240,
            'quotes_expire_after' => 15,
            // Set the invoice to read-only on sent by default;
            'read_only_toggle' => 2,
            'reports_in_new_tab' => true,
            'signup_automatically_assign_client' => 0,
            'signup_default_age_minimum_eighteen' => 1,
            'stop_logging_in' => false,
            'stop_signing_up' => false,
            'tax_rate_decimal_places' => 2,
            'telegram_chat_id' => '',
            'telegram_payment_method_id' => 1,
            'telegram_payment_notifications' => 0,
            'telegram_provider_token' => '',
            'telegram_token' => '',
            'telegram_webhook_secret_token' => '',
            'telegram_test_message_use' => 1,
            'company_latitude' => '',
            'company_longitude' => '',
            'thousands_separator' => ',',
            'time_zone' => 'Europe/London',
        ];
        $this->installDefaultSettings($default_settings, $sR);
    }

    /**
     * @param SettingRepository $sR
     * @param FamilyRepository $fR
     * @param UnitRepository $uR
     * @param ProductRepository $pR
     * @param TaxRateRepository $trR
     * @param ClientRepository $cR
     */
    private function installCheckForPreexistingTestData(
        SettingRepository $sR,
        FamilyRepository $fR,
        UnitRepository $uR,
        ProductRepository $pR,
        TaxRateRepository $trR,
        ClientRepository $cR,
    ): void {
        // The setting install_test_data exists
        if ($sR->repoCount('install_test_data') == 1
                // The test data does not exist yet
                && $fR->repoTestDataCount() == 0
                && $uR->repoTestDataCount() == 0
                && $pR->repoTestDataCount() == 0
                // The setting install_test_data has been set to Yes in
                // Settings...View
                && $sR->getSetting('install_test_data') == '1') {
            // The user wants the test data to be installed
            $this->installTestData($trR, $uR, $fR, $pR, $cR);
        } else {
            // Test Data Already exists => Settings...View install_test_data
            // must be set back to No
            // Only show this message if we are in Non-production debug mode
            $sR->getSetting('debug_mode') == '1' ? $this->flashMessage(
                'warning', $this->translator->translate(
                                    'install.test.data.exists.already')) : '';
            $setting = $sR->withKey('install_test_data');
            if (null !== $setting) {
                $setting->setSettingValue('0');
                $sR->save($setting);
            }
        }
    }

    /**
     * @param array $default_settings
     * @param SettingRepository $sR
     */
    private function installDefaultSettings(array $default_settings,
        SettingRepository $sR): void
    {
        $this->removeAllSettings($sR);
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($default_settings as $key => $value) {
            $setting = new Setting();
            $setting->setSettingKey($key);
            /** @psalm-suppress RedundantCastGivenDocblockType */
            $setting->setSettingValue((string) $value);
            $sR->save($setting);
        }
    }

    /**
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     */
    private function installTestData(TaxRateRepository $trR,
        UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR,
            ClientRepository $cR): void
    {
        $this->install($trR, $uR, $fR, $pR, $cR);
    }

    /**
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     */
    private function install(TaxRateRepository $trR, UnitRepository $uR,
        FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR): void
    {
        // Tax
        $this->installZeroRate($trR);
        $this->installStandardRate($trR);
        // Unit
        $this->installProductUnit($uR);
        $this->installServiceUnit($uR);
        // Family
        $this->installProductFamily($fR);
        $this->installServiceFamily($fR);
        // Product
        $this->installProduct($pR);
        $this->installService($pR);
        // Client
        $this->installForeignClient($cR);
        $this->installNonForeignClient($cR);
    }

    /**
     * @param TaxRateRepository $trR
     */
    private function installZeroRate(TaxRateRepository $trR): void
    {
        // Only allow two tax rates initially
        // These tax rates will not be deleted when test data is reset because
        // they are defaults
        if ($trR->repoCountAll() < 2) {
            $tax_rate = new TaxRate();
            $tax_rate->setTaxRateName('Zero');
            $tax_rate->setTaxRatePercent(0);
            $tax_rate->setTaxRateDefault(false);
            $trR->save($tax_rate);
        }
    }

    /**
     * @param TaxRateRepository $trR
     */
    private function installStandardRate(TaxRateRepository $trR): void
    {
        // Only allow two tax rates initially
        // These tax rates will not be deleted when test data is reset because
        // they are defaults
        if ($trR->repoCountAll() < 2) {
            $tax_rate = new TaxRate();
            $tax_rate->setTaxRateName('Standard');
            $tax_rate->setTaxRatePercent(20);
            $tax_rate->setTaxRateDefault(true);
            $trR->save($tax_rate);
        }
    }

    /**
     * @param UnitRepository $uR
     */
    private function installProductUnit(UnitRepository $uR): void
    {
        $unit = new Unit();
        $unit->setUnitName('unit');
        $unit->setUnitNamePlrl('units');
        $uR->save($unit);
    }

    /**
     * @param UnitRepository $uR
     */
    private function installServiceUnit(UnitRepository $uR): void
    {
        $unit = new Unit();
        $unit->setUnitName('service');
        $unit->setUnitNamePlrl('services');
        $uR->save($unit);
    }

    /**
     * @param FamilyRepository $fR
     */
    private function installProductFamily(FamilyRepository $fR): void
    {
        $family = new Family();
        $family->setFamilyName('Product');
        $fR->save($family);
    }

    /**
     * @param FamilyRepository $fR
     */
    private function installServiceFamily(FamilyRepository $fR): void
    {
        $family = new Family();
        $family->setFamilyName('Service');
        $fR->save($family);
    }

    /**
     * @param ProductRepository $pR
     */
    private function installProduct(ProductRepository $pR): void
    {
        $product = new Product();
        $product->setProductSku('12345678rgfyr');
        $product->setProductName('Tuch Padd');
        $product->setProductDescription('Description of Touch Pad');
        $product->setProductPrice(100.00);
        $product->setPurchasePrice(30.00);
        $product->setProviderName('We Provide');
        $product->setTaxRateId(2);
        $product->setUnitId(1);
        $product->setFamilyId(1);
        $pR->save($product);
    }

    /**
     * @param ProductRepository $pR
     */
    private function installService(ProductRepository $pR): void
    {
        $service = new Product();
        $service->setProductSku('d234ds678rgfyr');
        $service->setProductName('Cleen Screans');
        $service->setProductDescription('Clean a screen');
        $service->setProductPrice(5.00);
        $service->setPurchasePrice(0.00);
        $service->setProviderName('Employee');
        // Zero => tax_rate_id => 1
        $service->setTaxRateId(1);
        // Service => unit_id = 2; Product => unit_id = 1
        $service->setUnitId(2);
        // Service => family_id 2; Product => family_id = 1
        $service->setFamilyId(2);
        $pR->save($service);
    }

    /**
     * @param ClientRepository $cR
     */
    private function installForeignClient(ClientRepository $cR): void
    {
        $client = new Client();
        $client->setClientActive(true);
        $client->setClientName('Foreign');
        $client->setClientSurname('Client');
        $client->setClientEmail('email@email.com');
        $client->setClientLanguage('Japanese');
        $client->setClientBirthdate(new \DateTimeImmutable());
        $client->setClientGender(2);
        $cR->save($client);
    }

    /**
     * @param ClientRepository $cR
     */
    private function installNonForeignClient(ClientRepository $cR): void
    {
        $client = new Client();
        $client->setClientActive(true);
        $client->setClientName('Non');
        $client->setClientSurname('Foreign');
        $client->setClientEmail('email@foreign.com');
        $client->setClientLanguage('English');
        $client->setClientBirthdate(new \DateTimeImmutable());
        $client->setClientGender(2);
        $cR->save($client);
    }

    /**
     * @param GroupRepository $gR
     */
    private function installDefaultInvoiceAndQuoteGroup(
                                                    GroupRepository $gR): void
    {
        $i_group = new Group();
        $i_group->setName('Invoice Group');
        $i_group->setIdentifierFormat('INV{{{id}}}');
        $i_group->setNextId(1);
        $i_group->setLeftPad(0);
        $gR->save($i_group);

        $q_group = new Group();
        $q_group->setName('Quote Group');
        $q_group->setIdentifierFormat('QUO{{{id}}}');
        $q_group->setNextId(1);
        $q_group->setLeftPad(0);
        $gR->save($q_group);

        $so_group = new Group();
        $so_group->setName('Sales Order Group');
        $so_group->setIdentifierFormat('SO{{{id}}}');
        $so_group->setNextId(1);
        $so_group->setLeftPad(0);
        $gR->save($so_group);

        $icn_group = new Group();
        $icn_group->setName('Credit Note Group');
        $icn_group->setIdentifierFormat('CN{{{id}}}');
        $icn_group->setNextId(1);
        $icn_group->setLeftPad(0);
        $gR->save($icn_group);
    }

    /**
     * @param PaymentMethodRepository $pmR
     */
    private function installDefaultPaymentMethods(
                                            PaymentMethodRepository $pmR): void
    {
        // 1
        $pm_cash = new PaymentMethod();
        $pm_cash->setName('Cash');
        $pm_cash->setActive(true);
        $pmR->save($pm_cash);
        // 2
        $pm_cheque = new PaymentMethod();
        $pm_cheque->setName('Cheque');
        $pm_cheque->setActive(true);
        $pmR->save($pm_cheque);
        // 3
        $pm_succeeded = new PaymentMethod();
        $pm_succeeded->setName('Card / Direct Debit - Payment Succeeded');
        $pm_succeeded->setActive(true);
        $pmR->save($pm_succeeded);
        // 4
        $pm_processing = new PaymentMethod();
        $pm_processing->setName('Card / Direct Debit - Payment Processing');
        $pm_processing->setActive(true);
        $pmR->save($pm_processing);
        // 5
        $pm_unsuccessful = new PaymentMethod();
        $pm_unsuccessful->setName('Card / Direct Debit - Payment Unsuccessful');
        $pm_unsuccessful->setActive(true);
        $pmR->save($pm_unsuccessful);
        // 6
        $customer_ready = new PaymentMethod();
        $customer_ready->setName(
                            'Card / Direct Debit - Customer Ready for Payment');
        $customer_ready->setActive(true);
        $pmR->save($customer_ready);
        // 7
        $peppol_access_point = new PaymentMethod();
        $peppol_access_point->setName('Peppol Access Point');
        $peppol_access_point->setActive(true);
        $pmR->save($peppol_access_point);
        // 8
        $open_banking = new PaymentMethod();
        $open_banking->setName('Open Banking Third Party Provider');
        $open_banking->setActive(true);
        $pmR->save($open_banking);
    }

    /**
     * @param SettingRepository $sR
     */
    private function removeAllSettings(SettingRepository $sR): void
    {
        // Completely remove any currently existing settings
        $settings = $sR->findAllPreloaded();
        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $sR->delete($setting);
        }
    }

    /**
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     */
    private function testDataDelete(UnitRepository $uR, FamilyRepository $fR,
                            ProductRepository $pR, ClientRepository $cR): void
    {
        // Products
        $product = ($pR->withName('Tuch Padd') ?? null);
        null !== $product ? $pR->delete($product) : null;
        $service = ($pR->withName('Cleen Screans') ?? null);
        null !== $service ? $pR->delete($service) : null;
        // Family
        $family_product = ($fR->withName('Product') ?? null);
        null !== $family_product ? $fR->delete($family_product) : null;
        $family_service = ($fR->withName('Service') ?? null);
        null !== $family_service ? $fR->delete($family_service) : null;
        // Unit
        $unit = ($uR->withName('unit') ?? null);
        null !== $unit ? $uR->delete($unit) : null;
        $unit_service = ($uR->withName('service') ?? null);
        null !== $unit_service ? $uR->delete($unit_service) : null;
        // Client
        $client_non = ($cR->withName('Non') ?? null);
        null !== $client_non ? $cR->delete($client_non) : null;
        $client_foreign = ($cR->withName('Foreign') ?? null);
        null !== $client_foreign ? $cR->delete($client_foreign) : null;
        // Group data is not deleted because these are defaults
    }
}
