<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

trait SettingTooltipTrait
{

    public function tooltipArray(): array
    {
        return array_merge(
            $this->tooltipArrayA(),
            $this->tooltipArrayB(),
            $this->tooltipArrayC(),
            $this->tooltipArrayD(),
            $this->tooltipArrayE(),
        );
    }

    private function tooltipArrayA(): array
    {
        return [
            'active_only' => [
                'why' => 'Old fully paid up clients, that have cancelled i.e.'
                . ' flagged inactive, are excluded from the Invoice index',
                'where' => './resources/views/invoice/settings/views/'
                . 'partial_settings_invoices.php and src/Invoice/'
                . 'InvoiceController.php',
            ],
            'bcc_mails_to_admin' => [
                'why' => 'A blind carbon copy email, unseen to the recipient'
                . ' of the email, is sent to the administrator.',
                'where' => ' Helpers/MailerHelper yii_mailer_send function.',
            ],
            'bootstrap5_offcanvas_enable' => [
                'why' => 'An offcanvas is useful on smaller devices such as'
                . ' mobile phones with the menu typically coming in from the'
                . ' top, bottom, left (start), or right (end).',
                'where' => './resources/views/layout/invoice.php and'
                . ' src/ViewInjection/LayoutViewInjection and src/'
                . 'Invoice/InvoiceController.php',
            ],
            'bootstrap5_offcanvas_placement' => [
                'why' => 'The placement of the offcanvas defaults to coming'
                . ' in from the top.',
                'where' => './resources/views/layout/invoice.php and'
                . ' src/ViewInjection/LayoutViewInjection and'
                . ' src/Invoice/InvoiceController.php ',
            ],
            'bootstrap5_alert_message_font' => [
                'why' => 'Adjust the font of the alert message',
                'where' => './resources/views/invoice/settings/views/'
                . 'partial_settings_general.php and'
                . ' src/Invoice/InvoiceController.php',
            ],
            'bootstrap5_alert_message_font_size' => [
                'why' => 'Adjust the font size of the alert message',
                'where' => './resources/views/invoice/settings/views/'
                . 'partial_settings_general.php and'
                . ' src/Invoice/InvoiceController.php',
            ],
            'bootstrap5_alert_close_button_font_size' => [
                'why' => 'Adjust the font size of the close button i.e ❌',
                'where' => './resources/views/invoice/settings/views/'
                . 'partial_settings_general.php and'
                . ' src/Invoice/InvoiceController.php',
            ],
            'cron_key' => [
                'why' => 'A cron job is used on the server to automatically'
                . ' email recurring invoices to clients.',
                'where' => 'This will be setup later.',
            ],
            'currency_code_from_to' => [
                'why' => 'Necessary if Peppol Document Currency different to'
                . ' sender\'s currency.',
                'where' => 'src/Invoice/Helpers/Peppol/PeppolHelper/'
                . 'generate_invoice_peppol_ubl_xml_temp_file function',
            ],
            'currency_symbol' => [
                'why' => 'Used in NumberHelper/format_amount.',
                'where' => 'views/invoice/inv/partial_item_table,'
                . ' views/invoice/quote/partial_item_table, views/invoice/'
                . 'invitem/_item_edit_task and _item_edit_product',
            ],
            'currency_symbol_placement' => [
                'why' => 'NumberHelper/format_amount. ',
                'where' => 'views/invoice/inv/partial_item_table,'
                . ' views/invoice/quote/partial_item_table,'
                . ' views/invoice/invitem/_item_edit_task and _item_edit_product',
            ],
            'currency_code' => [
                'why' => 'Used in PaymentInformationController and the dropdown'
                . ' array is constructed in src/Invoice/Helpers/CurrencyHelper',
                'where' => 'PaymentInformationController and CurrencyHelper',
            ],
            'custom_title' => [
                'why' => 'This custom designed title appears in the top left'
                . ' corner of the current browser tab.',
                'where' => 'layout/invoice',
            ],
            'date_tax_point' => [
                'why' => 'Necessary for calculating VAT submissions to Receivers'
                . ' of Revenue',
                'where' => 'Refer to src\Invoice\Inv\InvService function'
                . ' set_tax_point. Variables used: 14 days, Date Supplied'
                . ' (Date Delivered), Date Created',
            ],
            'default_email_template' => [
                'why' => 'Build your first template using Settings'
                . '...Email Template. Your first email to the customer will use'
                . ' this template. '
                . 'Typically you will include various fields from the'
                . ' database in this template by dragging and dropping them'
                . ' when you build this template. '
                . 'Normally you will create three templates ie. Normal,'
                . ' Overdue, and Paid. '
                . 'The Normal Invoice Template that you create will be linked to'
                . ' the setting email_invoice_template. '
                . 'The Paid Invoice Template that you create will be linked to'
                . ' the setting email_invoice_template_paid. '
                . 'The Overdue Invoice Template that you create will be linked'
                . ' to the setting email_invoice_template_overdue. '
                . 'Depending on the status of the invoice, the TemplateHelper'
                . ' matches the appropriate email template to the status of the'
                . ' invoice. ',
                'where' => 'src/Invoice/Helpers/TemplateHelper/'
                . 'select_email_invoice_template',
            ],
            'date_format' => [
                'why' => 'This is used exclusively in DateHelper functions.',
                'where' => 'App/Invoice/Helpers/DateHelper.php',
            ],
            'default_country' => [
                'why' => 'If a user, or client, do not have a country linked to'
                . ' them, this is the default country used',
                'where' => 'ClientController/Edit and UserInvController',
            ],
            'default_include_item_tax' => [
                'why' => 'If true: Add item tax to item subtotal to work out'
                . ' e.g Quote Tax. Not applicable to VAT',
                'where' => 'InvController function defaultTaxInv and'
                . ' QuoteController function defaultTaxQuote and NumberHelper'
                . ' calculate_quote_taxes calculate_inv_taxes',
            ],
            'default_language' => [
                'why' => 'This is the default language assigned to new clients,'
                . ' and is used for printing documents.',
                'where' => 'client/_form and pdfHelper/get_print_language.'
                . ' To override this setting: The client will receive their'
                . ' documents in their language provided their language is set'
                . ' in the client form.',
            ],
        ];
    }

