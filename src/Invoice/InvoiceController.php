<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\Family;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\PaymentMethod;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\Setting;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Unit;
// Repositories
use App\Invoice\Client\ClientRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Project\ProjectRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Task\TaskRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
// Services and forms
use App\Service\WebControllerService;
use App\User\UserService;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use App\Invoice\Libraries\Crypt;

final class InvoiceController extends BaseController
{
    // New property for controller name
    protected string $controllerName = 'invoice';

    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        ViewRenderer $viewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        protected Crypt $crypt,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
    }

    /**
     * @param SessionInterface $session
     * @param SettingRepository $sR
     */
    private function install_default_settings_on_first_run(SessionInterface $session, SettingRepository $sR): void
    {
        $default_settings = [
            /**
             * Remove the 'default_settings_exist' setting from the settings table by manually
             * going into the mysql database table 'settings' and deleting it. This will remove &
             * reinstall the default settings listed below. The above index function will check
             * whether this setting exists. If not THIS function will be run.
             * CAUTION: THIS WILL ALSO REMOVE ALL THE SETTINGS INCLUDING SECRET KEYS
             */
            'default_settings_exist' => '1',

            'bootstrap5_offcanvas_enable' => 0,
            'bootstrap5_offcanvas_placement' => 'top',
            'bootstrap5_alert_message_font_size' => '10',
            'bootstrap5_alert_close_button_font_size' => '10',
            'bootstrap5_layout_invoice_navbar_font' => 'Arial',
            'bootstrap5_layout_invoice_navbar_font_size' => '10',
            'cron_key' => Random::string(32),
            'currency_symbol' => '£',
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
            'default_language' => 'English',
            //paginator list limit
            'default_list_limit' => 120,
            'disable_flash_messages_inv' => 0,
            'disable_flash_messages_quote' => 0,
            // Prevent documents from being made non-editable. By default documents are made non-editable
            // according to the read_only_toggle (listed below) which is set at sent ie 2. So when a document is sent it becomes non-editable i.e. read_only
            // By default this setting is on 0 ie. Invoices can be made read-only (through the
            // read_only_toggle)
            'disable_read_only' => 0,
            'disable_sidebar' => 1,
            'email_send_method' => 'symfony',
            // Invoice deletion by Law is not allowed. Invoices have to be cancelled with a credit invoice/note.
            'enable_invoice_deletion' => true,
            'enable_peppol_client_defaults' => 1,
            'enable_telegram' => 0,
            'enable_vat_registration' => 0,
            'enable_tfa' => 0,
            // Qr code is always shown
            'enable_tfa_with_disabling' => 0,
            // Archived pdfs are automatically sent to customers from view/invoice...Options...Send
            // The pdf is sent along with the attachment to the invoice on the view/invoice.
            'email_pdf_attachment' => 1,
            'generate_invoice_number_for_draft' => 1,
            'generate_quote_number_for_draft' => 1,
            'generate_so_number_for_draft' => 1,
            'install_test_data' => 0,
            //1 => None, 2 => Cash, 3 => Cheque, 4 => Card/Direct Debit - Succeeded
            //5 => Card/Direct Debit - Processing 6 => Card/Direct Debit - Customer Ready
            'invoice_default_payment_method' => 6,
            'invoices_due_after' => 30,
            'invoice_logo' => 'favicon.ico',
            //This setting should be zero during Production. See inv/mark_sent warning
            'mark_invoices_sent_copy' => 0,
            'mpdf_ltr' => 1,
            'mpdf_cjk' => 1,
            'mpdf_auto_script_to_lang' => 1,
            'mpdf_auto_vietnamese' => 1,
            'mpdf_auto_arabic' => 1,
            'mpdf_allow_charset_conversion' => 1,
            'mpdf_auto_language_to_font' => 1,
            'mpdf_show_image_errors' => 1,
            'no_front_about_page' => 0,
            'no_front_accreditations_page' => 0,
            'no_front_contact_details_page' => 0,
            'no_front_contact_interest_page' => 0,
            'no_front_gallery_page' => 0,
            'no_front_pricing_page' => 0,
            'no_front_site_slider_page' => 0,
            'no_front_team_page' => 0,
            'no_front_testimonial_page' => 0,
            'no_developer_sandbox_hmrc_continue_button' => 1,
            'no_facebook_continue_button' => 1,
            'no_github_continue_button' => 1,
            'no_google_continue_button' => 1,
            'no_govuk_continue_button' => 1,
            'no_linkedin_continue_button' => 1,
            'no_microsoftonline_continue_button' => 1,
            'no_x_continue_button' => 1,
            'no_yandex_continue_button' => 1,
            'no_vkontakte_continue_button' => 1,
            // Number format Default located in SettingsRepository
            'number_format' => 'number_format_us_uk',
            'payment_list_limit' => 20,
            // Show the pdf in the Browser ie. stream ...Settings...View...Invoices...Pdf Settings...G
            'pdf_stream_inv' => 1,
            // Accumulate pdf's in archive folder /src/Invoice/Uploads/Archive/Invoice
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
            // Templates used for processing online payments via customers/clients login portal
            'peppol_xml_stream' => 1,
            'public_invoice_template' => 'Invoice_Web',
            'public_quote_template' => 'Quote_Web',
            'quotes_expire_after' => 15,
            // Set the invoice to read-only on sent by default;
            'read_only_toggle' => 2,
            'reports_in_new_tab' => true,
            'signup_automatically_assign_client' => 0,
            'signup_default_age_minimum_eighteen' => 1,
            'stop_logging_in' => false,
            'stop_signing_up' => false,
            'sumex_canton' => 1,
            'sumex_role' => 1,
            'sumex_place' => 1,
            'tax_rate_decimal_places' => 2,
            'telegram_chat_id' => '',
            'telegram_payment_notifications' => 0,
            'telegram_token' => '',
            'telegram_webhook_secret_token' => '',
            'telegram_test_message_use' => 1,
            'thousands_separator' => ',',
            'time_zone' => 'Europe/London',
        ];
        $this->install_default_settings($default_settings, $sR);
    }

    public function faq(#[RouteArgument('topic')] string $topic): Response
    {
        $view = match ($topic) {
            'ai_callback_session' => $this->viewRenderer->renderPartialAsString('//invoice/info/ai/ai_callback_session'),
            'tp' => $this->viewRenderer->renderPartialAsString('//invoice/info/taxpoint'),
            'shared' => $this->viewRenderer->renderPartialAsString('//invoice/info/shared_hosting'),
            'alpine' => $this->viewRenderer->renderPartialAsString('//invoice/info/alpine'),
            'oauth2' => $this->viewRenderer->renderPartialAsString('//invoice/info/oauth2'),
            'paymentprovider' => $this->viewRenderer->renderPartialAsString('//invoice/info/payment_provider'),
            'consolecommands' => $this->viewRenderer->renderPartialAsString('//invoice/info/console_commands'),
            'ipaddress' => $this->viewRenderer->renderPartialAsString('//invoice/info/ip_address'),
            default => '',
        };
        return $this->viewRenderer->render('info/view', ['topic' => $view]);
    }

    public function phpinfo(#[RouteArgument('selection')] string $selection = '-1'): Response
    {
        $view = $this->viewRenderer->renderPartialAsString('//invoice/info/phpinfo', ['selection' => (int) $selection]);
        return $this->viewRenderer->render('info/view', ['topic' => $view]);
    }

    /**
     * Use curL to call the store_cove api ... 1.1.3. Make your first API call
     * Tab: ERP or Accounting System, NOT: Individual Company, NOT: Reseller or Systems Integrator
     * Related logic: see config\common\routes\routes.php api-store-cove
     * Related logic: see https://www.storecove.com/docs 3.3.2. Sending a document UBL format
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_call_api(): \Yiisoft\DataResponse\DataResponse
    {
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $store_cove = 'https://api.storecove.com/api/v2/discovery/receives';
        // 1.1.2 : Create a new API key by clicking the "Create New API Key" button. For the Integrator package, create a "Master" key.
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->sR->getSetting('gateway_storecove_apiKey'));
        $site = curl_init();
        if ($site != false) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, ['Accept: application/json',"Authorization: Bearer $api_key_here",'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            /**
             * Related logic: see https://www.storecove.com/docs/#_getting_started 1.1.3. Make your first API call
             */
            $data = '{"documentTypes": ["invoice"], "network": "peppol", "metaScheme": "iso6523-actorid-upis", "scheme": "nl:kvk", "identifier":"60881119"}';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            curl_close($site);
            $message = curl_error($site) ?: $this->translator->translate('curl.store.cove.api.setup.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->viewRenderer->render('curl/api_result', $parameters);
    }

    /**
     * Use curL to call the store_cove api ... 1.1.4a. Create a sender: Get the Legal Entity Id
     * Related logic: see config\common\routes\routes.php api-store-cove-get-legal-entity-id
     * Related logic: see https://www.storecove.com/docs/
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_call_api_get_legal_entity_id(): \Yiisoft\DataResponse\DataResponse
    {
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $store_cove = 'https://api.storecove.com/api/v2/legal_entities';
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->sR->getSetting('gateway_storecove_apiKey'));
        $site = curl_init();
        if ($site != false) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, ['Accept: application/json',"Authorization: Bearer $api_key_here",'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            $country_code_identifier = 'GB';
            $data = '{"party_name": "Test Party", "line1": "Test Street 1", "city": "Test City", "zip": "Zippy", "country": "' . $country_code_identifier . '"}';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            curl_close($site);
            $message = curl_error($site) ?: $this->translator->translate('curl.store.cove.api.get.legal.entity.id.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->viewRenderer->render('curl/api_result', $parameters);
    }

    /**
     * Use curL to call the store_cove api ... 1.1.4b Create a Sender: Create an Identifier
     * Related logic: see config\common\routes\routes.php api-store-cove-legal-entity-identifier
     * Related logic: see https://www.storecove.com/docs/
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_call_api_legal_entity_identifier(): \Yiisoft\DataResponse\DataResponse
    {
        // Obtain from above function store_cove_call_api_legal_entity()
        // store-cove regex: ^GB(\d{9}(\d{3})?$|^[A-Z]{2}\d{3})$ will match eg. GB000123456

        // eg. GB obtained from setting view storecove
        $legal = $this->sR->getSetting('storecove_country');
        // Must be a 9 digit number including preceding zeros or a 12 digit number
        // eg. 000217688
        $id = '000217793';
        $scheme_tax_identifier = 'GB:VAT';
        $combo_id = $legal . $id;
        $store_cove = "https://api.storecove.com/api/v2/legal_entities/$id/peppol_identifiers";
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->sR->getSetting('gateway_storecove_apiKey'));
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $site = curl_init();
        if ($site != false) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, ['Accept: application/json',"Authorization: Bearer $api_key_here",'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            $data = '{"superscheme": "iso6523-actorid-upis", "scheme": "' . $scheme_tax_identifier . '", "identifier": "' . $combo_id . '"}';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            curl_close($site);
            $message = curl_error($site) ?: $this->translator->translate('curl.store.cove.api.legal.entity.identifier.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->viewRenderer->render('curl/api_result', $parameters);
    }

    /**
     * Related logic: see https://app.storecove.com/en/docs #1.1.5 Send your first invoice .. Click on green button for json copy
     * Paste json copy into $data
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_send_test_json_invoice(): \Yiisoft\DataResponse\DataResponse
    {
        $store_cove = 'https://api.storecove.com/api/v2/document_submissions';
        // Remove zeros from '000217668' => integer'
        $legal_entity_id_as_integer = (int) $this->sR->getSetting('storecove_legal_entity_id');
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->sR->getSetting('gateway_storecove_apiKey'));
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $site = curl_init();
        if ($site != false) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, ['Accept: application/json',"Authorization: Bearer $api_key_here",'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            // World ie. GB,  to Germany a.k.a "World to DE"
            $data = '{
                "legalEntityId": ' . (string) $legal_entity_id_as_integer . ',
                "routing": {
                  "emails": [
                    "test@example.com"
                  ],
                  "eIdentifiers": [
                    {
                      "scheme": "DE:LWID",
                      "id": "10101010-STO-10"
                    }
                  ]
                },
                "document": {
                  "documentType": "invoice",
                  "invoice": {
                    "invoiceNumber": "202112007",
                    "issueDate": "2021-12-07",
                    "documentCurrencyCode": "EUR",
                    "taxSystem": "tax_line_percentages",
                    "accountingCustomerParty": {
                      "party": {
                        "companyName": "ManyMarkets Inc.",
                        "address": {
                          "street1": "Street 123",
                          "zip": "1111AA",
                          "city": "Here",
                          "country": "DE"
                        }
                      },
                      "publicIdentifiers": [
                        {
                          "scheme": "DE:LWID",
                          "id": "10101010-STO-10"
                        }
                      ]
                    },
                    "invoiceLines": [
                      {
                        "description": "The things you purchased",
                        "amountExcludingVat": 10,
                        "tax": {
                          "percentage": 0,
                          "category": "export",
                          "country": "DE"
                        }
                      }
                    ],
                    "taxSubtotals": [
                      {
                        "percentage": 0,
                        "category": "export",
                        "country": "DE",
                        "taxableAmount": 10,
                        "taxAmount": 0
                      }
                    ],
                    "paymentMeansArray": [
                      {
                        "account": "NL50ABNA0552321249",
                        "holder": "Storecove",
                        "code": "credit_transfer"
                      }
                    ],
                    "amountIncludingVat": 10
                  }
                }
            }';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            curl_close($site);
            $message = curl_error($site) ?: $this->translator->translate('curl.store.cove.api.send.test.json.invoice.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->viewRenderer->render('curl/api_result', $parameters);
    }

    public function store_cove_send_actual_json_invoice(): \Yiisoft\DataResponse\DataResponse
    {
        $store_cove = 'https://api.storecove.com/api/v2/document_submissions';
        // Remove zeros from '000217668' => integer'
        $legal_entity_id_as_integer = (int) $this->sR->getSetting('storecove_legal_entity_id');
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->sR->getSetting('gateway_storecove_apiKey'));
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $site = curl_init();
        if ($site != false) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, ['Accept: application/json',"Authorization: Bearer $api_key_here",'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            // World ie. GB,  to Germany a.k.a "World to DE"
            $data = '{
                "legalEntityId": ' . (string) $legal_entity_id_as_integer . ',
                "routing": {
                  "emails": [
                    "test@example.com"
                  ],
                  "eIdentifiers": [
                    {
                      "scheme": "DE:LWID",
                      "id": "10101010-STO-10"
                    }
                  ]
                },
                "document": {
                  "documentType": "invoice",
                  "invoice": {
                    "invoiceNumber": "202112007",
                    "issueDate": "2021-12-07",
                    "documentCurrencyCode": "EUR",
                    "taxSystem": "tax_line_percentages",
                    "accountingCustomerParty": {
                      "party": {
                        "companyName": "ManyMarkets Inc.",
                        "address": {
                          "street1": "Street 123",
                          "zip": "1111AA",
                          "city": "Here",
                          "country": "DE"
                        }
                      },
                      "publicIdentifiers": [
                        {
                          "scheme": "DE:LWID",
                          "id": "10101010-STO-10"
                        }
                      ]
                    },
                    "invoiceLines": [
                      {
                        "description": "The things you purchased",
                        "amountExcludingVat": 10,
                        "tax": {
                          "percentage": 0,
                          "category": "export",
                          "country": "DE"
                        }
                      }
                    ],
                    "taxSubtotals": [
                      {
                        "percentage": 0,
                        "category": "export",
                        "country": "DE",
                        "taxableAmount": 10,
                        "taxAmount": 0
                      }
                    ],
                    "paymentMeansArray": [
                      {
                        "account": "NL50ABNA0552321249",
                        "holder": "Storecove",
                        "code": "credit_transfer"
                      }
                    ],
                    "amountIncludingVat": 10
                  }
                }
            }';

            $data = '{
                "legalEntityId": 100000099999,
                "idempotencyGuid": "61b37456-5f9e-4d56-b63b-3b1a23fa5c73",
                "routing": {
                  "eIdentifiers": [
                    {
                      "scheme": "NL:KVK",
                      "id": "27375186"
                    }
                  ],
                  "emails": [
                    "receiver@example.com"
                  ],
                  "workflow": "full"
                },
                "attachments": [
                  {
                    "filename": "myname.pdf",
                    "document": "JVBERi0xLjIgCjkgMCBvYmoKPDwKPj4Kc3RyZWFtCkJULyAzMiBUZiggIFlPVVIgVEVYVCBIRVJFICAgKScgRVQKZW5kc3RyZWFtCmVuZG9iago0IDAgb2JqCjw8Ci9UeXBlIC9QYWdlCi9QYXJlbnQgNSAwIFIKL0NvbnRlbnRzIDkgMCBSCj4+CmVuZG9iago1IDAgb2JqCjw8Ci9LaWRzIFs0IDAgUiBdCi9Db3VudCAxCi9UeXBlIC9QYWdlcwovTWVkaWFCb3ggWyAwIDAgMjUwIDUwIF0KPj4KZW5kb2JqCjMgMCBvYmoKPDwKL1BhZ2VzIDUgMCBSCi9UeXBlIC9DYXRhbG9nCj4+CmVuZG9iagp0cmFpbGVyCjw8Ci9Sb290IDMgMCBSCj4+CiUlRU9G",
                    "mimeType": "application/pdf",
                    "primaryImage": false,
                    "documentId": "myId",
                    "description": "A Description"
                  }
                ],
                "document": {
                  "documentType": "invoice",
                  "invoice": {
                    "taxSystem": "tax_line_percentages",
                    "documentCurrency": "EUR",
                    "invoiceNumber": "F463333333336",
                    "issueDate": "2020-11-26",
                    "taxPointDate": "2020-11-26",
                    "dueDate": "2020-12-26",
                    "invoicePeriod": "2020-11-12 - 2020-11-17",
                    "references": [
                      {
                        "documentType": "purchase_order",
                        "documentId": "buyer reference or purchase order reference is recommended",
                        "lineId": "1",
                        "issueDate": "2021-12-01"
                      },
                      {
                        "documentType": "buyer_reference",
                        "documentId": "buyer reference or purchase order reference is recommended"
                      },
                      {
                        "documentType": "sales_order",
                        "documentId": "R06788111"
                      },
                      {
                        "documentType": "billing",
                        "documentId": "refers to a previous invoice"
                      },
                      {
                        "documentType": "contract",
                        "documentId": "contract123"
                      },
                      {
                        "documentType": "despatch_advice",
                        "documentId": "DDT123"
                      },
                      {
                        "documentType": "receipt",
                        "documentId": "aaaaxxxx"
                      },
                      {
                        "documentType": "originator",
                        "documentId": "bbbbyyyy"
                      }
                    ],
                    "accountingCost": "23089",
                    "note": "This is the invoice note. Senders can enter free text. This may not be read by the receiver, so it is not encouraged to use this.",
                    "accountingSupplierParty": {
                      "party": {
                        "contact": {
                          "email": "sender@company.com",
                          "firstName": "Jony",
                          "lastName": "Ponski",
                          "phone": "088-333333333"
                        }
                      }
                    },
                    "accountingCustomerParty": {
                      "publicIdentifiers": [
                        {
                          "scheme": "NL:KVK",
                          "id": "27375186"
                        },
                        {
                          "scheme": "NL:VAT",
                          "id": "NL999999999B01"
                        }
                      ],
                      "party": {
                        "companyName": "Receiver Company",
                        "address": {
                          "street1": "Streety 123",
                          "street2": null,
                          "city": "Alphen aan den Rijn",
                          "zip": "2400 AA",
                          "county": null,
                          "country": "NL"
                        },
                        "contact": {
                          "email": "receiver@company.com",
                          "firstName": "Pon",
                          "lastName": "Johnson",
                          "phone": "088-444444444"
                        }
                      }
                    },
                    "delivery": {
                      "deliveryPartyName": "Delivered To Name",
                      "actualDeliveryDate": "2020-11-01",
                      "deliveryLocation": {
                        "id": "871690930000478611",
                        "schemeId": "EAN",
                        "address": {
                          "street1": "line1",
                          "street2": "line2",
                          "city": "CITY",
                          "zip": "3423423",
                          "county": "CA",
                          "country": "US"
                        }
                      }
                    },
                    "paymentTerms": {
                      "note": "For payment terms, only a note is supported by Peppol currently."
                    },
                    "paymentMeansArray": [
                      {
                        "code": "credit_transfer",
                        "account": "NL50RABO0162432445",
                        "paymentId": "44556677"
                      }
                    ],
                    "invoiceLines": [
                      {
                        "lineId": "1",
                        "amountExcludingVat": 2.88,
                        "itemPrice": 0.12332,
                        "baseQuantity": 2,
                        "quantity": 63,
                        "quantityUnitCode": "KWH",
                        "allowanceCharges": [
                          {
                            "reason": "special discount",
                            "amountExcludingTax": -0.25
                          },
                          {
                            "reason": "even more special discount",
                            "amountExcludingTax": -0.75
                          }
                        ],
                        "tax": {
                          "percentage": 21,
                          "country": "NL",
                          "category": "standard"
                        },
                        "orderLineReferenceLineId": "3",
                        "accountingCost": "23089",
                        "name": "Supply peak",
                        "description": "Supply",
                        "invoicePeriod": "2020-11-12 - 2020-11-17",
                        "note": "Only half the story...",
                        "references": [],
                        "buyersItemIdentification": "9 008 115",
                        "sellersItemIdentification": "E_DVK_PKlik_KVP_LP",
                        "standardItemIdentification": "8718868597083",
                        "standardItemIdentificationSchemeId": "GTIN",
                        "additionalItemProperties": [
                          {
                            "name": "UtilityConsumptionPoint",
                            "value": "871690930000222221"
                          },
                          {
                            "name": "UtilityConsumptionPointAddress",
                            "value": "VE HAZERSWOUDE-XXXXX"
                          }
                        ]
                      }
                    ],
                    "allowanceCharges": [
                      {
                        "reason": "late payment",
                        "amountExcludingTax": 10.2,
                        "tax": {
                          "percentage": 21,
                          "country": "NL",
                          "category": "standard"
                        }
                      }
                    ],
                    "taxSubtotals": [
                      {
                        "taxableAmount": 13.08,
                        "taxAmount": 2.75,
                        "percentage": 21,
                        "country": "NL"
                      }
                    ],
                    "amountIncludingVat": 15.83,
                    "prepaidAmount": 1
                  }
                }
              }';

            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            curl_close($site);
            $message = curl_error($site) ?: $this->translator->translate('curl.store.cove.api.setup.legal.entity.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->viewRenderer->render('curl/api_result', $parameters);
    }

    /**
     * @param SessionInterface $session
     * @param ClientRepository $cR
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @param InvRecurringRepository $irR
     * @param QuoteRepository $qR
     * @param QuoteAmountRepository $qaR
     * @param SettingRepository $sR
     * @param TaskRepository $taskR
     * @param ProjectRepository $prjctR
     * @param TranslatorInterface $translator
     */
    public function dashboard(
        ClientRepository $cR,
        InvRepository $iR,
        InvAmountRepository $iaR,
        InvRecurringRepository $irR,
        QuoteRepository $qR,
        QuoteAmountRepository $qaR,
        SettingRepository $sR,
        TaskRepository $taskR,
        ProjectRepository $prjctR,
        TranslatorInterface $translator,
    ): \Yiisoft\DataResponse\DataResponse {
        $data = [
            'alerts' => $this->alert(),
            // Repositories
            'iR' => $iR,
            'irR' => $irR,
            'qR' => $qR,
            'qaR' => $qaR,
            'iaR' => $iaR,

            // All invoices and quotes
            'invoices' => $iR->findAllPreloaded(),
            'overdueInvoices' => $iR->is_overdue(),
            'quotes' => $qR->findAllPreloaded(),

            // Totals for status eg. draft, sent, viewed...
            'invoice_status_totals' => $iaR->get_status_totals($iR, $sR, $translator, $sR->getSetting('invoice_overview_period') ?: 'this-month'),
            'quote_status_totals' => $qaR->get_status_totals($qR, $sR, $translator, $sR->getSetting('quote_status_period') ?: 'this-month'),

            // Array of statuses: draft, sent, viewed, paid, cancelled
            'invoice_statuses' => $iR->getStatuses($this->translator),

            // Array of statuses: draft, sent, viewed, approved, rejected, cancelled
            'quote_statuses' => $qR->getStatuses($this->translator),

            // this-month, last-month, this-quarter, lsat-quarter, this-year, last-year
            'invoice_status_period' => str_replace('-', '_', $sR->getSetting('invoice_overview_period')),

            // this-month, last-month, this-quarter, lsat-quarter, this-year, last-year
            'quote_status_period' => str_replace('-', '_', $sR->getSetting('quote_overview_period')),

            // Projects
            'projects' => $prjctR->findAllPreloaded(),

            // Current tasks
            'taskR' => $taskR,

            'modal_create_client' => $this->viewRenderer->renderPartialAsString('//invoice/client/modal_create_client'),

            'client_count' => $cR->count(),
        ];
        return $this->viewRenderer->render('dashboard/index', $data);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SessionInterface $session
     * @param SettingRepository $sR
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param PaymentMethodRepository $pmR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param GroupRepository $gR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function index(
        CurrentRoute $currentRoute,
        SessionInterface $session,
        SettingRepository $sR,
        TaxRateRepository $trR,
        UnitRepository $uR,
        FamilyRepository $fR,
        PaymentMethodRepository $pmR,
        ProductRepository $pR,
        ClientRepository $cR,
        GroupRepository $gR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        if ($this->userService->hasPermission('noEntryToBaseController')) {
            return $this->webService->getNotFoundResponse();
        }
        if (($sR->getSetting('debug_mode') == '1') && $this->userService->hasPermission('editInv')) {
            $this->flashMessage('info', $this->viewRenderer->renderPartialAsString('//invoice/info/invoice'));
        }
        $gR->repoCountAll() === 0 ? $this->install_default_invoice_and_quote_group($gR) : '';
        $pmR->count() === 0 ? $this->install_default_payment_methods($pmR) : '';
        // If you want to reinstall the default settings, remove the default_settings_exist setting => its count will be zero
        $sR->repoCount('default_settings_exist') === 0 ? $this->install_default_settings_on_first_run($session, $sR) : '';
        $this->install_check_for_preexisting_test_data($sR, $fR, $uR, $pR, $trR, $cR);
        $session->set('_language', $currentRoute->getArgument('_language'));
        $parameters = [
            'alerts' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param SettingRepository $sR
     * @param FamilyRepository $fR
     * @param UnitRepository $uR
     * @param ProductRepository $pR
     * @param TaxRateRepository $trR
     * @param ClientRepository $cR
     */
    private function install_check_for_preexisting_test_data(
        SettingRepository $sR,
        FamilyRepository $fR,
        UnitRepository $uR,
        ProductRepository $pR,
        TaxRateRepository $trR,
        ClientRepository $cR,
    ): void {
        // The setting install_test_data exists
        if ($sR->repoCount('install_test_data') === 1
                && $fR->repoTestDataCount() == 0
                && $uR->repoTestDataCount() == 0
                && $pR->repoTestDataCount() == 0
                // The setting install_test_data has been set to Yes in Settings...View
                && $sR->getSetting('install_test_data') === '1') {
            $this->install_test_data($trR, $uR, $fR, $pR, $cR);
        } else {
            // Test Data Already exists => Settings...View install_test_data must be set back to No
            $this->flashMessage('warning', $this->translator->translate('install.test.data.exists.already'));
            $setting = $sR->withKey('install_test_data');
            if ($setting) {
                $setting->setSetting_value('0');
                $sR->save($setting);
            }
        }
    }

    /**
     * @param array $default_settings
     * @param SettingRepository $sR
     */
    private function install_default_settings(array $default_settings, SettingRepository $sR): void
    {
        $this->remove_all_settings($sR);
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($default_settings as $key => $value) {
            $setting = new Setting();
            $setting->setSetting_key($key);
            /** @psalm-suppress RedundantCastGivenDocblockType */
            $setting->setSetting_value((string) $value);
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
    private function install_test_data(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR): void
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
    private function install(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR): void
    {
        // Tax
        $this->install_zero_rate($trR);
        $this->install_standard_rate($trR);
        // Unit
        $this->install_product_unit($uR);
        $this->install_service_unit($uR);
        // Family
        $this->install_product_family($fR);
        $this->install_service_family($fR);
        // Product
        $this->install_product($pR);
        $this->install_service($pR);
        // Client
        $this->install_foreign_client($cR);
        $this->install_non_foreign_client($cR);
    }

    /**
     * @param TaxRateRepository $trR
     */
    private function install_zero_rate(TaxRateRepository $trR): void
    {
        // Only allow two tax rates initially
        // These tax rates will not be deleted when test data is reset because they are defaults
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
    private function install_standard_rate(TaxRateRepository $trR): void
    {
        // Only allow two tax rates initially
        // These tax rates will not be deleted when test data is reset because they are defaults
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
    private function install_product_unit(UnitRepository $uR): void
    {
        $unit = new Unit();
        $unit->setUnit_name('unit');
        $unit->setUnit_name_plrl('units');
        $uR->save($unit);
    }

    /**
     * @param UnitRepository $uR
     */
    private function install_service_unit(UnitRepository $uR): void
    {
        $unit = new Unit();
        $unit->setUnit_name('service');
        $unit->setUnit_name_plrl('services');
        $uR->save($unit);
    }

    /**
     * @param FamilyRepository $fR
     */
    private function install_product_family(FamilyRepository $fR): void
    {
        $family = new Family();
        $family->setFamily_name('Product');
        $fR->save($family);
    }

    /**
     * @param FamilyRepository $fR
     */
    private function install_service_family(FamilyRepository $fR): void
    {
        $family = new Family();
        $family->setFamily_name('Service');
        $fR->save($family);
    }

    /**
     * @param ProductRepository $pR
     */
    private function install_product(ProductRepository $pR): void
    {
        $product = new Product();
        $product->setProduct_sku('12345678rgfyr');
        $product->setProduct_name('Tuch Padd');
        $product->setProduct_description('Description of Touch Pad');
        $product->setProduct_price(100.00);
        $product->setPurchase_price(30.00);
        $product->setProvider_name('We Provide');
        $product->setTax_rate_id(2);
        $product->setUnit_id(1);
        $product->setFamily_id(1);
        $product->setProduct_tariff(5);
        $pR->save($product);
    }

    /**
     * @param ProductRepository $pR
     */
    private function install_service(ProductRepository $pR): void
    {
        $service = new Product();
        $service->setProduct_sku('d234ds678rgfyr');
        $service->setProduct_name('Cleen Screans');
        $service->setProduct_description('Clean a screen');
        $service->setProduct_price(5.00);
        $service->setPurchase_price(0.00);
        $service->setProvider_name('Employee');
        // Zero => tax_rate_id => 1
        $service->setTax_rate_id(1);
        // Service => unit_id = 2; Product => unit_id = 1
        $service->setUnit_id(2);
        // Service => family_id 2; Product => family_id = 1
        $service->setFamily_id(2);
        $service->setProduct_tariff(3);
        $pR->save($service);
    }

    /**
     * @param ClientRepository $cR
     */
    private function install_foreign_client(ClientRepository $cR): void
    {
        $client = new Client();
        $client->setClient_active(true);
        $client->setClient_name('Foreign');
        $client->setClient_surname('Client');
        $client->setClient_email('email@email.com');
        $client->setClient_language('Japanese');
        $client->setClient_birthdate(new \DateTime());
        $client->setClient_gender(2);
        $cR->save($client);
    }

    /**
     * @param ClientRepository $cR
     */
    private function install_non_foreign_client(ClientRepository $cR): void
    {
        $client = new Client();
        $client->setClient_active(true);
        $client->setClient_name('Non');
        $client->setClient_surname('Foreign');
        $client->setClient_email('email@foreign.com');
        $client->setClient_language('English');
        $client->setClient_birthdate(new \DateTime());
        $client->setClient_gender(2);
        $cR->save($client);
    }

    /**
     * @param GroupRepository $gR
     */
    private function install_default_invoice_and_quote_group(GroupRepository $gR): void
    {
        $i_group = new Group();
        $i_group->setName('Invoice Group');
        $i_group->setIdentifier_format('INV{{{id}}}');
        $i_group->setNext_id(1);
        $i_group->setLeft_pad(0);
        $gR->save($i_group);

        $q_group = new Group();
        $q_group->setName('Quote Group');
        $q_group->setIdentifier_format('QUO{{{id}}}');
        $q_group->setNext_id(1);
        $q_group->setLeft_pad(0);
        $gR->save($q_group);

        $so_group = new Group();
        $so_group->setName('Sales Order Group');
        $so_group->setIdentifier_format('SO{{{id}}}');
        $so_group->setNext_id(1);
        $so_group->setLeft_pad(0);
        $gR->save($so_group);

        $icn_group = new Group();
        $icn_group->setName('Credit Note Group');
        $icn_group->setIdentifier_format('CN{{{id}}}');
        $icn_group->setNext_id(1);
        $icn_group->setLeft_pad(0);
        $gR->save($icn_group);
    }

    /**
     * @param PaymentMethodRepository $pmR
     */
    private function install_default_payment_methods(PaymentMethodRepository $pmR): void
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
        $customer_ready->setName('Card / Direct Debit - Customer Ready for Payment');
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
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('viewInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('invoice/index');
        }
        return $canEdit;
    }

    /**
     * @param SettingRepository $sR
     */
    private function remove_all_settings(SettingRepository $sR): void
    {
        // Completely remove any currently existing settings
        $settings = $sR->findAllPreloaded();
        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $sR->delete($setting);
        }
    }

    /**
     * @param SettingRepository $sR
     * @return Response
     */
    public function setting_reset(SettingRepository $sR): Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if ($canEdit) {
            $this->remove_all_settings($sR);
        }
        return $this->webService->getRedirectResponse('invoice/index');
    }

    /**
     * @param SettingRepository $sR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param QuoteRepository $qR
     * @param InvRepository $iR
     */
    public function test_data_remove(
        SettingRepository $sR,
        UnitRepository $uR,
        FamilyRepository $fR,
        ProductRepository $pR,
        ClientRepository $cR,
        QuoteRepository $qR,
        InvRepository $iR,
    ): \Yiisoft\DataResponse\DataResponse {
        $flash = '';
        if ($sR->repoCount('use_test_data') > 0 && $sR->getSetting('use_test_data') == '0') {
            // Only remove the test data if the user's test quotes and invoices have been removed FIRST else integrity constraint violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('first.reset');
            } else {
                // Note: The Tax Rates are not deleted because you must have at least one zero tax rate and one standard rate
                // for the quotes and invoices to function corrrectly
                $this->test_data_delete($uR, $fR, $pR, $cR);
                $flash = $this->translator->translate('deleted');
            }
        } else {
            // Settings...General...Install Test Data => change to 'no' before you remove the test data
            $flash = $this->translator->translate('install.test.data');
        }
        $data = [
            'alerts' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $data);
    }

    /**
     * @param SettingRepository $sR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param QuoteRepository $qR
     * @param InvRepository $iR
     * @param TaxRateRepository $trR
     */
    public function test_data_reset(
        SettingRepository $sR,
        UnitRepository $uR,
        FamilyRepository $fR,
        ProductRepository $pR,
        ClientRepository $cR,
        QuoteRepository $qR,
        InvRepository $iR,
        TaxRateRepository $trR,
    ): \Yiisoft\DataResponse\DataResponse {
        $flash = '';
        if ($sR->repoCount('install_test_data') > 0 && $sR->getSetting('install_test_data') == 1) {
            // Only remove the test data if the user's test quotes and invoices have been removed FIRST else integrity constraint violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('first.reset');
            } else {
                $this->test_data_delete($uR, $fR, $pR, $cR);
                $this->install_test_data($trR, $uR, $fR, $pR, $cR);
                $flash = $this->translator->translate('reset');
            }
        } else {
            $flash = $this->translator->translate('install.test.data');
        }
        $this->flashMessage('info', $flash);
        $data = [
            'alerts' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $data);
    }

    /**
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     */
    private function test_data_delete(UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR): void
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
