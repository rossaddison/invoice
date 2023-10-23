<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
//https://github.com/brick/money
use Brick\Money\CurrencyConverter;
// Use settings/view/peppol to manually load the exchange rate for today via:
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
// Yiisoft
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;
// Entities
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Entity\DeliveryLocation as DL;
use App\Invoice\Entity\Upload;
// Helpers
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Libraries\PeppolUblXml;
use App\Invoice\Setting\SettingRepository as SRepo;
// Repositories
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\Contract\ContractRepository as ContractRepo;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\DeliveryParty\DeliveryPartyRepository as DelPartyRepo;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Upload\UploadRepository as upR;
use App\Invoice\UnitPeppol\UnitPeppolRepository as unpR;
// Ubl
use App\Invoice\Ubl\AdditionalDocumentReference;
use App\Invoice\Ubl\Address;
use App\Invoice\Ubl\Attachment;
use App\Invoice\Ubl\Contact;
use App\Invoice\Ubl\Country;
use App\Invoice\Ubl\FinancialInstitutionBranch;
use App\Invoice\Ubl\InvoicePeriod;
use App\Invoice\Ubl\Party;
use App\Invoice\Ubl\PartyLegalEntity;
use App\Invoice\Ubl\PartyTaxScheme;
use App\Invoice\Ubl\PayeeFinancialAccount;
use App\Invoice\Ubl\Schema;
use App\Invoice\Ubl\TaxScheme;
// Exceptions
use App\Invoice\Helpers\Peppol\Exception\PeppolBuyerReferenceNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolBuyerPostalAddressNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientIdNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientsAccountingCostNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolDeliveryLocationIDNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolDeliveryLocationCountryNameNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolInvoicePeriodDetailsIncompleteException;
use App\Invoice\Helpers\Peppol\Exception\PeppolInvoiceNoteNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolProductUnitCodeNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolProductItemClassificationCodeSchemeIdNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderPurchaseOrderNumberNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSupplierAssignedAccountIdNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolTaxCategoryPercentNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolTaxCategoryCodeNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolNoLinkedInvoiceFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolTryingToSendNonPdfFileException;
use \DateTimeImmutable;
use \DateTime;