    private function tooltipArrayB(): array
    {
        return [
            'default_list_limit' => [
                'why' => 'This value is used with the Paginator to limit the'
                . ' number of records viewed',
                'where' => 'ClientController/Edit',
            ],
            'default_invoice_group' => [
                'why' => 'When a new invoice or quote is created, the package'
                . ' uses invoice groups to determine the next invoice or quote'
                . ' number,'
                . 'and how it should be structured. The package comes with two'
                . ' default invoice groups namely Invoice Default and'
                . ' Quote Default. '
                . 'Both groups will generate simple incremental IDs starting at'
                . ' the number 1, but the Quote Default will be prefixed with QUO. '
                . 'An example of an identifier tag might be eg.'
                . ' {{{year}}}-{{{month}}}-{{{day}}}-{{{ID}}}'
                . 'The ID tab must be included in all identifiers, preferably'
                . ' towards the end of the identifier.',
                'where' => 'views\invoice\group\_form.',
            ],
            'default_terms' => [
                'why' => 'You can enter the default terms here for any invoice.',
                'where' => ' views\invoice\inv\_form',
            ],
            // Note: Appears as 'public_invoice_template' under settings table
            // but as
            //  'default_invoice_template' for language purposes =>ip_lang.php
            'default_public_template' => [
                'why' => 'This is the HTML template that the client will see'
                . ' online prior to payment. The template has a pay-now button.'
                . ' The client must log in having been assigned observer'
                . ' role status in order to see this html invoice template.'
                . ' Different HTML Templates can be created in this folder and'
                . ' chosen in this dropdown.',
                'where' => 'views/invoice/template/invoice/public/'
                . 'Invoice_Web.php (subsequent to client gateway selection'
                . ' from inv/view) and also InvController/url_key function that'
                . ' receives the url_key and gateway query parameters in the Url'
                . ' from inv/view. This HTML template holds the pay-now button'
                . ' with the chosen gateway (passed from inv/view) which at this'
                . ' point cannot be changed. If the payment is successful the'
                . ' template and therefore the pay-now button will reflect'
                . ' as paid.',
            ],
            'disable_quickactions' => [
                'why' => 'This setting is used in the dashboard.',
                'where' => 'views/invoice/dashboard/index.php and also in'
                . ' InvoiceController/dashboard function',
            ],
            'disable_sidebar' => [
                'why' => 'Enable or disable sidebar.',
                'where' => 'views/layout/invoice and also in'
                . ' InvoiceController/install_default_settings_on_first_run',
            ],
            'email_send_method' => [
                'why' => 'Symfony mailer is now the default mailer. '
                . 'What is ESMTP? In response to the rampant spam problem on'
                . ' the internet, '
                . 'an extension of SMTP was released in 1995:'
                . ' extended SMTP (ESMTP for short). '
                . 'It adds additional commands to the protocol in 8-bit ASCII'
                . ' code, enabling many '
                . 'new functions to save bandwidth and protect servers.'
                . ' These include, for example: '
                . 'Authentication of the sender, SSL encryption of e-mails,'
                . ' Possibility of attaching multimedia files to e-mails '
                . 'Restrictions on the size of e-mails according to server'
                . ' specifications, '
                . 'Simultaneous transmission to several recipients, '
                . 'Standardised error messages in case of undeliverability',
                'where' => 'src/Invoice/Helpers/MailerHelper/mailer_configured'
                . ' function.',
            ],
            'email_pdf_attachment' => [
                'why' => 'When an email is sent to a customer/client, the'
                . ' relevant invoice is automatically archived at'
                . ' src/Invoice/Uploads/Archive/Invoice. '
                . 'Send this archived pdf to the customer along with any'
                . ' attachments when using the button '
                . 'Options...Send on the view/invoice.'
                . 'This setting is enabled by default under the InvoiceController',
                'where' => 'src/Invoice/Helpers/MailerHelper/yii_mailer_send'
                . ' function variable email_attachment_with_pdf_template. '
                . 'Run with view/invoice Options...Send  using MailerInvForm',
            ],
            'enable_tfa' => [
                'why' => 'Two Factor Authentication is necessary to provide an'
                . ' additional layer of security i.e. User logs in and then'
                . ' verifies  e.g. fraud prevention headers require'
                . ' Timed One Time Password (TOTP)',
                'where' => 'src/Auth/Controller/AuthController function login'
                . ' augmenting src/Invoice/Setting/SettingRepository/function'
                . ' fphGeneratorMultiFactor',
            ],
            'enable_vat_registration' => [
                'why' => 'VAT uses line item tax and applying Invoice Taxes'
                . ' (whether before line item or after line tax) are disabled.'
                . ' Hence the tax_total field in the InvAmount Entity will'
                . ' always equal zero if VAT is used. '
                . 'A new nullable field ... belongs_to_vat_invoice...has been'
                . ' introduced in the InvItem entity to allow for companies'
                . ' making this transition. ',
                'where' => 'This setting is used in resources/views/invoice/'
                . 'inv/view.php',
            ],
            'front_page_file_locations_tooltip' => [
                'why' => 'Check to remove page from menu. These checkbox\'s'
                . ' affect the src\ViewInjection\LayoutViewInjection.php file,',
                'where' =>
        'resources\views\invoice\setting\views\partial_settings_front_page.php'
                . ' and'
                . '  src\Invoice\InvoiceController.php function'
                . ' install_default_settings_on_first_run',
            ],
            'first_day_of_week' => [
                'why' => 'This is used in the javascript function on'
                . ' views/layout/invoice.php along with the datehelper'
                . ' datepicker function.',
                'where' => 'views/layout/invoice.php',
            ],
            'generate_invoice_number_for_draft' => [
                'why' => 'Automatically generate an Invoice Number by means of'
                . ' the Group Identifier. '
                . 'When an invoice is first created, it is placed in Draft'
                . ' status by default. Sending an invoice by email will'
                . ' automatically change the status from Draft to Sent.'
                . ' Clients cannot view any invoices when they are in Draft'
                . ' status. ',
                'where' => 'InvController/generate_inv_get_number and'
                . ' InvRepository/get_inv_number',
            ],
        ];
    }

