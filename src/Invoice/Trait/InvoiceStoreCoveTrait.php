<?php

declare(strict_types=1);

namespace App\Invoice\Trait;

trait InvoiceStoreCoveTrait
{

    /**
     * Use curL to call the store_cove api ... 1.1.3. Make your first API call
     * Tab: ERP or Accounting System, NOT: Individual Company, NOT: Reseller or
     * Systems Integrator
     * Related logic: see config\common\routes\routes.php api-store-cove
     * Related logic: see https://www.storecove.com/docs 3.3.2. Sending a
     * document UBL format
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function storeCoveCallApi(): \Psr\Http\Message\ResponseInterface
    {
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $store_cove = 'https://api.storecove.com/api/v2/discovery/receives';
        // 1.1.2 : Create a new API key by clicking the "Create New API Key"
        // button. For the Integrator package, create a "Master" key.
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->sR->decode($this->sR->getSetting(
                                                'gateway_storecove_apiKey'));
        $site = curl_init();
        if ($site) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER,
                    ['Accept: application/json',
                        "Authorization: Bearer $api_key_here",
                            'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
/**
 * Related logic: see https://www.storecove.com/docs/#_getting_started 1.1.3.
 * Make your first API call
 */
            $data = '{"documentTypes": ["invoice"], "network": "peppol",'
                    . ' "metaScheme": "iso6523-actorid-upis",'
                    . ' "scheme": "nl:kvk", "identifier":"60881119"}';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            $message = curl_error($site) ?:
            $this->translator->translate('curl.store.cove.api.setup.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->webViewRenderer->render('curl/api_result', $parameters);
    }

