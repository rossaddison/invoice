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

// Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\DateHelper;
// Services and forms
use App\Invoice\Setting\SettingForm;
use App\Invoice\Setting\SettingService;
use App\Service\WebControllerService;
use App\User\UserService;

// Psr
use Psr\Http\Message\ResponseInterface as Response;

// Yiisoft
use Yiisoft\Form\FormHydrator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

use App\Invoice\Libraries\Crypt;

final class InvoiceController
{
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService; 
    private TranslatorInterface $translator;
    private SettingService $settingService;
    private SessionInterface $session;
    private SettingRepository $s;
    private Crypt $crypt;
    private FormHydrator $formHydrator;
           
    public function __construct(
        WebControllerService $webService, 
        UserService $userService, 
        TranslatorInterface $translator,
        SettingService $settingService,
        ViewRenderer $viewRenderer,
        SessionInterface $session,
        SettingRepository $s,
        Crypt $crypt,
        FormHydrator $formHydrator,
    )
    {
        $this->webService = $webService;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->settingService = $settingService;
        $this->viewRenderer = $viewRenderer;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->s = $s;
        $this->formHydrator = $formHydrator;
        $this->crypt = $crypt;
                
        // Authenticated (Signed Up) and Unauthorised (No Permissions)
        if (!$this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                               ->withLayout('@views/layout/guest.php');
            $this->flash_message('info' , $this->translator->translate('invoice.permission.unauthorised'));
        }
        
        // Client: Authenticated and Authorised (Permission: viewInv)
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                               ->withLayout('@views/layout/guest.php');
            $this->flash_message('info' , $this->translator->translate('invoice.permission.authorised.view'));
        }
        