    private function tooltipArrayC(): array
    {
        return [
            'generate_quote_number_for_draft' => [
                'why' => 'Automatically generate a Quote Number by means of the'
                . ' Group Identifier.',
                'where' => 'QuoteController/generate_quote_number_if_applicable'
                . ' and QuoteRepository/get_quote_number and'
                . ' GroupRepository/generate_number.',
            ],
            'google_translate_json_filename' => [
                'why' => 'GeneratorController includes a function'
                . ' google_translate_lang. '
                . 'This function takes the English app_lang array in'
                . ' src/Invoice/Language/English and translates it into the'
                . ' chosen locale (Settings...View...Google Translate)'
                . ' outputting it to'
                . ' resources/views/generator/output_overwrite' . "\r\n"
                . '---Step--1: Download https://curl.haxx.se/ca/cacert.pem'
                . ' into active c:\wamp64\bin\php\php8.5.0 folder' . "\r\n"
                . '---Step--2: Select your project that you created under'
                . ' https://console.cloud.google.com/projectselector2/iam-admin/'
                . 'serviceaccounts?supportedpurview=project' . "\r\n"
                . '---Step--3: Click on Actions icon and select Manage Keys'
                . "\r\n"
                . '---Step--4: Add Key' . "\r\n"
                . '---Step--5: Choose the Json File option and Download the'
                . ' file to src/Invoice/Google_translate_unique_folder' . "\r\n"
                . '---Step--6: You will have to enable the Cloud Translation API'
                . ' and provide your billing details. You will be charged 0'
                . ' currency. ' . "\r\n"
                . '---Step--7: Adjust the php.ini [apache_module] by means of'
                . ' the wampserver icon or by clicking on the symlink in the'
                . ' directory.' . "\r\n"
                . '---Step--8: Edit this symlink file manually at [curl] with'
                . ' eg. "c:/wamp64/bin/php/php8.5.0/cacert.pem'
                . '   Note the forward slashes.' . "\r\n"
                . '---Step--9: Reboot your server' . "\r\n"
                . '---Step--10: After generating the file, move the file from'
                . ' views/generator/output_overwrite to eg.'
                . ' resources/messages/{de}/app.php.',
                'where' => 'GeneratorController/google_translate_lang',
            ],
            'google_translate_diff' => [
                'why' => 'Translates src/Invoice/Language/English/diff_lang.php,'
                . ' which holds translation keys that are present in the English'
                . ' source but missing from resources/messages/en/app.php.'
                . ' Run this after adding new keys to the English source to find'
                . ' and fill translation gaps in other locales.',
                'where' => 'GeneratorController/googleTranslateLang (type=diff)',
            ],
            'google_translate_en_app_php' => [
                'why' => 'To translate resources/messages/en/app.php, make sure'
                . ' you have loaded a copy in the ../Language/English folder.'
                . "\r\n"
                . 'Note: gateway_lang and ip_lang arrays have been combined'
                . ' into app.php',
                'where' => 'GeneratorController/google_translate_lang',
            ],
            'google_translate_locale' => [
                'why' => 'To save time manually translating an ip_lang file'
                . ' using Google Translate Online, the Google Translate API'
                . ' https://github.com/googleapis/google-cloud-php-translate'
                . ' can be used to translate to your chosen locale. eg.'
                . ' es / Spanish',
                'where' => 'GeneratorController/google_translate_lang and this'
                . ' dropdown box is built with SettingRepository locales'
                . ' function',
            ],
            'include_delivery_period' => [
                'why' => 'A group of business terms providing information on the'
                . ' invoice period. Also called delivery period. If the group'
                . ' is used, the invoiceing period start date and/or end date'
                . ' must be used. ',
                'where' => 'src/Invoice/Delivery/DeliveryController',
            ],
            'include_zugferd' => [
                'why' => 'ZUGFeRD stands for Zentraler User Guide des Forums'
                . ' elektronische Rechnung Deutschland '
                . 'It is a uniform standard for the electronic transmission of'
                . ' invoice data in Germany. '
                . 'The aim of the standard is to harmonise the exchange of'
                . ' information between companies and with public authorities. '
                . 'With the standard, the information contained in invoices'
                . ' can be read and processed automatically. '
                . 'This enables both you and the recipients of your documents'
                . ' to automatically transfer the invoice data to third-party'
                . ' systems with little effort. '
                . 'With the help of the standard, the entire content of the'
                . ' invoice can be transferred to an ERP system. ',
                'where' => 'src/Invoice/Libraries and src/Invoice/Helpers/ZugFerdHelper',
            ],
            'install_test_data' => [
                'why' => 'This is used by Generator..Reset Data and Generator'
                . '..Remove Data during the testing of data',
                'where' => 'invoice/test_data_reset and invoice/test_data_remove',
            ],
            'invoice_default_payment_method' => [
                'why' => 'Default: 1  None, 2 Cash, 3 Cheque,'
                . ' 4 Card/Direct Debit - Succeeded '
                . '5 Card/Direct Debit - Processing'
                . ' 6 Card/Direct Debit - Customer Ready.',
                'where' =>
                'InvoiceController/install_default_settings_on_first_run and '
                . 'InvController/create_confirm function which assigns the'
                . ' default of 1 to all invoices when created. '
                . 'See src/Invoice/Asset/rebuild-1.13/js/inv.js'
                . ' #inv_create_confirm function and '
                . 'resources/views/invoice/inv/modal_create_inv.php as well.',
            ],
            'invoices_due_after' => [
                'why' => 'The number of days after the original invoice date'
                . ' when invoices become due for payment.',
                'where' => 'InvRepository/get_date_due and'
                . ' Entity/Inv/setDate_due().',
            ],
            'invoice_overview_period' => [
                'why' => 'This setting is used on the dashboard so that the'
                . ' invoices that are shown will either be this-month,'
                . ' last-month, this-quarter, last-quarter, this-year,'
                . ' or last-year',
                'where' => 'views/invoice/dashboard/index.php and also in'
                . ' InvoiceController/dashboard function',
            ],
            'login_logo' => [
                'why' => '',
                'where' => '',
            ],
            'mark_invoices_sent_pdf' => [
                'why' => 'If the invoice is downloaded it will be marked as sent.',
                'where' => 'InvController/pdf and InvController/email_stage_2'
                . ' when viewing the invoice.',
            ],
            'mark_invoices_sent_copy' => [
                'why' => 'Clients do not have access to draft invoices.'
                . ' Mark a copied invoice as sent so that the client can view it.'
                . ' Caution: Used for testing purposes only. '
                . 'By default copied invoices are marked as draft and'
                . ' therefore can not be viewed by the client online. '
                . 'They can only be viewed by the client once they have been'
                . ' sent by email or marked as sent manually in the Invoice'
                . ' Edit section under Inv/View/Options Dropdown Button. ',
                'where' => 'InvController/inv_to_inv',
            ],
        ];
    }