    /**
     * Use curL to call the store_cove api ... 1.1.4a. Create a sender: Get the
     * Legal Entity Id
     * Related logic:
     *   see config\common\routes\routes.php api-store-cove-get-legal-entity-id
     * Related logic: see https://www.storecove.com/docs/
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function storeCoveCallApiGetLegalEntityId():
                                            \Psr\Http\Message\ResponseInterface
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
        $api_key_here = $this->sR->decode($this->sR->getSetting(
            'gateway_storecove_apiKey'));
        $site = curl_init();
        if ($site) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER,
                    ['Accept: application/json',
                        "Authorization: Bearer $api_key_here",
                            'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            $country_code_identifier = 'GB';
            $data = '{"party_name": "Test Party", "line1": "Test Street 1",'
                    . ' "city": "Test City", "zip": "Zippy", "country": "'
                    . $country_code_identifier . '"}';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            $message = curl_error($site) ?: $this->translator->translate(
                         'curl.store.cove.api.get.legal.entity.id.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->webViewRenderer->render('curl/api_result', $parameters);
    }

    /**
     * Use curL to call the store_cove api ... 1.1.4b Create a Sender: Create
     * an Identifier
     * Related logic: see config\common\routes\routes.php api-store-cove-legal-entity-identifier
     * Related logic: see https://www.storecove.com/docs/
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function storeCoveCallApiLegalEntityIdentifier():
                                            \Psr\Http\Message\ResponseInterface
    {
        // Obtain from above A)
        // store-cove regex: ^GB(\d{9}(\d{3})?$|^[A-Z]{2}\d{3})$ will match eg.
        // GB000123456 eg. GB obtained from setting view storecove
        $legal = $this->sR->getSetting('storecove_country');
        // Must be a 9 digit number including preceding zeros or a
        // 12 digit number eg. 000217688
        $id = '000217793';
        $scheme_tax_identifier = 'GB:VAT';
        $combo_id = $legal . $id;
        $store_cove = "https://api.storecove.com/api/v2/legal_entities/"
                . "$id/peppol_identifiers";
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->sR->decode(
                            $this->sR->getSetting('gateway_storecove_apiKey'));
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $site = curl_init();
        if ($site) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                "Authorization: Bearer $api_key_here",
                'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            $data = '{"superscheme": "iso6523-actorid-upis", "scheme": "'
                    . $scheme_tax_identifier . '", "identifier": "'
                    . $combo_id . '"}';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            $message = curl_error($site) ?: $this->translator->translate(
                    'curl.store.cove.api.legal.entity.identifier.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->webViewRenderer->render('curl/api_result', $parameters);
    }

    /**
     * Related logic: see https://app.storecove.com/en/docs #1.1.5 Send your
     * first invoice .. Click on green button for json copy
     * Paste json copy into $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function storeCoveSendTestJsonInvoice():
                                            \Psr\Http\Message\ResponseInterface
    {
        $store_cove = 'https://api.storecove.com/api/v2/document_submissions';
        // Remove zeros from '000217668' => integer'
        $legal_entity_id_as_integer = (int) $this->sR->getSetting(
                                                    'storecove_legal_entity_id');
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->sR->decode($this->sR->getSetting(
                                                    'gateway_storecove_apiKey'));
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $site = curl_init();
        if ($site) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                "Authorization: Bearer $api_key_here",
                'Content-Type: application/json'
            ]);
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
            $message = curl_error($site) ?: $this->translator->translate(
                    'curl.store.cove.api.send.test.json.invoice.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->webViewRenderer->render('curl/api_result', $parameters);
    }

    public function storeCoveSendActualJsonInvoice():
                                            \Psr\Http\Message\ResponseInterface
    {
        $store_cove = 'https://api.storecove.com/api/v2/document_submissions';
        /**
         * @var mixed $api_key_here
         */
        $api_key_here = $this->sR->decode($this->sR->getSetting(
                                                'gateway_storecove_apiKey'));
        $parameters = [
            'result' => '',
            'message' => '',
            'status' => '',
        ];
        $site = curl_init();
        if ($site) {
            curl_setopt($site, CURLOPT_URL, $store_cove);
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                "Authorization: Bearer $api_key_here",
                'Content-Type: application/json']);
            curl_setopt($site, CURLOPT_HEADER, true);
            $legalEntityId = (string) (int) $this->sR->getSetting(
                                                'storecove_legal_entity_id');
            $dualArray = [
                $this->storeCoveWorldToJson($legalEntityId),
                $this->storeCoveMainJson(),
            ];
            curl_setopt($site, CURLOPT_POSTFIELDS, $dualArray[1]);
            $message = curl_error($site) ?: $this->translator->translate(
                          'curl.store.cove.api.setup.legal.entity.successful');
            $parameters = [
                'result' => curl_exec($site),
                'message' => $message,
                'status' => curl_error($site) ? 'warning' : 'success',
            ];
        }
        return $this->webViewRenderer->render('curl/api_result', $parameters);
    }

    private function storeCoveWorldToJson(string $legalEntityId): string
    {
        return '{
                "legalEntityId": ' . $legalEntityId . ',
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
    }

    private function storeCoveMainJson(): string
    {
        return $this->storeCoveMainJsonPart1()
             . $this->storeCoveMainJsonPart2();
    }

    private function storeCoveMainJsonPart1(): string
    {
            $p = "JVBERi0xLjIgCjkgMCBvYmoKPDwKPj4Kc3RyZWFtCkJULyAzMiBUZiggIFlP";
            $q = "VVIgVEVYVCBIRVJFICAgKScgRVQKZW5kc3RyZWFtCmVuZG9iago0IDAgb2Jq";
            $r = "Cjw8Ci9UeXBlIC9QYWdlCi9QYXJlbnQgNSAwIFIKL0NvbnRlbnRzIDkgMCBS";
            $s = "Cj4+CmVuZG9iago1IDAgb2JqCjw8Ci9LaWRzIFs0IDAgUiBdCi9Db3VudCAx";
            $t = "Ci9UeXBlIC9QYWdlcwovTWVkaWFCb3ggWyAwIDAgMjUwIDUwIF0KPj4KZW5k";
            $u = "b2JqCjMgMCBvYmoKPDwKL1BhZ2VzIDUgMCBSCi9UeXBlIC9DYXRhbG9nCj4+";
            $v = "CmVuZG9iagp0cmFpbGVyCjw8Ci9Sb290IDMgMCBSCj4+CiUlRU9G";
            $w = "This is the invoice note. Senders can enter free text.";
            $x = "This may not be read by the receiver,";
            $y = "so it is not encouraged to use this.";

        return '{
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
                    "document":' . $p . $q . $r . $s . $t . $u . $v .
                    '"mimeType": "application/pdf",
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
                        "documentId":
                    "buyer reference or purchase order reference is recommended",
                        "lineId": "1",
                        "issueDate": "2021-12-01"
                      },
                      {
                        "documentType": "buyer_reference",
                        "documentId":
                    "buyer reference or purchase order reference is recommended"
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
                    "note":' . $w . $x . $y . ',
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
                    },';
    }

    private function storeCoveMainJsonPart2(): string
    {
        return '                    "delivery": {
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
                      "note":
            "For payment terms, only a note is supported by Peppol currently."
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
    }
}
