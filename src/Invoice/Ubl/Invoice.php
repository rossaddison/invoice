<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

// Sabre
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use App\Invoice\Ubl\Schema;
use App\Invoice\Setting\SettingRepository;
use DateTime;
use InvalidArgumentException;

class Invoice implements XmlSerializable {
  private SettingRepository $settingRepository;
  // http://www.datypic.com/sc/ubl23/t-ns53_InvoiceType.html
  private ?string $UBLVersionID = '2.1';
  private ?string $customizationID = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0';
  private ?string $profileID;
  private ?string $id;
  private DateTime $issueDate;  
  private ?DateTime $dueDate;
  protected ?int $invoiceTypeCode = InvoiceTypeCode::INVOICE;
  private ?string $note;
  private ?DateTime $taxPointDate;
  private string $documentCurrencyCode = 'EUR';
  /* @see Settings ... View ... Peppol Electronic Invoicing ... from_currency and to_currency */ 
  private ?string $accountingCostCode;  
  private ?string $buyerReference;  
  private ?InvoicePeriod $invoicePeriod;
  private ?OrderReference $orderReference;
  private ?AdditionalDocumentReference $additionalDocumentReference;
  private ?ContractDocumentReference $contractDocumentReference;
  private ?Party $accountingSupplierParty;
  private ?Party $accountingCustomerParty;
  private ?Delivery $delivery;
  private ?PaymentMeans $paymentMeans;
  private ?PaymentTerms $paymentTerms;
  private array $allowanceCharges;
  private array $taxAmounts;
  private array $taxSubtotal;
  private ?LegalMonetaryTotal $legalMonetaryTotal;
  protected array $invoiceLines;
  private ?bool $isCopyIndicator;
  private ?string $supplierAssignedAccountID;

  public function __construct(
    SettingRepository $settingRepository,
    ?string $profileID,
    ?string $id,
    DateTime $issueDate,
    DateTime $dueDate,
    ?string $note,
    DateTime $taxPointDate,    
    ?string $accountingCostCode,
    ?string $buyerReference,
    ?InvoicePeriod $invoicePeriod,
    ?OrderReference $orderReference,
    ContractDocumentReference $contractDocumentReference,
    AdditionalDocumentReference $additionalDocumentReference,
    ?Party $accountingSupplierParty,
    ?Party $accountingCustomerParty,
    ?Delivery $delivery,
    PaymentMeans $paymentMeans,
    PaymentTerms $paymentTerms,
    array $allowanceCharges,
    array $taxAmounts,
    array $taxSubtotal,
    ?LegalMonetaryTotal $legalMonetaryTotal,
    array $invoiceLines,
    ?bool $isCopyIndicator,
    ?string $supplierAssignedAccountID,
  ) {
    $this->settingRepository = $settingRepository;
    $this->profileID = $profileID;
    $this->id = $id;
    $this->issueDate = $issueDate;
    $this->dueDate = $dueDate;
    $this->note = $note;    
    $this->taxPointDate = $taxPointDate;
    $this->accountingCostCode = $accountingCostCode;
    $this->buyerReference = $buyerReference;
    $this->invoicePeriod = $invoicePeriod;
    $this->orderReference = $orderReference;
    $this->contractDocumentReference = $contractDocumentReference;
    $this->additionalDocumentReference = $additionalDocumentReference;
    $this->accountingSupplierParty = $accountingSupplierParty;
    $this->accountingCustomerParty = $accountingCustomerParty;
    $this->delivery = $delivery;    
    $this->paymentMeans = $paymentMeans;
    $this->paymentTerms = $paymentTerms;
    $this->allowanceCharges = $allowanceCharges;
    $this->taxAmounts = $taxAmounts;
    $this->taxSubtotal = $taxSubtotal;
    $this->legalMonetaryTotal = $legalMonetaryTotal;
    $this->invoiceLines = $invoiceLines;
    $this->isCopyIndicator = $isCopyIndicator;
    $this->supplierAssignedAccountID = $supplierAssignedAccountID;
  }

  /**
   * @return null|string
   */
  public function getUBLVersionID(): ?string {
    return $this->UBLVersionID;
  }

  /**
   * @see http://www.schemacentral.com Business Document Standards
   * @param null|string $UBLVersionID
   * eg. '2.0', '2.1', '2.2', '2.3'
   * @return Invoice
   */
  public function setUBLVersionID(?string $UBLVersionID): Invoice {
    $this->UBLVersionID = $UBLVersionID;
    return $this;
  }

  /**
   * @return Invoice
   */
  public function setDocumentCurrencyCode(): Invoice {
    $this->documentCurrencyCode = $this->settingRepository->get_setting('currency_code_to');
    return $this;
  }

  public function getDocumentCurrencyCode(): string {
    return $this->settingRepository->get_setting('currency_code_to');
  }