    private function tooltipArrayD(): array
    {
        return [
            'monospace_amounts' => [
                'why' => 'Evenly spaced characters for better presentation.',
                'where' => 'views/layout/invoice.php and views/layout/guest.php',
            ],
            'mpdf_ltr' => [
                'why' => 'Settings for https://mpdf.github.io/',
                'where' =>
                'src/Invoice/Helpers/MpdfHelper.php function initializePdf',
            ],
            'number_format' => [
                'why' => 'When the number format is chosen, the decimal point, '
                . "\r\n"
                . 'and thousands_separator settings have to be derived from'
                . "\r\n"
                . 'the number_format array located in SettingsRepository using '
                . "\r\n"
                . 'the tab_index_number_format function in the'
                . ' SettingController.' . "\r\n"
                . 'Note: This setting does not effect the number of'
                . ' decimal places: ' . "\r\n"
                . 'Only the type of decimal point used i.e comma or dot, and'
                . ' the space' . "\r\n"
                . 'between the numbers for display.',
                'where' => 'SettingController/tab_index_number_format',
            ],
            'oauth2' => [
                'why' => 'Check to remove continue button from both login and'
                . ' signup forms. These checkbox\'s affect the'
                . ' src\Auth\Controller\AuthController.php, and'
                . ' ..resources\views\invoice\setting\tab_index.php file,',
                'where' =>
             'resources\views\invoice\setting\views\partial_settings_oauth2.php'
                . ' and src\Invoice\InvoiceController.php function'
                . ' install_default_settings_on_first_run',
            ],
            'open_reports_in_new_tab' => [
                'why' => 'Open reports up in a new tab. Featured in eg.'
                . ' Reports...invoice_aging_index.php',
                'where' => ' eg. views/invoice/invoice_aging_index.php',
            ],
            'pdf_archive_inv' => [
                'why' => 'Pdf\'s that are generated can be archived under a'
                . ' folder called Archive situated in the Uploads folder.',
                'where' => 'pdfHelper pdfCreate function',
            ],
            'pdf_watermark' => [
                'why' => 'eg. If an invoice is paid, a watermark with the word'
                . ' paid will appear across it. The same applies to overdue'
                . ' invoices.',
                'where' =>
    'src/Invoice/Helpers/MpdfHelper/initialize_pdf function.',
            ],
            'pdf_invoice_template' => [
                'why' => 'Clients can download pdfs online if logged in and'
                . ' given observer status. This represents the normal template.'
                . ' ie. if an invoice is neither paid or overdue and is used'
                . ' alongside the paid and overdue template.',
                'where' =>
    'src/Invoice/Helpers/TemplateHelper/select_pdf_invoice_template function.',
            ],
            'pdf_invoice_template_paid' => [
                'why' => 'Clients can download pdfs online if logged in and'
                . ' given observer status. This represents the paid template.'
                . ' ie. if an invoice is paid and is used alongside the normal'
                . ' and overdue template.',
                'where' =>
    'src/Invoice/Helpers/TemplateHelper/select_pdf_invoice_template function.',
            ],
            'pdf_invoice_template_overdue' => [
                'why' => 'Clients can download pdfs online if logged in and'
                . ' given observer status. This represents the overdue template.'
                . ' ie. if an invoice is overdue and is used alongside the'
                . ' normal and paid template.',
                'where' =>
    'src/Invoice/Helpers/TemplateHelper/select_pdf_invoice_template function.',
            ],
            'pdf_stream_inv' => [
                'why' => 'To stream is to present in the browser normally as'
                . ' xml, html, or a pdf. Not to stream is to print to a file.'
                . ' Hence the use of the The Google sign located under'
                . ' settings ... Views ... Invoices... ',
                'where' =>
    'resources/views/invoice/setting/views/partial_settings_invoices'
                . ' with InvController/email_stage_1 variable $stream'
                . ' ... pdfHelper..generate_inv_pdf ... mpdfHelper..pdfCreate',
            ],
            'peppol_document_currency' => [
                'why' => 'UBL Invoice can be in either the Supplier\'s currency'
                . ' or the Buyer\'s currency',
                'where' => 'inv/view peppol_doc_currency_toggle and '
                . 'inv/peppol_doc_currency_toggle',
            ],
            'peppol_debug_with_emojis' => [
                'why' => 'To temporarily highlight the toggling '
                . 'DocumentCurrencyCode with a left arrow in the e-invoice and'
                . ' amounts that change with currency conversion with a'
                . ' left-right arrow in the e-invoice as well.',
                'where' => 'settingRepository function currencyConverter'
            ],
            'peppol_xml_stream' => [
                'why' => 'To show on screen or to save under Uploads/Temp/Peppol'
                . ' folder.',
                'where' => 'inv/peppol',
            ],
            'quote_overview_period' => [
                'why' => 'This setting is used on the dashboard so that the'
                . ' quotes that are shown will either be this-month,'
                . ' last-month, this-quarter, last-quarter, this-year,'
                . ' or last-year',
                'where' => 'views/invoice/dashboard/index.php and also in'
                . ' InvoiceController/dashboard function',
            ],
            'read_only_toggle' => [
                'why' => 'To prevent an invoice from being edited i.e.'
                . ' is read only. By default set to read only if sent. ',
                'where' => 'Sent:'
                . ' src/Invoice/Setting/SettingRepository/invoice_mark_sent'
                . ' with InvController (several places) '
                . 'View:'
                . ' src/Invoice/Setting/SettingRepository/invoice_mark_viewed'
                . ' InvController/url_key (when users view their invoices online) '
                . 'Paid:'
    . ' src/Invoice/Helpers/NumberHelper/inv_balance_zero_set_to_read_only_if_fully_paid. ',
            ],
            'stand_in_code' => [
                'why' => 'If a tax point date cannot be determined because a'
                . ' Delivery Period has been setup and there is no Date'
                . ' Supplied (ie. Actual Delivery Date) and no subsequent Date'
                . ' Issued, this code mutually excludes the tax point date'
                . ' value on an e-invoice. If you are using Accrual Based Vat'
                . ' Accouning use 3 Issue date or most likely 35 Supply date,'
                . ' if you are using Cash Based Vat Accounting use 432. The'
                . ' tax point date must be excluded from an e-invoice if'
                . ' Delivery Periods are used. ',
                'where' => 'src/Invoice/Inv/InvService/BothInv function and'
                . ' set_tax_point function. It is not included in AddInv and'
                . ' SaveInv since these two functions are deprecated.',
                'href' => 'https://docs.peppol.eu/poacc/billing/3.0/syntax/'
                . 'ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/',
            ],
        ];
    }