        // Administrator: Authenticated and Authorised (Permission: editInv)
        if ($this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/invoice.php');
            $this->flash_message('info' , $this->translator->translate('invoice.permission.authorised.edit')); 
        }
    }
    
   /**
    * @return string
    */
     private function alert(): string {
       return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
       [ 
         'flash' => $this->flash,
         'errors' => [],
       ]);
     }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function faq(CurrentRoute $currentRoute) : Response 
    {
        switch ($currentRoute->getArgument('topic')) {
            case 'tp':
                $view = $this->viewRenderer->renderPartialAsString('/invoice/info/taxpoint');
                break;
            case 'shared':
                $view = $this->viewRenderer->renderPartialAsString('/invoice/info/shared_hosting');
                break;
            default:
                $view = '';
                break;
        }
        return $this->viewRenderer->render('/invoice/info/view',['topic'=> $view]);
    }
    
    /**
     * Use curL to call the store_cove api ... 1.1.3. Make your first API call
     * Tab: ERP or Accounting System, NOT: Individual Company, NOT: Reseller or Systems Integrator
     * @see config\common\routes\routes.php api-store-cove
     * @see https://www.storecove.com/docs 3.3.2. Sending a document UBL format
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_call_api() : \Yiisoft\DataResponse\DataResponse 
    {
        $store_cove = "https://api.storecove.com/api/v2/discovery/receives";
        // 1.1.2 : Create a new API key by clicking the "Create New API Key" button. For the Integrator package, create a "Master" key.
        /** 
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->s->get_setting('gateway_storecove_apiKey')); 
        $site = curl_init();
        curl_setopt($site, CURLOPT_URL, $store_cove);
        curl_setopt($site, CURLOPT_POST, true);
        curl_setopt($site, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($site, CURLOPT_HTTPHEADER, ["Accept: application/json","Authorization: Bearer $api_key_here","Content-Type: application/json"]);
        curl_setopt($site, CURLOPT_HEADER, true); 
        /**
         * @see https://www.storecove.com/docs/#_getting_started 1.1.3. Make your first API call 
         */
        $data = '{"documentTypes": ["invoice"], "network": "peppol", "metaScheme": "iso6523-actorid-upis", "scheme": "nl:kvk", "identifier":"60881119"}';
        curl_setopt($site, CURLOPT_POSTFIELDS, $data);
        curl_close($site);
        $message = curl_error($site) ? curl_error($site) : 
        $this->translator->translate('invoice.curl.store.cove.api.setup.successful');
        $parameters = [
           'result' => curl_exec($site),
           'message' => $message,
           'status' => curl_error($site) ? 'warning' : 'success'
        ];
        return $this->viewRenderer->render('/invoice/curl/api_result', $parameters);
    }
    
    /**
     * Use curL to call the store_cove api ... 1.1.4a. Create a sender: Get the Legal Entity Id
     * @see config\common\routes\routes.php api-store-cove-get-legal-entity-id
     * @see https://www.storecove.com/docs/
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_call_api_get_legal_entity_id() : \Yiisoft\DataResponse\DataResponse 
    {
        $store_cove = "https://api.storecove.com/api/v2/legal_entities";
        /** 
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->s->get_setting('gateway_storecove_apiKey'));
        $site = curl_init();
        curl_setopt($site, CURLOPT_URL, $store_cove);
        curl_setopt($site, CURLOPT_POST, true);
        curl_setopt($site, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($site, CURLOPT_HTTPHEADER, ["Accept: application/json","Authorization: Bearer $api_key_here","Content-Type: application/json"]);
        curl_setopt($site, CURLOPT_HEADER, true);
        $country_code_identifier = 'GB';
        $data = '{"party_name": "Test Party", "line1": "Test Street 1", "city": "Test City", "zip": "Zippy", "country": "'.$country_code_identifier.'"}';
        curl_setopt($site, CURLOPT_POSTFIELDS, $data);
        curl_close($site);
        $message = curl_error($site) ? curl_error($site) : 
        $this->translator->translate('invoice.curl.store.cove.api.get.legal.entity.id.successful');
        $parameters = [
           'result' => curl_exec($site),
           'message' => $message,
           'status' => curl_error($site) ? 'warning' : 'success'
        ];
        return $this->viewRenderer->render('/invoice/curl/api_result', $parameters);
    }
    
    /**
     * Use curL to call the store_cove api ... 1.1.4b Create a Sender: Create an Identifier
     * @see config\common\routes\routes.php api-store-cove-legal-entity-identifier
     * @see https://www.storecove.com/docs/
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_call_api_legal_entity_identifier() : \Yiisoft\DataResponse\DataResponse 
    {
        // Obtain from above function store_cove_call_api_legal_entity()
        // store-cove regex: ^GB(\d{9}(\d{3})?$|^[A-Z]{2}\d{3})$ will match eg. GB000123456
        
        // eg. GB obtained from setting view storecove
        $legal = $this->s->get_setting('storecove_country');
        // Must be a 9 digit number including preceding zeros or a 12 digit number
        // eg. 000217688
        $id = '000217793';
        $scheme_tax_identifier = 'GB:VAT';
        $combo_id = $legal.$id;
        $store_cove = "https://api.storecove.com/api/v2/legal_entities/$id/peppol_identifiers";
        /** 
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->s->get_setting('gateway_storecove_apiKey'));
        $site = curl_init();
        curl_setopt($site, CURLOPT_URL, $store_cove);
        curl_setopt($site, CURLOPT_POST, true);
        curl_setopt($site, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($site, CURLOPT_HTTPHEADER, ["Accept: application/json","Authorization: Bearer $api_key_here","Content-Type: application/json"]);
        curl_setopt($site, CURLOPT_HEADER, true); 
        $data = '{"superscheme": "iso6523-actorid-upis", "scheme": "'.$scheme_tax_identifier.'", "identifier": "'.$combo_id.'"}';
        curl_setopt($site, CURLOPT_POSTFIELDS, $data);
        curl_close($site);
        $message = curl_error($site) ? curl_error($site) : 
        $this->translator->translate('invoice.curl.store.cove.api.legal.entity.identifier.successful');
        $parameters = [
           'result' => curl_exec($site),
           'message' => $message,
           'status' => curl_error($site) ? 'warning' : 'success'
        ];
        return $this->viewRenderer->render('/invoice/curl/api_result', $parameters);
    }
    
    /**
     * @see https://app.storecove.com/en/docs #1.1.5 Send your first invoice .. Click on green button for json copy
     * Paste json copy into $data
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function store_cove_send_test_json_invoice() : \Yiisoft\DataResponse\DataResponse 
    {
        $store_cove = "https://api.storecove.com/api/v2/document_submissions";
        // Remove zeros from '000217668' => integer'
        $legal_entity_id_as_integer = (int)$this->s->get_setting('storecove_legal_entity_id');
        /** 
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->s->get_setting('gateway_storecove_apiKey'));
        $site = curl_init();
        curl_setopt($site, CURLOPT_URL, $store_cove);
        curl_setopt($site, CURLOPT_POST, true);
        curl_setopt($site, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($site, CURLOPT_HTTPHEADER, ["Accept: application/json","Authorization: Bearer $api_key_here","Content-Type: application/json"]);
        curl_setopt($site, CURLOPT_HEADER, true); 
        // World ie. GB,  to Germany a.k.a "World to DE"     
        $data = '{
            "legalEntityId": '.$legal_entity_id_as_integer.',
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
        $message = curl_error($site) ? curl_error($site) : 
        $this->translator->translate('invoice.curl.store.cove.api.send.test.json.invoice.successful');
        $parameters = [
           'result' => curl_exec($site),
           'message' => $message,
           'status' => curl_error($site) ? 'warning' : 'success'
        ];
        return $this->viewRenderer->render('/invoice/curl/api_result', $parameters);
    }
    
    public function store_cove_send_actual_json_invoice() : \Yiisoft\DataResponse\DataResponse 
    {
        $store_cove = "https://api.storecove.com/api/v2/document_submissions";
        // Remove zeros from '000217668' => integer'
        $legal_entity_id_as_integer = (int)$this->s->get_setting('storecove_legal_entity_id');
        /** 
         * @var mixed $api_key_here
         */
        $api_key_here = $this->crypt->decode($this->s->get_setting('gateway_storecove_apiKey'));
        $site = curl_init();
        curl_setopt($site, CURLOPT_URL, $store_cove);
        curl_setopt($site, CURLOPT_POST, true);
        curl_setopt($site, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($site, CURLOPT_HTTPHEADER, ["Accept: application/json","Authorization: Bearer $api_key_here","Content-Type: application/json"]);
        curl_setopt($site, CURLOPT_HEADER, true); 
        // World ie. GB,  to Germany a.k.a "World to DE"     
        $data = '{
            "legalEntityId": '.$legal_entity_id_as_integer.',
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
        $message = curl_error($site) ? curl_error($site) : 
        $this->translator->translate('invoice.curl.store.cove.api.setup.legal.entity.successful');
        $parameters = [
           'result' => curl_exec($site),
           'message' => $message,
           'status' => curl_error($site) ? 'warning' : 'success'
        ];
        return $this->viewRenderer->render('/invoice/curl/api_result', $parameters);
    }
        
    /**
     * @param SessionInterface $session
     * @param ClientRepository $cR
     * @param GroupRepository $gR
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @param InvRecurringRepository $irR
     * @param QuoteRepository $qR
     * @param QuoteAmountRepository $qaR
     * @param SettingRepository $sR
     * @param TaskRepository $taskR
     * @param ProjectRepository $prjctR
     */
    public function dashboard (
                               ClientRepository $cR,
                               GroupRepository $gR,
                               InvRepository $iR,
                               InvAmountRepository $iaR,
                               InvRecurringRepository $irR,
                               QuoteRepository $qR,
                               QuoteAmountRepository $qaR,
                               SettingRepository $sR,
                               TaskRepository $taskR,
                               ProjectRepository $prjctR
                              ) : \Yiisoft\DataResponse\DataResponse {
        $data = [
            'alerts'=>$this->alert(),
            'clienthelper'=>new ClientHelper($sR),
            // Repositories
            'irR'=>$irR,
            'qaR'=>$qaR,
            'iaR'=>$iaR,
            
            // All invoices and quotes
            'invoices'=>$iR->findAllPreloaded(),
            'quotes'=>$qR->findAllPreloaded(),
            
            // Totals for status eg. draft, sent, viewed...
            'invoice_status_totals'=>$iaR->get_status_totals($iR, $sR, $sR->get_setting('invoice_overview_period') ?: 'this-month'),
            'quote_status_totals'=>$qaR->get_status_totals($qR, $sR, $sR->get_setting('quote_status_period') ?: 'this-month'),
            
            // Array of statuses: draft, sent, viewed, paid, cancelled
            'invoice_statuses'=>$iR->getStatuses($sR),
            
            // Array of statuses: draft, sent, viewed, approved, rejected, cancelled
            'quote_statuses'=>$qR->getStatuses($sR),
            
            // this-month, last-month, this-quarter, lsat-quarter, this-year, last-year
            'invoice_status_period'=>str_replace('-', '_', $sR->get_setting('invoice_overview_period')),
            
            // this-month, last-month, this-quarter, lsat-quarter, this-year, last-year
            'quote_status_period'=>str_replace('-', '_', $sR->get_setting('quote_overview_period')),
            
            // Projects
            'projects'=>$prjctR->findAllPreloaded(),
            
            // Current tasks
            'tasks'=>$taskR->findAllPreloaded(),
            
            'task_statuses'=>$taskR->getTask_statuses($sR),
            
            'modal_create_client'=>$this->viewRenderer->renderPartialAsString('/invoice/client/modal_create_client',[
                'datehelper'=> new DateHelper($sR)
            ]),            
            'modal_create_quote'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/modal_create_quote',[
                'clients'=>$cR->findAllPreloaded(),
                'invoice_groups'=>$gR->findAllPreloaded(),
                'datehelper'=> new DateHelper($sR)
            ]),
            'modal_create_inv'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/modal_create_inv',[
                'clients'=>$cR->findAllPreloaded(),
                'invoice_groups'=>$gR->findAllPreloaded(),
                'datehelper'=> new DateHelper($sR)
            ]),
            'client_count' =>$cR->count(), 
        ];
        return $this->viewRenderer->render('/invoice/dashboard/index',$data);
    }
    
    /**
     * 
     * @param string $drop_down_locale
     * @param SettingRepository $sR
     * @return void
     */
    private function cldr(string $drop_down_locale, SettingRepository $sR) : void {
        $cldr = $sR->withKey('cldr');
        if ($cldr) {
            $cldr->setSetting_value($drop_down_locale);
            $sR->save($cldr);
        }        
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
     $this->flash->removeAll(); 
     $this->flash->add($level, $message, true);
      return $this->flash;
    }
    
    /**
     * @param SessionInterface $session
     * @param SettingRepository $sR
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param PaymentMethodRepository $pmR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param GroupRepository $gR
     */
    public function index(CurrentRoute $currentRoute,
                          SessionInterface $session, 
                          SettingRepository $sR,
                          TaxRateRepository $trR,
                          UnitRepository $uR,
                          FamilyRepository $fR,
                          PaymentMethodRepository $pmR,
                          ProductRepository $pR,
                          ClientRepository $cR,
                          GroupRepository $gR
                         ): \Yiisoft\DataResponse\DataResponse {
        if (($sR->get_setting('debug_mode') == '1') && ($this->userService->hasPermission('editInv'))) {
            $this->flash_message('info' , $this->viewRenderer->renderPartialAsString('/invoice/info/invoice'));
        }    
        $gR->repoCountAll() === 0 ? $this->install_default_invoice_and_quote_group($gR) : '';
        $pmR->count() === 0 ? $this->install_default_payment_methods($pmR) : '';
        // If you want to reinstall the default settings, remove the default_settings_exist setting => its count will be zero
        $sR->repoCount('default_settings_exist') === 0 ? $this->install_default_settings_on_first_run($session, $sR) : '';
        $this->install_check_for_preexisting_test_data($sR, $fR, $uR, $pR, $trR, $cR); 
        // The cldr is saved from the $session->get('_language') parameter in the Invoice/Layout/main.php file as soon as the user changes the locale.
        // The below line is an additional line of code to ensure that the locale (session runtime file) is saved to database 'cldr' and may be removed in future. 
        // The cldr setting is not accessible in non debug mode by means of the tab-index
        //$sR->repoCount('cldr') === 1 && $sR->get_setting('cldr') !== (string)$session->get('_language') ? $this->cldr((string)$session->get('_language') ?: $currentRoute->getArgument('_language'),$sR) : '';
        $session->set('_language', $currentRoute->getArgument('_language'));
        $parameters = [
            'alerts'=> $this->alert(),
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
     * @return void
     */
    private function install_check_for_preexisting_test_data(
        SettingRepository $sR, 
        FamilyRepository $fR, 
        UnitRepository $uR, 
        ProductRepository $pR, 
        TaxRateRepository $trR, 
        ClientRepository $cR) : void {
        // The setting install_test_data exists
        if ($sR->repoCount('install_test_data') === 1 
                && $fR->repoTestDataCount() == 0
                && $uR->repoTestDataCount() == 0
                && $pR->repoTestDataCount() == 0
                // The setting install_test_data has been set to Yes in Settings...View
                && $sR->get_setting('install_test_data') === '1') {
                $this->install_test_data($trR, $uR, $fR, $pR, $cR, $sR);
        } else {
                // Test Data Already exists => Settings...View install_test_data must be set back to No
                $this->flash_message('warning', $this->translator->translate('invoice.install.test.data.exists.already'));
                $setting = $sR->withKey('install_test_data');
                if ($setting) {
                    $setting->setSetting_value('0');
                    $sR->save($setting);
                }
        }
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param SettingRepository $sR
     * @return void
     */
    private function install_default_settings_on_first_run(SessionInterface $session, SettingRepository $sR) : void {
        $default_settings = [
            //*************************************************************************************//
            // Remove the 'default_settings_exist' setting from the settings table by manually     //
            // going into the mysql database table 'settings' and deleting it. This will remove &  //
            // reinstall the default settings listed below. The above index function will check    //
            // whether this setting exists. If not THIS function will be run.                      //
            // CAUTION: THIS WILL ALSO REMOVE ALL THE SETTINGS INCLUDING SECRET KEYS
            //*************************************************************************************//
            'default_settings_exist'=> '1',                  
            'cldr'=> $session->get('_language') ?? 'en',
            'cron_key' => Random::string(32),
            'currency_symbol' => '£',
            'currency_symbol_placement' => 'before',
            // default payment gateway currency code
            'currency_code' => 'GBP',
            'currency_code_from' => 'GBP',
            'currency_code_to' => 'GBP',
            'custom_title' => 'Yii-invoice',
            'date_format' => 'd/m/Y', 
            'decimal_point' => '.',
            'default_invoice_group' => 1,
            'default_quote_group' => 2,
            'default_invoice_terms' => '',
            'default_sales_order_group' => 3,
            'default_language' => $sR->get_folder_language() ?: 'English', 
            //paginator list limit
            'default_list_limit'=>120, 
            // Prevent documents from being made non-editable. By default documents are made non-editable
            // according to the read_only_toggle which is set at sent ie 2.
            // By default this setting is on 0 ie. Invoices can be made read-only (through the 
            // read_only_toggle)
            'disable_read_only'=> 0,
            'disable_sidebar' => 1,
            // Invoice deletion by Law is not allowed. Invoices have to be cancelled with a credit invoice.
            'enable_invoice_deletion'=>true,
            'enable_peppol_client_defaults'=> 1,
            'enable_vat_registration'=> 0,
            // Archived pdfs are automatically sent to customers from view/invoice...Options...Send
            // The pdf is sent along with the attachment to the invoice on the view/invoice.
            'email_pdf_attachment' => 1,
            'generate_invoice_number_for_draft' => 1,
            'generate_quote_number_for_draft' => 1,
            'generate_so_number_for_draft' => 1,
            'install_test_data'=>0,            
            //1 => None, 2 => Cash, 3 => Cheque, 4 => Card/Direct Debit - Succeeded
            //5 => Card/Direct Debit - Processing 6 => Card/Direct Debit - Customer Ready 
            'invoice_default_payment_method'=>1,
            'invoices_due_after' => 30,
            'invoice_logo' => 'favicon.ico',
            'mark_invoices_sent_copy' => 0,
            'mpdf_ltr' => 1,
            'mpdf_cjk' => 1,
            'mpdf_auto_script_to_lang' => 1,
            'mpdf_auto_vietnamese' => 1,
            'mpdf_auto_arabic' => 1,
            'mpdf_allow_charset_conversion' => 1,
            'mpdf_auto_language_to_font' => 1,
            'mpdf_show_image_errors' => 1,
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
            'sumex_canton' => 1,
            'sumex_role' => 1,
            'sumex_place' => 1,
            'tax_rate_decimal_places' => 3, 
            'thousands_separator' => ',',
            'time_zone' => 'Europe/London'
        ]; 
        $this->install_default_settings($default_settings, $sR);        
    }
    
    /**
     * 
     * @param array $default_settings
     * @param SettingRepository $sR
     * @return void
     */
    private function install_default_settings(array $default_settings, SettingRepository $sR) : void
    {
        $this->remove_all_settings($sR);        
        /** 
         * @var string $key
         * @var string $value
         */
        foreach ($default_settings as $key => $value) {
            $form = new SettingForm();
            $array = [
                'setting_key'=>$key,
                'setting_value'=>$value,
            ];
            if ($this->formHydrator->populate($form, $array)) {
                $this->settingService->saveSetting(new Setting(), $form);
            }
        }    
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param SettingRepository $sR
     * @return void
     */
    private function install_test_data(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR, SettingRepository $sR) : void {
        $this->install($trR, $uR, $fR, $pR, $cR, $sR);
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param SettingRepository $sR
     * @return void
     */
    private function install(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR, SettingRepository $sR) : void {
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
        $this->install_product($trR, $uR, $fR, $pR);
        $this->install_service($trR, $uR, $fR, $pR);
        // Client
        $this->install_foreign_client($cR, $sR);
        $this->install_non_foreign_client($cR, $sR);
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @return void
     */
    private function install_zero_rate(TaxRateRepository $trR) : void {
        // Only allow two tax rates initially
        // These tax rates will not be deleted when test data is reset because they are defaults
        if ($trR->repoCountAll() < 2) {
            $tax_rate = new TaxRate();
            $tax_rate->setTax_rate_name('Zero');
            $tax_rate->setTax_rate_percent(0);
            $tax_rate->setTax_rate_default(false);
            $trR->save($tax_rate);
        }    
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @return void
     */
    private function install_standard_rate(TaxRateRepository $trR) : void {
        // Only allow two tax rates initially        
        // These tax rates will not be deleted when test data is reset because they are defaults
        if ($trR->repoCountAll() < 2) {
            $tax_rate = new TaxRate();
            $tax_rate->setTax_rate_name('Standard');
            $tax_rate->setTax_rate_percent(20);
            $tax_rate->setTax_rate_default(true);
            $trR->save($tax_rate);
        }    
    }
    
    /**
     * 
     * @param UnitRepository $uR
     * @return void
     */
    private function install_product_unit(UnitRepository $uR) : void {
        $unit = new Unit();
        $unit->setUnit_name('unit');
        $unit->setUnit_name_plrl('units');
        $uR->save($unit);
    }
    
    /**
     * 
     * @param UnitRepository $uR
     * @return void
     */
    private function install_service_unit(UnitRepository $uR) : void {
        $unit = new Unit();
        $unit->setUnit_name('service');
        $unit->setUnit_name_plrl('services');
        $uR->save($unit);
    }
    
    /**
     * 
     * @param FamilyRepository $fR
     * @return void
     */
    private function install_product_family(FamilyRepository $fR) : void {
        $family = new Family();
        $family->setFamily_name('Product');
        $fR->save($family);
    }
    
    /**
     * 
     * @param FamilyRepository $fR
     * @return void
     */
    private function install_service_family(FamilyRepository $fR) : void {
        $family = new Family();
        $family->setFamily_name('Service');
        $fR->save($family);
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @return void
     */
    private function install_product(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR) : void {
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
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @return void
     */
    private function install_service(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR) : void {
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
     * 
     * @param ClientRepository $cR
     * @param SettingRepository $s
     * @return void
     */
    private function install_foreign_client(ClientRepository $cR, SettingRepository $s) : void {
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
     * 
     * @param ClientRepository $cR
     * @param SettingRepository $s
     * @return void
     */
    private function install_non_foreign_client(ClientRepository $cR, SettingRepository $s) : void {
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
     * 
     * @param GroupRepository $gR
     * @return void
     */
    private function install_default_invoice_and_quote_group(GroupRepository $gR) : void {
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
    }
    
    /**
     * 
     * @param PaymentMethodRepository $pmR
     * @return void
     */    
    private function install_default_payment_methods(PaymentMethodRepository $pmR) : void {
        // 1
        $pm_none = new PaymentMethod();
        $pm_none->setName('None');
        $pmR->save($pm_none);
        // 2 
        $pm_cash = new PaymentMethod();
        $pm_cash->setName('Cash');
        $pmR->save($pm_cash);
        // 3
        $pm_cheque = new PaymentMethod();
        $pm_cheque->setName('Cheque');
        $pmR->save($pm_cheque);
        // 4
        $pm_succeeded = new PaymentMethod();
        $pm_succeeded->setName('Card / Direct Debit - Payment Succeeded');
        $pmR->save($pm_succeeded);
        // 5
        $pm_processing = new PaymentMethod();
        $pm_processing->setName('Card / Direct Debit - Payment Processing');
        $pmR->save($pm_processing);
        // 6
        $pm_unsuccessful = new PaymentMethod();
        $pm_unsuccessful->setName('Card / Direct Debit - Payment Unsuccessful');
        $pmR->save($pm_unsuccessful);
        // 7
        $customer_ready = new PaymentMethod();
        $customer_ready->setName('Card / Direct Debit - Customer Ready for Payment');
        $pmR->save($customer_ready);
        // 8
        $peppol_access_point = new PaymentMethod();
        $peppol_access_point->setName('Peppol Access Point');
        $pmR->save($peppol_access_point);
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('viewInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('invoice/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param SettingRepository $sR
     * @return void
     */
    private function remove_all_settings(SettingRepository $sR): void {
        // Completely remove any currently existing settings
        $settings = $sR->findAllPreloaded();
        /** @var Setting $setting */
        foreach ($settings as $setting) {
           $sR->delete($setting);
        }
    }
    
    /**
     * 
     * @param SettingRepository $sR
     * @return Response
     */
    public function setting_reset(SettingRepository $sR): Response{
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
    public function test_data_remove(SettingRepository $sR,
                                    UnitRepository $uR,
                                    FamilyRepository $fR,
                                    ProductRepository $pR,
                                    ClientRepository $cR,
                                    QuoteRepository $qR,
                                    InvRepository $iR,
                                   ): \Yiisoft\DataResponse\DataResponse {
        $flash =  '';
        if (($sR->repoCount('use_test_data') > 0 && $sR->get_setting('use_test_data') == '0')) {
            // Only remove the test data if the user's test quotes and invoices have been removed FIRST else integrity constraint violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('invoice.first.reset');
            } else {
                // Note: The Tax Rates are not deleted because you must have at least one zero tax rate and one standard rate
                // for the quotes and invoices to function corrrectly
                $this->test_data_delete($uR, $fR, $pR, $cR); 
                $flash = $this->translator->translate('invoice.deleted');           
            }
        } else {
                // Settings...General...Install Test Data => change to 'no' before you remove the test data
                $flash = $this->translator->translate('invoice.install.test.data');                 
        }
        $data = [
                'alerts'=> $this->alert(),          
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
    public function test_data_reset(SettingRepository $sR,
                                    UnitRepository $uR,
                                    FamilyRepository $fR,
                                    ProductRepository $pR,
                                    ClientRepository $cR,
                                    QuoteRepository $qR,
                                    InvRepository $iR,
                                    TaxRateRepository $trR
                                   ): \Yiisoft\DataResponse\DataResponse {        
        $flash =  '';
        if ($sR->repoCount('install_test_data') > 0 && $sR->get_setting('install_test_data') == 1) {
            // Only remove the test data if the user's test quotes and invoices have been removed FIRST else integrity constraint violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('invoice.first.reset');
            } else {
                $this->test_data_delete($uR, $fR, $pR, $cR); 
                $this->install_test_data($trR, $uR, $fR, $pR, $cR, $sR);
                $flash = $sR->trans('reset');
            }
        } else {
                $flash = $this->translator->translate('invoice.install.test.data');           
        }
        $this->flash_message('info', $flash);
        $data = [
            'alerts'=> $this->alert(),          
        ];
        return $this->viewRenderer->render('index', $data);
    }   
    
    /**
     * 
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @return void
     */
    private function test_data_delete(UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR) : void {
        // Products
        $product = (null !==$pR->withName('Tuch Padd') ? $pR->withName('Tuch Padd') : null);
        null !==$product ? $pR->delete($product) : null; 
        $service = (null !==$pR->withName('Cleen Screans') ? $pR->withName('Cleen Screans') : null);
        null !==$service ? $pR->delete($service) : null;
        // Family
        $family_product = (null !==$fR->withName('Product') ? $fR->withName('Product') : null);
        null !==$family_product ? $fR->delete($family_product) : null;
        $family_service = (null !==$fR->withName('Service') ? $fR->withName('Service') : null);
        null !==$family_service ? $fR->delete($family_service) : null;
        // Unit
        $unit = (null !==$uR->withName('unit') ? $uR->withName('unit') : null);
        null !==$unit ? $uR->delete($unit) : null;
        $unit_service = (null !==$uR->withName('service') ? $uR->withName('service') : null);
        null !==$unit_service ? $uR->delete($unit_service) : null;
        // Client
        $client_non = (null !==$cR->withName('Non') ? $cR->withName('Non') : null);
        null !==$client_non ? $cR->delete($client_non) : null;
        $client_foreign = (null !==$cR->withName('Foreign') ? $cR->withName('Foreign') : null);
        null !==$client_foreign ? $cR->delete($client_foreign) : null;
        // Group data is not deleted because these are defaults
    }    
}