  /**
   * The validate function that is called during xml writing to validate the data of the object.
   *
   * @return void
   * @throws InvalidArgumentException An error with information about required data that is missing to write the XML
   */
  public function validate() : void {
    if ($this->id === null) {
      throw new InvalidArgumentException('Missing invoice id');
    }
    
    if (empty($this->note)) {
      throw new InvalidArgumentException('Missing invoice note');
    }

    if (!$this->issueDate instanceof DateTime) {
      throw new InvalidArgumentException('Invalid invoice issueDate');
    }

    if ($this->invoiceTypeCode === null) {
      throw new InvalidArgumentException('Missing invoice invoiceTypeCode');
    }

    if ($this->accountingSupplierParty === null) {
      throw new InvalidArgumentException('Missing invoice accountingSupplierParty');
    }

    if ($this->accountingCustomerParty === null) {
      throw new InvalidArgumentException('Missing invoice accountingCustomerParty');
    }

    if (empty($this->invoiceLines)) {
      throw new InvalidArgumentException('Missing invoice lines');
    }

    if ($this->legalMonetaryTotal === null) {
      throw new InvalidArgumentException('Missing invoice LegalMonetaryTotal');
    }
  }

  /**
   *
   * @param Writer $writer
   * @return void
   */
  public function xmlSerialize(Writer $writer): void {
    $this->validate();

    $writer->write([
      Schema::CBC . 'UBLVersionID' => $this->UBLVersionID,
      Schema::CBC . 'CustomizationID' => $this->customizationID,
    ]);

    if ($this->profileID !== null) {
      $writer->write([
        Schema::CBC . 'ProfileID' => $this->profileID
      ]);
    }

    $writer->write([
      Schema::CBC . 'ID' => $this->id
    ]);
    
    /**
     * Rule set: OpenPeppol UBL Invoice (3.15.0) (a.k.a BIS Billing 3.0.14)
     *
     * @see https://ecosio.com/en/peppol-and-xml-document-validator-button/   
     * @see https://docs.peppol.eu/poacc/billing/3.0/rules/UBL-CR-004/
     * Warning
     * Location: src/Invoice/Helpers/Peppol/EcosioTestFiles/invoice_CtuZ7QoIINV107_peppol
     * Element/context: /:Invoice[1]
     * XPath test: not(cbc:CopyIndicator)
     * Error message: [UBL-CR-004]-A UBL invoice should not include the CopyIndicator
     */
    
    //if ($this->isCopyIndicator !== null) {
    //  $writer->write([
    //    Schema::CBC . 'CopyIndicator' => $this->isCopyIndicator ? 'true' : 'false'
    //  ]);
    //}

    $writer->write([
      Schema::CBC . 'IssueDate' => $this->issueDate->format('Y-m-d'),
    ]);

    if ($this->dueDate !== null) {
      $writer->write([
        Schema::CBC . 'DueDate' => $this->dueDate->format('Y-m-d')
      ]);
    }

    $writer->write([
      Schema::CBC . 'InvoiceTypeCode' => $this->invoiceTypeCode
    ]);

    if ($this->note !== null) {
      $writer->write([
        Schema::CBC . 'Note' => $this->note
      ]);
    }

    if ($this->taxPointDate !== null) {
      $writer->write([
        Schema::CBC . 'TaxPointDate' => $this->taxPointDate->format('Y-m-d')
      ]);
    }

    $writer->write([
      Schema::CBC . 'DocumentCurrencyCode' => $this->getDocumentCurrencyCode(),
    ]);
    
    /* 
     * Warning
     * Location: src/Invoice/Helpers/Peppol/EcosioTestFiles/invoice_a0Vc8Tz6INV107_peppol
     * Element/context: /:Invoice[1]
     * XPath test: not(cbc:AccountingCostCode)
     * Error message: [UBL-CR-010]-A UBL invoice should not include the AccountingCostCode
    */
     //if ($this->accountingCostCode !== null) {
     //
     // $writer->write([
     //   Schema::CBC . 'AccountingCostCode' => $this->accountingCostCode
     // ]);
     //}

    if ($this->buyerReference !== null) {
      $writer->write([
        Schema::CBC . 'BuyerReference' => $this->buyerReference
      ]);
    }

    if ($this->invoicePeriod !== null) {
      $writer->write([
        Schema::CAC . 'InvoicePeriod' => $this->invoicePeriod
      ]);
    }

    if ($this->orderReference !== null) {
      $writer->write([
        Schema::CAC . 'OrderReference' => $this->orderReference
      ]);
    }

    if ($this->contractDocumentReference !== null) {
      $writer->write([
        Schema::CAC . 'ContractDocumentReference' => $this->contractDocumentReference,
      ]);
    }
    
    /**
     * @see src/Invoice/Helpers/Peppol/PeppolHelper 
     * Warning
     * Location: src/Invoice/Helpers/Peppol/EcosioTestFiles/invoice_a0Vc8Tz6INV107_peppol
     * Element/context: /:Invoice[1]
     * XPath test: not(cac:AdditionalDocumentReference/cbc:DocumentType)
     * Error message: [UBL-CR-114]-A UBL invoice should not include the AdditionalDocumentReference DocumentType
     */
    
    if ($this->additionalDocumentReference !== null) {
      $writer->write([
        Schema::CAC . 'AdditionalDocumentReference' => $this->additionalDocumentReference
      ]);
    }

    
    /*
     * Warning
     * Location: invoice_a0Vc8Tz6INV107_peppol
     * Element/context: /:Invoice[1]
     * XPath test: not(cac:AccountingCustomerParty/cbc:SupplierAssignedAccountID)
     * Error message: [UBL-CR-202]-A UBL invoice should not include the AccountingCustomerParty SupplierAssignedAccountID
     */
    //if ($this->supplierAssignedAccountID !== null) {
    //  $customerParty = [
    //    Schema::CBC . 'SupplierAssignedAccountID' => $this->supplierAssignedAccountID,
    //    Schema::CAC . "Party" => $this->accountingCustomerParty
    //  ];
    //} else {
    $customerParty = [
      Schema::CAC . "Party" => $this->accountingCustomerParty
    ];
    //}

    $writer->write([
      Schema::CAC . 'AccountingSupplierParty' => [Schema::CAC . "Party" => $this->accountingSupplierParty],
      Schema::CAC . 'AccountingCustomerParty' => $customerParty,
    ]);

    if ($this->delivery !== null) {
      $writer->write([
        Schema::CAC . 'Delivery' => $this->delivery
      ]);
    }

    if ($this->paymentMeans !== null) {
      $writer->write([
        Schema::CAC . 'PaymentMeans' => $this->paymentMeans
      ]);
    }

    if ($this->paymentTerms !== null) {
      $writer->write([
        Schema::CAC . 'PaymentTerms' => $this->paymentTerms
      ]);
    }

    if (!empty($this->allowanceCharges)) {
      /** @var AllowanceCharge $allowanceCharge */
      foreach ($this->allowanceCharges as $allowanceCharge) {
        $writer->write([
          Schema::CAC . 'AllowanceCharge' => $allowanceCharge
        ]);
      }
    }
    
    $this->validate();
         $tst = $this->taxAmounts;
        /**
         * @var float $tst['supp_tax_cc_tax_amount']
         */
        $supp_tax_cc_tax_amount = $tst['supp_tax_cc_tax_amount'] ?: 0.00;
        /**
         * @var float $tst['doc_cc_tax_amount']
         */
        $doc_cc_tax_amount = $tst['doc_cc_tax_amount'] ?: 0.00;
        /**
         * @var string $tst['supp_tax_cc']
         */
        $supp_cc = $tst['supp_tax_cc'] ?? '';
        /**
         * @var string $tst['doc_cc']
         */
        $doc_cc = $tst['doc_cc'] ?? '';
    
    // if the document's currency code is the same as us (Supplier) ie. sending locally    
    if ($doc_cc === $supp_cc) {    
    $writer->write([
      [
        'name' => Schema::CAC . 'TaxTotal',
        'value' => [
           [
              'name' => Schema::CBC . 'TaxAmount',
              'value' => number_format($supp_tax_cc_tax_amount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $supp_cc,
              ]
           ],
           [
              $this->build_tax_sub_totals_array()
           ], 
        ],
      ],
    ]); 
    } else {
        // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-TaxTotal/
        // Suppliers Tax Amount in Suppliers Currency without subtotal breakdown
        $writer->write([
          [
            'name' => Schema::CAC . 'TaxTotal',
            'value' => [
               [
                  'name' => Schema::CBC . 'TaxAmount',
                  'value' => number_format($supp_tax_cc_tax_amount ?: 0.00, 2, '.', ''),
                    'attributes' => [
                        'currencyID' => $supp_cc,
                  ]
               ],
               [
                  $this->build_tax_sub_totals_array()
               ], 
            ],
          ],
        ]);
        // Document Recipients TaxAmount in Document Recipient's Currency
        $writer->write([
            [
                'name' => Schema::CBC . 'TaxAmount',
                'value' => number_format((float)(string)$doc_cc_tax_amount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $doc_cc
                ]
            ],
        ]);
        
        } // elseif
        
    $writer->write([
      Schema::CAC . 'LegalMonetaryTotal' => $this->legalMonetaryTotal
    ]);

    /**
     * @see src/Invoice/Helpers/Peppol/PeppolHelper function build_invoice_lines_array
     * @var array $this->invoiceLines
     * @var array $invoiceLine
     */
    foreach ($this->invoiceLines as $invoiceLine) {
       $writer->write($invoiceLine);
    }
  }
  
  /*
   * @see PeppolHelper function build_TaxSubtotal_array
   * Take each Tax Category and build a tax sub total 
   * @return array
   */
  public function build_tax_sub_totals_array() : array 
  {
    $merged_array = [];
    /**
     * @var array $this->taxSubtotal
     * @var array $value
     */
    foreach ($this->taxSubtotal as $value) {
      $tst = new TaxSubtotal($value);
      array_push($merged_array, $tst->build_pre_serialized_array());
    }
    return $merged_array;
  }
}