    private function tooltipArrayE(): array
    {
        return [
            'storecove_country' => [
                'why' => 'The first step in sending an invoice is to create'
                . ' a sender. This sender is called'
                . ' a \'LegalEntity\'. LegalEntities can both send and receive,'
                . ' but for now we will focus on their sending role. Although'
                . ' the LegalEntity we are creating now can contain dummy data,'
                . ' you should carefully choose the LegalEntity’s country,'
                . ' because this will be important for the contents of the'
                . ' invoice.',
                'where' => 'src/Invoice/Helpers/StoreCove/StoreCoveHelper',
            ],
            'storecove_sender_identifier' => [
                'why' => 'Legal Identifiers - A legal identifier identifies the'
                . ' legal entity from a legal perspective. It can be a local'
                . ' chambre of commerce number, or a DUNS, GLN, etc. However,'
                . ' in many countries the tax identifier is also the legal'
                . ' identifiers. In that case you don’t need to set this up'
                . ' separately. '
                . 'Tax Identifiers - A tax identifier identifies the legal'
                . ' entity from a tax perspective. In the EU, all tax'
                . ' identifiers are VAT numbers and are prefixed with the'
                . ' ISO3166-2 country code, e.g. "IT12345678901". In India,'
                . ' the tax identifier is issued by the state in which the'
                . ' LegalEntity resides. '
                . 'It’s first two digits are always the numercial code of the'
                . ' state that issued it.',
                'where' => 'src/Invoice/Helpers/StoreCove/StoreCoveHelper'
                . ' A)',
            ],
            'storecove_sender_identifier_basis' => [
                'why' => 'Before selecting here, check that it is available'
                . ' in the sender identifier list. If not available, the'
                . ' available identifier will be chosen.',
                'where' => 'src/Invoice/Helpers/StoreCove/StoreCoveHelper'
                . ' A)',
            ],
            'tax_rate_decimal_places' => [
                'why' => 'TODO: Currency decimal places vary per country.'
                . ' The decimal column of the TaxRate table, tax_rate_percent'
                . ' column has to be adjusted during runtime using the'
                . ' ALTER COMMAND sql statement preferably in a FRAGMENT',
                'where' => 'SettingController/tab_index_change_decimal_column',
            ],
            'time_zone' => [
                'why' => 'This is used in the DateHelper function'
                . ' datetime_zone_style which is used in TaskForm to get'
                . ' an accurate Finish Date for a Task.' . '/n'
                . 'It is also used in paymentinformation/amazon_signature'
                . ' to get a region from a time zone.',
                'where' => '',
            ],
        ];
    }