Class PeppolHelper {

  private SRepo $s;
  private IIAR $iiaR;
  private InvAmount $inv_amount;
  private DL $delivery_location;
  private Translator $t;
  private DateHelper $datehelper;
  private string $from_currency;
  private string $to_currency;
  private string $from_to_manual_input;
  private string $to_from_manual_input;

  public function __construct(
    SRepo $s,
    IIAR $iiaR,
    InvAmount $inv_amount,
    DL $delivery_location,
    Translator $translator,
    string $from_currency,
    string $to_currency,
    string $from_to_manual_input,
    string $to_from_manual_input,
  ) {
    $this->s = $s;
    $this->iiaR = $iiaR;
    $this->inv_amount = $inv_amount;
    // client's delivery location
    $this->delivery_location = $delivery_location;
    $this->t = $translator;
    $this->datehelper = new DateHelper($this->s);
    $this->from_currency = $from_currency;
    $this->to_currency = $to_currency;
    // today's currency value
    $this->from_to_manual_input = $from_to_manual_input;
    // today's currency value
    $this->to_from_manual_input = $to_from_manual_input;
  }

  /**
   *
   * @param SRepo $sR
   * @return Aliases
   */
  private function ensure_temp_peppol_folder_and_uploads_folder_exist(): Aliases {
    $aliases = new Aliases(['@invoice' => dirname(dirname(__DIR__)), '@Uploads' => '@invoice/Uploads']);
    // Invoice/Uploads/Archive
    $folder = $aliases->get('@Uploads');
    // Check if the uploads folder is available
    if (!(is_dir($folder) || is_link($folder))) {
      FileHelper::ensureDirectory($folder, 0775);
    }
    // Invoice/Uploads/Temp/Peppol
    $temp_peppol_folder = $aliases->get('@Uploads') . $this->s::getTempPeppolfolderRelativeUrl();
    if (!is_dir($temp_peppol_folder)) {
      FileHelper::ensureDirectory($temp_peppol_folder, 0775);
    }
    return $aliases;
  }

  /**
   * 
   * @see \config\common\params.php and src\Invoice\Setting\SettingRepository
   * @param SOR $soR
   * @param Inv $invoice
   * @param IAR $iaR
   * @param IIAR $iiaR
   * @param IIR $iiR
   * @param ContractRepo $contractRepo
   * @param DelRepo $delRepo
   * @param DelPartyRepo $delPartyRepo
   * @param paR $paR
   * @param cpR $cpR
   * @param unpR $unpR
   * @param upR $upR
   * @param ACIR $aciR
   * @param ACIIR $aciiR
   * @param SOIR $soiR
   * @param TRR $trR
   * @return string
   * @throws \Exception
   * @throws PeppolBuyerReferenceNotFoundException
   */
  public function generate_invoice_peppol_ubl_xml_temp_file(
    SOR $soR,
    Inv $invoice,
    IAR $iaR,
    IIAR $iiaR,
    IIR $iiR,
    ContractRepo $contractRepo,
    DelRepo $delRepo,
    DelPartyRepo $delPartyRepo,
    // PostalAddress Repository
    paR $paR,
    // ClientPeppol Repository
    cpR $cpR,
    // UnitPeppol Repository
    unpR $unpR,
    // Upload Repository
    upR $upR,    
    // Document Level InvAllowanceCharge Repository;
    // used to retrieve invoice allowance charges
    ACIR $aciR,
    ACIIR $aciiR,
    SOIR $soiR,
    TRR $trR
  ): string {
    $invoice_id = $invoice->getId();
    if (null !== $invoice_id) {
      $this->ensure_temp_peppol_folder_and_uploads_folder_exist();
      $path = $this->UploadsTempPeppolXmlFileNamePathWithExt($invoice);
      // Generate inv items from Entity Inv->getItems() HasMany function
      // Generate inv item amounts from $iiaR
      $peppol_ubl_xml = new PeppolUblXml($this->s, $this->t, $invoice, $iiaR, $this->inv_amount);
      $f = fopen($path, 'wb');
      if (!$f) {
        throw new \Exception(sprintf('Unable to create output file %s', $path));
      }
      $deliveryLocation_ID_scheme = $this->build_delivery_location_ID_scheme();
      $deliveryLocation_Address = $this->build_delivery_location_address();
      // If no actual delivery date has been set, return the date supplied
      $actualDeliveryDate_datetime = $this->ActualDeliveryDate($invoice, $delRepo);
      $cdr_id = $this->ContractDocumentReference($invoice, $contractRepo);
      $deliveryParty_Party = $this->DeliveryParty($invoice, $delRepo, $delPartyRepo);
      // if invoice/delivery periods are used retrieve from there or alternatively retrieve from invoice
      $invoice_period = $this->ubl_invoice_period($invoice, $this->s);
      $start_datetime = $invoice_period->getStartDate();
      $end_datetime = $invoice_period->getEndDate();
      $numberhelper = new NumberHelper($this->s);
      $totals_of_line_items_array = $numberhelper->inv_calculateTotalsofItemTotals($invoice_id, $iiR, $iiaR);

      // The lineExtensionAmount must reconcile with the taxExclusiveAmount
      // $lineExtensionAmount = sum of all line item line extension amounts
      /**
       * @var float $totals_of_line_items_array['subtotal']
       * @var float $totals_of_line_items_array['discount']
       */
      $lineExtensionAmount = $totals_of_line_items_array['subtotal'] - $totals_of_line_items_array['discount'];
      $taxExclusiveAmount = $this->inv_amount->getItem_subtotal();

      $taxInclusiveAmount = $taxExclusiveAmount + $this->inv_amount->getItem_tax_total();

      // Early settlement discount is an allowance
      /** @var float $totals_of_line_items_array['discount'] */
      $allowanceTotalAmount = $totals_of_line_items_array['discount'];
      /** @var float $totals_of_line_items_array['total'] */
      $payableAmount = $totals_of_line_items_array['total'];

      $so = $soR->repoSalesOrderUnloadedquery($invoice->getSo_id());
      // Order Reference https://docs.peppol.eu/poacc/billing/3.0/bis/#orderref
      $client_purchase_order_id = '';
      if ($so && $so->getClient_po_number()) {
        $client_purchase_order_id = $so->getClient_po_number();
        $sales_order_id = $so->getNumber();
      }
      // Buyer Reference https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
      $BuyerReference = '';
      if ($so && $so->getClient_po_person()) {
        $BuyerReference = $so->getClient_po_person();
      }
      $config_company_details = $this->s->get_config_company_details();
      /**
       * @var string $config_company_details['name']
       */
      $supplier_name = $config_company_details['name'];
      $config_peppol = $this->s->get_config_peppol();
      /**
       * @var string $config_peppol['SupplierPartyIdentificationId']
       * @var string $config_peppol['SupplierPartyIdentificationSchemeId']
       */
      $supplier_partyIdentificationId = $config_peppol['SupplierPartyIdentificationId'];
      $supplier_partyIdentificationSchemeId = $config_peppol['SupplierPartyIdentificationSchemeId'];
      $supplier_postalAddress = $this->SupplierPostalAddress();
      $supplier_contact = $this->SupplierContact();
      $supplier_partyTaxScheme = $this->SupplierPartyTaxScheme();
      $supplier_partyLegalEntity = $this->SupplierPartyLegalEntity();
      $supplier_endpointID = $this->SupplierEndpointID();
      $supplier_endpointID_schemeID = $this->SupplierEndpointIDSchemeID();
      $customer_name = $invoice->getClient()?->getClient_full_name();
      $party = $this->build_peppol_accounting_customer_party_array($invoice, $paR, $cpR);
      /**
       * @var array $party['Party']
       * @var array $party['Party']['PartyIdentification']
       * @var array $party['Party']['PartyIdentification']['ID']
       * @var string $party['Party']['PartyIdentification']['ID']['value']
       */
      $customer_partyIdentificationId = $party['Party']['PartyIdentification']['ID']['value'] ?? null;
      /**
       * @var array $party['Party']['PartyIdentification']['ID']
       * @var string $party['Party']['PartyIdentification']['ID']['schemeID']
       */
      $customer_partyIdentificationSchemeId = $party['Party']['PartyIdentification']['ID']['schemeID'] ?? null;
      $customer_postalAddress = $this->build_customer_postal_address($party);
      $customer_contact = $this->build_customer_contact($party);
      $customer_partyTaxScheme = $this->build_customer_party_tax_scheme($party);
      $customer_partyLegalEntity = $this->build_customer_legal_entity($party);
      /**
       * @var array $party['Party]
       * @var array $party['Party']['EndPointID']
       * @var string $party['Party']['EndPointID']['value']
       */
      $customer_endpointID = $party['Party']['EndPointID']['value'] ?? '';
      /**
       * @var array $party['Party']['EndPointID']
       * @var string $party['Party']['EndPointID']['schemeID']
       */
      $customer_endpointID_schemeID = $party['Party']['EndPointID']['schemeID'] ?? '';
      $payment_means_array = $this->build_peppol_payment_means_array();
      $payeeFinancialAccount = $this->build_financial_account($payment_means_array);
      // return the $paymentId (ie. a payment reference id)
      $paymentId = 'peppol' . ($invoice->getNumber() ?: 'Number unavailable') . (new DateTime())->format('Y-m-d');
      $payment_terms = $invoice->getTerms();
      // @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-TaxTotal/
      // When the tax currency code is different and therefore provided,
      // two instances of the tax total must be present,
      // but only one with tax subtotal ie. the elected doc currency code's tax subtotal
      $inv_amount = ($iaR->repoInvquery((int) $invoice->getId()));
      $supp_tax_cc_tax_amount = (null !== $inv_amount ? $inv_amount->getItem_tax_total() : 0.00);
      $taxAmounts_item_subtotal = $this->TaxAmounts($supp_tax_cc_tax_amount);
      $taxSubtotal = $this->build_TaxSubtotal_array($invoice, $iiaR, $trR);
      $issueDate = DateTime::createFromImmutable($invoice->getDate_created());
      $taxPointDate = DateTime::createFromImmutable($invoice->getDate_tax_point());
      $dueDate = DateTime::createFromImmutable($invoice->getDate_due());
      $accountingCost = $this->AccountingCost($invoice, $cpR);
      $additionalDocumentReferences = $this->AdditionalDocumentReference($invoice, $upR);
      $allowanceCharges = $this->DocumentLevelAllowanceCharges($invoice, $aciR);
      // https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
      // $buyer_fallback_reference derived from ClientPeppol entity => extension table to Client
      // This is a fallback reference provided by the client on their login side
      $buyer_fallback_reference = $this->BuyerReference($invoice, $cpR);
      // if no client purchase order person is provided use the $buyer_fallback_reference
      $buyerReference = (null !== $BuyerReference ? $BuyerReference : $buyer_fallback_reference);
      // No reference can be made therefore throw an exception
      if (empty($buyerReference)) {
        throw new PeppolBuyerReferenceNotFoundException();
      }
      $isCopyIndicator = true;
      $id = $invoice->getId();
      $invoiceLines = $this->build_invoice_lines_array($invoice, $invoice_period, $iiaR, $cpR, $soiR, $aciiR, $unpR);
      $profileID = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
      $supplierAssignedAccountID = $this->SupplierAssignedAccountId($invoice, $cpR);
      $note = $invoice->getNote() ?: '';
      if (empty($note)) {
        throw new PeppolInvoiceNoteNotFoundException($this->t);
      }
      if ($invoice->getSo_id()) {
        $sales_order = $soR->repoSalesOrderUnLoadedquery($invoice->getSo_id());
        if (null !== $sales_order) {
          $client_po_number = $sales_order->getClient_po_number();
          if (null !== $client_po_number && !empty($client_po_number)) {
            $sales_order_id = $invoice->getSo_id();
            // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/
            // Only permit a description code if there is no tax point date ie. DateTimeImmutable->format('Y-m-d') === 1901/01/01
            // since the tax_point_date and description code are mutually exclusive
            $description_code = $this->no_tax_point_date($invoice) ? $this->DescriptionCode($invoice, $delRepo) : '';
            // input parameters follow the sequence of 
            // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
            $xml = $peppol_ubl_xml->xml(
              $profileID,
              $id,
              $issueDate,
              $dueDate,
              $note,
              $taxPointDate,
              $accountingCost,
              $buyerReference,
              // InvoicePeriod
              $start_datetime,
              $end_datetime,
              $description_code,
              // OR
              $client_purchase_order_id,
              $sales_order_id,
              // CDR
              $cdr_id,
              $additionalDocumentReferences,             
              // aSP
              $supplier_name,
              $supplier_partyIdentificationId,
              $supplier_partyIdentificationSchemeId,
              $supplier_postalAddress,
              $supplier_contact,
              $supplier_partyTaxScheme,
              $supplier_partyLegalEntity,
              $supplier_endpointID,
              $supplier_endpointID_schemeID,
              // cSP
              $customer_name,
              $customer_partyIdentificationId,
              $customer_partyIdentificationSchemeId,
              $customer_postalAddress,
              $customer_contact,
              $customer_partyTaxScheme,
              $customer_partyLegalEntity,
              $customer_endpointID,
              $customer_endpointID_schemeID,
               // Delivery
              $actualDeliveryDate_datetime,
              $deliveryLocation_ID_scheme,
              $deliveryLocation_Address,
              $deliveryParty_Party,
              // PM
              $payeeFinancialAccount,
              $paymentId,
              // PT
              $payment_terms,
              $allowanceCharges,
              // TT
              $taxAmounts_item_subtotal,
              // TST
              $taxSubtotal,
               // LegalMonetaryTotal
              $lineExtensionAmount,
              $taxExclusiveAmount,
              $taxInclusiveAmount,
              $allowanceTotalAmount,
              $payableAmount,
              
              $invoiceLines,
              $isCopyIndicator,
              $supplierAssignedAccountID,
            );
            fwrite($f, $peppol_ubl_xml->output($xml));
            fclose($f);
            return $path;
          } // if $client_po_number
        } // null!==sales order
        throw new PeppolSalesOrderNotFoundException($this->t);
      } else { // if $invoice->getSo_id() > 0
        throw new PeppolBuyerReferenceNotFoundException();
      }
      throw new PeppolBuyerReferenceNotFoundException();
    } else {
      throw new PeppolNoLinkedInvoiceFoundException($this->t);
    }
  }

  /**
   * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AdditionalDocumentReference/
   * @param Inv $invoice
   * @param UPR $upR
   * @return AdditionalDocumentReference
   */
  private function AdditionalDocumentReference(Inv $invoice, UPR $upR): AdditionalDocumentReference {
    $url_key = $invoice->getUrl_key();
    $invoice_number = $this->t->translate('invoice.peppol.document.reference.null'). ($invoice->getId() ?: 'Not Found');
    if (null!==$invoice->getNumber()) {
      $invoice_number = $invoice->getNumber();
    }
    $inv_attachments = $upR->repoUploadUrlClientquery($url_key, (int) $invoice->getClient_id());
    $aliases = $this->s->get_customer_files_folder_aliases();
    $targetPath = $aliases->get('@customer_files');
    $attachments = [];
    /**
     * @var Upload $inv_attachment
     */
    foreach ($inv_attachments as $inv_attachment) {
      $original_file_name = $inv_attachment->getFile_name_original();
      $url_key = $inv_attachment->getUrl_key();
      $target_path_with_filename = $targetPath . '/' . $url_key . '_' . $original_file_name;
      $path_info = pathinfo($target_path_with_filename);
      /**
       * @var string $path_info['extension']
       */
      $path_info_extension = $path_info['extension'];
      if ($path_info_extension === 'pdf') {
        // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AdditionalDocumentReference/
        // $inv_attachment->getId() => upload repository id
        $attachments[$inv_attachment->getId()] = //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AdditionalDocumentReference/cac-Attachment/cac-ExternalReference/
          new Attachment(
          // 'filePath' used to generate file_contents
          $target_path_with_filename,
          // see Invoice/Ubl/Attachment
          'invoice/download_file/' . $inv_attachment->getId()
        );
      } else {
        throw new PeppolTryingToSendNonPdfFileException($this->t);
      }
    }
    $invoice_id = $invoice->getId();
    $additionalDocumentReferences = new AdditionalDocumentReference(
      $this->t,
      $invoice_number ?: $this->t->translate('invoice.peppol.document.reference.null').($invoice_id ?: 'Not Found'),
      '130',
      $invoice->getDocumentDescription(),
      $attachments,
      
      /*
       * DocumentType not to be included => set to true
       * Warning
       * Location: invoice___yItaG5INV107_peppol
       * Element/context: /:Invoice[1]
       * XPath test: not(cac:AdditionalDocumentReference/cbc:DocumentType)
       * Error message: [UBL-CR-114]-A UBL invoice should not include the AdditionalDocumentReference DocumentType
       */
       true
    );
    return $additionalDocumentReferences;
  }

  /**
   *
   * @param array $party
   * @return Contact
   */
  public function build_customer_contact(array $party): Contact {
    /**
     * @var array $party['Party']
     * @var array $party['Party']['Contact']
     */
    $contact = $party['Party']['Contact'];

    /**
     * @var string $contact['Name']
     */
    $Name = $contact['Name'] ?? '';
    /**
     * @var string $contact['FirstName']
     */
    $FirstName = $contact['FirstName'] ?? '';
    /**
     * @var string $contact['LastName']
     */
    $LastName = $contact['LastName'] ?? '';
    /**
     * @var string $contact['Telephone']
     */
    $Telephone = $contact['Telephone'] ?? '';
    /**
     * @var string $contact['ElectronicMail']
     */
    $ElectronicMail = $contact['ElectronicMail'] ?? '';
    $customer_contact = new Contact(
      $Name,
      $FirstName,
      $LastName,
      $Telephone,
      /**
       * Customer's telefax must not be included => null
       * Warning
       * Location: invoice_sqKOvgahINV107_peppol
       * Element/context: /:Invoice[1]
       * XPath test: not(cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Telefax)
       * Error message: [UBL-CR-254]-A UBL invoice should not include the AccountingCustomerParty Party Contact Telefax
       */
      null,
      $ElectronicMail
    );
    return $customer_contact;
  }

  /**
   * @param array $party
   * @return PartyLegalEntity
   */
  public function build_customer_legal_entity(array $party): PartyLegalEntity {
    /**
     * @var array $party['Party']
     * @var array $party['Party']['PartyLegalEntity']
     */
    $party_legal_entity = $party['Party']['PartyLegalEntity'] ?? [];
    /**
     * @var string $party_legal_entity['RegistrationName']
     */
    $registration_name = $party_legal_entity['RegistrationName'] ?? '';
    /**
     * @var string $party_legal_entity['CompanyID']
     */
    $company_id = $party_legal_entity['CompanyID'] ?? '';
    /**
     * @var array $party_legal_entity['Attributes']
     */
    $attributes = $party_legal_entity['Attributes'] ?? [];
    /**
     * @var string $party_legal_entity['CompanyLegalForm']
     */
    $company_legal_form = $party_legal_entity['CompanyLegalForm'] ?? '';
    $customer_partyLegalEntity = new PartyLegalEntity(
      $registration_name,
      $company_id,
      $attributes,
      $company_legal_form
    );
    return $customer_partyLegalEntity;
  }

  /**
   * @param array $party
   * @return PartyTaxScheme
   */
  public function build_customer_party_tax_scheme(array $party): PartyTaxScheme {
    //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AccountingCustomerParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/

    /**
     * @var array $party['Party']
     * @var array $party['Party']['PartyTaxScheme']
     */
    $party_tax_scheme = $party['Party']['PartyTaxScheme'] ?? [];
    /**
     * @var array $party_tax_scheme['TaxScheme']
     */
    $party_tax_scheme_scheme = $party_tax_scheme['TaxScheme'] ?? [];
    /**
     * @var string $party_tax_scheme_scheme['ID']
     */
    $party_tax_scheme_ID = $party_tax_scheme_scheme['ID'] ?? '';
    /**
     * @var string $party_tax_scheme['CompanyID']
     */
    $party_tax_scheme_companyID = $party_tax_scheme['CompanyID'];
    
    $customer_partyTaxScheme = new PartyTaxScheme(
      $party_tax_scheme_companyID,
      new TaxScheme($party_tax_scheme_ID)
    );
    return $customer_partyTaxScheme;
  }

  /**
   * @param array $party
   * @return Address
   */
  public function build_customer_postal_address(array $party): Address {
    /**
     * @var array $party['Party']
     * @var array $party['Party']['PostalAddress']
     */
    $postal_address = $party['Party']['PostalAddress'] ?? [];
    /**
     * @var string $postal_address['StreetName']
     */
    $street_name = $postal_address['StreetName'] ?? '';
    /**
     * @var string $postal_address['AdditionalStreetName']
     */
    $additional_street_name = $postal_address['AdditionalStreetName'] ?? '';
    /**
     * @var array $postal_address['AddressLine']
     */
    $address_line = $postal_address['AddressLine'] ?? [];
    /**
     * @var string $address_line['Line']
     */
    $line = $address_line['Line'] ?? '';
    /**
     * @var string $postal_address['CityName']
     */
    $city_name = $postal_address['CityName'] ?? '';
    /**
     * @var string $postal_address['PostalZone']
     */
    $postal_zone = $postal_address['PostalZone'] ?? '';
    /**
     * @var string $postal_address['CountrySubentity']
     */
    $country_sub_entity = $postal_address['CountrySubentity'] ?? '';
    /**
     * @var array $postal_address['Country']
     */
    $country = $postal_address['Country'] ?? [];
    /**
     * @var string $country['IdentificationCode']
     */
    $identification_code = $country['IdentificationCode'] ?? '';
    /**
     * @var string $country['ListId']
     */
    $listId = $country['ListId'] ?? '';
    $customer_postalAddress = new Address(
      $street_name,
      $additional_street_name,
      $line,
      $city_name,
      $postal_zone,
      $country_sub_entity,
      new Country($identification_code,
        $listId),
      // this is a customer related address therefore exclude building number UBL_CR_218      
      false,
      true,
      false
    );
    return $customer_postalAddress;
  }

  public function build_delivery_location_ID_scheme(): array {
    $id = $this->delivery_location->getGlobal_location_number();
    if (empty($id)) {
      throw new PeppolDeliveryLocationIDNotFoundException($this->t);
    }
    $array = [
      'ID' => $id,
      'attributes' => [
        'schemeID' => $this->delivery_location->getElectronic_address_scheme()
      ]
    ];
    return $array;
  }

  /**
   * @return Address
   */
  public function build_delivery_location_address(): Address {
    // The customer/client must choose their delivery location from their dashboard
    // Alternatively the administrator can edit the invoice under view...options.
    // Peppol 3.0: Building number can be included in address_1
    $street_name = $this->delivery_location->getAddress_1();
    $additional_street_name = $this->delivery_location->getAddress_2();
    $building_number = $this->delivery_location->getBuildingNumber();
    $cityName = $this->delivery_location->getCity();
    $postalZone = $this->delivery_location->getZip();
    $countrySubEntity = $this->delivery_location->getState();
    $country_name = $this->delivery_location->getCountry();
    /**
     * @see App\Invoice\Entity\DeliveryLocation
     */
    if (null !== $country_name) {
      $deliveryLocation_Address = $this->ubl_delivery_location(
        $street_name,
        $additional_street_name,
        $building_number,
        $cityName,
        $postalZone,
        $countrySubEntity,
        // Use the country_name to build Invoice\Ubl\Country
        $country_name
      );
      return $deliveryLocation_Address;
    } else {
      throw new PeppolDeliveryLocationCountryNameNotFoundException($this->t);
    }
  }

  /**
   * @param array $payment_means_array
   * @return PayeeFinancialAccount
   */
  public function build_financial_account(array $payment_means_array): PayeeFinancialAccount {
    /**
     * @var array $payment_means_array['PayeeFinancialAccount']
     */
    $payee_financial_account_array = $payment_means_array['PayeeFinancialAccount'];
    /**
     * @var string $payee_financial_account_array['ID']
     */
    $payee_ID = $payee_financial_account_array['ID'] ?? '';
    /**
     * @var string $payee_financial_account_array['Name']
     */
    $payee_name = $payee_financial_account_array['Name'] ?? '';
    /**
     * @var array $payee_financial_account_array['FinancialInstitutionBranch']
     */
    $financial_institution_branch = $payee_financial_account_array['FinancialInstitutionBranch'];
    /**
     * @var string $financial_institution_branch['ID']
     */
    $branch_ID = $financial_institution_branch['ID'];
    $payeeFinancialAccount = new PayeeFinancialAccount(
      new FinancialInstitutionBranch($branch_ID),
      // $id eg. IBAN123456789
      $payee_ID,
      $payee_name
    );
    return $payeeFinancialAccount;
  }

  /**
   * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/
   * @see \resources\views\invoice\setting\views\partial_settings_peppol
   * @param Inv $invoice
   * @param DelRepo $delRepo
   * @return string
   * @throws PeppolInvoicePeriodDetailsIncompleteException
   * @throws PeppolDeliveryLocationIDNotFoundException
   */
  public function DescriptionCode(Inv $invoice, DelRepo $delRepo): string {
    $description_code = '';
    if ($this->s->get_setting('include_delivery_period') == '1' && !empty($this->s->get_setting('stand_in_code'))) {
      if ($invoice->getDelivery_location_id() > 0) {
        $delivery = $delRepo->repoInvoicequery((string) $invoice->getId());
        if ((null !== $delivery) && (!empty($invoice->getStand_in_code()))) {
          $description_code = $invoice->getStand_in_code();
        } else {
          throw new PeppolInvoicePeriodDetailsIncompleteException();
        }
      } else {
        throw new PeppolDeliveryLocationIDNotFoundException($this->t);
      }
    } else {
      $description_code = '';
    }
    return $description_code;
  }

  /**
   *
   * @param Inv $invoice
   * @param ACIR $aciR
   * @return array
   */
  public function DocumentLevelAllowanceCharges(Inv $invoice, ACIR $aciR): array {
    $invoice_id = $invoice->getId();
    if (null !== $invoice_id) {
      // Get the Document Level Invoice's allowance/charges
      // ie. NOT invoice line allowance/charges
      $allowances_or_charges = $aciR->repoACIquery($invoice_id);
      $array = [];
      if ($aciR->repoACICount($invoice_id)) {
        /**
         * @var InvAllowanceCharge $ac
         */
        foreach ($allowances_or_charges as $ac) {
          $array[] = [
            'chargeIndicator' => $ac->getAllowanceCharge()?->getIdentifier(),
            'allowanceChargeReasonCode' => $ac->getAllowanceCharge()?->getReason_code(),
            'allowanceChargeReason' => $ac->getAllowanceCharge()?->getReason(),
            'multiplierFactorNumeric' => $ac->getAllowanceCharge()?->getMultiplier_factor_numeric(),
            'baseAmount' => $ac->getAllowanceCharge()?->getBase_amount(),
            'amount' => $ac->getAmount(),
            // if chosen document currency (settings...view...peppol electronic invoicing...) different
            // to local supplier's currency,
            // invoice must still have local supplier currency
            // equivalent displayed
            'taxTotal' => [
              // document level currency code tax amount
              'doc_cc_tax_amount' => $ac->getVat(),
              // document currency code
              // views/invoice/setting/views/partial_settings_peppol
              'doc_cc' => $this->s->get_setting('currency_code_to'),
              // supplier tax currency code tax amount
              'supp_tax_cc_tax_amount' => $ac->getVat(),
              // supplier currency code
              // views/invoice/setting/views/partial_settings_peppol
              'supp_cc' => $this->s->get_setting('currency_code_from')
            ],
            'taxCategory' => [
              'taxScheme' => [
                // Mandatory default 'VAT'
                'value' => 'VAT'
              ],
            ],
          ];
        }
      }
      return $array;
    }
    return [];
  }

  /**
   * @param BigNumber|int|float|string $from
   * @return string
   */
  private function currency_converter(BigNumber|int|float|string $from): string {
    $a = $this->from_currency;
    $b = $this->to_currency;
    $one_of_a_converts_to_this_of_b = $this->from_to_manual_input;
    $one_of_b_converts_to_this_of_a = $this->to_from_manual_input;
    $provider = new ConfigurableProvider();
    $provider->setExchangeRate($a, $b, $one_of_a_converts_to_this_of_b);
    $provider->setExchangeRate($b, $a, $one_of_b_converts_to_this_of_a);
    $converter = new CurrencyConverter($provider);
    $money = Money::of($from, $a);
    // see https://github.com/brick/money#Using an ORM
    $float = (float) $converter->convert($money, $b, null, RoundingMode::DOWN)
        // convert to cents in order to use the int
        ->getMinorAmount()
        ->toInt();
    return number_format($float / 100, 2);
  }

  /**
   *
   * @param Inv $invoice
   * @param paR $paR
   * @param cpR $cpR
   * @return array
   * @throws PeppolBuyerPostalAddressNotFoundException
   * @throws PeppolClientNotFoundException
   */
  private function build_peppol_accounting_customer_party_array(Inv $invoice, paR $paR, cpR $cpR): array {
    $client = $invoice->getClient();
    if ($client) {
      $postaladdress_id = $client->getPostaladdress_id();
      $client_peppol = $cpR->repoClientPeppolLoadedquery((string) $client->getClient_id());
      if (null == $postaladdress_id) {
        throw new PeppolBuyerPostalAddressNotFoundException();
      }
      if ($postaladdress_id) {
        $postaladdress = $paR->repoClient((string) $postaladdress_id);
        $accounting_customer_party = [];
        $country_helper = new CountryHelper();
        if ($postaladdress && $client_peppol) {
          $accounting_customer_party = [
            'Party' => [
              'EndPointID' => [
                'value' => $client_peppol->getEndpointid(),
                'schemeID' => $client_peppol->getEndpointid_schemeid()
              ],
              //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AccountingSupplierParty/cac-Party/cac-PartyIdentification/
              'PartyIdentification' => [
                'ID' => [
                  'value' => $client_peppol->getIdentificationid(),
                  // optional
                  'schemeID' => $client_peppol->getIdentificationid_schemeid()
                ]
              ],
              'PostalAddress' => [
                'StreetName' => $postaladdress->getStreet_name(),
                'AdditionalStreetName' => $postaladdress->getAdditional_street_name(),
                'AddressLine' => [
                  'Line' => $postaladdress->getBuilding_number()
                ],
                'CityName' => $postaladdress->getCity_name(),
                'PostalZone' => $postaladdress->getPostalZone(),
                'CountrySubentity' => $postaladdress->getCountrysubentity(),
                'Country' => [
                  'IdentificationCode' => $country_helper->get_country_identification_code_with_league($postaladdress->getCountry()),
                  //https://docs.peppol.eu/poacc/billing/3.0/codelist/ISO3166/
                  'ListId' => 'ISO3166-1:Alpha2'
                ],
              ],
              'PhysicalLocation' => [
                'StreetName' => (string) $client->getClient_address_1(),
                'AdditionalStreetName' => (string) $client->getClient_address_2(),
                'AddressLine' => [
                  'Line' => (string) $client->getClient_building_number()
                ],
                'CityName' => (string) $client->getClient_city(),
                'PostalZone' => (string) $client->getClient_zip(),
                'CountrySubentity' => (string) $client->getClient_state(),
                'Country' => [
                  'IdentificationCode' => $country_helper->get_country_identification_code_with_league((string) $client->getClient_country()),
                  //https://docs.peppol.eu/poacc/billing/3.0/codelist/ISO3166/
                  'ListId' => 'ISO3166-1:Alpha2'
                ],
              ],
              'Contact' => [
                'Name' => $client->getClient_name(),
                'Telephone' => (string) $client->getClient_phone(),
                'ElectronicMail' => $client->getClient_email()
              ],
              'PartyTaxScheme' => [
                'CompanyID' => $client_peppol->getTaxschemecompanyid(),
                'CompanyID_attributes' => [
                  'schemeID' => '',
                  'schemeAgencyID' => ''
                ],
                'TaxScheme' => [
                  // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AccountingSupplierParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/cbc-ID/
                  // VAT / !VAT
                  'ID' => $client_peppol->getTaxSchemeid(),
                  'Attributes' => [
                    'schemeID' => '',
                    'schemeAgencyID' => ''
                  ]
                ],
              ],
              'PartyLegalEntity' => [
                'RegistrationName' => $client_peppol->getLegal_entity_registration_name(),
                'CompanyID' => $client_peppol->getLegal_entity_companyid(),
                'Attributes' => [
                  'schemeID' => $client_peppol->getLegal_entity_companyid_schemeid(),
                ],
                'CompanyLegalform' => $client_peppol->getLegal_entity_company_legal_form()
              ],
            ],
          ];
        }
        return $accounting_customer_party;
      }
      return [];
    } else {
      throw new PeppolClientNotFoundException($this->t);
    }
  }

  /**
   * @param Inv $invoice
   * @param cpR $cpR
   * @return string
   * @throws PeppolClientNotFoundException
   * @throws PeppolClientsAccountingCostNotFoundException
   */
  private function AccountingCost(Inv $invoice, cpR $cpR): string {
    $client = $invoice->getClient();
    if (null !== $client) {
      $client_peppol = $cpR->repoClientPeppolLoadedquery((string) $client->getClient_id());
      if (null === $client_peppol) {
        throw new PeppolClientNotFoundException($this->t);
      } else {
        if ($client_peppol->getAccountingCost()) {
          return $client_peppol->getAccountingCost();
        }
        if (empty($client_peppol->getAccountingCost())) {
          throw new PeppolClientsAccountingCostNotFoundException($this->t);
        }
        return '';
      }
      return '';
    } else {
      throw new PeppolClientNotFoundException($this->t);
    }
  }

  /**
   * @param Inv $invoice
   * @param DelRepo $delRepo
   * @return DateTime|null
   */
  public function ActualDeliveryDate(Inv $invoice, DelRepo $delRepo): DateTime|null {
    $invoice_id = $invoice->getId();
    if (null !== $invoice_id) {
      $delivery = $delRepo->repoInvoicequery($invoice_id);
      if (null !== $delivery) {
        $actual_delivery_date = $delivery->getActual_delivery_date();
        if (null !== $actual_delivery_date) {
          return DateTime::createFromImmutable($actual_delivery_date);
        }
        return DateTime::createFromImmutable($invoice->getDate_supplied());
      }
      return DateTime::createFromImmutable($invoice->getDate_supplied());
    }
    return null;
  }

  /**
   * 
   * @param Inv $invoice
   * @param InvoicePeriod $invoice_period
   * @param iiaR $iiaR
   * @param cpR $cpR
   * @param SOIR $soiR
   * @param ACIIR $aciiR
   * @param unpR $unpR
   * @return array
   * @throws PeppolProductUnitCodeNotFoundException
   * @throws PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException
   * @throws PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException
   * @throws PeppolClientNotFoundException
   */
  private function build_invoice_lines_array(Inv $invoice, InvoicePeriod $invoice_period, iiaR $iiaR, cpR $cpR, SOIR $soiR, ACIIR $aciiR, unpR $unpR): array {
    $client = $invoice->getClient();
    if ($client) {
      $client_peppol = $cpR->repoClientPeppolLoadedquery((string) $client->getClient_id());
      if ($client_peppol) {
        $invoiceLines = [];
        $b = Schema::CBC;
        $a = Schema::CAC;
        /**
         * @var InvItem $item
         */
        foreach ($invoice->getItems() as $item) {
          $product = $item->getProduct();
          if (null!==$product) {
            if (empty($product->getUnit_peppol_id())) {
               throw new PeppolProductUnitCodeNotFoundException($this->t, $product);
            }
          }  
          // Item Identification number eg. TRQWERQERQ9879
          $peppol_po_itemid = $this->Peppol_po_itemid($item, $soiR);
          if (empty($peppol_po_itemid)) {
            throw new PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException($this->t);
          }
          
          // Item Line Number eg. Line 1 of 4
          $peppol_po_lineid = $this->Peppol_po_lineid($item, $soiR);
          if (empty($peppol_po_lineid)) {
            throw new PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException($this->t);
          }
          /**
           * Error
           * Location: invoice_6p-24oxnINV115_peppol
           * Element/context: /:Invoice[1]/cac:InvoiceLine[1]/cac:Item[1]/cac:CommodityClassification[1]/cbc:ItemClassificationCode[1]
           * XPath test: ((not(contains(normalize-space(@listID), ' ')) and contains(' AA AB AC AD AE AF AG AH AI AJ AK AL AM AN AO AP .. ZZZ ', concat(' ', normalize-space(@listID), ' '))))
           * Error message: [BR-CL-13]-Item classification identifier identification scheme identifier MUST be coded using one of the UNTDID 7143 list.
           */
          $listid = $product?->getProduct_icc_listid();
          if (empty($listid) && null!==$product) {
            throw new PeppolProductItemClassificationCodeSchemeIdNotFoundException($this->t, $product); 
          }
          $price = (null !== $item->getPrice() ? $item->getPrice() : 0.00);
          $discount = (null !== $item->getDiscount_amount() ? $item->getDiscount_amount() : 0.00);
          
          $item_id = $item->getId();
          $inv_item_amount = $this->getInvItemAmount((string) $item_id, $iiaR);
          if (null !== $inv_item_amount) {
            $sub_total = $inv_item_amount->getSubtotal();
            if (null !== $sub_total && null !== $price && null !== $discount) {
              $convert_sub_total = $this->currency_converter($sub_total);
              $unit_peppol_id = $item->getProduct()?->getUnit_peppol_id();
              if (null!==$unit_peppol_id) {
                $unit_peppol = $unpR->repoUnitPeppolLoadedquery($unit_peppol_id);
                if (null!==$unit_peppol) {
                  // using Array Format 2
                  // ..\vendor\sabre\xml\lib\Writer.php
                  // https://kinsta.com/blog/php-8-2/#deprecate--string-interpolation
                  // Note: The following string interpolation, ie. curly brackets within double quotes, conforms with php 8.2 requirements
                  $invoiceLines[(int) $item_id] = ["name" => "{$a}InvoiceLine", 
                  "value" => [

                  ["name" => "{$b}ID", "value" => (string)$item_id],
                  ["name" => "{$b}Note", "value" => $item->getDescription()],
                  ["name" => "{$b}InvoicedQuantity", 
                             "value" => (string) $item->getQuantity(), 
                             "attributes" => [
                             "unitCode" => $unit_peppol->getCode()]],
                  ["name" => "{$b}LineExtensionAmount", "value" => $convert_sub_total, "attributes" => ["currencyID" => $this->to_currency]],
                  ["name" => "{$b}AccountingCost", "value" => $client_peppol->getAccountingCost()],
                  ["name" => "{$a}InvoicePeriod", "value" => [
                      ["name" => "{$b}StartDate", "value" => $invoice_period->getStartDate()],
                      ["name" => "{$b}EndDate", "value" => $invoice_period->getEndDate()]
                  ]],
                  ["name" => "{$a}OrderLineReference", "value" => [
                      ["name" => "{$b}LineID", "value" => $peppol_po_lineid,]
                  ]],
                  ["name" => "{$a}DocumentReference", "value" => [
                      ["name" => "{$b}ID", "value" => $invoice->getNumber()],
                      ["name" => "{$b}DocumentTypeCode", "value" => '130']
                    ],
                  ],
                  ["name" => "{$a}Item", "value" => [
                      ["name" => "{$b}Description", "value" => $item->getDescription()],
                      ["name" => "{$b}Name", "value" => $item->getName()],
                      ["name" => "{$a}BuyersItemIdentification", "value" =>
                        [
                          ["name" => "{$b}ID", "value" => $peppol_po_itemid],
                        ]
                      ],
                      ["name" => "{$a}SellersItemIdentification", "value" => 
                        [
                          ["name" => "{$b}ID", "value" => $item->getProduct()?->getProduct_sku()]
                        ]
                      ],
                      ["name" => "{$a}StandardItemIdentification", "value" => 
                        [
                          ["name" => "{$b}ID", "value" => $item->getProduct()?->getProduct_sii_id(),
                            "attributes" => [
                              "schemeID" => $item->getProduct()?->getProduct_sii_schemeid()
                            ]
                          ],
                        ]
                      ],
                      ["name" => "{$a}OriginCountry", "value" => [
                          ["name" => "{$b}IdentificationCode", "value" => $item->getProduct()?->getProduct_country_of_origin_code()]
                        ]
                      ],
                      ["name" => "{$a}CommodityClassification", "value" => 
                        [
                          ["name" => "{$b}ItemClassificationCode", "value" => $item->getProduct()?->getProduct_icc_id(),
                            "attributes" => [
                              "listID" => $item->getProduct()?->getProduct_icc_listid(),
                              "listVersionID" => $item->getProduct()?->getProduct_icc_listversionid()
                            ]
                          ]
                        ]
                      ],
                      ["name" => "{$a}ClassifiedTaxCategory", "value" => 
                        [
                          ["name" => "{$b}ID", "value" => $item->getTaxRate()?->getPeppol_tax_rate_code()],
                          ["name" => "{$b}Percent", "value" => $item->getTaxRate()?->getTax_rate_percent()],
                          ["name" => "{$a}TaxScheme", "value" => 
                            [ 
                              ["name" => "{$b}ID", "value" => 'VAT'],
                            ],
                          ],
                        ],
                      ],
                    ],
                    ["name" => "{$a}AdditionalItemProperty", "value" => 
                      [
                        ["name" => "{$b}Name", "value" => $item->getProduct()?->getProduct_additional_item_property_name()],
                        ["name" => "{$b}Value", "value" => $item->getProduct()?->getProduct_additional_item_property_value()],
                      ],
                    ],
                  ],        
                  ["name" => "{$a}Price", "value" => 
                    [
                      ["name" => "{$b}PriceAmount", "value" => $this->currency_converter($price), "attributes" => ["currencyID" => $this->s->get_setting('currency_code_to')]],
                      ["name" => "{$b}BaseQuantity", "value" => $item->getQuantity(), "attributes" => ["unitCode" => $unit_peppol->getCode()]],
                      // This is an allowance/discount that is specific to price
                      ["name" => "{$a}AllowanceCharge", "value" => 
                        [
                          // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Price/cac-AllowanceCharge/cbc-ChargeIndicator/
                          // Mandatory false:  discount on the price => An allowance or discount => ChargeIndicator = false
                          // If there is a reduction of the price, the discount must be shown here
                          ["name" => "{$b}ChargeIndicator", "value" => 'false'],
                          ["name" => "{$b}Amount", "value" => $this->currency_converter($discount), "attributes" => ["currencyID" => $this->s->get_setting('currency_code_to')]],
                          // Item gross price
                          // Base Amount: The unit price, exclusive of VAT, before subtracting Item price discount, can not be negative
                          ["name" => "{$b}BaseAmount", "value" => $this->currency_converter($price), "attributes" => ["currencyID" => $this->s->get_setting('currency_code_to')]],
                        ],
                      ],
                     ],
                    ],
                  ],          
                  ];

                  $inv_item_allowance_charges = $aciiR->repoInvItemquery((string) $item_id);
                  /**
                   * @var InvItemAllowanceCharge $acii
                   */
                  foreach ($inv_item_allowance_charges as $acii) {
                    /**
                     * @var array $invoiceLines[$item_id]
                     * @var array $item_line
                     */
                    $item_line = $invoiceLines[$item_id];
                    /**
                     * @var array $item_line['AllowancesCharges']
                     * @var array $item_line['AllowancesCharges'][]
                     */
                    $item_line['AllowancesCharges'][] = [
                      ["name" => "{$a}AllowanceCharge", "value" => [
                          ["name" => "{$b}ChargeIndicator", "value" => $acii->getAllowanceCharge()?->getIdentifier()],
                          ["name" => "{$b}AllowanceChargeReasonCode", "value" => $acii->getAllowanceCharge()?->getReason_code()],
                          ["name" => "{$b}AllowanceChargeReason", "value" => $acii->getAllowanceCharge()?->getReason()],
                          ["name" => "{$b}MultiplierFactorNumeric", "value" => ''],
                          ["name" => "{$b}Amount", "value" => $acii->getAllowanceCharge()?->getMultiplier_factor_numeric()],
                          ["name" => "{$b}BaseAmount", "value" => $acii->getAllowanceCharge()?->getBase_amount()],
                        ]],
                    ];
                  } // inv item allowance charge
                } // null!== $unit_peppol  
              } // null!== $unit_peppol_id
            } // null !== $subtotal  
          } // null!== $inv_item_amount
        } // foreach
        return $invoiceLines;
      } else {
        throw new PeppolClientNotFoundException($this->t);
      }
    } else {
      throw new PeppolClientNotFoundException($this->t);
    }
  }

  /**
   * Build a payment means array from the config/common/params file
   * @return array
   */
  private function build_peppol_payment_means_array(): array {
    $config_peppol = $this->s->get_config_peppol();
    /**
     * @var array $config_peppol
     * @var array $config_peppol['PaymentMeans']
     */
    $config = $config_peppol['PaymentMeans'] ?? [];
    /**
     * @var array $config['PayeeFinancialAccount']
     * @var array $config['PayeeFinancialAccount']['FinancialInstitutionBranch']
     * @var string $config['PayeeFinancialAccount']['ID']
     * @var string $config['PayeeFinancialAccount']['Name']
     */
    $payment_means_array = [
      'PayeeFinancialAccount' => [
        // eg. IBAN number
        'ID' => $config['PayeeFinancialAccount']['ID'] ?? '',
        'Name' => $config['PayeeFinancialAccount']['Name'] ?? '',
        'FinancialInstitutionBranch' => [
          'ID' => $config['PayeeFinancialAccount']['FinancialInstitutionBranch']['ID'] ?? ''
        ]
      ],
    ];
    return $payment_means_array;
  }

  /**
   * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cbc-BuyerReference/
   * @see https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
   * @param Inv $invoice
   * @param cpR $cpR
   * @return string
   */
  private function BuyerReference(Inv $invoice, cpR $cpR): string {
    $client = $invoice->getClient();
    if (null !== $client) {
      $client_id = $client->getClient_id();
      if (null !== $client_id) {
        $client_peppol = $cpR->repoClientPeppolLoadedquery((string) $client_id);
        if (null !== $client_peppol) {
          $buyer_fallback_reference = $client_peppol->getBuyerReference();
          return $buyer_fallback_reference;
        } else {
          throw new PeppolBuyerReferenceNotFoundException();
        }
      } else {
        throw new PeppolClientNotFoundException($this->t);
      }
      throw new PeppolClientNotFoundException($this->t);
    }
    throw new PeppolClientNotFoundException($this->t);
  }

  /**
   * @param Inv $invoice
   * @param ContractRepo $contractRepo
   * @return string|null
   */
  public function ContractDocumentReference(Inv $invoice, ContractRepo $contractRepo): string|null {
    $contract_id = $invoice->getContract_id();
    $contract = $contractRepo->repoContractquery($contract_id);
    if ($contract) {
      return $contract->getReference();
    }
    return null;
  }

  /**
   * @param Inv $invoice
   * @param DelRepo $delRepo
   * @return Party|null
   */
  public function DeliveryParty(Inv $invoice, DelRepo $delRepo, DelPartyRepo $delpartyRepo): Party|null {
    $invoice_id = $invoice->getId();
    if (null !== $invoice_id) {
      $inv = $delRepo->repoPartyquery($invoice_id);
      if ($inv) {
        $delivery_party_id = $inv->getDelivery_party_id();
        $delparty = $delpartyRepo->repoDeliveryPartyquery($delivery_party_id);
        $partyName = (null !== $delparty ? $delparty->getPartyName() : null);
        $party = null !== $partyName ? new Party($this->t, $partyName, null, null, null, null, null, null, null, null, null) : null;
        return $party;
      }
    }
    return null;
  }

  /**
   * Default config document currency code
   * Subjective to $s->get_setting('currency_code_to')
   * @return string
   */
  public function DocumentCurrencyCode(): string {
    /** @var array $config */
    $config = $this->s->get_config_peppol();
    /** @var string $config['DocumentCurrencyCode'] */
    return $config['DocumentCurrencyCode'] ?? '';
  }

  /**
   * @param string $item_id
   * @param IIAR $iiaR
   * @return null|InvItemAmount
   */
  public function getInvItemAmount(string $item_id, IIAR $iiaR): ?InvItemAmount {
    $inv_item_amount = $iiaR->repoInvItemAmountquery($item_id);
    if (null !== $inv_item_amount) {
      return $inv_item_amount;
    }
    return null;
  }

  /**
   * Retrieve the Client/Customer's purchase order item id
   * @param InvItem $item
   * @param SOIR $soiR
   * @return string|null
   * @throws PeppolSalesOrderPurchaseOrderNumberNotExistException
   * @throws PeppolSalesOrderItemNotExistException
   */
  private function Peppol_po_itemid(InvItem $item, SOIR $soiR): string|null {
    $sales_order_item_id = $item->getSo_item_id();
    if ($sales_order_item_id) {
      $sales_order_item = $soiR->repoSalesOrderItemquery($sales_order_item_id);
      if (null !== $sales_order_item) {
        $peppol_po_itemid = $sales_order_item->getPeppol_po_itemid();
        if (!empty($peppol_po_itemid)) {
          return $peppol_po_itemid;
        } else {
          throw new PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException($this->t);
        }
      } else {
        throw new PeppolSalesOrderItemNotExistException($this->t);
      }
    }
    return null;
  }
  
  /**
   * Retrieve the Client/Customer's purchase order line id
   * @param InvItem $item
   * @param SOIR $soiR
   * @return string|null
   * @throws PeppolSalesOrderPurchaseOrderNumberNotExistException
   * @throws PeppolSalesOrderItemNotExistException
   */
  private function Peppol_po_lineid(InvItem $item, SOIR $soiR): string|null {
    $sales_order_item_id = $item->getSo_item_id();
    if ($sales_order_item_id) {
      $sales_order_item = $soiR->repoSalesOrderItemquery($sales_order_item_id);
      if (null !== $sales_order_item) {
        $peppol_po_lineid = $sales_order_item->getPeppol_po_lineid();
        if (!empty($peppol_po_lineid)) {
          return $peppol_po_lineid;
        } else {
          throw new PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException($this->t);
        }
      } else {
        throw new PeppolSalesOrderItemNotExistException($this->t);
      }
    }
    return null;
  }

  /**
   * Retrieve Client's Account Id given by Supplier
   * @param Inv $invoice
   * @param cpR $cpR
   * @return string
   */
  private function SupplierAssignedAccountId(Inv $invoice, cpR $cpR): string {
    $client = $invoice->getClient();
    $supplier_assigned_account_id = '';
    if (null !== $client) {
      $client_peppol = $cpR->repoClientPeppolLoadedquery((string) $client->getClient_id());
      $supplier_assigned_account_id = null !== $client_peppol ? $client_peppol->getSupplierAssignedAccountId() :
        throw new PeppolClientIdNotFoundException($this->t);
    } else {
      throw new PeppolClientNotFoundException($this->t);
    }
    if (empty($supplier_assigned_account_id)) {
      throw new PeppolSupplierAssignedAccountIdNotFoundException($this->t);
    } else {
      return $supplier_assigned_account_id;
    }
  }

  /**
   * @return Contact
   */
  public function SupplierContact(): Contact {
    $config = $this->s->get_config_peppol();
    /**
     * @var array $config
     * @var array $config['Contact']
     */
    return new Contact(
      (string) $config['Contact']['Name'],
      (string) $config['Contact']['FirstName'],
      (string) $config['Contact']['LastName'],
      (string) $config['Contact']['Telephone'],
      /**
       * Supplier's Telefax must not be supplied => null
       * Warning
       * Location: invoice_sqKOvgahINV107_peppol
       * Element/context: /:Invoice[1]
       * XPath test: not(cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telefax)
       * Error message: [UBL-CR-190]-A UBL invoice should not include the AccountingSupplierParty Party Contact Telefax
       */
      null,
      (string) $config['Contact']['ElectronicMail'],
    );
  }

  /**
   *
   * @return string
   */
  public function SupplierEndpointID(): string {
    $config = $this->s->get_config_peppol();
    /**
     * @var array $config
     * @var array $config['EndPointID']
     */
    return (string) $config['EndPointID']['value'];
  }

  /**
   * @return string
   */
  public function SupplierEndPointIDSchemeID(): string {
    $config = $this->s->get_config_peppol();
    /**
     * @var array $config
     * @var array $config['EndPointID']
     */
    return (string) $config['EndPointID']['schemeID'];
  }

  /**
   *
   * @return PartyLegalEntity
   */
  public function SupplierPartyLegalEntity(): PartyLegalEntity {
    $config = $this->s->get_config_peppol();
    /**
     * @var array $config
     * @var array $config['PartyLegalEntity']
     */
    return new PartyLegalEntity(
      (string) $config['PartyLegalEntity']['RegistrationName'],
      (string) $config['PartyLegalEntity']['CompanyID'],
      (array) $config['PartyLegalEntity']['Attributes'],
      (string) $config['PartyLegalEntity']['CompanyLegalForm'],
    );
  }

  /**
   * If the DateTimeImmutable formatted tax point is 1901/01/01, it is NOT a tax point
   * @param Inv $invoice
   * @return bool
   */
  private function no_tax_point_date(Inv $invoice): bool {
    $date = ($invoice->getDate_tax_point())->format('Y-m-d');
    return $date === '1901/01/01';
  }

  /**
   * @return PartyTaxScheme
   */
  public function SupplierPartyTaxScheme(): PartyTaxScheme {
    $config = $this->s->get_config_peppol();
    /**
     * @var array $config['PartyTaxScheme']
     * @var array $config['PartyTaxScheme']['TaxScheme']
     */
    $tax_scheme = $config['PartyTaxScheme']['TaxScheme'];
    /**
     * @var string $tax_scheme['ID']
     */
    $id = $tax_scheme['ID'] ?? '';
    
    $taxScheme = new TaxScheme(
      $id,
    );
    /**
     * @var array $config
     * @var array $config['PartyTaxScheme']
     */
    return new PartyTaxScheme(
      (string) $config['PartyTaxScheme']['CompanyID'],
      $taxScheme
    );
  }

  /**
   *
   * @return Address
   */
  public function SupplierPostalAddress(): Address {
    $config = $this->s->get_config_peppol();
    /**
     * @var array $config
     * @var array $config['SupplierPartyIdentificationPostalAddress']
     * @var array $config['SupplierPartyIdentificationPostalAddress']['Country']
     * @var array $config['SupplierPartyIdentificationPostalAddress']['AddressLine']
     */
    return new Address(
      (string) $config['SupplierPartyIdentificationPostalAddress']['StreetName'],
      (string) $config['SupplierPartyIdentificationPostalAddress']['AdditionalStreetName'],
      (string) $config['SupplierPartyIdentificationPostalAddress']['AddressLine']['Line'],
      (string) $config['SupplierPartyIdentificationPostalAddress']['CityName'],
      (string) $config['SupplierPartyIdentificationPostalAddress']['PostalZone'],
      (string) $config['SupplierPartyIdentificationPostalAddress']['CountrySubentity'],
      new Country(
        (string) $config['SupplierPartyIdentificationPostalAddress']['Country']['IdentificationCode'],
        (string) $config['SupplierPartyIdentificationPostalAddress']['Country']['ListId']
      ),
      /**
       * Warning
       * Location: invoice_IP4PC20OINV107_peppol
       * Element/context: /:Invoice[1]
       * XPath test: not(cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:BuildingNumber)
       * Error message: [UBL-CR-155]-A UBL invoice should not include the AccountingSupplierParty Party PostalAddress BuildingNumber
       */
       true,
       false,
       false
    );
  }

  /**
   * Used later in src\Invoice\Ubl\TaxTotal xmlSerialize
   *
   * If the document currency code is different to the company's currency code
   * $doc_cc_tax_amount will be different
   *
   * @param float $supp_tax_cc_tax_amount
   * @return array
   */
  private function TaxAmounts(float $supp_tax_cc_tax_amount): array {
    // doc_cc_tax_amount will be compared with supp_tax_cc_amount
    // so make sure same type ie. float
    // currency_converter outputs a string
    $doc_cc_tax_amount = (float) $this->currency_converter($supp_tax_cc_tax_amount);
    $tax_amounts = [
      // first tax total
      'supp_tax_cc_tax_amount' => $supp_tax_cc_tax_amount,
      'supp_tax_cc' => $this->from_currency,
      // second tax total
      'doc_cc_tax_amount' => $doc_cc_tax_amount,
      'doc_cc' => $this->to_currency
    ];
    return $tax_amounts;
  }

  /**
   *
   * @param Inv $invoice
   * @param iiaR $iiaR
   * @param TRR $trR
   * @return array
   * @throws PeppolTaxCategoryCodeNotFoundException
   * @throws PeppolTaxCategoryPercentNotFoundException
   */
  private function build_TaxSubtotal_array(Inv $invoice, iiaR $iiaR, TRR $trR): array {
    $array = [];
    $item_tax_rates = [];
    $taxable_amount_total = 0;
    $tax_amount_total = 0;
    /**
     * What tax types do the items use? Build a list of tax type ids
     * @var InvItem $item
     */
    foreach ($invoice->getItems() as $item) {
      if (!in_array($item->getTax_rate_id(), $item_tax_rates)) {
        array_push($item_tax_rates, $item->getTax_rate_id());
      }
    }
    $tax_percent = 0.00;
    $tax_category = '';
    foreach ($item_tax_rates as $id) {
      $taxRate = $trR->repoTaxRatequery($id);
      if (null!==$taxRate) {
        $tax_category = $taxRate->getPeppol_tax_rate_code();
        $tax_percent = $taxRate->getTax_rate_percent();
        // Throw an exception if any Tax Category does not have a code
        if (empty($tax_category)) {
          throw new PeppolTaxCategoryCodeNotFoundException($this->t);
        }
        if (null === $tax_percent) {
          throw new PeppolTaxCategoryPercentNotFoundException($this->t);
        }
        if (!empty($id)) {
          $taxable_amount_total = 0;
          $tax_amount_total = 0;
          $items = $invoice->getItems();
          /**
           * @var InvItem $item
           */
          foreach ($items as $item) {
            $item_id = $item->getId();
            if (null !== $item_id) {
              if ($id == $item->getTaxRate()?->getTax_rate_id()) {
                $item_amount = $iiaR->repoInvItemAmountquery((string) $item_id);
                if (null !== $item_amount) {
                  $item_sub_total = $item_amount->getSubtotal();
                  if (!empty($item_sub_total)) {
                    $taxable_amount_total += $item_sub_total;
                  }
                  $item_tax_total = $item_amount->getTax_total();
                  if (!empty($item_tax_total)) {
                    $tax_amount_total += $item_tax_total;
                  }
                }
              }
            }
          }
        }    
      
        /**
         * @var array $array[$id]
         */
        $sub_array = $array[$id] ?? [];
        /**
         *  @var float $sub_array['TaxableAmounts']
         */
        $sub_array['TaxableAmounts'] = (float) $this->currency_converter($taxable_amount_total);
        /**
         *  @var float $sub_array['TaxAmount']
         */
        $sub_array['TaxAmount'] = (float) $this->currency_converter($tax_amount_total);
        /**
         *  @var float $sub_array['TaxCategory']
         */
        $sub_array['TaxCategory'] = $tax_category;
        /**
         *  @var float $sub_array['TaxCategoryPercent']
         */
        $sub_array['TaxCategoryPercent'] = $tax_percent;
        /**
         *  @var string $sub_array['DocumentCurrency']
         */
        $sub_array['DocumentCurrency'] = $this->to_currency;
        $array[$id] = $sub_array;
      } // null!==$id
    }
    return $array;
  }

  /**
   * Build  \Invoice\Ubl\Country.php with CountryHelper and country_name
   * @param string|null $streetName
   * @param string|null $additionalStreetName
   * @param string|null $buildingNumber
   * @param string|null $cityName
   * @param string|null $postalZone
   * @param string|null $countrySubEntity
   * @param string $country_name
   * @return Address
   */
  public function ubl_delivery_location(?string $streetName, ?string $additionalStreetName, ?string $buildingNumber, ?string $cityName, ?string $postalZone, ?string $countrySubEntity, string $country_name): Address {
    //https://docs.peppol.eu/poacc/billing/3.0/rules/ubl-tc434/
    $country_helper = new CountryHelper();
    $cic = $country_helper->get_country_identification_code_with_league($country_name);
    $country = new Country($cic, 'ISO3166-1:Alpha2');
    $deliveryLocation = new Address(
      $streetName,
      $additionalStreetName,
      $buildingNumber,
      $cityName,
      $postalZone,
      $countrySubEntity,
      $country,
      false,
      false,
      /**
       * Delivery Location not include building number => true
       * Warning
       * Location: invoice_sqKOvgahINV107_peppol 
       * Element/context: /:Invoice[1]
       * XPath test: not(cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:BuildingNumber)
       * Error message: [UBL-CR-367]-A UBL invoice should not include the Delivery DeliveryLocation Address BuildingNumber
       */
      true
    );
    return $deliveryLocation;
  }

  /**
   * This function creates the Invoice/Delivery period by outputting
   * the month's start and end date based on either the tax point
   * or the date_created (=> a.k.a date issued). If no tax point date has been calculated
   * due to goods not delivered yet, there will be no need for a description code in the Invoice/Delivery Period
   *
   * The description code indicates what the tax point date calculation will be
   * based on in the future when the goods are delivered or paid.
   *
   * A tax point is only valid if different to the date_created a.k.a date issued
   *
   * If a Peppol Invoice has a visible and calculated tax point it will not need a description code
   * in the Invoice Period since they are mutually exclusive, as explained above.
   *
   * Delivered/paid already => tax/point can be calculated => no need for a description code => 'Invoice Period'
   * Not delivered/paid yet => tax point cannot be calculated yet => need a description code => 'Delivery Period'
   *
   * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cbc-TaxPointDate/
   * @see https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
   * @param Inv $invoice
   * @param SRepo $s
   * @return InvoicePeriod
   */
  public function ubl_invoice_period(Inv $invoice, SRepo $s): InvoicePeriod {

    // @see InvService set_tax_point

    $datehelper = new DateHelper($s);
    $date_tax_point = $invoice->getDate_tax_point();
    $date_created_or_issued = $invoice->getDate_created();
    $date_supplied = $invoice->getDate_supplied();
    if ($date_tax_point === $date_created_or_issued) {
      // => there is NO need for a visible peppol tax point
      // therefore base the invoice period on the date_created
      // and include the description code Business Rule (BT-8)
      // Note: The description code describes what date the future
      // tax point will be based on ie. date supplied/delivery date
      // or date created or payment date
      $input_date = DateTime::createFromImmutable($date_created_or_issued);
      $description_code = $this->get_description_code_for_tax_point($invoice, $date_supplied, $date_created_or_issued);
    } else {
      // => there IS a need for a visible peppol tax point
      // therefore base the invoice period on the tax point
      // but exclude the description code Business Rule (BT-8)
      $input_date = DateTime::createFromImmutable($date_tax_point);
      $description_code = '';
    }
    // if the invoice has a delivery period use the delivery period's begin and end date
    $start_end_array = $datehelper->invoice_period_start_end($invoice, $input_date);
    $startDate = (string) $start_end_array['StartDate'];
    $endDate = (string) $start_end_array['EndDate'];
    return new InvoicePeriod($startDate, $endDate, $description_code);
  }

  /**
   *
   * @param Inv $invoice
   * @return string
   */
  public function UploadsTempPeppolXmlFileNamePathWithExt(Inv $invoice): string {
    $path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'Uploads'
      . DIRECTORY_SEPARATOR . 'Temp'
      . DIRECTORY_SEPARATOR . 'Peppol'
      . DIRECTORY_SEPARATOR . 'invoice_' . Random::string(8)
      . ($invoice->getNumber() ?? '_search_null_invoice_id_' ) . '_peppol.xml';

    return $path;
  }

  /**
   * Return a number represented as a string indicating how the tax point was determined: according to date supplied or date created/issued
   * @see src\Invoice\Inv\InvService set_tax_point function
   * @param Inv $inv
   * @param DateTimeImmutable $date_supplied
   * @param DateTimeImmutable $date_created
   * @return string
   */
  public function get_description_code_for_tax_point(Inv $inv, DateTimeImmutable $date_supplied, DateTimeImmutable $date_created): string {
    // For yii3-i,'Date created' is used interchangeably with 'Date issued'
    // https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
    // The below array has been built manually from src\Invoice\Helpers\Peppol\uncl2005.php
    $uncl2005_subset_array = [
      'Invoice Issue Date/Time ie. Date Created/Issued' => '3',
      'Actual Delivery Date/Time ie. Date Supplied' => '35',
      'Paid to Date' => '432'
    ];
    if (null !== $inv->getClient()?->getClient_vat_id()) {
      if ($date_created > $date_supplied) {
        $diff = $date_supplied->diff($date_created)->format('%R%a');
        if ((int) $diff > 14) {
          // date supplied more than 14 days before invoice date => use date supplied
          return $uncl2005_subset_array['Actual Delivery Date/Time ie. Date Supplied'];
        } else {
          // if the issue date (created) is within 14 days after the supply (basic) date then use the issue/created date.
          return $uncl2005_subset_array['Invoice Issue Date/Time ie. Date Created/Issued'];
        }
      }
      if ($date_created < $date_supplied) {
        // normally set the tax point to the date_created
        return $uncl2005_subset_array['Invoice Issue Date/Time ie. Date Created/Issued'];
      }
      if ($date_created === $date_supplied) {
        // normally set the tax point to the date_created
        return $uncl2005_subset_array['Invoice Issue Date/Time ie. Date Created/Issued'];
      }
    }
    // If the client is not VAT registered, the tax point is the date supplied
    if (null == $inv->getClient()?->getClient_vat_id()) {
      return $uncl2005_subset_array['Actual Delivery Date/Time ie. Date Supplied'];
    }
    // Default to date created
    return $uncl2005_subset_array['Invoice Issue Date/Time ie. Date Created/Issued'];
  }

  /**
   * TODO phase 2: insert translator here
   * @see https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5189/
   * @return array
   */
  private function get_peppol_charges_subset_array(): array {
    $array = [
      '41' => 'Bonus for works ahead of schedule',
      '42' => 'Other Bonus',
      '60' => 'Manufacturer’s consumer discount',
      '62' => 'Due to military status',
      '63' => 'Due to work accident',
      '64' => 'Special agreement',
      '65' => 'Production error discount',
      '66' => 'New outlet discount',
      '67' => 'Sample discount',
      '68' => 'End-of-range discount',
      '70' => 'Incoterm discount',
      '71' => 'Point of sales threshold allowance',
      '88' => 'Material surcharge/deduction',
      '95' => 'Discount',
      '100' => 'Special rebate',
      '102' => 'Fixed long term',
      '103' => 'Temporary',
      '104' => 'Standard',
      '105' => 'Yearly turnover'
    ];
    return $array;
  }

  /**
   * TODO phase 2: insert translator here
   * @see https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5189/
   * @return array
   */
  private function get_peppol_allowances_array(): array {
    $array = [
      'AA' => ['Advertising',
        'The service of providing advertising.'],
      'AAA' => ['Telecommunication',
        'The service of providing telecommunication activities and/or faclities.'],
      'AAC' => ['Technical modification',
        'The service of making technical modifications to a product.'],
      'AAD' => ['Job-order production',
        'The service of producing to order.'],
      'AAE' => ['Outlays',
        'The service of providing money for outlays on behalf of a trading partner.'],
      'AAF' => ['Off-premises',
        'The service of providing services outside the premises of the provider.'],
      'AAH' => ['Additional processing',
        'The service of providing additional processing.'],
      'AAI' => ['Attesting',
        'The service of certifying validity.'],
      'AAS' => ['Acceptance',
        'The service of accepting goods or services.'],
      'AAT' => ['Rush delivery',
        'The service to provide a rush delivery.'],
      'AAV' => ['Special construction',
        'The service of providing special construction.'],
      'AAY' => ['Airport facilities',
        'The service of providing airport facilities.'],
      'AAZ' => ['Concession',
        'The service allowing a party to use another party' . "'" . 's facilities.'],
      'ABA' => ['Compulsory storage',
        'The service provided to hold a compulsory inventory.'],
      'ABB' => ['Fuel removal',
        'Remove or off-load fuel from vehicle, vessel or craft.'],
      'ABC' => ['Into plane',
        'Service of delivering goods to an aircraft from local storage.'],
      'ABD' => ['Overtime',
        'The service of providing labour beyond the established limit of working hours.'],
      'ABF' => ['Tooling',
        'The service of providing specific tooling.'],
      'ABK' => ['Miscellaneous',
        'Miscellaneous services.'],
      'ABL' => ['Additional packaging',
        'The service of providing additional packaging.'],
      'ABN' => ['Dunnage',
        'The service of providing additional padding materials required to secure and protect a cargo within a shipping container.'],
      'ABR' => ['Containerisation',
        'The service of packing items into a container.'],
      'ABS' => ['Carton packing',
        'The service of packing items into a carton.'],
      'ABT' => ['Hessian wrapped',
        'The service of hessian wrapping.'],
      'ABU' => ['Polyethylene wrap packing',
        'The service of packing in polyethylene wrapping.'],
      'ACF' => ['Miscellaneous treatment',
        'Miscellaneous treatment service.'],
      'ACG' => ['Enamelling treatment',
        'The service of providing enamelling treatment.'],
      'ACH' => ['Heat treatment',
        'The service of treating with heat.'],
      'ACI' => ['Plating treatment',
        'The service of providing plating treatment.'],
      'ACJ' => ['Painting',
        'The service of painting.'],
      'ACK' => ['Polishing',
        'The service of polishing.'],
      'ACL' => ['Priming',
        'The service of priming.'],
      'ACM' => ['Preservation treatment',
        'The service of preservation treatment.'],
      'ACS' => ['Fitting',
        'Fitting service.'],
      'ADC' => ['Consolidation',
        'The service of consolidating multiple consignments into one shipment.'],
      'ADE' => ['Bill of lading',
        'The service of providing a bill of lading document.'],
      'ADJ' => ['Airbag',
        'The service of surrounding a product with an air bag.'],
      'ADK' => ['Transfer',
        'The service of transferring.'],
      'ADL' => ['Slipsheet',
        'The service of securing a stack of products on a slipsheet.'],
      'ADM' => ['Binding', 'Binding service.'],
      'ADN' => ['Repair or replacement of broken returnable package.',
        'The service of repairing or replacing a broken returnable package.'],
      'ADO' => ['Efficient logistics',
        'A code indicating efficient logistics services.'],
      'ADP' => ['Merchandising',
        'A code indicating that merchandising services are in operation.'],
      'ADQ' => ['Product mix',
        'A code indicating that product mixing services are in operation.'],
      'ADR' => ['Other services',
        'A code indicating that other non-specific services are in operation.'],
      'ADT' => ['Pick-up',
        'The service of picking up or collection of goods.'],
      'ADW' => ['Chronic illness',
        'The special services provided due to chronic illness.'],
      'ADY' => ['New product introduction',
        'A service provided by a buyer when introducing a new product from a suppliers range to the range traded by the buyer.'],
      'ADZ' => ['Direct delivery',
        'Direct delivery service.'],
      'AEA' => ['Diversion',
        'The service of diverting deliverables.'],
      'AEB' => ['Disconnect',
        'The service is a disconnection.'],
      'AEC' => ['Distribution',
        'Distribution service.'],
      'AED' => ['Handling of hazardous cargo',
        'A service for handling hazardous cargo.'],
      'AEF' => ['Rents and leases',
        'The service of renting and/or leasing.'],
      'AEH' => ['Location differential',
        'Delivery to a different location than previously contracted.'],
      'AEI' => ['Aircraft refueling',
        'Fuel being put into the aircraft.'],
      'AEJ' => ['Fuel shipped into storage',
        'Fuel being shipped into a storage system.'],
      'AEK' => ['Cash on delivery',
        'The provision of a cash on delivery (COD) service.'],
      'AEL' => ['Small order processing service',
        'A service related to the processing of small orders.'],
      'AEM' => ['Clerical or administrative services',
        'The provision of clerical or administrative services.'],
      'AEN' => ['Guarantee',
        'The service of providing a guarantee.'],
      'AEO' => ['Collection and recycling',
        'The service of collection and recycling products.'],
      'AEP' => ['Copyright fee collection',
        'The service of collecting copyright fees.'],
      'AES' => ['Veterinary inspection service',
        'The service of providing veterinary inspection.'],
      'AET' => ['Pensioner service',
        'Special service when the subject is a pensioner.'],
      'AEU' => ['Medicine free pass holder',
        'Special service when the subject holds a medicine free pass.'],
      'AEV' => ['Environmental protection service',
        'The provision of an environmental protection service.'],
      'AEW' => ['Environmental clean-up service',
        'The provision of an environmental clean-up service.'],
      'AEX' => ['National cheque processing service outside account area',
        'Service of processing a national cheque outside the ordering customer' . "'" . 's bank trading area.'],
      'AEY' => ['National payment service outside account area',
        'Service of processing a national payment to a beneficiary holding an account outside the trading area of the ordering customer' . "'" . 's bank.'],
      'AEZ' => ['National payment service within account area',
        'Service of processing a national payment to a beneficiary holding an account within the trading area of the ordering customer' . "'" . 's bank.'],
      'AJ' => ['Adjustments',
        'The service of making adjustments.'],
      'AU' => ['Authentication',
        'The service of authenticating.'],
      'CA' => ['Cataloguing',
        'The provision of cataloguing services.'],
      'CAB' => ['Cartage',
        'Movement of goods by heavy duty cart or vehicle.'],
      'CAD' => ['Certification',
        'The service of certifying.'],
      'CAE' => ['Certificate of conformance',
        'The service of providing a certificate of conformance.'],
      'CAF' => ['Certificate of origin',
        'The service of providing a certificate of origin.'],
      'CAI' => ['Cutting',
        'The service of cutting.'],
      'CAJ' => ['Consular service',
        'The service provided by consulates.'],
      'CAK' => ['Customer collection',
        'The service of collecting goods by the customer.'],
      'CAL' => ['Payroll payment service',
        'Provision of a payroll payment service.'],
      'CAM' => ['Cash transportation',
        'Provision of a cash transportation service.'],
      'CAN' => ['Home banking service',
        'Provision of a home banking service.'],
      'CAO' => ['Bilateral agreement service',
        'Provision of a service as specified in a bilateral special agreement.'],
      'CAP' => ['Insurance brokerage service',
        'Provision of an insurance brokerage service.'],
      'CAQ' => ['Cheque generation',
        'Provision of a cheque generation service.'],
      'CAR' => ['Preferential merchandising location',
        'Service of assigning a preferential location for merchandising.'],
      'CAS' => ['Crane',
        'The service of providing a crane.'],
      'CAT' => ['Special colour service',
        'Providing a colour which is different from the default colour.'],
      'CAU' => ['Sorting',
        'The provision of sorting services.'],
      'CAV' => ['Battery collection and recycling',
        'The service of collecting and recycling batteries.'],
      'CAW' => ['Product take back fee',
        'The fee the consumer must pay the manufacturer to take back the product.'],
      'CAX' => ['Quality control released',
        'Informs the stockholder it is free to distribute the quality controlled passed goods.'],
      'CAY' => ['Quality control held',
        'Instructs the stockholder to withhold distribution of the goods until the manufacturer has completed a quality control assessment.'],
      'CAZ' => ['Quality control embargo',
        'Instructs the stockholder to withhold distribution of goods which have failed quality control tests.'],
      'CD' => ['Car loading',
        'Car loading service.'],
      'CG' => ['Cleaning',
        'Cleaning service.'],
      'CS' => ['Cigarette stamping',
        'The service of providing cigarette stamping.'],
      'CT' => ['Count and recount',
        'The service of doing a count and recount.'],
      'DAB' => ['Layout/design',
        'The service of providing layout/design.'],
      'DAC' => ['Assortment allowance',
        'Allowance given when a specific part of a suppliers assortment is purchased by the buyer.'],
      'DAD' => ['Driver assigned unloading',
        'The service of unloading by the driver.'],
      'DAF' => ['Debtor bound',
        'A special allowance or charge applicable to a specific debtor.'],
      'DAG' => ['Dealer allowance',
        'An allowance offered by a party dealing a certain brand or brands of products.'],
      'DAH' => ['Allowance transferable to the consumer',
        'An allowance given by the manufacturer which should be transfered to the consumer.'],
      'DAI' => ['Growth of business',
        'An allowance or charge related to the growth of business over a pre-determined period of time.'],
      'DAJ' => ['Introduction allowance',
        'An allowance related to the introduction of a new product to the range of products traded by a retailer.'],
      'DAK' => ['Multi-buy promotion',
        'A code indicating special conditions related to a multi-buy promotion.'],
      'DAL' => ['Partnership',
        'An allowance or charge related to the establishment and on-going maintenance of a partnership.'],
      'DAM' => ['Return handling',
        'An allowance or change related to the handling of returns.'],
      'DAN' => ['Minimum order not fulfilled charge',
        'Charge levied because the minimum order quantity could not be fulfilled.'],
      'DAO' => ['Point of sales threshold allowance',
        'Allowance for reaching or exceeding an agreed sales threshold at the point of sales.'],
      'DAP' => ['Wholesaling discount',
        'A special discount related to the purchase of products through a wholesaler.'],
      'DAQ' => ['Documentary credits transfer commission',
        'Fee for the transfer of transferable documentary credits.'],
      'DL' => ['Delivery',
        'The service of providing delivery.'],
      'EG' => ['Engraving',
        'The service of providing engraving.'],
      'EP' => ['Expediting',
        'The service of expediting.'],
      'ER' => ['Exchange rate guarantee',
        'The service of guaranteeing exchange rate.'],
      'FAA' => ['Fabrication',
        'The service of providing fabrication.'],
      'FAB' => ['Freight equalization',
        'The service of load balancing.'],
      'FAC' => ['Freight extraordinary handling',
        'The service of providing freight' . "'" . 's extraordinary handling.'],
      'FC' => ['Freight service',
        'The service of moving goods, by whatever means, from one place to another.'],
      'FH' => ['Filling/handling',
        'The service of providing filling/handling.'],
      'FI' => ['Financing',
        'The service of providing financing.'],
      'GAA' => ['Grinding',
        'The service of grinding.'],
      'HAA' => ['Hose',
        'The service of providing a hose.'],
      'HD' => ['Handling',
        'Handling service.'],
      'HH' => ['Hoisting and hauling',
        'The service of hoisting and hauling.'],
      'IAA' => ['Installation',
        'The service of installing.'],
      'IAB' => ['Installation and warranty',
        'The service of installing and providing warranty.'],
      'ID' => ['Inside delivery',
        'The service of providing delivery inside.'],
      'IF' => ['Inspection',
        'The service of inspection.'],
      'IR' => ['Installation and training',
        'The service of providing installation and training.'],
      'IS' => ['Invoicing',
        'The service of providing an invoice.'],
      'KO' => ['Koshering',
        'The service of preparing food in accordance with Jewish law.'],
      'L1' => ['Carrier count',
        'The service of counting by the carrier.'],
      'LA' => ['Labelling',
        'Labelling service.'],
      'LAA' => ['Labour',
        'The service to provide required labour.'],
      'LAB' => ['Repair and return',
        'The service of repairing and returning.'],
      'LF' => ['Legalisation',
        'The service of legalising.'],
      'MAE' => ['Mounting',
        'The service of mounting.'],
      'MI' => ['Mail invoice',
        'The service of mailing an invoice.'],
      'ML' => ['Mail invoice to each location',
        'The service of mailing an invoice to each location.'],
      'NAA' => ['Non-returnable containers',
        'The service of providing non-returnable containers.'],
      'OA' => ['Outside cable connectors',
        'The service of providing outside cable connectors.'],
      'PA' => ['Invoice with shipment',
        'The service of including the invoice with the shipment.'],
      'PAA' => ['Phosphatizing (steel treatment)',
        'The service of phosphatizing the steel.'],
      'PC' => ['Packing',
        'The service of packing.'],
      'PL' => ['Palletizing',
        'The service of palletizing.'],
      'RAB' => ['Repacking',
        'The service of repacking.'],
      'RAC' => ['Repair',
        'The service of repairing.'],
      'RAD' => ['Returnable container',
        'The service of providing returnable containers.'],
      'RAF' => ['Restocking',
        'The service of restocking.'],
      'RE' => ['Re-delivery',
        'The service of re-delivering.'],
      'RF' => ['Refurbishing',
        'The service of refurbishing.'],
      'RH' => ['Rail wagon hire',
        'The service of providing rail wagons for hire.'],
      'RV' => ['Loading',
        'The service of loading goods.'],
      'SA' => ['Salvaging',
        'The service of salvaging.'],
      'SAA' => ['Shipping and handling',
        'The service of shipping and handling.'],
      'SAD' => ['Special packaging',
        'The service of special packaging.'],
      'SAE' => ['Stamping',
        'The service of stamping.'],
      'SAI' => ['Consignee unload',
        'The service of unloading by the consignee.'],
      'SG' => ['Shrink-wrap',
        'The service of shrink-wrapping.'],
      'SH' => ['Special handling',
        'The service of special handling.'],
      'SM' => ['Special finish',
        'The service of providing a special finish.'],
      'SU' => ['Set-up',
        'The service of setting-up.'],
      'TAB' => ['Tank renting',
        'The service of providing tanks for hire.'],
      'TAC' => ['Testing',
        'The service of testing.'],
      'TT' => ['Transportation - third party billing',
        'The service of providing third party billing for transportation.'],
      'TV' => ['Transportation by vendor',
        'The service of providing transportation by the vendor.'],
      'V1' => ['Drop yard',
        'The service of delivering goods at the yard.'],
      'V2' => ['Drop dock',
        'The service of delivering goods at the dock.'],
      'WH' => ['Warehousing',
        'The service of storing and handling of goods in a warehouse.'],
      'XAA' => ['Combine all same day shipment',
        'The service of combining all shipments for the same day.'],
      'YY' => ['Split pick-up',
        'The service of providing split pick-up.'],
      'ZZZ' => ['Mutually defined',
        'A code assigned within a code list to be used on an interim basis and as defined among trading partners until a precise code can be assigned to the code list.'],
    ];
    return $array;
  }

  /**
   * Used with product/edit
   * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/icd.xml
   * @return array
   */
  private function getIso_6523_icd(): array {
    // 'ISO 6523 ICD list',
    // 'Identifier' => 'ICD',
    //  'Agency' => 'The International Organization for Standardization (ISO)',
    $iso6523_icd = [
      0 => [
        'Id' => '0002',
        'Name' => 'System Information et Repertoire des Entreprise et des Etablissements: SIRENE',
        'Description' => 'Notes on Use of Code: The Sirene number is used in France mainly for the official registration in the Trade Register and as the only number used between authorities and organizations, and between authorities when dealing with data interchange on organizations. Issuing agency: Institut National de la Statistique et des Etudes Economiques, (I.N.S.E.E.), France.',
      ],
      1 => [
        'Id' => '0003',
        'Name' => 'Codification Numerique des Etablissments Financiers En Belgique',
        'Description' => 'Notes on Use of Code: Many financial institutions have more than one code number, e.g. to indicate each branch individually. The codes can be reallocated over the time (mostly in the case where a financial institution terminates its activity). Some code numbers are currently unused. Code numbers 990 through 999 are reserved. Issuing agency: Association Belge des Banques, Belgium.',
      ],
      2 => [
        'Id' => '0004',
        'Name' => 'NBS/OSI NETWORK',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing and naming tree as depicted in Addendum 2 to ISO 8348. Issuing agency: National Bureau of Standards, USA.',
      ],
      3 => [
        'Id' => '0005',
        'Name' => 'USA FED GOV OSI NETWORK',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing and naming tree as depicted in Addendum 2 to ISO 8348. Issuing agency: National Bureau of Standards, USA.',
      ],
      4 => [
        'Id' => '0006',
        'Name' => 'USA DOD OSI NETWORK',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing and naming tree as depicted in Addendum 2 to ISO 8348. Issuing agency: Defense Communication Agency, USA.',
      ],
      5 => [
        'Id' => '0007',
        'Name' => 'Organisationsnummer',
        'Description' => 'Notes on Use of Code: The third digit in the organisation number is never lower than 2 in order to
                      avoid it being confused with personal numbers. Issuing agency: The National Tax Board, SWEDEN.',
      ],
      6 => [
        'Id' => '0008',
        'Name' => 'LE NUMERO NATIONAL',
        'Description' => 'Issuing agency: Ministere De L\'interieur et de la Fonction Publique, Belgium.',
      ],
      7 => [
        'Id' => '0009',
        'Name' => 'SIRET-CODE',
        'Description' => 'Issuing agency: DU PONT DE NEMOURS (FRANCE) S.A. France.',
      ],
      8 => [
        'Id' => '0010',
        'Name' => 'Organizational Identifiers for Structured Names under ISO 9541 Part 2',
        'Description' => 'Notes on Use of Code: The organizational codes established under this coding systems constitute the registered organizational identifiers recognised under ISO 9541-2. That standard effectively establishes agreements under which, as allowed by clauses 5.1 and 5.3 of ISO 6523, both the ICD and the organization name are generally omitted, from the SIO, and thus only the organization code portion of the SIO is interchanged. Issuing agency: Association for Font Information Interchange, USA.',
      ],
      9 => [
        'Id' => '0011',
        'Name' => 'International Code Designator for the Identification of OSI-based, Amateur Radio
                      Organizations, Network Objects and Application Services.',
        'Description' => 'Notes on Use of Code: Specific object and attribute naming conventions are currently being defined. Issuing agency: The Radio Amateur Telecommunications Society, USA.',
      ],
      10 => [
        'Id' => '0012',
        'Name' => 'European Computer Manufacturers Association: ECMA',
        'Description' => 'Issuing agency: European Computer Manufacturers Association, SWITZERLAND.',
      ],
      11 => [
        'Id' => '0013',
        'Name' => 'VSA FTP CODE (FTP = File Transfer Protocol)',
        'Description' => 'Notes on Use of Code: The code serves the addressing between the communicating partners. Issuing agency: Verband der Automobilindustrie e.V., GERMANY.',
      ],
      12 => [
        'Id' => '0014',
        'Name' => 'NIST/OSI Implememts\' Workshop',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the Workshop naming and addressing tree. Issuing agency: United States Department of Commerce, National Institute of Standards and Technology, Gaithersburg, USA.',
      ],
      13 => [
        'Id' => '0015',
        'Name' => 'Electronic Data Interchange: EDI',
        'Description' => 'Issuing agency: Avon Rubber p.l.c. UK.',
      ],
      14 => [
        'Id' => '0016',
        'Name' => 'EWOS Object Identifiers',
        'Description' => 'Notes on Use of Code: a) In the SIO the Organization Name will normally be omitted, b) The code is primarily intended for the registration of Objects Identifiers according to ISO 8824: Level 1: iso (1), Level 2: identified-organization (3), Level 3: ewos (0016), Level 4: and higher: (defined by EWOS conventions) Issuing agency: EWOS (European Workshop for Open Systems), BELGIUM.',
      ],
      15 => [
        'Id' => '0017',
        'Name' => 'COMMON LANGUAGE',
        'Description' => 'Notes on Use of Code: Codes for named populated places, geographic places, geopolitical places, outlaying areas, and other related entities of the state of the United States, provinces and territories of Canada, countries of the world, and other, unique areas. Also for the identification of organizations, places, equipment and governmental entities by the telecommunication industry. Issuing agency: Data Communications Technology Planning, USA.',
      ],
      16 => [
        'Id' => '0018',
        'Name' => 'SNA/OSI Network',
        'Description' => 'Notes on Use of Code: The ICD code will also form the initial part of the OSI Network addressing and naming tree as depicted in Addendum 2 to ISO 8348. Issuing agency: International Business Machines Corporation, USA.',
      ],
      17 => [
        'Id' => '0019',
        'Name' => 'Air Transport Industry Services Communications Network',
        'Description' => 'The ICD code forms the initial part of the OSI network addressing and naming tree as depicted in Addendum 2 to ISO 8348. Issuing agency: International Air Transport Association, Switzerland.',
      ],
      18 => [
        'Id' => '0020',
        'Name' => 'European Laboratory for Particle Physics: CERN',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing and naming tree as depicted in Addendum 2 of ISO 8348. Issuing agency: European Laboratory for Particle Physics, Switzerland.',
      ],
      19 => [
        'Id' => '0021',
        'Name' => 'SOCIETY FOR WORLDWIDE INTERBANK FINANCIAL, TELECOMMUNICATION S.W.I.F.T.',
        'Description' => 'Notes on Use of Code: To be used for assignment of object identifiers (ISO 8824/8825) Issuing agency: SOCIETY FOR WORLDWIDE INTERBANK FINANCIAL, TELECOMMUNICATION S.W.I.F.T. BELGIUM.',
      ],
      20 => [
        'Id' => '0022',
        'Name' => 'OSF Distributed Computing Object Identification',
        'Description' => 'Notes on Use of Code: OSF provides public domain software in OS, ISO networking and management. The initial use of the coding system are for identifying the following objects in OSF\'s distributed computing environment: the attributes of entries in the distributed directory, the object class of each entry in the directory, the type of name components (RDNs), the communication protocol profiles, the interfaces offered by. Issuing agency: Open Software Foundation, USA.',
      ],
      21 => [
        'Id' => '0023',
        'Name' => 'Nordic University and Research Network: NORDUnet',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing and tree as depicted in Addendum 2 of ISO 8348. Issuing agency: NORDUnet, c/o SICS, Sweden.',
      ],
      22 => [
        'Id' => '0024',
        'Name' => 'Digital Equipment Corporation: DEC',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing as described in ISO8348 Addendum 2. Issuing agency: Digital Equipment (Europe) S.A.R.L. France.',
      ],
      23 => [
        'Id' => '0025',
        'Name' => 'OSI ASIA-OCEANIA WORKSHOP',
        'Description' => 'Notes on Use of Code: The code is used as an element of object identifiers which need to be assigned relating the ISPs (International Standardized Profiles) that AOW is working on. Issuing agency: OSI ASIA-OCEANIA WORKSHOP, JAPAN.',
      ],
      24 => [
        'Id' => '0026',
        'Name' => 'NATO ISO 6523 ICDE coding scheme',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing and naming tree depicted in Addendum 2 of ISO 8348. Issuing agency: North Atlantic Treaty Organisation (NATO), Belgium.',
      ],
      25 => [
        'Id' => '0027',
        'Name' => 'Aeronautical Telecommunications Network (ATN)',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the ISO network addressing and naming tree as depicted in Addendum No 2 to ISO 8348 Issuing agency: International Civil Aviation Organization (ICAO), CANADA.',
      ],
      26 => [
        'Id' => '0028',
        'Name' => 'International Standard ISO 6523',
        'Description' => 'Issuing agency: Styria Federn GmbH, AUSTRIA.',
      ],
      27 => [
        'Id' => '0029',
        'Name' => 'The All-Union Classifier of Enterprises and Organisations',
        'Description' => 'Issuing agency: General Computing Centre of the State, Committee of the
                      USSR on Statistics, U S S R.',
      ],
      28 => [
        'Id' => '0030',
        'Name' => 'AT&T/OSI Network',
        'Description' => 'Notes on Use of Code: The ICD code will also form the Initial Domain Part of the OSI network, addressing and naming tree as specified in Addendum 2 to ISO 8348. Issuing agency: AT&T, Standards and Regulatory Support, UNITED STATES OF AMERICA.',
      ],
      29 => [
        'Id' => '0031',
        'Name' => 'EDI Partner Identification Code',
        'Description' => 'Notes on Use of Code: To identify EDI partners. Issuing agency: Odette NL, The Netherlands.',
      ],
      30 => [
        'Id' => '0032',
        'Name' => 'Telecom Australia',
        'Description' => 'Notes on Use of Code: The code is used as an element of Object Identifier when defining objects within Telecom Australia. In addition the code shall be used as an element of NSAP addressing. Issuing agency: Australia Telecommunications Corporation, AUSTRALIA.',
      ],
      31 => [
        'Id' => '0033',
        'Name' => 'S G W OSI Internetwork',
        'Description' => 'Notes on Use of Code: Exclusive use by S G W .Issuing agency: S G Warburg Group Management Ltd, UK.',
      ],
      32 => [
        'Id' => '0034',
        'Name' => 'Reuter Open Address Standard',
        'Description' => 'Notes on Use of Code: To be used in the formation of OSI Network Service Access Point (NSAP) addresses. Issuing agency: Reuters Ltd, UK.',
      ],
      33 => [
        'Id' => '0035',
        'Name' => 'ISO 6523 - ICD',
        'Description' => 'Notes on Use of Code: This code will be used internationally by BP thus a non-geographic code is requested. Issuing agency: The British Petroleum Co Plc, UK.',
      ],
      34 => [
        'Id' => '0036',
        'Name' => 'TeleTrust Object Identifiers',
        'Description' => 'Notes on Use of Code: a) In the SIO the Organization name will normally be omitted. b) The code is primarily intended for the registration of Object Identifiers for security related objects according to ISO/IEC 8824, Level 1: iso(1), Level 2: identified-organization(3), Level 3: teletrust(0036), Level 4 and higher: (defined by TeleTrust conventions) Issuing agency: TeleTrust Deutschland e.V., GERMANY.',
      ],
      35 => [
        'Id' => '0037',
        'Name' => 'LY-tunnus',
        'Description' => 'Notes on Use of Code: It is possible to add 0-4 characters set to the code for more detailed use ofone organization. Characters are digits or capital letter. Issuing agency: National Board of Taxes, FINLAND.',
      ],
      36 => [
        'Id' => '0038',
        'Name' => 'The Australian GOSIP Network',
        'Description' => 'Notes on Use of Code: As noted above it will be used as the initial identifier of an NSAP codingscheme. Issuing agency: Standards Australia.',
      ],
      37 => [
        'Id' => '0039',
        'Name' => 'The OZ DOD OSI Network',
        'Description' => 'The ICD code forms the initial part of the OSI naming and addressing, tree as depicted in ISO 8348/Add 2 standard. Format of the tree is described in the Australian GOSIP Manuals and used globally. Issuing agency: The Australian Department of Defence, AUSTRALIA.',
      ],
      38 => [
        'Id' => '0040',
        'Name' => 'Unilever Group Companies',
        'Description' => 'Notes on Use of Code: To be used in data communications to form part of the Network Address as defined in ISO 8348. The ISO 6523, ICD IDI format with Binary syntax will be used. Issuing agency: Information Technology Group, Unilever Plc, UK.',
      ],
      39 => [
        'Id' => '0041',
        'Name' => 'Citicorp Global Information Network',
        'Description' => 'Notes on Use of Code: The ICD code will also form the initial part of the Citicorp Network addressing object identifier tree and naming tree as depicted in Addendum 2 to ISO 8348. Issuing agency: Citicorp Global Information Network, USA.',
      ],
      40 => [
        'Id' => '0042',
        'Name' => 'DBP Telekom Object Identifiers',
        'Description' => 'Notes on Use of Code: 1) The ICD is primarily intended for the registration of Object Identifiers, according to ISO 8824/8825 (ANS.1) to be used for the identification resp. registration of: - application layer protocols, - file & document formats, - information objects, - local/remote procedures. The OID structure and the inclusion of the ICD therein is given below: level 1: iso(1), level 2: identifiedOrganisation(3), level 3 (ICD): dbpt(0042), level 4 to n: (defined by Telekom). Issuing agency: DBP Telekom, GERMANY.',
      ],
      41 => [
        'Id' => '0043',
        'Name' => 'HydroNETT',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing as depicted in ISO 8348/AD2. Issuing agency: Norsk Hydro a.s.,
                      Norway.',
      ],
      42 => [
        'Id' => '0044',
        'Name' => 'Thai Industrial Standards Institute (TISI)',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of international addressing for Thailand. Issuing agency: Thai Industrial Standards Institute (TISI), THAILAND.',
      ],
      43 => [
        'Id' => '0045',
        'Name' => 'ICI Company Identification System',
        'Description' => 'Notes on Use of Code: The ICD code will be used to manage NSAP allocation for all ICI companies on a worldwide basis. The organisation code is used Worldwide by ICI application systems to identify ICI registered companies in machine to machine communications. Issuing agency: ICI PLC, UK.',
      ],
      44 => [
        'Id' => '0046',
        'Name' => 'FUNLOC',
        'Description' => 'Notes on Use of Code: Current applications are Philips accounting and logistic systems; new application is the identification of objects in the open network environment according to ISO 8824 which starts with a party identification Issuing agency: Royal Philips Electronics N.V., The Netherlands.',
      ],
      45 => [
        'Id' => '0047',
        'Name' => 'BULL ODI/DSA/UNIX Network',
        'Description' => 'Notes on Use of Code: To be used in data communications to form part of the network address. The ISO 6523 ICD IDI format with binary syntax will be used. Issuing agency: BULL S.A. FRANCE.',
      ],
      46 => [
        'Id' => '0048',
        'Name' => 'OSINZ',
        'Description' => 'Notes on Use of Code: ISO 6523 ICD IDI format with binary syntax will be used. Issuing agency: OSINZ, New Zealand.',
      ],
      47 => [
        'Id' => '0049',
        'Name' => 'Auckland Area Health',
        'Description' => 'Notes on Use of Code: ISO 6523 ICD IDI format with binary syntax will be used Issuing agency: Auckland Area Health Board, Information Systems, Greenlane/National Women\'s Hospital, New Zealand.',
      ],
      48 => [
        'Id' => '0050',
        'Name' => 'Firmenich',
        'Description' => 'Notes on Use of Code: Interconnect the plants by an OSI network essentially over X.25 carrier. Issuing agency: Firmenich S A, Switzerland.',
      ],
      49 => [
        'Id' => '0051',
        'Name' => 'AGFA-DIS',
        'Description' => 'Notes on Use of Code: Medical Communication Issuing agency: AGFA N.V. BELGIUM.',
      ],
      50 => [
        'Id' => '0052',
        'Name' => 'Society of Motion Picture and Television Engineers (SMPTE)',
        'Description' => 'Notes on Use of Code: The ICD code will also be used to identify SMPTE constituent organizations (committees, working groups, task forces, etc./), and the objects they, define. The ICD code will also form the Initial Domain Part of the OSI network addressing and naming tree as specified in Addendum 2 tot ISO 8348 Issuing agency: Society of Motion Picture and Television Engineers (SMPTE), USA.',
      ],
      51 => [
        'Id' => '0053',
        'Name' => 'Migros_Network M_NETOPZ',
        'Description' => 'Issuing agency: Migros-Genossenschafts-Bund, Switzerland.',
      ],
      52 => [
        'Id' => '0054',
        'Name' => 'ISO6523 - ICDPCR',
        'Description' => 'Notes on Use of Code: This code could be used internationally by Pfizer thus a non-geographic code is required. The code forms the initial part of the OSI network addressing and naming tree depicted in Addendum 2 of ISO 8348. Issuing agency: Pfizer Central Research, UK.',
      ],
      53 => [
        'Id' => '0055',
        'Name' => 'Energy Net',
        'Description' => 'Issuing agency: ABB Asea Brown Boveri Ltd, Switzerland.',
      ],
      54 => [
        'Id' => '0056',
        'Name' => 'Nokia Object Identifiers (NOI)',
        'Description' => 'Notes on Use of Code: a) In the SIO the organization name will normally be omitted, b) The code is primarily intended for the registration of Object Identifiers according to ISO/IEC 8824: Level 1:iso(1), Level 2:identified-organization(3), Level 3:nokia(xxxx), Level 4 and higher:defined by Nokia conventions Issuing agency: Nokia Corporation, FINLAND.',
      ],
      55 => [
        'Id' => '0057',
        'Name' => 'Saint Gobain',
        'Description' => 'Notes on Use of Code: To be used for assignment of: N.E.T (ISO 8348/Add 2), A.E.T (FTAM, X.400 Psaps, and so on), and object identification (ISO 8824/8825) Issuing agency: Saint Gobain, France.',
      ],
      56 => [
        'Id' => '0058',
        'Name' => 'Siemens Corporate Network',
        'Description' => 'Notes on Use of Code: The ICD code will form the initial part of the OSI Network addressing and naming tree as depicted in Addendum 2 to ISO 8348 (Network layer addressing). These addresses will uniquely identify systems within SCN and to the outside world. Issuing agency: Siemens AG, Germany.',
      ],
      57 => [
        'Id' => '0059',
        'Name' => 'DANZNET',
        'Description' => 'Issuing agency: DANZAS AG, Switzerland.',
      ],
      58 => [
        'Id' => '0060',
        'Name' => 'Data Universal Numbering System (D-U-N-S Number)',
        'Description' => 'Notes on Use of Code: The D-U-N-S Number originated to facilitate the compilation of financial status reports on those involved in business transactions but it is now widely used for other purposes also. The number has world wide recognition as a means of identifying businesses and institutions. Issuing agency: Dun and Bradstreet Ltd, UK.',
      ],
      59 => [
        'Id' => '0061',
        'Name' => 'SOFFEX OSI',
        'Description' => 'Notes on Use of Code: This code is to assist in uniquely identifying data network node addresses in an international supporting network for financial applications. This supporting network may have operational interfaces to other (private) data networks. Issuing agency: SOFFEX Swiss Options and Financial Futures Exchange AG. Switzerland.',
      ],
      60 => [
        'Id' => '0062',
        'Name' => 'KPN OVN',
        'Description' => 'Notes on Use of Code: This code is used in the VTOA network of KPN OVN. Issuing agency: Koninklijke KPN, The Netherlands.',
      ],
      61 => [
        'Id' => '0063',
        'Name' => 'ascomOSINet',
        'Description' => 'Issuing agency: Ascom AG, Switzerland.',
      ],
      62 => [
        'Id' => '0064',
        'Name' => 'UTC: Uniforme Transport Code',
        'Description' => 'Notes on Use of Code: The code identifies an individual transport or handling unit (e.g. pallet, parcel) for reasons of tracing or tracing. The unit may have an international destination. Issuing agency: Foundation UTC, The Netherlands.',
      ],
      63 => [
        'Id' => '0065',
        'Name' => 'SOLVAY OSI CODING',
        'Description' => 'Notes on Use of Code: Whenever possible, ISO 8348 addresses using this code will comply with FIPS PUB 146, with an End System ID of exactly 4 octets, so that the DSP can also conform to ECMA 117 where ECMA\'s subnet-address maps onto FIPS\'s Subnet ID concatenated with the End System ID. Issuing agency: Direction Centrale Technique (Informatique Scientifique), Belgium.',
      ],
      64 => [
        'Id' => '0066',
        'Name' => 'Roche Corporate Network',
        'Description' => 'Notes on Use of Code: Will be used internationaly by Roche thus a non-geographic code is required. Issuing agency: F. HOFFMANN - LA ROCHE AG, Switzerland.',
      ],
      65 => [
        'Id' => '0067',
        'Name' => 'ZellwegerOSINet',
        'Description' => 'Notes on Use of Code: BAKOM - Switzerland. Issuing agency: Zellweger Uster AG, Switzerland.',
      ],
      66 => [
        'Id' => '0068',
        'Name' => 'Intel Corporation OSI',
        'Description' => 'Notes on Use of Code: The ICD code will be used to form the Initial Domain Identifier (IDI) portion of the Initial Domain Part (IDP) as described in ISO 8348 Addendum 2 for OSI NSAP addressing. Issuing agency: Intel Corporation, USA.',
      ],
      67 => [
        'Id' => '0069',
        'Name' => 'SITA Object Identifier Tree',
        'Description' => 'Notes on Use of Code: SITA intends to use its OID Tree to define its own Objects for use with its OSI-based services (e.g. MHS & OSI Management). Issuing agency: SITA, France.',
      ],
      68 => [
        'Id' => '0070',
        'Name' => 'DaimlerChrysler Corporate Network',
        'Description' => 'Notes on Use of Code: The ICD code will form the initial part of the OSI Network addressing and naming free as depicted in Addendum 2 to ISO 8348 (Network Layer addressing). These addresses will uniquely identify systems within DBCN and to the outside world. Issuing agency: DaimlerChrysler AG, GERMANY.',
      ],
      69 => [
        'Id' => '0071',
        'Name' => 'LEGO /OSI NETWORK',
        'Description' => 'Notes on Use of Code: The ICD code will also form the Initial Domain Part of the OSI network addressing and naming tree as specified in addendum 2 to ISO 8348. Issuing agency: LEGO Systems Inc, USA.',
      ],
      70 => [
        'Id' => '0072',
        'Name' => 'NAVISTAR/OSI Network',
        'Description' => 'Notes on Use of Code: The ICD code will also form the Initial Domain Part of the OSI Network addressing and naming tree as specified in Addendum 2 to ISO 8348. Issuing agency: International Truck & Engine Corp, USA.',
      ],
      71 => [
        'Id' => '0073',
        'Name' => 'ICD Formatted ATM address',
        'Description' => 'Notes on Use of Code: Used as an ATM address prefix by, 1) Newbridge ATM terminal equipment: a) when performing user - network address registration, b) transparently initiating signalled ATM connections on behalf of other non-ATM (LAN) devices, c) directly initiating signalled ATM connections, 2) Newbridge ATM switching equipment used to: a) perform network - user address registration, b) perform routing of Switched Virtual Connections across a private ATM cell switching network. Issuing agency: Newbridge Networks Corporation, CANADA.',
      ],
      72 => [
        'Id' => '0074',
        'Name' => 'ARINC',
        'Description' => 'Notes on Use of Code: ARINC will define its own Objects for use with its OSI-based systems and services. ARINC will also define Objects for use within the Aeronautical industry. Issuing agency: ARINC Incorporated, USA.',
      ],
      73 => [
        'Id' => '0075',
        'Name' => 'Alcanet/Alcatel-Alsthom Corporate Network',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network addressing scheme as depicted in Addendum 2 of ISO 8384. Issuing agency: Alcatel Network Services Deutschland GmbH, GERMANY.',
      ],
      74 => [
        'Id' => '0076',
        'Name' => 'Sistema Italiano di Identificazione di ogetti gestito da UNINFO',
        'Description' => 'Notes on Use of Code: To be used for assignments of object identifiers according to ISO 8824 and ISO 8825. Issuing agency: UNINFO, ITALY.',
      ],
      75 => [
        'Id' => '0077',
        'Name' => 'Sistema Italiano di Indirizzamento di Reti OSI Gestito da UNINFO',
        'Description' => 'Notes on Use of Code: The ICD code forms the initial part of the OSI network Addressing and naming tree depicted in Addendum 2 of ISO 8348. Issuing agency: UNINFO, ITALY.',
      ],
      76 => [
        'Id' => '0078',
        'Name' => 'Mitel terminal or switching equipment',
        'Description' => 'Notes on Use of Code: The ICD code will form the initial part of the naming tree for: 1 - Private Integrated Services Network manufacturer-specific information as the Organization identifier forming the initial part of the OBJECT IDENTIFIER tree. 2 - OSI Application Layer such as CSTA (ECMA 179). Issuing agency: Mitel Corporation, Canada.',
      ],
      77 => [
        'Id' => '0079',
        'Name' => 'ATM Forum',
        'Description' => 'Notes on Use of Code: The ICD code will also form part of the Initial Domain Part of the OSI network addressing as specified in Addendum 2 to ISO 8348. Issuing agency: The ATM Forum, USA.',
      ],
      78 => [
        'Id' => '0080',
        'Name' => 'UK National Health Service Scheme, (EDIRA compliant)',
        'Description' => 'Notes on Use of Code: EDIRA recommendations for coding in EDIFACT and other EDI systems. Issuing agency: National Health Service, UK.',
      ],
      79 => [
        'Id' => '0081',
        'Name' => 'International NSAP',
        'Description' => 'Issuing agency: Federal Office for Communications, Switzerland.',
      ],
      80 => [
        'Id' => '0082',
        'Name' => 'Norwegian Telecommunications Authority\'s, NTA\'S, EDI, identifier scheme (EDIRA
                      compliant)',
        'Description' => 'Notes on Use of Code: For use in EDIFACT messages in accordance with current national recommendation on identification of EDI objects. (EDIRA compliant). Issuing agency: Norwegian Telecommunications Authority, NORWAY.',
      ],
      81 => [
        'Id' => '0083',
        'Name' => 'Advanced Telecommunications Modules Limited, Corporate Network',
        'Description' => 'Notes on Use of Code: The ICD code will also form part of the Initial Domain Part of the OSI network
                      addressing as specified in Addendum 2 to ISO 8348. Issuing agency: ATM Ltd, ENGLAND.',
      ],
      82 => [
        'Id' => '0084',
        'Name' => 'Athens Chamber of Commerce & Industry Scheme (EDIRA compliant)',
        'Description' => 'Notes on Use of Code : EDIRA recommendations for coding in EDIFACT and other EDI syntaxes. Issuing agency: Athens Chamber of Commerce & Industry, Greece.',
      ],
      83 => [
        'Id' => '0085',
        'Name' => 'Swiss Chambers of Commerce Scheme (EDIRA) compliant',
        'Description' => 'Intended Purpose/App. Area Numerical identifiers of organizations. Issuing agency: Zurich Chamber of Commerce on behalf of Swiss Chambers, of Commerce, Switzerland.',
      ],
      84 => [
        'Id' => '0086',
        'Name' => 'United States Council for International Business (USCIB) Scheme, (EDIRA compliant)',
        'Description' => 'EDIRA recommendations for coding in EDIFACT and other EDI syntaxes. Issuing agency: United States Council for Internationa Business (USCIB), 1212 Avenue of the Americas, USA.',
      ],
      85 => [
        'Id' => '0087',
        'Name' => 'National Federation of Chambers of Commerce & Industry of Belgium, Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: National Federartion of Chambers of Commerce & Industry of, Belgium, Belgium.',
      ],
      86 => [
        'Id' => '0088',
        'Name' => 'EAN Location Code',
        'Description' => 'Issuing agency: EAN International, Belgium.',
      ],
      87 => [
        'Id' => '0089',
        'Name' => 'The Association of British Chambers of Commerce Ltd. Scheme, (EDIRA compliant)',
        'Description' => 'Issuing agency: The Association of British Chambers of Commerce Ltd., UK.',
      ],
      88 => [
        'Id' => '0090',
        'Name' => 'Internet IP addressing - ISO 6523 ICD encoding',
        'Description' => 'Issuing agency: Internet Assigned Numbers Authority, USA.',
      ],
      89 => [
        'Id' => '0091',
        'Name' => 'Cisco Sysytems / OSI Network',
        'Description' => 'Issuing agency: Cisco Systems, USA.',
      ],
      90 => [
        'Id' => '0093',
        'Name' => 'Revenue Canada Business Number Registration (EDIRA compliant)',
        'Description' => 'Issuing agency: Revenue Canada, CANADA.',
      ],
      91 => [
        'Id' => '0094',
        'Name' => 'DEUTSCHER INDUSTRIE- UND HANDELSTAG (DIHT) Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: Deutscher Industrie -und Handelstag (DIHT), Germany.',
      ],
      92 => [
        'Id' => '0095',
        'Name' => 'Hewlett - Packard Company Internal AM Network',
        'Description' => 'Issuing agency: Hewlett - Packard Company, USA.',
      ],
      93 => [
        'Id' => '0096',
        'Name' => 'DANISH CHAMBER OF COMMERCE Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: Danish Chamber of Commerce, Denmark.',
      ],
      94 => [
        'Id' => '0097',
        'Name' => 'FTI - Ediforum Italia, (EDIRA compliant)',
        'Description' => 'Issuing agency: FTI - Ediforum Italia, ITALY.',
      ],
      95 => [
        'Id' => '0098',
        'Name' => 'CHAMBER OF COMMERCE TEL AVIV-JAFFA Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: Chamber of Commerce Tel Aviv-Jaffa, ISRAEL.',
      ],
      96 => [
        'Id' => '0099',
        'Name' => 'Siemens Supervisory Systems Network',
        'Description' => 'Issuing agency: Siemens AG, Germany.',
      ],
      97 => [
        'Id' => '0100',
        'Name' => 'PNG_ICD Scheme',
        'Description' => 'Issuing agency: GPT Limited, UK.',
      ],
      98 => [
        'Id' => '0101',
        'Name' => 'South African Code Allocation',
        'Description' => 'Issuing agency: Thawte Consulting, 33 Protea Way, Durbanville 7550, South
                      Africa',
      ],
      99 => [
        'Id' => '0102',
        'Name' => 'HEAG',
        'Description' => 'Issuing agency: Hessische Elektrizitats-AG, Germany.',
      ],
      100 => [
        'Id' => '0104',
        'Name' => 'BT - ICD Coding System',
        'Description' => 'Issuing agency: Tony Holmes, UK.',
      ],
      101 => [
        'Id' => '0105',
        'Name' => 'Portuguese Chamber of Commerce and Industry Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: Portuguese Chamber of Commerce and Industry, Portugal.',
      ],
      102 => [
        'Id' => '0106',
        'Name' => 'Vereniging van Kamers van Koophandel en Fabrieken in Nederland (Association of Chambers of Commerce and Industry in the Netherlands), Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: Vereniging van Kamers van Koophandel en Fabrieken in Nederland
                      Watermolenlaan, The Netherlands.',
      ],
      103 => [
        'Id' => '0107',
        'Name' => 'Association of Swedish Chambers of Commerce and Industry Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: Association of Swedish Chambers of Commerce and Industry, Sweden.',
      ],
      104 => [
        'Id' => '0108',
        'Name' => 'Australian Chambers of Commerce and Industry Scheme (EDIRA compliant)',
        'Description' => 'Issuing agency: Australian Chambers of Commerce and Industry, Australia.',
      ],
      105 => [
        'Id' => '0109',
        'Name' => 'BellSouth ICD AESA (ATM End System Address)',
        'Description' => 'Issuing agency: BellSouth Corporation, USA.',
      ],
      106 => [
        'Id' => '0110',
        'Name' => 'Bell Atlantic',
        'Description' => 'Issuing agency: Bell Atlantic, USA.',
      ],
      107 => [
        'Id' => '0111',
        'Name' => 'Object Identifiers',
        'Description' => 'Issuing agency: Institute of Electrical and Electronics Engineers, USA.',
      ],
      108 => [
        'Id' => '0112',
        'Name' => 'ISO register for Standards producing Organizations',
        'Description' => 'Issuing agency: International Organization for Standardization (ISO), SWITZERLAND.',
      ],
      109 => [
        'Id' => '0113',
        'Name' => 'OriginNet',
        'Description' => 'Issuing agency: Origin BV, The Netherlands.',
      ],
      110 => [
        'Id' => '0114',
        'Name' => 'Check Point Software Technologies',
        'Description' => 'Issuing agency: Check Point Software Technologies Ltd, ISRAEL.',
      ],
      111 => [
        'Id' => '0115',
        'Name' => 'Pacific Bell Data Communications Network',
        'Description' => 'Issuing agency: Pacific Bell, USA.',
      ],
      112 => [
        'Id' => '0116',
        'Name' => 'PSS Object Identifiers',
        'Description' => 'Issuing agency: PSS (Postal Security Services), FINLAND.',
      ],
      113 => [
        'Id' => '0117',
        'Name' => 'STENTOR-ICD CODING SYSTEM',
        'Description' => 'Issuing agency: Stentor Resource Centre Inc., Canada.',
      ],
      114 => [
        'Id' => '0118',
        'Name' => 'ATM-Network ZN\'96',
        'Description' => 'Issuing agency: Deutsche Telekom AG, Germany.',
      ],
      115 => [
        'Id' => '0119',
        'Name' => 'MCI / OSI Network',
        'Description' => 'Issuing agency: MCI Telecommunications Corporation, Technical Standards Management, USA.',
      ],
      116 => [
        'Id' => '0120',
        'Name' => 'Advantis',
        'Description' => 'Issuing agency: Advantis, USA.',
      ],
      117 => [
        'Id' => '0121',
        'Name' => 'Affable Software Data Interchange Codes',
        'Description' => 'Issuing agency: Affable Software Corporation, Canada.',
      ],
      118 => [
        'Id' => '0122',
        'Name' => 'BB-DATA GmbH',
        'Description' => 'Issuing agency: BB-DATA GmbH, Germany.',
      ],
      119 => [
        'Id' => '0123',
        'Name' => 'BASF Company ATM-Network',
        'Description' => 'Issuing agency: BASF Computer Services GmbH, Germany.',
      ],
      120 => [
        'Id' => '0124',
        'Name' => 'IOTA Identifiers for Organizations for Telecommunications Addressing using the ICD system format defined in ISO/IEC 8348',
        'Description' => 'Issuing agency: DISC, British Standards Institution, UK.',
      ],
      121 => [
        'Id' => '0125',
        'Name' => 'Henkel Corporate Network (H-Net)',
        'Description' => 'Issuing agency: Henkel KgaA, Germany.',
      ],
      122 => [
        'Id' => '0126',
        'Name' => 'GTE/OSI Network',
        'Description' => 'Issuing agency: GTE, Industry Standards, USA.',
      ],
      123 => [
        'Id' => '0127',
        'Name' => 'Dresdner Bank Corporate Network',
        'Description' => 'Issuing agency: Dresdner Bank AG, Germany.',
      ],
      124 => [
        'Id' => '0128',
        'Name' => 'BCNR (Swiss Clearing Bank Number)',
        'Description' => 'Issuing agency: Telekurs AG, Switzerland.',
      ],
      125 => [
        'Id' => '0129',
        'Name' => 'BPI (Swiss Business Partner Identification) code',
        'Description' => 'Issuing agency: Telekurs AG, Switzerland.',
      ],
      126 => [
        'Id' => '0130',
        'Name' => 'Directorates of the European Commission',
        'Description' => 'Issuing agency: European Commission, Belgium',
      ],
      127 => [
        'Id' => '0131',
        'Name' => 'Code for the Identification of National Organizations',
        'Description' => 'Issuing agency: China National Organization Code Registration Authority, P.R. of China.',
      ],
      128 => [
        'Id' => '0132',
        'Name' => 'Certicom Object Identifiers',
        'Description' => 'Issuing agency: Certicom Corp, U.S.A.',
      ],
      129 => [
        'Id' => '0133',
        'Name' => 'TC68 OID',
        'Description' => 'Issuing agency: ISO TC68, Banking and Related Financial Services, USA.',
      ],
      130 => [
        'Id' => '0134',
        'Name' => 'Infonet Services Corporation',
        'Description' => 'Issuing agency: Infonet NV/SA, Belgium.',
      ],
      131 => [
        'Id' => '0135',
        'Name' => 'SIA Object Identifiers',
        'Description' => 'Issuing agency: SIA-Società Interbancaria per l\'Automazione S.p.A., ITALIA.',
      ],
      132 => [
        'Id' => '0136',
        'Name' => 'Cable & Wireless Global ATM End-System Address Plan',
        'Description' => 'Issuing agency: Cable & Wireless Global Business Inc., USA',
      ],
      133 => [
        'Id' => '0137',
        'Name' => 'Global AESA scheme',
        'Description' => 'Construct and Administer AESAs, Routing of ATM switched connections Use to from globally unique Global One ICD AESAs. Issuing agency: Global One, Belgium.',
      ],
      134 => [
        'Id' => '0138',
        'Name' => 'France Telecom ATM End System Address Plan',
        'Description' => 'The coding system will be used to provide ATM End System Addresses based on ICD format NSAP addresses. These addresses will be used to uniquely identify User Network. Interfaces to ATM networks as specified by the ATM Forum UNI specifications. France telecom will also use these addresses Internally and to provide worldwide customers with non- Geographic private AESAs. These global addresses should be Reachable by non-France Telecom ATM users via Interconnecting ATM carriers. The ICD Code will also form part of the Initial Domain Part of the OSI network addressing as specified in Addendum 2 to ISO 8348. Issuing agency: France Telecom, France.',
      ],
      135 => [
        'Id' => '0139',
        'Name' => 'Savvis Communications AESA:.',
        'Description' => 'Global Addressing of Savvis ATM Switches and any direct customer ATM networks for implementation of PNNI Used to form a globally unique Savvis ICD ATM End System Address. Issuing agency: Savvis Communications,USA.',
      ],
      136 => [
        'Id' => '0140',
        'Name' => 'Toshiba Organizations, Partners, And Suppliers\' (TOPAS) Code',
        'Description' => 'The purpose of this coding system is to identify organizations world-wide that have business or technical transactions with Toshiba Corporation in terms of ISO 13584 Parts Library standard based electronic catalogue interchange service. The interchange is not limited to those between a member organization and Toshiba Corporation. Interchanges between member organizations based on the organization identifier of this coding system are also in scope. Reference to this organization identification code in other business transactions is also allowed Reference to this organization identifier in other business transactions is also possible provided the organizations concerned are registered as members of the. Issuing agency: Toshiba Corporation, Japan.',
      ],
      137 => [
        'Id' => '0141',
        'Name' => 'NATO Commercial and Government Entity system',
        'Description' => 'To identify all Commercial and Governmental entities that provide material and/or services to the Armed Forces of the NATO nations and several non-NATO nations (Sponsored) around the world. This information is used by NATO and Sponsored nations\' Logisticians to identify Commercial and Government Entities they deal with. This Information is used by all functions of Logistics support such as Acquisition, Sourcing, EDI, Re-Provisioning, Material Management, etc. Determination of the real source for an item of supply is one of the most important prerequisites for proper application of the Uniform System of Item Identification within NATO. It is the source where documentation will be obtained from and its location normally gives advice for codification responsibility. Within the NATO Codification System the term Manufacturer covers the whole range of possible sources of technical data for items entering the supply chains or participating, countries. The primary use of manufacturers coding is in ADP operations related to support management programs such as material management codification, standardization, etc. Issuing agency: NATO Group of National Director on Codification (AC/135), Luxembourg.',
      ],
      138 => [
        'Id' => '0142',
        'Name' => 'SECETI Object Identifiers',
        'Description' => 'The function as the \'Application Centre\' for the Italian National Interbank Network, having been authorized by the Bank of Italy, and the Italian Banking Association to operate in that capacity. The scheme is intended for the registration of object identifiers according to ISO 8824 and ISO 8825 The code is primarily intended for the registration of Object Identifiers according to ISO 8824/8825, Level 1: ISO (), Level 2: identified -organization (), Level 3: SECETI S.p.A. (), Level 4: and higher: (defined by SECETI conventions).Issuing agency: Servizi Centralizzati SECETI S.p.A., ITALY.',
      ],
      139 => [
        'Id' => '0143',
        'Name' => 'EINESTEINet AG',
        'Description' => 'Initially the Network covers the geographical area of Germany with the intention of expanding into all the European countries EINSTEINet\'s goal is to provide Application Services using an ATM network to customers located throughout Europe. The need for the international ATM address structure is to serve EINSTENet\'s customers with consistent ATM addresses from end-to-end. Issuing agency: EINSTEINet AG, Germany.',
      ],
      140 => [
        'Id' => '0144',
        'Name' => 'DoDAAC (Department of Defense Activity Address Code)',
        'Description' => 'A code assigned to uniquely identify all military units in the United States Department of Defense. Issuing agency: DoD (Unites States Department of Defense), USA.',
      ],
      141 => [
        'Id' => '0145',
        'Name' => 'DGCP (Direction Générale de la Comptabilité Publique)administrative accounting identification scheme',
        'Description' => 'de assigned by the French public accounting office. Issuing agency: DGCP
                      (Direction Générale de la Comptabilité Publique), 139 Rue de Bercy, 75572 Paris Cedex
                      12, France',
      ],
      142 => [
        'Id' => '0146',
        'Name' => 'DGI (Direction Générale des Impots) code',
        'Description' => 'French taxation authority. Issuing agency: DGI (Direction Générale des Impots), France.',
      ],
      143 => [
        'Id' => '0147',
        'Name' => 'Standard Company Code',
        'Description' => 'Partner identification code which is registered with JIPDEC/ECPC. Issuing agency: JIPDEC, Japan.',
      ],
      144 => [
        'Id' => '0148',
        'Name' => 'ITU (International Telecommunications Union)Data Network Identification Codes (DNIC)',
        'Description' => 'Data Network Identification Codes assigned by the ITU. Issuing agency: ITU (International Telecommunications Union), Switzerland.',
      ],
      145 => [
        'Id' => '0149',
        'Name' => 'Global Business Identifier',
        'Description' => 'For a company\'s ability to obtain complete and accurate information about potential suppliers Used to identify and designate in electronic commerce Issuing agency: ResolveNet (IOM) Ltd, UK.',
      ],
      146 => [
        'Id' => '0150',
        'Name' => 'Madge Networks Ltd- ICD ATM Addressing Scheme',
        'Description' => 'The code will be used as part of an ATM NSAP addressing scheme for the establishment of PVC and SPVC connections Addressing for Madge Networks global ATM network and the connections of any Madge Customers requiring the allocation of ATM addresses from Madge Networks. Issuing agency: Madge Networks, UK.',
      ],
      147 => [
        'Id' => '0151',
        'Name' => 'Australian Business Number (ABN) Scheme',
        'Description' => 'The ABN will be a unique identifier for a business to interact with Government (Commonwealth, State and Local) throughout, Australia and is the supporting number for the Goods and Service Tax (GST). The Legislation covering the use of ABN, (see notes on use) will have application throughout the Commonwealth of The ABN is established by: A New Tax System (Australian, Business Number) Act 1999, enacted by the Australian Parliament. The scheme is expected to last for at least 100 Years without reallocation of identification numbers. The ABN is specified in English. Issuing agency: Australian Taxation Office,  AUSTRALIA.',
      ],
      148 => [
        'Id' => '0152',
        'Name' => 'Edira Scheme Identifier Code',
        'Description' => 'For the unambiguous identification of registration scheme used in e-commerce (not to be used for the identification of organizations). The code is used to designate unambiguously schemes used in e-commerce to specify any entity but organizations. Issuing agency: EDIRA Association, c/o Zurich chamber of commerce, Switzerland.',
      ],
      149 => [
        'Id' => '0153',
        'Name' => 'Concert Global Network Services ICD AESA',
        'Description' => 'Global Addressing of the Concert ATM switches and any direct customer ATM networks for implementation of PNNI. It will also be used for any attached carrier ATM networks. Used to form globally unique Concert ICD ATM End System Addresses (AESA\'s). Issuing agency: Concert Global Network Services Ltd, Bermuda.',
      ],
      150 => [
        'Id' => '0154',
        'Name' => 'Identification number of economic subjects: (ICO)',
        'Description' => 'Unique identification of economic subjects for all administrative purposes The identification number ICO is used in the Czech Republic mainly in all administrative acts (tax system, banking system, statistics. etc.) Issuing agency: Czech Statistical Office, Czech Republic.',
      ],
      151 => [
        'Id' => '0155',
        'Name' => 'Global Crossing AESA (ATM End System Address)',
        'Description' => 'Construction, administration and implementation of a scalable AESA schema for routing if ATM switched connections. ICD will be used as a component of the IDP (Initial Domain Part) for OSI addressing. Issuing agency: Global Crossing Ltd, Bermuda.',
      ],
      152 => [
        'Id' => '0156',
        'Name' => 'AUNA',
        'Description' => 'Telecommunication network of operators in the AUNA Group. This code shall be used as an element of NSAP addressing Issuing agency: AUNA, Spain.',
      ],
      153 => [
        'Id' => '0157',
        'Name' => 'ATM interconnection with the Dutch KPN Telecom',
        'Description' => 'ITO Drager Net. The ICD code also form the initial part of the OSI network addressing scheme (Addendum 2 of ISO 8384) Issuing agency: Informatie en Communicatie Technologie Organisatie, The Netherlands.',
      ],
      154 => [
        'Id' => '0158',
        'Name' => 'Identification number of economic subject (ICO) Act on State Statistics of 29 November 2001, § 27',
        'Description' => 'The unique identification of economic subjects (legal persons and natural persons-entrepreneurs) used for registration The identification number ICO is used in Slovakia in almost all administrative acts (tax system, banking system, statistics, etc.) Issuing agency: Slovak Statistical Office, Slovak             Republic.',
      ],
      155 => [
        'Id' => '0159',
        'Name' => 'ACTALIS Object Identifiers',
        'Description' => 'The code is primarily intended for the registration of Object Identifiers (OIDs) according to ISO 8824/8825: Level 1: iso (1), Level 2: identified-organization (3), Level 3: ACTALIS SpA (0159), Level 4 and higher: (defined by ACTALIS) See "Intended purpose/application area" Issuing agency: ACTALIS S.p.A., ITALY.',
      ],
      156 => [
        'Id' => '0160',
        'Name' => 'GTIN - Global Trade Item Number',
        'Description' => 'The GTIN is a globally unique identifier of trade items. A trade item is any item (product or service) upon which there is a need to retrieve pre-defined information and that may be priced, ordered or invoiced at any point in any supply chain. The GTIN identification scheme is currently (2002) used by more than 900,000 organizations in the world. It is widely in the consumer goods and other industries to identify items and packages. The GTIN can be represented in a standard bar code format. Issuing agency: EAN Inernational.',
      ],
      157 => [
        'Id' => '0161',
        'Name' => 'ECCMA Open Technical Directory',
        'Description' => 'A centralized dictionary of names and definitions of trading concepts, essentially goods and services that are bought, sold or exchanged. This is a classification neutral dictionary of names and attributes (also referred to as characteristics or properties). The eOTD will help improve the speed and accuracy of Internet searches and can be imported into sourcing, procurement and ERP systems with minimal data transformation costs. Issuing agency: Electronic Commerce Code Management Association, USA.',
      ],
      158 => [
        'Id' => '0162',
        'Name' => 'CEN/ISSS Object Identifier Scheme',
        'Description' => 'To allocate OIDs to objects defined in the standards and specifications developed in CEN’s technical bodies (TCs, Workshops, etc) The code is primarily intended for the registration ofObject Identifiers according to ISO 8824-1 Annex BLevel 1: iso (1)Level 2: identified-organization (3)Level 3: CEN (nnnn –the ICD allocated)Level 4: and higher: (defined by CEN conventions). Issuing agency: Comité Européen de Normalization, Belgium.',
      ],
      159 => [
        'Id' => '0163',
        'Name' => 'US-EPA Facility Identifier',
        'Description' => 'To provide for the unique identification of facilities regulated or monitored by the United States Environmental Protection Agency (EPA).A facility is a distinct real property entity (i.e., a man-made object and its surrounding real estate). Facilities incorporate the characteristics of being: (1) objects, established at (2) specific places, for (3) specific purposes. A facility can include monitoring stations, waste sites, and other entities of environmental interest that cannot be classified as single facilities. This is maintained within the U.S. Environmental Protection Agency Facility Registration System (FRS). Issuing agency: U.S. Environmental Protection Agency, USA.',
      ],
      160 => [
        'Id' => '0164',
        'Name' => 'TELUS Corporation',
        'Description' => 'SA Addressing Scheme for ATM PNNI Implementation ICD is required for PNNI implementation on TELUS’ ATM network in order to establish an addressing scheme for SPVC connections within and between regions Issuing agency: TELUS Corporation, Canada.',
      ],
      161 => [
        'Id' => '0165',
        'Name' => 'FIEIE Object identifiers',
        'Description' => 'To provide identifiers for international enterprises and organizations operating in fields of business served by the Jaakko Poyry Group. On the date of the application, these fields include Forest industry, Energy, Infrastructure and Environment. To provide an internationally unambiguous framework for existing coding practices in this The code is primarily intended for the registration of Object Identifiers according to ISO/IEC 8824, 8825 and 11179: Level 1: iso (1) Level 2: identified organization (3) Level 3: fieie code (nnnn, the ICD allocated) Level 4 and higher: (defined by FIEIE conventions). Issuing agency: Jaakko Poyry Group Oyj, Finland.',
      ],
      162 => [
        'Id' => '0166',
        'Name' => 'Swissguide Identifier Scheme',
        'Description' => 'To uniquely identify objects, esp. companies and professionals in directories/databases The code is used to uniquely identify the objects in the Swissguide directory. Issuing agency: Swissguide AG, Switzerland.',
      ],
      163 => [
        'Id' => '0167',
        'Name' => 'Priority Telecom ATM End System Address Plan',
        'Description' => 'The coding system will be used to provide ATM End System Address based on IDC format NSAP addresses required for Priority Telecom ATM PNNI implementation. These addresses will be used to uniquely identify User Network interfaces to Priority Telecom ATM Networks as specified by the ATM Forum UNI specifications. PT plans to use these addresses to connect to other public ATM networks in the countries PT is operating (The Netherlands, Norway and Austria) Used to form a globally unique Priority Telecom ATM End System Address. PT customers and interconnect with public ATM networks requires the use of unique AESA Issuing agency: Priority Telecom Netherlands, The Netherlands.',
      ],
      164 => [
        'Id' => '0168',
        'Name' => 'Vodafone Ireland OSI Addressing',
        'Description' => 'Implementation of an ATM network in connection with 3G rollout. The code will be used for ATM network related addressing purposes, and for CLNS network. Issuing agency: Vodafone Ireland Limited, Ireland.',
      ],
      165 => [
        'Id' => '0169',
        'Name' => 'Swiss Federal Business Identification Number. Central Business names Index (zefix) Identification Number',
        'Description' => 'To uniquely identify all companies/organizations registered in the Swiss Register of Commerce and the Swiss Central Business Names Index To uniquely identify entries in Swiss Central Business Names Index (zefix). The principle purpose of the zefix on internet is to provide a swisswide search function, and thus provide the public with a service to determine the legal domicile, the cantonal office for the register of commerce in charge, and the latter’s address. Issuing agency: Swiss Federal Office of Justice, Switzerland.',
      ],
      166 => [
        'Id' => '0170',
        'Name' => 'Teikoku Company Code',
        'Description' => 'Teikoku Company Code is allocated to all incorporations, business owners, government organizations and other public offices in Japan. TDB (Teikoku Databank Ltd.) retains company codes of approximately 1.7 million companies within Japan. Teikoku Company Code, a unique company ID, has already been adopted by many companies both as a standard company code in customer data managements and as an identification code for online electronic commerce transactions. Since every company trades with companies abroad, they need to use it in their international business transaction. Therefore, it is desired to register TDB as an ICD to RA of the ISO/IEC 6523. Issuing agency: TEIKOKU DATABANK LTD., JAPAN.',
      ],
      167 => [
        'Id' => '0171',
        'Name' => 'Luxembourg CP & CPS (Certification Policy and Certification Practice Statement) Index',
        'Description' => 'Index of the Certification Policies and Certification Practice Statement issued by Luxembourg PKI Issuing agency: Ministry of The Economy and Foreign Trade, Luxembourg.',
      ],
      168 => [
        'Id' => '0172',
        'Name' => 'Project Group “Lists of Properties” (PROLIST®)',
        'Description' => 'To uniquely identify properties, blocks and lists of properties (LOP) for products and services in the process industry. The products are electrical and process control devices. The code is used to uniquely identify the objects in the PROLIST online dictionary. Issuing agency: Project Group “Lists of Properties” (PROLIST®) c/o Bayer Technology Services GmbH Geb., Germany.',
      ],
      169 => [
        'Id' => '0173',
        'Name' => 'eCI@ss',
        'Description' => 'To uniquely identify properties, classes and list of characteristics (LoC) for products and services available in the eCI@ss classification system The code is used to uniquely identify objects in the eCI@ss classification system. Issuing agency: eCI@ss, Germany.',
      ],
      170 => [
        'Id' => '0174',
        'Name' => 'StepNexus',
        'Description' => 'To provide identifiers within StepNexus loader objects. These addresses will be used to uniquely identify StepNexu key usage fields within X509 certificates for use in the StepNexus loader scheme. Used to define unique certificate attributes within X509 certificates Issuing agency: StepNexus, UK.',
      ],
      171 => [
        'Id' => '0175',
        'Name' => 'Siemens AG',
        'Description' => 'To uniquely identify properties, blocks, classes and lists of properties used or specified by Siemens AG - Power Generation The code is used to uniquely identify objects in the Siemens AG - Power Generation corporate dictionary Issuing agency: Siemens AG, Germany.',
      ],
      172 => [
        'Id' => '0176',
        'Name' => 'Paradine GmbH',
        'Description' => 'To uniquely identify properties, classes,and list of properties (LoP) for products and services available in Paradine Reference Dictionary Systems The code is used to uniquely identify objects in Paradine Reference Dictionary Systems. Issuing agency: Paradine GmbH, Austria.',
      ],
      173 => [
        'Id' => '0177',
        'Name' => 'Odette International Limited',
        'Description' => 'For use in EDI and other B2B exchanges in the European automotive industry to identify business entities (organisations). The scheme is used to identify organisations, and parts of organisations which are parties to or are referenced in automotive supply chain transactions such as EDI messaging and other B2B exchanges. Issuing agency: Odette International Limited, UK.',
      ],
      174 => [
        'Id' => '0178',
        'Name' => 'Route1 MobiNET',
        'Description' => 'For rooting OIDs defined by Route1 Security Corporation for Route1 MobiNET. Intended to cover MobiNET connected organizations, Route1 Security Corporation, its subdivisions, customers and any organization using MobiNET or Route1\'s services and products For rooting OIDs defined by Route1 Security Corporation for Route1 MobiNET. Intended to cover MobiNET connected organizations, Route1 Security Corporation, its subdivisions, customers and any organization using MobiNET or Route1\'s services and products. The OID structure and the inclusion therein of the ICS is as follows: ISO.Identifiedorganization.ICD(Route1 MobiNET).AFI.PCI.Org_ID.OPI.MC Issuing agency: Route1 Security Corporation,Canada.',
      ],
      175 => [
        'Id' => '0179',
        'Name' => 'Penango Object Identifiers',
        'Description' => 'To identify objects, policies, and data related to Penango’s products and services. The ICD is primarily intended for registration of Object Identifiers in accordance with ISO/IEC 8824 (ASN.1). Issuing agency: Penango, Inc., Canada.',
      ],
      176 => [
        'Id' => '0180',
        'Name' => 'Lithuanian military PKI',
        'Description' => 'dex of the Certification Policies and Certification Practices Statements issued by Lithuanian military PKI The code is used to uniquely identify Certification Policies and Certification Practice Statements in Lithuanian military PKI Issuing agency: The Ministry of National Defence of the Republic of Lithuania, Lithuania.',
      ],
      177 => [
        'Id' => '0183',
        'Name' => 'Numéro d\'identification suisse des enterprises (IDE), Swiss Unique Business Identification Number (UIDB)',
        'Description' => 'Intended Purpose/App. Area: To uniquely identify all companies/organizations registered in Switzerland in all official register (Swiss Register of Commerce, VAT register, Canton register, etc) The UIDB shall make lt possible to identify an enterprise quickly, unambiguously and on a permanent basis. The UIDB and the other identification characteristics associated with it shall be managed via a specific UIDB register. The main identification characteristics (status, address, etc.) shall be accessible to the public. Issuing agency: Swiss Federal Statistical Office (FSO), Switzerland).',
      ],
      178 => [
        'Id' => '0184',
        'Name' => 'DIGSTORG',
        'Description' => 'Intended Purpose/App. Area: To be used for identifying Danish companies included juridical persons and associations in international trade It is possible to add 0-4 characters set to the code for more detailed use of one organization. Characters are digits or capital letter. Issuing agency: The Danish Agency for Digitisation, Denmark.',
      ],
      179 => [
        'Id' => '0185',
        'Name' => 'Perceval Object Code',
        'Description' => 'Intended Purpose/App. Area: Intended to uniquely identify in an international context any physical and or abstract entities related to Perceval products and services using Abstract Syntax Notation One in accordance with ISO/IEC 8824 The ICD is primarily intended for registration and resolution of Object Identifiers in accordance with ISO/IEC 8824 with reduced encoding size and non-geographic context Issuing agency: Perceval SA, Tenbosch, Belgium.',
      ],
      180 => [
        'Id' => '0186',
        'Name' => 'TrustPoint Object Identifiers',
        'Description' => 'Intended Purpose/App. Area: To uniquely identify objects and mechanisms globally throughout communications
                      networks using TrustPoint security products and services Issuing agency: TrustPoint
                      Innovation Technologies, Attn: Sherry Shannon-Vanstone, 816 Hideaway Circle East, Unit
                      244 Marco Island, FL 34145 USA http://www.trustpointinnovation.com Tel: +1 905 302 6929
                      Email: sviconsulting@aol.com',
      ],
      181 => [
        'Id' => '0187',
        'Name' => 'Amazon Unique Identification Scheme',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers for properties, classes, groups, or lists of data and objects specified by or used by Amazon.com, Inc. and its Affiliates Identifiers assigned under this scheme may be usable as Object Identifiers in accordance with ISO/IEC 8824,  usable with Directories in accordance with ISO/IEC 9594, usable in accordance with ISO/IEC 8348, or usable in other contexts as defined by Amazon. Issuing agency: Amazon Technologies, Inc. in the United States.',
      ],
      182 => [
        'Id' => '0188',
        'Name' => 'Corporate Number of The Social Security and Tax Number System',
        'Description' => 'Intended Purpose/App. Area: The number system of Japan is a social infrastructure to improve efficiency and the transparency of the social security and the tax system, and to achieve a highly convenient, impartial, and fair society. Additionally, the profit of the number system can be free usage for various purposes, so we want to use the Corporate Number as identifiers in various fields, like in electronic commerce, transportation, etc. The preliminary work, numbering the identifiers for the beginning of usage in January 2016, is being done. Issuing agency: National Tax Agency Japan.',
      ],
      183 => [
        'Id' => '0189',
        'Name' => 'European Business Identifier (EBID)',
        'Description' => 'Intended Purpose/App. Area: For use in EDI or other B2B exchanges to identify business entities (organizations). The scheme is used to identify organisations, and parts of organisations which are parties to or are referenced in electronic transactions such as EDI messaging or other B2B exchanges. Issuing agency: EBID Service AG CAS-Weg in Germany.',
      ],
      184 => [
        'Id' => '0190',
        'Name' => 'Organisatie Indentificatie Nummer (OIN)',
        'Description' => 'Intended Purpose/App. Area: The OIN is part of the Dutch standard ‘Digikoppeling’ and is used for identifying the organisations that take part in electronic message exchange with the Dutch Government. The OIN must also be included in the PKIo certificate. Issuing agency: Logius in the Netherlands.',
      ],
      185 => [
        'Id' => '0191',
        'Name' => 'Company Code (Estonia)',
        'Description' => 'Intended Purpose/App. Area: Company code is major and only unique identifier of all institutions and organisations in Estonia. This code is widely used for various purposes, including electronic commerce. Usage of company code is required in communication between institutions and also in communication between private and public organisations. For use in EDI or other B2B (B2C) exchanges to identify private and public organisations. Issuing agency: Centre of Registers and Information Systems of the Ministry of Justice in Estonia.',
      ],
      186 => [
        'Id' => '0192',
        'Name' => 'Organisasjonsnummer',
        'Description' => 'Intended Purpose/App. Area: Identify entities registered in the Central Coordinating Register for Legal Entities in Norway. The scheme with ICD code + organization number will be used to identify organisations that are parties to or referenced in electronic transactions such as electronic invoicing or other B2B exchanges. Issuing agency: The Brønnøysund Register Centre in Norway.',
      ],
      187 => [
        'Id' => '0193',
        'Name' => 'UBL.BE Party Identifier',
        'Description' => 'Intended Purpose/App. Area: Identification and addressing of different parties involved in invoicing. Issuing agency: UBL.BE in Belgium.',
      ],
      188 => [
        'Id' => '0194',
        'Name' => 'KOIOS Open Technical Dictionary',
        'Description' => 'Intended Purpose/App. Area: The KOIOS OTD is a collection of terminology defined by and obtained from consensus bodies such as ISO, IEC, and other groups that have a consensus process for developing terminology. The KOIOS OTD contains terms, definitions, and images of concepts used to describe individuals, organizations, locations, goods and services. The KOIOS OTD conforms to ISO 22745 (all parts) and is designed to enable the exchange of characteristic data in all stages of the life-cycle of an item, and to ensure that the resulting specifications conform to ISO 8000-110. Issuing agency: KOIOS Master Data Limited in UK.',
      ],
      189 => [
        'Id' => '0195',
        'Name' => 'Singapore Nationwide E-lnvoice Framework',
        'Description' => 'Intended Purpose/App. Area: For use in electronic messages in accordance to the Singapore
                      nationwide e-invoice framework on Identification of organization. Issuing agency: Infocomm Media Development Authority in Singapore.',
      ],
      190 => [
        'Id' => '0196',
        'Name' => 'Icelandic identifier - Íslensk kennitala',
        'Description' => 'Intended Purpose/App. Area: Identification of Icelandic individuals and legal entities. Issuing agency: For individual, Icelandic National Registry, www.skra.is. For legal entities, Directorate of Internal Revenue, www.rsk.is in Iceland.',
      ],
      191 => [
        'Id' => '0197',
        'Name' => 'APPLiA Pl Standard',
        'Description' => 'Intended Purpose/App. Area: Through their European industry association APPLiA (Home Appliance Europe), manufacturers of home appliances have launched the Product Information (Pl) initiative. The initiative introduces a standard structure for product information. Pl Standard helps retailers to take full advantage of electronic communication and data processing, as the Internet and ICT are fundamentally changing how products and services are offered, bought, and sold.. Issuing agency: APPLiA Home Appliance Europe, in Belgium',
      ],
      192 => [
        'Id' => '0198',
        'Name' => 'ERSTORG',
        'Description' => 'Intended Purpose/App. Area: To be used for identifying Danish companies based on VAT numbers included juridical. Issuing agency: The Danish Business Authority in Denmark.',
      ],
      193 => [
        'Id' => '0199',
        'Name' => 'Legal Entity Identifier (LEI)',
        'Description' => 'Intended Purpose/App. Area: The LEI is the global, open identifier established at the urging of the Financial Stability Board and the recommendation of the G20. The LEI is established as the ISO 17442 standard, is governed by the LEI Regulatory Oversight Committee (LEI-ROC) and has been implemented by the Global Legal Entity Identifier Foundation (GLEIF). The LEI code connects to key reference information that enables clear and unique identification of legal entities participating in financial transactions. Each LEI contains information about an entity\'s ownership structure and thus answers the questions of \'who is who\' and \'who owns whom\'. Simply put, the publicly available LEI data pool can be regarded as a global directory, which greatly enhances transparency in the global marketplace. Already applied very broadly within financial regulation and rapidly being adopted for KYC and a number of other purposes in financial markets, the LEI is set to spread into a range of other fields, including trade facilitation, business reporting and supply chain management. Issuing agency: GLEIF, a global organization.',
      ],
      194 => [
        'Id' => '0200',
        'Name' => 'Legal entity code (Lithuania)',
        'Description' => 'Intended Purpose/App. Area: For use in EDI (electronic data interchange) for C2B and others exchanges to identify legal entities. Issuing agency: State Enterprise Centre of Registers in Lithuania.',
      ],
      195 => [
        'Id' => '0201',
        'Name' => 'Codice Univoco Unità Organizzativa iPA',
        'Description' => 'Intended Purpose/App. Area: Used to identify uniquely all organizational units of public bodies, authorities and public services in Italy. Issuing agency: Agenzia per l’Italia digitale in Italy.',
      ],
      196 => [
        'Id' => '0202',
        'Name' => 'Indirizzo di Posta Elettronica Certificata',
        'Description' => 'Intended Purpose/App. Area: Used to identify senders and receivers of certified electronic mail as defined by Italian law. Issuing agency: Agenzia per l’Italia digitale in Italy.',
      ],
      197 => [
        'Id' => '0203',
        'Name' => 'eDelivery Network Participant identifier',
        'Description' => 'Intended Purpose/App. Area: Used as an electronic address identifier for participants within a secure data communication network. Issuing agency: Agency for Digital Government in Sweden.',
      ],
      198 => [
        'Id' => '0204',
        'Name' => 'Leitweg-ID',
        'Description' => 'Intended Purpose/App. Area: Identification of Public Authorities. Issuing agency: Koordinierungsstelle für IT-Standards (KoSIT) in Germany.',
      ],
      199 => [
        'Id' => '0205',
        'Name' => 'CODDEST',
        'Description' => 'Intended Purpose/App. Area: Electronic Invoicing trough Sdl, the Exchange System used in Italy where the electronic invoices are transmitted to the Public Administration (Article 1, paragraph 211, of Italian Law no. 244 of 24 December 2007) or to private entities (Article 1, paragraph 2, of Legislative Decree 127/2015). Issuing agency: Agenzia delle Entrate in Italy.',
      ],
      200 => [
        'Id' => '0206',
        'Name' => 'Registre du Commerce et de l’Industrie : RCI',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers for organizations at national level in Monaco. Issuing agency: Agence Monégasque de Sécurité Numérique (AMSN) in Monaco.',
      ],
      201 => [
        'Id' => '0207',
        'Name' => 'PiLog Ontology Codification Identifier (POCI)',
        'Description' => 'Intended Purpose/App. Area: A repository of concepts pertaining to any entity such as products, services, business partners, assets, organizations, locations, persons, addresses, languages, records etc along with the terminologies to describe each entity using class, characteristics, values, JoMs, QoMs, groups, definitions, guidelines, images, drawings, pictures. codes and any classification thereof. The codification will help exchange/integrate the data between operational, ERP, CRM, SRM or any other systems without any human interpretation and interaction without losing the meaning of the information in multiple languages, this will help organizations achieve their digital transformation goals more precisely in order to assess the real value-proposition of the underlying data that is driving their businesses. Issuing agency: PiLog Group in South Africa.',
      ],
      202 => [
        'Id' => '0208',
        'Name' => 'Numero d\'entreprise / ondernemingsnummer / Unternehmensnummer',
        'Description' => 'Intended Purpose/App. Area: Identification number attributed by the BCE/KBO/ZDU (the Belgian register) to identify entities and establishment units operating in Belgium. Issuing agency: Banque-Carrefour des Entreprises (BCE) / Kruispuntbank van Ondernemingen (KBO) / Zentrale Datenbank der Unternehmen (ZOU) Service public fédéral Economie, P.M.E.in Belgium.
          Classes moyennes et Energie',
      ],
      203 => [
        'Id' => '0209',
        'Name' => 'GS1 identification keys',
        'Description' => 'Intended Purpose/App. Area: GS1 identification keys and key qualifiers may be used by an information system to refer unambiguously to an entity such as a trade item, logistics unit, physical location, document, or service relationship. Issuing agency: GS1, a global organization.',
      ],
      204 => [
        'Id' => '0210',
        'Name' => 'CODICE FISCALE',
        'Description' => 'Intended Purpose/App. Area: Electronic Invoicing and e-procurement. Issuing agency: Agenzia delle Entrate, Italy.',
      ],
      205 => [
        'Id' => '0211',
        'Name' => 'PARTITA IVA',
        'Description' => 'Intended Purpose/App. Area: Electronic Invoicing and e-procurement. Issuing agency: Agenzia delle Entrate, Italy.',
      ],
      206 => [
        'Id' => '0212',
        'Name' => 'Finnish Organization Identifier',
        'Description' => 'Intended Purpose/App. Area: Identification scheme will be used for electronic trade purposes in e-invoicing, purchasing, electronic receipts. Issuing agency: State Treasury of Finland / Valtiokonttor.',
      ],
      207 => [
        'Id' => '0213',
        'Name' => 'Finnish Organization Value Add Tax Identifier',
        'Description' => 'Intended Purpose/App. Area: Identification scheme will be used for electronic trade purposes in e-invoicing, purchasing, electronic receipts. Issuing agency: State Treasury of Finland / Valtiokonttor.',
      ],
      208 => [
        'Id' => '0214',
        'Name' => 'Tradeplace TradePI Standard',
        'Description' => 'Intended Purpose/App. Area: Tradeplace is an independent company, set up as a joint venture of several Home Appliance- and Consumer Electronics manufacturers. Tradeplace has launched their TradePI (Product Information) initiative for home appliances, consumer electronics, DIY and affiliated industries that are connected to Tradeplace. The initiative introduces an enhanced standard structure for product information. The TradePI Standard helps retailers to take full advantage of electronic communication and data processing, as the Internet and ICT are fundamentally changing how products and services are offered, bought, and sold. Issuing agency: Tradeplace B.V., The Netherlands.',
      ],
      209 => [
        'Id' => '0215',
        'Name' => 'Net service ID',
        'Description' => 'Intended Purpose/App. Area: Identification scheme will be used for electronic trade purposes in e-invoicing, purchasing, electronic receipts. Issuing agency: Tieto Finland Oy, FINLAND.',
      ],
      210 => [
        'Id' => '0216',
        'Name' => 'OVTcode',
        'Description' => 'Intended Purpose/App. Area: Identification scheme will be used for electronic trade purposes in e-invoicing, purchasing, electronic receipts. Issuing agency: TIEKE- Tietoyhteiskunnan kehittamiskeskus, FINLAND.',
      ],
      211 => [
        'Id' => '0217',
        'Name' => 'The Netherlands Chamber of Commerce and Industry establishment number',
        'Description' => 'Intended Purpose/App. Area: Electronic invoicing. Issuing agency: Nederlands Normalisatie Instituut (NEN)',
      ],
      212 => [
        'Id' => '0218',
        'Name' => 'Unified registration number (Latvia)',
        'Description' => 'Intended Purpose/App. Area: Each legal entity registered with the Register of Enterprises of the Republic of Latvia is assigned a unique unified registration number. This unique unified registration number is used to identify legal subjects for every purpose where it might be necessary, including for the use of the tax authority. Issuing agency: The Register of Enterprises of the Republic of Latvia.',
      ],
      213 => [
        'Id' => '0219',
        'Name' => 'Taxpayer registration code (Latvia)',
        'Description' => 'Intended Purpose/App. Area: For use in Electronic data interchange (EDI) to identify private and public organizations. Issuing agency: State Revenue Service of the Republic of Latvia.',
      ],
      214 => [
        'Id' => '0220',
        'Name' => 'The Register of Natural Persons (Latvia)',
        'Description' => 'Intended Purpose/App. Area: The Register combines the functionality of the current information system of the Population Register and Civil Register. The Register is a uniform state registration and recording system of information and natural persons that provides identification of natural persons, data processing and accumulation, and includes and updates information about civil entries. The data included in the Register is used for statistical surveys, tax forecasting and calculation, organizing of elections and other processes of national importance. When entering information regarding a person in the Register, the Office of Citizenship and Migration Affairs of the Republic of Latvia shall assign an automatically generated individual personal identity number thereto. Issuing agency: Office of Citizenship and Migration Affairs of the Republic of Latvia.',
      ],
      215 => [
        'Id' => '0221',
        'Name' => 'The registered number of the qualified invoice issuer',
        'Description' => 'Intended Purpose/App. Area: The registered number of the qualified invoice issuer is used on the invoice-based method for Japanese consumption tax, which will be implemented on 1 October 2023. Issuing agency: National Tax Agency Japan',
      ],
      216 => [
        'Id' => '0222',
        'Name' => 'Metadata Registry Support',
        'Description' => 'Intended Purpose/App. Area: Database of metadata supporting description of object-data-information-etc. Issuing agency: Farance Inc.',
      ],
      217 => [
        'Id' => '0223',
        'Name' => 'EU based company',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers for organizations based in EU. Issuing agency: AIFE (Agence pour l’Informatique Financière de l’Etat)',
      ],
      218 => [
        'Id' => '0224',
        'Name' => 'FTCTC CODE ROUTAGE',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers used in electronic invoices for routing among accredited platforms for the French Continuous Transactional Control reform on e-invoicing. Issuing agency: AIFE (Agence pour l’Informatique Financière de l’Etat)',
      ],
      219 => [
        'Id' => '0225',
        'Name' => 'FRCTC ELECTRONIC ADDRESS',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers used as electronic addresses in the context of the French Continuous Transactional Control reform on e-invoicing. Issuing agency: AIFE (Agence pour l’Informatique Financière de l’Etat)',
      ],
      220 => [
        'Id' => '0226',
        'Name' => 'FRCTC Particulier',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers for French citizen sending invoices to the French Public Sector. Issuing agency: AIFE (Agence pour l’Informatique Financière de l’Etat)',
      ],
      221 => [
        'Id' => '0227',
        'Name' => 'NON - EU based company',
        'Description' => 'Intended Purpose/App. Area: NON - EU based company. Issuing agency: AIFE (Agence pour l’Informatique Financière de l’Etat)',
      ],
      222 => [
        'Id' => '0228',
        'Name' => 'Répertoire des Entreprises et des Etablissements (RIDET)',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers for organizations at national level in Nouvelle Caledonie (French). Issuing agency: AIFE (Agence pour l’Informatique Financière de l’Etat)',
      ],
      223 => [
        'Id' => '0229',
        'Name' => 'T.A.H.I.T.I (traitement automatique hiérarchisé des institutions de Tahiti et des îles)',
        'Description' => 'Intended Purpose/App. Area: To provide identifiers for organizations at national level in TAHITI (French). Issuing agency: AIFE (Agence pour l’Informatique Financière de l’Etat)',
      ],
      224 => [
        'Id' => '0230',
        'Name' => 'National e-Invoicing Framework',
        'Description' => 'Intended Purpose/App. Area: Identifier for  organizations. Issuing agency: Malaysia Digital Economy Corporation Sdn Bhd (MDEC)',
      ],
    ];
    return $iso6523_icd;
  }

  /**
   * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7143.xml
   * @return array
   */
  public function getUnc7143(): array {
    //Item type identification code (UNCL7143)
    //Identifier UNCL7143
    //version D.19A
    //UN/CEFACT
    $uncl7143 = [
      0 => [
        'Id' => 'AA',
        'Name' => 'Product version number',
        'Description' => 'Number assigned by manufacturer or seller to identify the release of a
                  product.',
      ],
      1 => [
        'Id' => 'AB',
        'Name' => 'Assembly',
        'Description' => 'The item number is that of an assembly.',
      ],
      2 => [
        'Id' => 'AC',
        'Name' => 'HIBC (Health Industry Bar Code)',
        'Description' => 'Article identifier used within health sector to indicate data used conforms to HIBC.',
      ],
      3 => [
        'Id' => 'AD',
        'Name' => 'Cold roll number',
        'Description' => 'Number assigned to a cold roll.',
      ],
      4 => [
        'Id' => 'AE',
        'Name' => 'Hot roll number',
        'Description' => 'Number assigned to a hot roll.',
      ],
      5 => [
        'Id' => 'AF',
        'Name' => 'Slab number',
        'Description' => 'Number assigned to a slab, which is produced in a particular production step.',
      ],
      6 => [
        'Id' => 'AG',
        'Name' => 'Software revision number',
        'Description' => 'A number assigned to indicate a revision of software.',
      ],
      7 => [
        'Id' => 'AH',
        'Name' => 'UPC (Universal Product Code) Consumer package code (1-5-5)',
        'Description' => 'An 11-digit code that uniquely identifies consumer does not have a check
                  digit.',
      ],
      8 => [
        'Id' => 'AI',
        'Name' => 'UPC (Universal Product Code) Consumer package code (1-5-5-1)',
        'Description' => 'A 12-digit code that uniquely identifies the consumer packaging of a product, including a check digit.',
      ],
      9 => [
        'Id' => 'AJ',
        'Name' => 'Sample number',
        'Description' => 'Number assigned to a sample.',
      ],
      10 => [
        'Id' => 'AK',
        'Name' => 'Pack number',
        'Description' => 'Number assigned to a pack containing a stack of items put together (e.g. cold roll sheets (steel product)).',
      ],
      11 => [
        'Id' => 'AL',
        'Name' => 'UPC (Universal Product Code) Shipping container code (1-2-5-5)',
        'Description' => 'A 13-digit code that uniquely identifies the manufacturer\'s shipping unit, including the packaging indicator.',
      ],
      12 => [
        'Id' => 'AM',
        'Name' => 'UPC (Universal Product Code)/EAN (European article number) Shipping container code (1-2-5-5-1)',
        'Description' => 'Shipping container code (1-2-5-5-1)manufacturer\'s shipping unit, including the
                  packagingindicator and the check digit.',
      ],
      13 => [
        'Id' => 'AN',
        'Name' => 'UPC (Universal Product Code) suffix',
        'Description' => 'A suffix used in conjunction with a higher level UPC (Universal product code) to define packing variations for a product.',
      ],
      14 => [
        'Id' => 'AO',
        'Name' => 'State label code',
        'Description' => 'A code which specifies the codification of the state\'s labelling requirements.',
      ],
      15 => [
        'Id' => 'AP',
        'Name' => 'Heat number',
        'Description' => 'Number assigned to the heat (also known as the iron charge) for the production of steel products.',
      ],
      16 => [
        'Id' => 'AQ',
        'Name' => 'Coupon number',
        'Description' => 'A number identifying a coupon.',
      ],
      17 => [
        'Id' => 'AR',
        'Name' => 'Resource number',
        'Description' => 'A number to identify a resource.',
      ],
      18 => [
        'Id' => 'AS',
        'Name' => 'Work task number',
        'Description' => 'A number to identify a work task.',
      ],
      19 => [
        'Id' => 'AT',
        'Name' => 'Price look up number',
        'Description' => 'Identification number on a product allowing a quick electronic retrieval of price information for that product.',
      ],
      20 => [
        'Id' => 'AU',
        'Name' => 'NSN (North Atlantic Treaty Organization Stock Number)',
        'Description' => 'Number assigned under the NATO (North Atlantic Treaty Organization) codification system to provide the identification of an approved item of supply.',
      ],
      21 => [
        'Id' => 'AV',
        'Name' => 'Refined product code',
        'Description' => 'A code specifying the product refinement designation.',
      ],
      22 => [
        'Id' => 'AW',
        'Name' => 'Exhibit',
        'Description' => 'A code indicating that the product is identified by an',
      ],
      23 => [
        'Id' => 'AX',
        'Name' => 'End item',
        'Description' => 'A number specifying an end item.',
      ],
      24 => [
        'Id' => 'AY',
        'Name' => 'Federal supply classification',
        'Description' => 'A code to specify a product\'s Federal supply classification.',
      ],
      25 => [
        'Id' => 'AZ',
        'Name' => 'Engineering data list',
        'Description' => 'A code specifying the product\'s engineering data list.',
      ],
      26 => [
        'Id' => 'BA',
        'Name' => 'Milestone event number',
        'Description' => 'A number to identify a milestone event.',
      ],
      27 => [
        'Id' => 'BB',
        'Name' => 'Lot number',
        'Description' => 'A number indicating the lot number of a product.',
      ],
      28 => [
        'Id' => 'BC',
        'Name' => 'National drug code 4-4-2 format',
        'Description' => 'A code identifying the product in national drug format 4-4-2.',
      ],
      29 => [
        'Id' => 'BD',
        'Name' => 'National drug code 5-3-2 format',
        'Description' => 'A code identifying the product in national drug format 5-3-2.',
      ],
      30 => [
        'Id' => 'BE',
        'Name' => 'National drug code 5-4-1 format',
        'Description' => 'A code identifying the product in national drug format 5-4-1.',
      ],
      31 => [
        'Id' => 'BF',
        'Name' => 'National drug code 5-4-2 format',
        'Description' => 'A code identifying the product in national drug format 5-4-2.',
      ],
      32 => [
        'Id' => 'BG',
        'Name' => 'National drug code',
        'Description' => 'A code specifying the national drug classification.',
      ],
      33 => [
        'Id' => 'BH',
        'Name' => 'Part number',
        'Description' => 'A number indicating the part.',
      ],
      34 => [
        'Id' => 'BI',
        'Name' => 'Local Stock Number (LSN)',
        'Description' => 'A local number assigned to an item of stock.',
      ],
      35 => [
        'Id' => 'BJ',
        'Name' => 'Next higher assembly number',
        'Description' => 'A number specifying the next higher assembly or component into which the product is being incorporated.',
      ],
      36 => [
        'Id' => 'BK',
        'Name' => 'Data category',
        'Description' => 'A code specifying a category of data.',
      ],
      37 => [
        'Id' => 'BL',
        'Name' => 'Control number',
        'Description' => 'To specify the control number.',
      ],
      38 => [
        'Id' => 'BM',
        'Name' => 'Special material identification code',
        'Description' => 'A number to identify the special material code.',
      ],
      39 => [
        'Id' => 'BN',
        'Name' => 'Locally assigned control number',
        'Description' => 'A number assigned locally for control purposes.',
      ],
      40 => [
        'Id' => 'BO',
        'Name' => 'Buyer\'s colour',
        'Description' => 'Colour assigned by buyer.',
      ],
      41 => [
        'Id' => 'BP',
        'Name' => 'Buyer\'s part number',
        'Description' => 'Reference number assigned by the buyer to identify an article.',
      ],
      42 => [
        'Id' => 'BQ',
        'Name' => 'Variable measure product code',
        'Description' => 'A code assigned to identify a variable measure item.',
      ],
      43 => [
        'Id' => 'BR',
        'Name' => 'Financial phase',
        'Description' => 'To specify as an item, the financial phase.',
      ],
      44 => [
        'Id' => 'BS',
        'Name' => 'Contract breakdown',
        'Description' => 'To specify as an item, the contract breakdown.',
      ],
      45 => [
        'Id' => 'BT',
        'Name' => 'Technical phase',
        'Description' => 'To specify as an item, the technical phase.',
      ],
      46 => [
        'Id' => 'BU',
        'Name' => 'Dye lot number',
        'Description' => 'Number identifying a dye lot.',
      ],
      47 => [
        'Id' => 'BV',
        'Name' => 'Daily statement of activities',
        'Description' => 'A statement listing activities of one day.',
      ],
      48 => [
        'Id' => 'BW',
        'Name' => 'Periodical statement of activities within a bilaterally agreed time period',
        'Description' => 'Periodical statement listing activities within a bilaterally agreed time period.',
      ],
      49 => [
        'Id' => 'BX',
        'Name' => 'Calendar week statement of activities',
        'Description' => 'A statement listing activities of a calendar week.',
      ],
      50 => [
        'Id' => 'BY',
        'Name' => 'Calendar month statement of activities',
        'Description' => 'A statement listing activities of a calendar month.',
      ],
      51 => [
        'Id' => 'BZ',
        'Name' => 'Original equipment number',
        'Description' => 'Original equipment number allocated to spare parts by the manufacturer.',
      ],
      52 => [
        'Id' => 'CC',
        'Name' => 'Industry commodity code',
        'Description' => 'The codes given to certain commodities by an industry.',
      ],
      53 => [
        'Id' => 'CG',
        'Name' => 'Commodity grouping',
        'Description' => 'Code for a group of articles with common characteristics (e.g. used for statistical purposes).',
      ],
      54 => [
        'Id' => 'CL',
        'Name' => 'Colour number',
        'Description' => 'Code for the colour of an article.',
      ],
      55 => [
        'Id' => 'CR',
        'Name' => 'Contract number',
        'Description' => 'Reference number identifying a contract.',
      ],
      56 => [
        'Id' => 'CV',
        'Name' => 'Customs article number',
        'Description' => 'Code defined by Customs authorities to an article or a group of articles for Customs purposes.',
      ],
      57 => [
        'Id' => 'DR',
        'Name' => 'Drawing revision number',
        'Description' => 'Reference number indicating that a change or revision has been applied to a drawing.',
      ],
      58 => [
        'Id' => 'DW',
        'Name' => 'Drawing',
        'Description' => 'Reference number identifying a drawing of an article.',
      ],
      59 => [
        'Id' => 'EC',
        'Name' => 'Engineering change level',
        'Description' => 'Reference number indicating that a change or revision has been applied to an article\'s specification.',
      ],
      60 => [
        'Id' => 'EF',
        'Name' => 'Material code',
        'Description' => 'Code defining the material\'s type, surface, geometric form plus various classifying characteristics.',
      ],
      61 => [
        'Id' => 'EMD',
        'Name' => 'EMDN (European Medical Device Nomenclature)',
        'Description' => 'Nomenclature system for identification of medical devices based on European Medical Device Nomenclature classification system.',
      ],
      62 => [
        'Id' => 'EN',
        'Name' => 'International Article Numbering Association (EAN)',
        'Description' => 'Number assigned to a manufacturer\'s product according to the International Article Numbering Association.',
      ],
      63 => [
        'Id' => 'FS',
        'Name' => 'Fish species',
        'Description' => 'Identification of fish species.',
      ],
      64 => [
        'Id' => 'GB',
        'Name' => 'Buyer\'s internal product group code',
        'Description' => 'Product group code used within a buyer\'s internal systems.',
      ],
      65 => [
        'Id' => 'GN',
        'Name' => 'National product group code',
        'Description' => 'National product group code. Administered by a national agency.',
      ],
      66 => [
        'Id' => 'GS',
        'Name' => 'General specification number',
        'Description' => 'The item number is a general specification number.',
      ],
      67 => [
        'Id' => 'HS',
        'Name' => 'Harmonised system',
        'Description' => 'The item number is part of, or is generated in the context of the Harmonised Commodity Description and Coding System (Harmonised System), as developed and maintained by the World Customs Organization (WCO).',
      ],
      68 => [
        'Id' => 'IB',
        'Name' => 'ISBN (International Standard Book Number)',
        'Description' => 'A unique number identifying a book.',
      ],
      69 => [
        'Id' => 'IN',
        'Name' => 'Buyer\'s item number',
        'Description' => 'The item number has been allocated by the buyer.',
      ],
      70 => [
        'Id' => 'IS',
        'Name' => 'ISSN (International Standard Serial Number)',
        'Description' => 'A unique number identifying a serial publication.',
      ],
      71 => [
        'Id' => 'IT',
        'Name' => 'Buyer\'s style number',
        'Description' => 'Number given by the buyer to a specific style or form of an article, especially used for garments.',
      ],
      72 => [
        'Id' => 'IZ',
        'Name' => 'Buyer\'s size code',
        'Description' => 'Code given by the buyer to designate the size of an article in textile and shoe industry.',
      ],
      73 => [
        'Id' => 'MA',
        'Name' => 'Machine number',
        'Description' => 'The item number is a machine number.',
      ],
      74 => [
        'Id' => 'MF',
        'Name' => 'Manufacturer\'s (producer\'s) article number',
        'Description' => 'The number given to an article by its manufacturer.',
      ],
      75 => [
        'Id' => 'MN',
        'Name' => 'Model number',
        'Description' => 'Reference number assigned by the manufacturer to differentiate variations in similar products in a class or group.',
      ],
      76 => [
        'Id' => 'MP',
        'Name' => 'Product/service identification number',
        'Description' => 'Reference number identifying a product or service.',
      ],
      77 => [
        'Id' => 'NB',
        'Name' => 'Batch number',
        'Description' => 'The item number is a batch number.',
      ],
      78 => [
        'Id' => 'ON',
        'Name' => 'Customer order number',
        'Description' => 'Reference number of a customer\'s order.',
      ],
      79 => [
        'Id' => 'PD',
        'Name' => 'Part number description',
        'Description' => 'Reference number identifying a description associated with a number ultimately used to identify an article.',
      ],
      80 => [
        'Id' => 'PL',
        'Name' => 'Purchaser\'s order line number',
        'Description' => 'Reference number identifying a line entry in a customer\'s order for goods or services.',
      ],
      81 => [
        'Id' => 'PO',
        'Name' => 'Purchase order number',
        'Description' => 'Reference number identifying a customer\'s order.',
      ],
      82 => [
        'Id' => 'PV',
        'Name' => 'Promotional variant number',
        'Description' => 'The item number is a promotional variant number.',
      ],
      83 => [
        'Id' => 'QS',
        'Name' => 'Buyer\'s qualifier for size',
        'Description' => 'The item number qualifies the size of the buyer.',
      ],
      84 => [
        'Id' => 'RC',
        'Name' => 'Returnable container number',
        'Description' => 'Reference number identifying a returnable container.',
      ],
      85 => [
        'Id' => 'RN',
        'Name' => 'Release number',
        'Description' => 'Reference number identifying a release from a buyer\'s purchase order.',
      ],
      86 => [
        'Id' => 'RU',
        'Name' => 'Run number',
        'Description' => 'The item number identifies the production or manufacturing run or sequence in which the item was manufactured, processed or assembled.',
      ],
      87 => [
        'Id' => 'RY',
        'Name' => 'Record keeping of model year',
        'Description' => 'The item number relates to the year in which the particular model was kept.',
      ],
      88 => [
        'Id' => 'SA',
        'Name' => 'Supplier\'s article number',
        'Description' => 'Number assigned to an article by the supplier of that article.',
      ],
      89 => [
        'Id' => 'SG',
        'Name' => 'Standard group of products (mixed assortment)',
        'Description' => 'The item number relates to a standard group of other items (mixed) which are grouped together as a single item for identification purposes.',
      ],
      90 => [
        'Id' => 'SK',
        'Name' => 'SKU (Stock keeping unit)',
        'Description' => 'Reference number of a stock keeping unit.',
      ],
      91 => [
        'Id' => 'SN',
        'Name' => 'Serial number',
        'Description' => 'Identification number of an item which distinguishes this specific item out of a number of identical items.',
      ],
      92 => [
        'Id' => 'SRS',
        'Name' => 'RSK number',
        'Description' => 'Plumbing and heating.',
      ],
      93 => [
        'Id' => 'SRT',
        'Name' => 'IFLS (Institut Francais du Libre Service) 5 digit product classification code',
        'Description' => '5 digit code for product classification managed by the Institut Francais du Libre Service.',
      ],
      94 => [
        'Id' => 'SRU',
        'Name' => 'IFLS (Institut Francais du Libre Service) 9 digit product classification code',
        'Description' => '9 digit code for product classification managed by the Institut Francais du Libre Service.',
      ],
      95 => [
        'Id' => 'SRV',
        'Name' => 'GS1 Global Trade Item Number',
        'Description' => 'A unique number, up to 14-digits, assigned according to the numbering structure of the GS1 system.',
      ],
      96 => [
        'Id' => 'SRW',
        'Name' => 'EDIS (Energy Data Identification System)',
        'Description' => 'European system for identification of meter data.',
      ],
      97 => [
        'Id' => 'SRX',
        'Name' => 'Slaughter number',
        'Description' => 'Unique number given by a slaughterhouse to an animal or a group of animals of the same breed.',
      ],
      98 => [
        'Id' => 'SRY',
        'Name' => 'Official animal number',
        'Description' => 'Unique number given by a national authority to identify an animal individually.',
      ],
      99 => [
        'Id' => 'SRZ',
        'Name' => 'Harmonized tariff schedule',
        'Description' => 'The international Harmonized Tariff Schedule (HTS) to classify the article for customs, statistical and other purposes.',
      ],
      100 => [
        'Id' => 'SS',
        'Name' => 'Supplier\'s supplier article number',
        'Description' => 'Article number referring to a sales catalogue of supplier\'s supplier.',
      ],
      101 => [
        'Id' => 'SSA',
        'Name' => '46 Level DOT Code',
        'Description' => 'A US Department of Transportation (DOT) code to identify hazardous (dangerous) goods, managed by the Customs and Border Protection (CBP) agency.',
      ],
      102 => [
        'Id' => 'SSB',
        'Name' => 'Airline Tariff 6D',
        'Description' => 'A US code agreed to by the airline industry to identify hazardous (dangerous) goods, managed by the Customs and Border Protection (CBP) agency.',
      ],
      103 => [
        'Id' => 'SSC',
        'Name' => 'Title 49 Code of Federal Regulations',
        'Description' => 'A US Customs and Border Protection (CBP) code used to identify hazardous (dangerous) goods.',
      ],
      104 => [
        'Id' => 'SSD',
        'Name' => 'International Civil Aviation Administration code',
        'Description' => 'A US Department of Transportation/Federal Aviation Administration code used to identify hazardous (dangerous) goods, managed by the Customs and Border Protection (CBP) agency.',
      ],
      105 => [
        'Id' => 'SSE',
        'Name' => 'Hazardous Materials ID DOT',
        'Description' => 'A US Department of Transportation (DOT) code used toCustoms and Border
                  Protection (CBP) agency.',
      ],
      106 => [
        'Id' => 'SSF',
        'Name' => 'Endorsement',
        'Description' => 'A US Customs and Border Protection (CBP) code used to identify hazardous (dangerous) goods.',
      ],
      107 => [
        'Id' => 'SSG',
        'Name' => 'Air Force Regulation 71-4',
        'Description' => 'A department of Defense/Air Force code used to identifyBorder Protection (CBP)
                  agency.',
      ],
      108 => [
        'Id' => 'SSH',
        'Name' => 'Breed',
        'Description' => 'The breed of the item (e.g. plant or animal).',
      ],
      109 => [
        'Id' => 'SSI',
        'Name' => 'Chemical Abstract Service (CAS) registry number',
        'Description' => 'A unique numerical identifier for for chemical compounds, polymers, biological sequences, mixtures and alloys.',
      ],
      110 => [
        'Id' => 'SSJ',
        'Name' => 'Engine model designation',
        'Description' => 'A name or designation to identify an engine model.',
      ],
      111 => [
        'Id' => 'SSK',
        'Name' => 'Institutional Meat Purchase Specifications (IMPS) Number',
        'Description' => 'A number assigned by agricultural authorities to identify and track meat and meat products.',
      ],
      112 => [
        'Id' => 'SSL',
        'Name' => 'Price Look-Up code (PLU)',
        'Description' => 'A number assigned by agricultural authorities to identify and track meat and meat products.',
      ],
      113 => [
        'Id' => 'SSM',
        'Name' => 'International Maritime Organization (IMO) Code',
        'Description' => 'An International Maritime Organization (IMO) code used to identify hazardous (dangerous) goods.',
      ],
      114 => [
        'Id' => 'SSN',
        'Name' => 'Bureau of Explosives 600-A (rail)',
        'Description' => 'A Department of Transportation/Federal Railroad Administration code used to identify hazardous (dangerous) goods.',
      ],
      115 => [
        'Id' => 'SSO',
        'Name' => 'United Nations Dangerous Goods List',
        'Description' => 'A UN code used to classify and identify dangerous goods.',
      ],
      116 => [
        'Id' => 'SSP',
        'Name' => 'International Code of Botanical Nomenclature (ICBN)',
        'Description' => 'A code established by the International Code of Botanical Nomenclature (ICBN) used to classify and identify botanical articles and commodities.',
      ],
      117 => [
        'Id' => 'SSQ',
        'Name' => 'International Code of Zoological Nomenclature (ICZN)',
        'Description' => 'A code established by the International Code of Zoological Nomenclature (ICZN) used to classify and identify animals.',
      ],
      118 => [
        'Id' => 'SSR',
        'Name' => 'International Code of Nomenclature for Cultivated Plants (ICNCP)',
        'Description' => 'A code established by the International Code of Nomenclature for Cultivated Plants (ICNCP) used to classify and identify animals.',
      ],
      119 => [
        'Id' => 'SSS',
        'Name' => 'Distributor’s article identifier',
        'Description' => 'Identifier assigned to an article by the distributor of that article.',
      ],
      120 => [
        'Id' => 'SST',
        'Name' => 'Norwegian Classification system ENVA',
        'Description' => 'Product classification system used in the Norwegian market.',
      ],
      121 => [
        'Id' => 'SSU',
        'Name' => 'Supplier assigned classification',
        'Description' => 'Product classification assigned by the supplier.',
      ],
      122 => [
        'Id' => 'SSV',
        'Name' => 'Mexican classification system AMECE',
        'Description' => 'Product classification system used in the Mexican market.',
      ],
      123 => [
        'Id' => 'SSW',
        'Name' => 'German classification system CCG',
        'Description' => 'Product classification system used in the German market.',
      ],
      124 => [
        'Id' => 'SSX',
        'Name' => 'Finnish classification system EANFIN',
        'Description' => 'Product classification system used in the Finnish market.',
      ],
      125 => [
        'Id' => 'SSY',
        'Name' => 'Canadian classification system ICC',
        'Description' => 'Product classification system used in the Canadian market.',
      ],
      126 => [
        'Id' => 'SSZ',
        'Name' => 'French classification system IFLS5',
        'Description' => 'Product classification system used in the French market.',
      ],
      127 => [
        'Id' => 'ST',
        'Name' => 'Style number',
        'Description' => 'Number given to a specific style or form of an article, especially used for garments.',
      ],
      128 => [
        'Id' => 'STA',
        'Name' => 'Dutch classification system CBL',
        'Description' => 'Product classification system used in the Dutch market.',
      ],
      129 => [
        'Id' => 'STB',
        'Name' => 'Japanese classification system JICFS',
        'Description' => 'Product classification system used in the Japanese market.',
      ],
      130 => [
        'Id' => 'STC',
        'Name' => 'European Union dairy subsidy eligibility classification',
        'Description' => 'Category of product eligible for EU subsidy (applies for certain dairy products with specific level of fat content).',
      ],
      131 => [
        'Id' => 'STD',
        'Name' => 'GS1 Spain classification system',
        'Description' => 'Product classification system used in the Spanish market.',
      ],
      132 => [
        'Id' => 'STE',
        'Name' => 'GS1 Poland classification system',
        'Description' => 'Product classification system used in the Polish market.',
      ],
      133 => [
        'Id' => 'STF',
        'Name' => 'Federal Agency on Technical Regulating and Metrology of the Russian Federation',
        'Description' => 'A Russian government agency that serves as a national standardization body of the Russian Federation.',
      ],
      134 => [
        'Id' => 'STG',
        'Name' => 'Efficient Consumer Response (ECR) Austria classification system',
        'Description' => 'Product classification system used in the Austrian market.',
      ],
      135 => [
        'Id' => 'STH',
        'Name' => 'GS1 Italy classification system',
        'Description' => 'Product classification system used in the Italian market.',
      ],
      136 => [
        'Id' => 'STI',
        'Name' => 'CPV (Common Procurement Vocabulary)',
        'Description' => 'Official classification system for public procurement in the European Union.',
      ],
      137 => [
        'Id' => 'STJ',
        'Name' => 'IFDA (International Foodservice Distributors Association)',
        'Description' => 'International Foodservice Distributors Association (IFDA).',
      ],
      138 => [
        'Id' => 'STK',
        'Name' => 'AHFS (American Hospital Formulary Service) pharmacologic -therapeutic classification',
        'Description' => 'Pharmacologic -therapeutic classification maintained by the American Hospital Formulary Service (AHFS).',
      ],
      139 => [
        'Id' => 'STL',
        'Name' => 'ATC (Anatomical Therapeutic Chemical) classification system',
        'Description' => 'Anatomical Therapeutic Chemical classification system maintained by the World Health Organisation (WHO).',
      ],
      140 => [
        'Id' => 'STM',
        'Name' => 'CLADIMED (Classification des Dispositifs Médicaux)',
        'Description' => 'A five level classification system for medical decvices maintained by the CLADIMED organisation used in the French market.',
      ],
      141 => [
        'Id' => 'STN',
        'Name' => 'CMDR (Canadian Medical Device Regulations) classification system',
        'Description' => 'Classification system related to the Canadian Medical Device Regulations maintained by Health Canada.',
      ],
      142 => [
        'Id' => 'STO',
        'Name' => 'CNDM (Classificazione Nazionale dei Dispositivi Medici)',
        'Description' => 'A classification system for medical devices used in the Italian market.',
      ],
      143 => [
        'Id' => 'STP',
        'Name' => 'UK DM&D (Dictionary of Medicines & Devices) standard coding scheme',
        'Description' => 'A classification system for medicines and devices used in the UK market.',
      ],
      144 => [
        'Id' => 'STQ',
        'Name' => 'eCl@ss',
        'Description' => 'Standardized material and service classification and dictionary maintained by eClass e.V.',
      ],
      145 => [
        'Id' => 'STR',
        'Name' => 'EDMA (European Diagnostic Manufacturers Association) Product Classification',
        'Description' => 'Classification for in vitro diagnostics medical devices maintained by the European Diagnostic Manufacturers Association.',
      ],
      146 => [
        'Id' => 'STS',
        'Name' => 'EGAR (European Generic Article Register)',
        'Description' => 'A classification system for medical devices.',
      ],
      147 => [
        'Id' => 'STT',
        'Name' => 'GMDN (Global Medical Devices Nomenclature)',
        'Description' => 'Nomenclature system for identification of medical devices officially apprroved by the European Union.',
      ],
      148 => [
        'Id' => 'STU',
        'Name' => 'GPI (Generic Product Identifier)',
        'Description' => 'A drug classification system managed by Medi-Span.',
      ],
      149 => [
        'Id' => 'STV',
        'Name' => 'HCPCS (Healthcare Common Procedure Coding System)',
        'Description' => 'A classification system used with US healthcare insurance programs.',
      ],
      150 => [
        'Id' => 'STW',
        'Name' => 'ICPS (International Classification for Patient Safety)',
        'Description' => 'A patient safety taxonomy maintained by the World Health Organisation.',
      ],
      151 => [
        'Id' => 'STX',
        'Name' => 'MedDRA (Medical Dictionary for Regulatory Activities)',
        'Description' => 'A medical dictionary maintained by the International Federation of Pharmaceutical Manufacturers and Associations (IFPMA).',
      ],
      152 => [
        'Id' => 'STY',
        'Name' => 'Medical Columbus',
        'Description' => 'Medical product classification system used in the German market.',
      ],
      153 => [
        'Id' => 'STZ',
        'Name' => 'NAPCS (North American Product Classification System)',
        'Description' => 'Product classification system used in the North American market.',
      ],
      154 => [
        'Id' => 'SUA',
        'Name' => 'NHS (National Health Services) eClass',
        'Description' => 'Product and Service classification system used in United Kingdom market.',
      ],
      155 => [
        'Id' => 'SUB',
        'Name' => 'US FDA (Food and Drug Administration) Product Code Classification Database',
        'Description' => 'US FDA Product Code Classification Database contains medical device names and associated information developed by the Center for Devices and Radiological Health (CDRH).',
      ],
      156 => [
        'Id' => 'SUC',
        'Name' => 'SNOMED CT (Systematized Nomenclature of Medicine-Clinical Terms)',
        'Description' => 'A medical nomenclature system developed between the NHS and the College of American Pathologists.',
      ],
      157 => [
        'Id' => 'SUD',
        'Name' => 'UMDNS (Universal Medical Device Nomenclature System)',
        'Description' => 'A standard international nomenclature and computer coding system for medical devices maintained by the Emergency Care Research Institute (ECRI).',
      ],
      158 => [
        'Id' => 'SUE',
        'Name' => 'GS1 Global Returnable Asset Identifier, non-serialised',
        'Description' => 'A unique, 13-digit number assigned according to the numbering structure of the GS1 system and used to identify a type of Reusable Transport Item (RTI).',
      ],
      159 => [
        'Id' => 'SUF',
        'Name' => 'IMEI',
        'Description' => 'The International Mobile Station Equipment Identity (IMEI) is a unique number to identify mobile phones. It includes the origin, model and serial number of the device. The structure is specified in 3GPP TS 23.003.',
      ],
      160 => [
        'Id' => 'SUG',
        'Name' => 'Waste Type (EMSA)',
        'Description' => 'Classification of waste as defined by the European Maritime Safety Agency (EMSA).',
      ],
      161 => [
        'Id' => 'SUH',
        'Name' => 'Ship\'s store classification type',
        'Description' => 'Classification of ship’s stores.',
      ],
      162 => [
        'Id' => 'SUI',
        'Name' => 'Emergency fire code',
        'Description' => 'Classification for emergency response procedures related to fire.',
      ],
      163 => [
        'Id' => 'SUJ',
        'Name' => 'Emergency spillage code',
        'Description' => 'Classification for emergency response procedures related to spillage.',
      ],
      164 => [
        'Id' => 'SUK',
        'Name' => 'IMDG packing group',
        'Description' => 'Packing group as defined in the International Marititme Dangerous Goods (IMDG) specification.',
      ],
      165 => [
        'Id' => 'SUL',
        'Name' => 'MARPOL Code IBC',
        'Description' => 'International Bulk Chemical (IBC) code defined by the International Convention for the Prevention of Pollution from Ships (MARPOL).',
      ],
      166 => [
        'Id' => 'SUM',
        'Name' => 'IMDG subsidiary risk class',
        'Description' => 'Subsidiary risk class as defined in the International Maritime Dangerous Goods (IMDG) specification.',
      ],
      167 => [
        'Id' => 'TG',
        'Name' => 'Transport group number',
        'Description' => '(8012) Additional number to form article groups for packing and/or transportation purposes.',
      ],
      168 => [
        'Id' => 'TSN',
        'Name' => 'Taxonomic Serial Number',
        'Description' => 'A unique number assigned to a taxonomic entity, commonly to a species of plants or animals, providing information on their hierarchical classification, scientific name, taxonomic rank, associated synonyms and vernacular names where appropriate, data source information and data quality indicators.',
      ],
      169 => [
        'Id' => 'TSO',
        'Name' => 'IMDG main hazard class',
        'Description' => 'Main hazard class as defined in the International Maritime Dangerous Goods (IMDG) specification.',
      ],
      170 => [
        'Id' => 'TSP',
        'Name' => 'EU Combined Nomenclature',
        'Description' => 'The number is part of, or is generated in the context of the Combined Nomenclature classification, as developed and maintained by the European Union (EU).',
      ],
      171 => [
        'Id' => 'TSQ',
        'Name' => 'Therapeutic classification number',
        'Description' => 'A code to specify a product\'s therapeutic classification.',
      ],
      172 => [
        'Id' => 'TSR',
        'Name' => 'European Waste Catalogue',
        'Description' => 'Waste type number according to the European Waste Catalogue (EWC).',
      ],
      173 => [
        'Id' => 'TSS',
        'Name' => 'Price grouping code',
        'Description' => 'Number assigned to identify a grouping of products based on price.',
      ],
      174 => [
        'Id' => 'TST',
        'Name' => 'UNSPSC',
        'Description' => 'The UNSPSC commodity classification system.',
      ],
      175 => [
        'Id' => 'TSU',
        'Name' => 'EU RoHS Directive',
        'Description' => 'European Union Directive on the restriction of hazardous substances.',
      ],
      176 => [
        'Id' => 'UA',
        'Name' => 'Ultimate customer\'s article number',
        'Description' => 'Number assigned by ultimate customer to identify relevant article.',
      ],
      177 => [
        'Id' => 'UP',
        'Name' => 'UPC (Universal product code)',
        'Description' => 'Number assigned to a manufacturer\'s product by the Product Code Council.',
      ],
      178 => [
        'Id' => 'VN',
        'Name' => 'Vendor item number',
        'Description' => 'Reference number assigned by a vendor/seller identifying',
      ],
      179 => [
        'Id' => 'VP',
        'Name' => 'Vendor\'s (seller\'s) part number',
        'Description' => 'Reference number assigned by a vendor/seller identifying a product/service/article.',
      ],
      180 => [
        'Id' => 'VS',
        'Name' => 'Vendor\'s supplemental item number',
        'Description' => 'The item number is a specified by the vendor as a supplemental number for the vendor\'s purposes.',
      ],
      181 => [
        'Id' => 'VX',
        'Name' => 'Vendor specification number',
        'Description' => 'The item number has been allocated by the vendor as a specification number.',
      ],
      182 => [
        'Id' => 'ZZZ',
        'Name' => 'Mutually defined',
        'Description' => 'Item type identification mutually agreed between interchanging parties.',
      ],
    ];
    return $uncl7143;
  }

}