    /**
      * @param string $setting
      * @param bool $debug_mode
      * @return string
      */
    public function where(string $setting, bool $debug_mode = true): string
    {
        $tooltip = $this->tooltipArray();
        $why = '';
        $where = '';
        /**
         * @var array $value
         * @var string $key
         */
        foreach ($tooltip as $key => $value) {
            if ($key === $setting) {
                /**
                 * @var string $_value
                 * @var string $_key
                 */
                foreach ($value as $_key => $_value) {
                    if ($_key === 'why') {
                        $why = $_value;
                    }
                    if ($_key === 'where') {
                        $where = $_value;
                    }
                }
            }
        }
        $information = 'data-bs-toggle = "tooltip" data-bs-placement= "bottom" '
                . ' ' . 'title = "' . $why . ' and is used in ' . $where . '"';
        return $debug_mode ? $information : '';
    }

    /**
     * Returns only the plain-text tooltip content (why + where) suitable for
     * use as a PHP array 'title' attribute value, e.g. in DropdownItem itemAttributes.
     * Unlike where(), this does NOT return HTML attribute markup.
     */
    public function tooltipTitle(string $setting): string
    {
        return $this->tooltipWhy($setting) . ' | ' . $this->tooltipWhere($setting);
    }

    /**
     * Returns only the 'why' text for a tooltip key — use as data-bs-content
     * on a popover so the body can be formatted independently of the header.
     */
    public function tooltipWhy(string $setting): string
    {
        $tooltip = $this->tooltipArray();
        /** @var array $value */
        foreach ($tooltip as $key => $value) {
            if ($key === $setting) {
                return (string) ($value['why'] ?? '');
            }
        }
        return '';
    }

    /**
     * Returns only the 'where' text for a tooltip key — use as data-bs-title
     * on a popover so the header shows the file/controller location.
     */
    public function tooltipWhere(string $setting): string
    {
        $tooltip = $this->tooltipArray();
        /** @var array $value */
        foreach ($tooltip as $key => $value) {
            if ($key === $setting) {
                return (string) ($value['where'] ?? '');
            }
        }
        return '';
    }

    /**
     * @param string $setting
     * @param bool $debug_mode
     * @return string
     */
    public function href(string $setting, bool $debug_mode = true): string
    {
        $tooltip = $this->tooltipArray();
        $href = '';
        /**
         * @var array $value
         * @var string $key
         */
        foreach ($tooltip as $key => $value) {
            if ($key === $setting) {
                /**
                 * @var string $_value
                 * @var string $_key
                 */
                foreach ($value as $_key => $_value) {
                    if ($_key === 'href') {
                        $href = $_value;
                    }
                }
            }
        }
        return $debug_mode ? $href : '';
    }
}
