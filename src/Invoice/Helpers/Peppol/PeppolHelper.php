<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

// Yiisoft
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;
// Entities
use App\Infrastructure\Persistence\{
    DeliveryLocation\DeliveryLocation as DL,
    InvAllowanceCharge\InvAllowanceCharge, Inv\Inv, InvItem\InvItem,
    InvItemAllowanceCharge\InvItemAllowanceCharge,
    InvAmount\InvAmount, InvItemAmount\InvItemAmount
};
use App\Infrastructure\Persistence\UnitPeppol\UnitPeppol;
use App\Infrastructure\Persistence\Upload\Upload;
use App\Invoice\Helpers\{CountryHelper, DateHelper, NumberHelper};
use App\Invoice\Libraries\PeppolUblXml;
use App\Invoice\{Setting\SettingRepository as SRepo,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR, InvItem\InvItemRepository as IIR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    Contract\ContractRepository as ContractRepo,
    ClientPeppol\ClientPeppolRepository as cpR,
    Delivery\DeliveryRepository as DelRepo,
    DeliveryParty\DeliveryPartyRepository as DelPartyRepo,
    PostalAddress\PostalAddressRepository as paR,
    SalesOrder\SalesOrderRepository as SOR,
    SalesOrderItem\SalesOrderItemRepository as SOIR,
    TaxRate\TaxRateRepository as TRR,
    Upload\UploadRepository as upR,
    UnitPeppol\UnitPeppolRepository as unpR};
use App\Invoice\Ubl\{AdditionalDocumentReference, Address, Attachment, Contact,
    ContractDocumentReference, Country, Delivery, FinancialInstitutionBranch,
    InvoicePeriod, LegalMonetaryTotal, OrderReference, Party,
    PartyLegalEntity, PartyTaxScheme, PayeeFinancialAccount, PaymentMeans,
    PaymentTerms, Schema, TaxScheme};
use App\Invoice\Libraries\{PeppolFinancialData, PeppolInvoiceDates,
    PeppolInvoiceHeader, PeppolInvoiceReferences, PeppolPaymentData};
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolBuyerReferenceNotFoundException as BuyerRefNf,
    PeppolBuyerPostalAddressNotFoundException as BuyerPostAddNf,
    PeppolClientNotFoundException as ClientNf,
    PeppolClientIdNotFoundException as ClientIdNf,
    PeppolClientsAccountingCostNotFoundException as ClientsAccCostNf,
    PeppolDeliveryLocationIDNotFoundException as DelLocIdNf,
    PeppolDeliveryLocationCountryNameNotFoundException as DelLocCounNameNf,
    PeppolInvoicePeriodDetailsIncompleteException as InvPeriodDetIncompleteNf,
    PeppolInvoiceNoteNotFoundException as InvoiceNoteNf,
    PeppolProductUnitCodeNotFoundException as ProductUnitCodeNf,
    PeppolProductItemClassificationCodeSchemeIdNotFoundException as PICCSINf,
    PeppolSalesOrderNotFoundException as SalesOrderNf,
    PeppolSalesOrderPurchaseOrderNumberNotExistException as SalesOrderPONumNe,
    PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException as SOIPOINNe,
    PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException as SOIPOLNNe,
    PeppolSalesOrderItemNotExistException as SOINe,
    PeppolSupplierAssignedAccountIdNotFoundException as SAAINf,
    PeppolTaxCategoryPercentNotFoundException as TCPNf,
    PeppolTaxCategoryCodeNotFoundException as TCCNf,
    PeppolNoLinkedInvoiceFoundException as NLIf,
    PeppolTryingToSendNonPdfFileException as TTSNPdfFile,
};
use DateTimeImmutable;
use DateTime;

class PeppolHelper
{
    public const string SETTING_PEPPOL_DOCUMENT_CURRENCY = 'peppol_document_currency';
    public const string TAX_CATEGORY_VAT = 'VAT';
    public const string DATE_FORMAT_YMD = 'Y-m-d';

    // UBL element name constants (used 5+ times as array keys/values)
    public const string UBL_SCHEME_ID = 'schemeID';
    public const string UBL_ENDPOINT_ID = 'EndPointID';
    public const string UBL_PAYEE_FINANCIAL_ACCOUNT = 'PayeeFinancialAccount';
    public const string UBL_PARTY_TAX_SCHEME = 'PartyTaxScheme';
    public const string UBL_PARTY_LEGAL_ENTITY = 'PartyLegalEntity';
    public const string UBL_COMPANY_ID = 'CompanyID';
    public const string UBL_PARTY_IDENTIFICATION = 'PartyIdentification';
    public const string UBL_CURRENCY_ID = 'currencyID';
    public const string UBL_FINANCIAL_INSTITUTION_BRANCH = 'FinancialInstitutionBranch';
    public const string UBL_STREET_NAME = 'StreetName';
    public const string UBL_ADDITIONAL_STREET_NAME = 'AdditionalStreetName';
    public const string UBL_ADDRESS_LINE = 'AddressLine';
    public const string UBL_CITY_NAME = 'CityName';
    public const string UBL_POSTAL_ZONE = 'PostalZone';
    public const string UBL_COUNTRY_SUBENTITY = 'CountrySubentity';
    public const string UBL_IDENTIFICATION_CODE = 'IdentificationCode';
    public const string UBL_TAX_SCHEME = 'TaxScheme';
    public const string UBL_REGISTRATION_NAME = 'RegistrationName';
    public const string UBL_TELEPHONE = 'Telephone';
    public const string UBL_ELECTRONIC_MAIL = 'ElectronicMail';
    public const string UBL_LIST_ID = 'ListId';

    // ISO 6523 ICD description prefix constants (used 6+ times in getIso6523Icd())
    public const string ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL =
            'A code identifying the product in national ';
    public const string ICD_A_IN_THE_SIO_THE =
            'a) In the SIO the ';
    public const string ICD_FORMS_THE = 'The ICD code forms the ';
    public const string ICD_EDIRA_COMPLIANT = '(EDIRA compliant)';
    public const string ICD_E_INVOICING_PURCHASING_ELECTRONIC_RECEIPTS =
            'e-invoicing, purchasing, electronic receipts. ';
    public const string ICD_FINANCIERE_DE_L_ETAT = 'FinanciÃ¨re de lâ€™Etat)';
    public const string ICD_INVOICE_ISSUE_DATE =
            'Invoice Issue Date/Time ie. Date Created/Issued';
    public const string ICD_INITIAL_PART_OSI =
            'initial part of the OSI network addressing and naming ';
    public const string ICD_ISSUING_AGENCY_AIFE_AGENCE_POUR_L_INFORMATIQUE =
            'Issuing agency: AIFE (Agence pour lâ€™Informatique ';
    public const string ICD_ISSUE_INVOICE_DATE =
            'Invoice Issue Date/Time ie. Date Created/Issued';
    public const string ICD_PURPOSE_TO_PROVIDE =
            'Intended Purpose/App. Area: To provide ';
    public const string ICD_PURPOSE_IDENTIFICATION =
            'Intended Purpose/App. Area: Identification ';
    public const string ICD_PURPOSE_FOR_USE_IN_EDI =
            'Intended Purpose/App. Area: For use in EDI ';
    public const string ICD_PURPOSE_USED_TO_IDENTIFY =
            'Intended Purpose/App. Area: Used to identify';
    public const string ICD_PURPOSE_ELECTRONIC =
            'Intended Purpose/App. Area: Electronic ';
    public const string ICD_REFERENCE_NUMBER_IDENTIFYING_A =
            'Reference number identifying a ';
    public const string ICD_SCHEME_WILL_BE_USED_FOR_ELECTRONIC_TRADE_PURPOSES_IN =
            'scheme will be used for electronic trade purposes in ';
    public const string ICD_TO_BE_USED_FOR =
            'To be used for ';
    public const string ICD_THE_ICD_CODE_WILL_FORM =
            'The ICD code will form ';
    public const string ICD_TREE_DEPICTED_ADDENDUM_2_ISO_8348 =
            'tree as depicted in Addendum 2 to ISO 8348. ';
    public const string ICD_WILL_ALSO = 'The ICD code will also ';
   
    private readonly DateHelper $datehelper;
    private string $documentCurrency;
    private string $from_currency;
    private string $to_currency;

    public function __construct(
        public SRepo $s,
        private readonly DelRepo $delRepo,
        private readonly InvAmount $inv_amount,
        private readonly DL $delivery_location,
        private readonly Translator $t,
    ) {
        $this->datehelper = new DateHelper($this->s);
        $this->documentCurrency =
            $this->s->getSetting(self::SETTING_PEPPOL_DOCUMENT_CURRENCY);
        $this->from_currency = $this->s->getSetting('currency_code_from');
        $this->to_currency   = $this->s->getSetting('currency_code_to');
    }
    
    /** @psalm-suppress UnusedReturnValue */
    private function ensureTempPeppolFolderAndUploadsFolderExist(): Aliases
    {
        $aliases = new Aliases([
            '@invoice' => dirname(__DIR__, 2),
            '@Uploads' => '@invoice/Uploads'
        ]);
        // Invoice/Uploads/Archive
        $folder = $aliases->get('@Uploads');
        // Check if the uploads folder is available
        if (!(is_dir($folder) || is_link($folder))) {
            FileHelper::ensureDirectory($folder, 0o775);
        }
        // Invoice/Uploads/Temp/Peppol
        $temp_peppol_folder = $aliases->get('@Uploads')
            . $this->s::getTempPeppolfolderRelativeUrl();
        if (!is_dir($temp_peppol_folder)) {
            FileHelper::ensureDirectory($temp_peppol_folder, 0o775);
        }
        return $aliases;
    }

    /**
     * Related logic:
     *  see \config\common\params.php and src\Invoice\Setting\SettingRepository
     * @param Inv $invoice
     * @param PeppolHelperInvDeps $inv
     * @param PeppolHelperNetDeps $net
     * @param PeppolHelperChargeDeps $charge
     * @throws \Exception
     * @throws BuyerRefNf
     * @return string
     */
    public function generateInvoicePeppolUblXmlTempFile(
        Inv $invoice,
        PeppolHelperInvDeps $inv,
        PeppolHelperNetDeps $net,
        PeppolHelperChargeDeps $charge,
    ): string {
        $invoice_id = $invoice->reqId();
        $this->ensureTempPeppolFolderAndUploadsFolderExist();
        $path = $this->UploadsTempPeppolXmlFileNamePathWithExt($invoice);
        // Generate inv items from Entity Inv->getItems() HasMany function
        // Generate inv item amounts from $iiaR
        $peppol_ubl_xml = new PeppolUblXml($this->s);
        $f = fopen($path, 'wb');
        if (!$f) {
            throw new PeppolHelperException(
                    sprintf('Unable to create output file %s', $path));
        }
        $deliveryLocation_ID_scheme =
                $this->buildDeliveryLocationIDScheme();
        $deliveryLocation_Address =
                $this->buildDeliveryLocationAddress();
        // If no actual delivery date has been set, return the date supplied
        $actualDeliveryDate_datetime =
                $this->ActualDeliveryDate($invoice, $net->delRepo);
        $cdr_id = $this->ContractDocumentReference($invoice, $net->contractRepo);
        $deliveryParty_Party =
                $this->DeliveryParty($invoice, $net->delRepo, $net->delPartyRepo);
        // if invoice/delivery periods are used retrieve from there or
        // alternatively retrieve from invoice
        $invoice_period = $this->ublInvoicePeriod($invoice, $this->s);
        $start_datetime = $invoice_period->getStartDate();
        $end_datetime = $invoice_period->getEndDate();
        $numberhelper = new NumberHelper($this->s);
        $totals_of_line_items_array =
            $numberhelper->invCalculateTotalsofItemTotals($invoice_id, $inv->iiR, $inv->iiaR);

        // The lineExtensionAmount must reconcile with the taxExclusiveAmount
        // $lineExtensionAmount = sum of all line item line extension amounts
        /**
         * @var float $totals_of_line_items_array['subtotal']
         * @var float $totals_of_line_items_array['discount']
         */
        $lineExtensionAmount = $totals_of_line_items_array['subtotal']
                                    - $totals_of_line_items_array['discount'];
        $taxExclusiveAmount = $this->inv_amount->getItemSubtotal();

        $taxInclusiveAmount =
                $taxExclusiveAmount + $this->inv_amount->getItemTaxTotal();

        // Early settlement discount is an allowance
        $allowanceTotalAmount = $totals_of_line_items_array['discount'];
        /** @var float $totals_of_line_items_array['total'] */
        $payableAmount = $totals_of_line_items_array['total'];

// Buyer Reference https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
        $buyerReference = $this->resolveInitialBuyerReference($invoice, $inv->soR);
        $supplierParty = $this->buildSupplierParty();
        $customerParty = $this->buildCustomerParty($invoice, $inv->paR, $inv->cpR);
        $payment_means_array = $this->buildPeppolPaymentMeansArray();
        $payeeFinancialAccount = $this->buildFinancialAccount(
                                                        $payment_means_array);
        // return the $paymentId (ie. a payment reference id)
        $paymentId =
                'peppol' . ($invoice->getNumber() ?? 'Number unavailable')
                .  new DateTime()->format(self::DATE_FORMAT_YMD);
        $payment_terms = $invoice->getTerms();
// Related logic:
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-TaxTotal/
// When the tax currency code is different and therefore provided,
// two instances of the tax total must be present,
// but only one with tax subtotal ie. the elected doc currency code's tax subtotal
        $inv_amount = $inv->iaR->repoInvquery($invoice->reqId());
        $supp_tax_cc_tax_amount = (null !== $inv_amount ?
                $inv_amount->getItemTaxTotal() : 0.00);
        $taxAmounts_item_subtotal = $this->TaxAmounts($supp_tax_cc_tax_amount);
        $taxSubtotal = $this->buildTaxSubtotalArray($invoice, $inv->iiaR, $charge->trR);
        $issueDate = DateTime::createFromImmutable(
                                            $invoice->getDateCreated());
        $taxPointDate = DateTime::createFromImmutable(
                                            $invoice->getDateTaxPoint());
        $dueDate = DateTime::createFromImmutable(
                                            $invoice->getDateDue());
        $accountingCost = $this->AccountingCost(
                                            $invoice, $inv->cpR);
        $additionalDocumentReferences =
                $this->AdditionalDocumentReference(
                                            $invoice, $net->upR);
        $allowanceCharges = $this->DocumentLevelAllowanceCharges(
                                            $invoice, $charge->aciR);
// https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
// $buyer_fallback_reference derived from ClientPeppol entity => extension table
// to Client. This is a fallback reference provided by the client on their login
// side
        $buyer_fallback_reference = $this->BuyerReference($invoice, $inv->cpR);
// if no client purchase order person is provided use the
// $buyer_fallback_reference
        $buyerReference = $buyerReference ?: $buyer_fallback_reference;
// No reference can be made therefore throw an exception
        if (empty($buyerReference)) {
            throw new BuyerRefNf();
        }
        $isCopyIndicator = true;
        $id = $invoice->reqId();
        $invoiceLines =
            $this->buildInvoiceLinesArray(
                $invoice, $invoice_period, $inv->iiaR, $inv->cpR, $charge->soiR,
                    $charge->aciiR, $net->unpR);
        $profileID = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
        $supplierAssignedAccountID = $this->SupplierAssignedAccountId(
                                                            $invoice, $inv->cpR);
        $note = $invoice->getNote() ?? '';
        if (null == $note) {
            throw new InvoiceNoteNf($this->t);
        }
        // Resolve PO number and optional SO id; throws SalesOrderNf / BuyerRefNf
        $po_data = $this->resolvePoData($invoice, $inv);
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/
// Only permit a description code if there is no tax point date ie.
//                           DateTimeImmutable->format('Y-m-d') === 1901/01/01
// since the tax_point_date and description code are mutually exclusive
        $description_code = $this->noTaxPointDate($invoice)
            ? $this->DescriptionCode($invoice, $net->delRepo) : '';
        $peppolHeader = new PeppolInvoiceHeader(
            $profileID,
            $id,
            new PeppolInvoiceDates(
                $issueDate,
                $dueDate,
                $taxPointDate,
                new InvoicePeriod($start_datetime, $end_datetime, $description_code),
            ),
            $note,
            $accountingCost,
            $buyerReference,
            new PeppolInvoiceReferences(
                new OrderReference(
                    $po_data['po_number'],
                    $po_data['so_id'] !== null ? (string) $po_data['so_id'] : null,
                ),
                null !== $cdr_id ? new ContractDocumentReference($cdr_id) : null,
                $isCopyIndicator,
                $supplierAssignedAccountID,
            ),
        );
        $peppolPayment = new PeppolPaymentData(
            new PaymentMeans($payeeFinancialAccount, $paymentId),
            new PaymentTerms($payment_terms),
        );
        $peppolFinancial = new PeppolFinancialData(
            $allowanceCharges,
            $taxAmounts_item_subtotal,
            $taxSubtotal,
            new LegalMonetaryTotal(
                $lineExtensionAmount,
                $taxExclusiveAmount,
                $taxInclusiveAmount,
                $allowanceTotalAmount,
                $payableAmount,
                $this->s->getSetting(self::SETTING_PEPPOL_DOCUMENT_CURRENCY),
                $this->s,
            ),
            $invoiceLines,
        );
        $xml = $peppol_ubl_xml->xml(
            $peppolHeader,
            $additionalDocumentReferences,
            $supplierParty,
            $customerParty,
            new Delivery(
                $actualDeliveryDate_datetime,
                $deliveryLocation_ID_scheme,
                $deliveryLocation_Address,
                $deliveryParty_Party,
            ),
            $peppolPayment,
            $peppolFinancial,
        );
        fwrite($f, $peppol_ubl_xml->output($xml));
        fclose($f);
        return $path;
    }

    private function buildSupplierParty(): Party
    {
        $config_company_details = $this->s->getConfigCompanyDetails();
/**
* @var string $config_company_details['name']
*/
        $supplier_name = $config_company_details['name'];
        $config_peppol = $this->s->getConfigPeppol();
/**
* @var string $config_peppol['SupplierPartyIdentificationId']
* @var string $config_peppol['SupplierPartyIdentificationSchemeId']
*/
        $supplier_partyIdentificationId =
            $config_peppol['SupplierPartyIdentificationId'];
        $supplier_partyIdentificationSchemeId =
                $config_peppol['SupplierPartyIdentificationSchemeId'];
        $supplier_postalAddress = $this->SupplierPostalAddress();
        $supplier_contact = $this->SupplierContact();
        $supplier_partyTaxScheme = $this->SupplierPartyTaxScheme();
        $supplier_partyLegalEntity = $this->SupplierPartyLegalEntity();
        $supplier_endpointID = $this->SupplierEndpointID();
        $supplier_endpointID_schemeID = $this->SupplierEndpointIDSchemeID();
        return new Party(
            $this->t,
            $supplier_name,
            $supplier_partyIdentificationId,
            $supplier_partyIdentificationSchemeId,
            $supplier_postalAddress,
            null,
            $supplier_contact,
            $supplier_partyTaxScheme,
            $supplier_partyLegalEntity,
            $supplier_endpointID,
            $supplier_endpointID_schemeID,
        );
    }

    private function buildCustomerParty(Inv $invoice, paR $paR, cpR $cpR): Party
    {
        $customer_name = $invoice->getClient()?->getClientFullName();
        $party =
    $this->buildPeppolAccountingCustomerPartyArray($invoice, $paR, $cpR);
        /**
         * @var array $party['Party']
         * @var array $party['Party']['PartyIdentification']
         * @var array $party['Party']['PartyIdentification']['ID']
         * @var string $party['Party']['PartyIdentification']['ID']['value']
         */
        $customer_partyIdentificationId =
            $party['Party']['PartyIdentification']['ID']['value'] ?? null;
        /**
         * @var string $party['Party']['PartyIdentification']['ID']['schemeID']
         */
        $customer_partyIdentificationSchemeId =
            $party['Party']['PartyIdentification']['ID']['schemeID'] ?? null;
        $customer_postalAddress =
                                $this->buildCustomerPostalAddress($party);
        $customer_contact =
                                        $this->buildCustomerContact($party);
        $customer_partyTaxScheme =
                            $this->buildCustomerPartyTaxScheme($party);
        $customer_partyLegalEntity =
                                $this->buildCustomerLegalEntity($party);
        /**
         * @var array $party['Party]
         * @var array $party['Party']['EndPointID']
         * @var string $party['Party']['EndPointID']['value']
         */
        $customer_endpointID = $party['Party']['EndPointID']['value'] ?? '';
        /**
         * @var string $party['Party']['EndPointID']['schemeID']
         */
        $customer_endpointID_schemeID = $party
                                ['Party']['EndPointID']['schemeID'] ?? '';
        return new Party(
            $this->t,
            $customer_name,
            $customer_partyIdentificationId,
            $customer_partyIdentificationSchemeId,
            $customer_postalAddress,
            null,
            $customer_contact,
            $customer_partyTaxScheme,
            $customer_partyLegalEntity,
            $customer_endpointID,
            $customer_endpointID_schemeID,
        );
    }

    /**
     * @throws SalesOrderNf
     * @throws BuyerRefNf
     * @return array{po_number: string, so_id: int|null}
     */
    private function resolvePoData(Inv $invoice, PeppolHelperInvDeps $inv): array
    {
        if ($invoice->getSoId() > 0) {
            $so = $inv->soR->repoSalesOrderUnLoadedquery((int) $invoice->getSoId());
            if (null === $so) {
                throw new SalesOrderNf($this->t);
            }
            $po = $so->getClientPoNumber();
            if ($po === null || $po === '') {
                throw new SalesOrderNf($this->t);
            }
            return ['po_number' => $po, 'so_id' => $invoice->getSoId()];
        }
        $po = $invoice->getClientPoNumber();
        if ($po === null || $po === '') {
            throw new BuyerRefNf();
        }
        return ['po_number' => $po, 'so_id' => null];
    }

    private function resolveInitialBuyerReference(Inv $invoice, SOR $soR): string
    {
        $so = $soR->repoSalesOrderUnLoadedquery((int) $invoice->getSoId());
        if ($so !== null) {
            $person = $so->getClientPoPerson();
            if ($person !== null) {
                return $person;
            }
        }
        return $invoice->getClientPoPerson() ?? '';
    }

    /**
     * Related logic:
     * https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
     *                                          cac-AdditionalDocumentReference/
     * @param Inv $invoice
     * @param UPR $upR
     * @return AdditionalDocumentReference
     */
    private function additionalDocumentReference(Inv $invoice, upR $upR):
                                                    AdditionalDocumentReference
    {
        $url_key = $invoice->getUrlKey();
        $invoice_number = $this->t->translate('peppol.document.reference.null')
            . ($invoice->reqId() ?: 'Not Found');
        if (null !== $invoice->getNumber()) {
            $invoice_number = $invoice->getNumber();
        }
        $inv_attachments = $upR->repoUploadUrlClientquery(
                                        $url_key, $invoice->reqClientId());
        $aliases = $this->s->getCustomerFilesFolderAliases();
        $targetPath = $aliases->get('@customer_files');
        $attachments = [];
        /**
         * @var Upload $inv_attachment
         */
        foreach ($inv_attachments as $inv_attachment) {
            $original_file_name = $inv_attachment->getFileNameOriginal();
            $url_key = $inv_attachment->getUrlKey();
            $target_path_with_filename = $targetPath . '/' . $url_key . '_'
                                                        . $original_file_name;
            $path_info = pathinfo($target_path_with_filename);
            /**
             * @var string $path_info['extension']
             */
            $path_info_extension = $path_info['extension'];
            if ($path_info_extension === 'pdf') {
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
//                                              cac-AdditionalDocumentReference/
// $inv_attachment->reqId() => upload repository id
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
//      cac-AdditionalDocumentReference/cac-Attachment/cac-ExternalReference/
                $attachments[$inv_attachment->reqId()]
                  = new Attachment(
                      // 'filePath' used to generate file_contents
                      $target_path_with_filename,
                      // see Invoice/Ubl/Attachment
                      'invoice/download_file/' . $inv_attachment->reqId(),
                  );
            } else {
                throw new TTSNPdfFile($this->t);
            }
        }
        $invoice_id = $invoice->reqId();
        return new additionalDocumentReference(
            $this->t,
            $invoice_number ?? $this->t->translate(
                'peppol.document.reference.null') . ($invoice_id ?: 'Not Found'),
            '130',
            $invoice->getDocumentDescription(),
            $attachments,

            /*
             * DocumentType not to be included => set to true
             * Warning
             * Location: invoice___yItaG5INV107_peppol
             * Element/context: /:Invoice[1]
             * XPath test: not(cac:AdditionalDocumentReference/cbc:DocumentType)
             * Error message: [UBL-CR-114]-A UBL invoice should not include the
             *  AdditionalDocumentReference DocumentType
             */
            true,
        );
    }

    /**
     * @param array $party
     * @return Contact
     */
    public function buildCustomerContact(array $party): Contact
    {
        /**
         * @var array $party['Party']
         * @var array $party['Party']['Contact']
         */
        $contact = $party['Party']['Contact'];

        /**
         * @var string $contact['Name']
         */
        $name = $contact['Name'] ?? '';
        /**
         * @var string $contact['FirstName']
         */
        $firstName = $contact['FirstName'] ?? '';
        /**
         * @var string $contact['LastName']
         */
        $lastName = $contact['LastName'] ?? '';
        /**
         * @var string $contact['Telephone']
         */
        $telephone = $contact['Telephone'] ?? '';
        /**
         * @var string $contact['ElectronicMail']
         */
        $electronicMail = $contact['ElectronicMail'] ?? '';
        return new Contact(
            $name,
            $firstName,
            $lastName,
            $telephone,
            /**
             * Customer's telefax must not be included => null
             * Warning
             * Location: invoice_sqKOvgahINV107_peppol
             * Element/context: /:Invoice[1]
             * XPath test:
             * not(cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Telefax)
             * Error message: [UBL-CR-254]-A UBL invoice should not include the
             *  AccountingCustomerParty Party Contact Telefax
             */
            null,
            $electronicMail,
        );
    }

    /**
     * @param array $party
     * @return PartyLegalEntity
     */
    public function buildCustomerLegalEntity(array $party): PartyLegalEntity
    {
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
        return new PartyLegalEntity(
            $registration_name,
            $company_id,
            $attributes,
            $company_legal_form,
        );
    }

    /**
     * @param array $party
     * @return PartyTaxScheme
     */
    public function buildCustomerPartyTaxScheme(array $party): PartyTaxScheme
    {
//https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
//      cac-AccountingCustomerParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/

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

        return new PartyTaxScheme(
            $party_tax_scheme_companyID,
            new TaxScheme($party_tax_scheme_ID),
        );
    }

    /**
     * @param array $party
     * @return Address
     */
    public function buildCustomerPostalAddress(array $party): Address
    {
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
        return new Address(
            $street_name,
            $additional_street_name,
            $line,
            $city_name,
            $postal_zone,
            $country_sub_entity,
            new Country(
                $identification_code,
                $listId,
            ),
// a customer related address therefore exclude building number UBL_CR_218
            false,
            true,
            false,
        );
    }

    public function buildDeliveryLocationIDScheme(): array
    {
        $id = $this->delivery_location->getGlobalLocationNumber();
        if (null == $id) {
            throw new DelLocIdNf($this->t);
        }
        return [
            'ID' => $id,
            'attributes' => [
                'schemeID' =>
                    $this->delivery_location->getElectronicAddressScheme(),
            ],
        ];
    }

    /**
     * @return Address
     */
    public function buildDeliveryLocationAddress(): Address
    {
// The customer/client must choose their delivery location from their dashboard
// Alternatively the administrator can edit the invoice under view...options.
// Peppol 3.0: Building number can be included in address_1
        $street_name = $this->delivery_location->getAddress1();
        $additional_street_name = $this->delivery_location->getAddress2();
        $building_number = $this->delivery_location->getBuildingNumber();
        $cityName = $this->delivery_location->getCity();
        $postalZone = $this->delivery_location->getZip();
        $countrySubEntity = $this->delivery_location->getState();
        $country_name = $this->delivery_location->getCountry();
        /**
         * Related logic: see App\Invoice\Entity\DeliveryLocation
         */
        if (null !== $country_name) {
            return $this->ublDeliveryLocation(
                $street_name,
                $additional_street_name,
                $building_number,
                $cityName,
                $postalZone,
                $countrySubEntity,
                // Use the country_name to build Invoice\Ubl\Country
                $country_name,
            );
        }
        throw new DelLocCounNameNf($this->t);
    }

    /**
     * @param array $payment_means_array
     * @return PayeeFinancialAccount
     */
    public function buildFinancialAccount(array $payment_means_array):
        PayeeFinancialAccount
    {
        /**
         * @var array $payment_means_array['PayeeFinancialAccount']
         */
        $payee_financial_account_array =
                                $payment_means_array['PayeeFinancialAccount'];
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
        $financial_institution_branch =
                    $payee_financial_account_array['FinancialInstitutionBranch'];
        /**
         * @var string $financial_institution_branch['ID']
         */
        $branch_ID = $financial_institution_branch['ID'];
        return new PayeeFinancialAccount(
            new FinancialInstitutionBranch($branch_ID),
            // $id eg. IBAN123456789
            $payee_ID,
            $payee_name,
        );
    }

    /**
     * Related logic:
     * https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
     *                                  cac-InvoicePeriod/cbc-DescriptionCode/
     * Related logic:
     *  \resources\views\invoice\setting\views\partial_settings_peppol
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @throws InvPeriodDetIncompleteNf
     * @throws DelLocIdNf
     * @return string
     */
    public function descriptionCode(Inv $invoice, DelRepo $delRepo): string
    {
        if ($this->s->getSetting('include_delivery_period') == '1'
                            && !empty($this->s->getSetting('stand_in_code'))) {
            if ((int) $invoice->getDeliveryLocationId() > 0) {
                $delivery = $delRepo->repoInvoicequery($invoice->reqId());
                if ((null !== $delivery)
                        && (!empty($invoice->getStandInCode()))) {
                    $description_code = $invoice->getStandInCode();
                } else {
                    throw new InvPeriodDetIncompleteNf();
                }
            } else {
                throw new DelLocIdNf($this->t);
            }
        } else {
            $description_code = '';
        }
        return $description_code;
    }

    /**
     * @param Inv $invoice
     * @param ACIR $aciR
     * @return array
     */
    public function documentLevelAllowanceCharges(Inv $invoice, ACIR $aciR): array
    {
        $invoice_id = $invoice->reqId();
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
                    'chargeIndicator' =>
                                $ac->getAllowanceCharge()?->getIdentifier(),
                    'allowanceChargeReasonCode' =>
                                $ac->getAllowanceCharge()?->getReasonCode(),
                    'allowanceChargeReason' =>
                                    $ac->getAllowanceCharge()?->getReason(),
                    'multiplierFactorNumeric' =>
                    $ac->getAllowanceCharge()?->getMultiplierFactorNumeric(),
                    'baseAmount' =>
                                $ac->getAllowanceCharge()?->getBaseAmount(),
                    'amount' => $ac->getAmount(),
  // if chosen document currency (settings...view...peppol electronic invoicing...)
  // different to local supplier's currency, invoice must still have local supplier
  // currency equivalent displayed
                    'taxTotal' => [
                        // document level currency code tax amount
                        'doc_cc_tax_amount' => $ac->getVatOrTax(),
                        // document currency code
                        // views/invoice/setting/views/partial_settings_peppol
                        'doc_cc' => $this->documentCurrency,
                        // supplier tax currency code tax amount
                        'supp_tax_cc_tax_amount' =>
                            $this->s->currencyConverter($ac->getVatOrTax() ?? 0.00),
                        // supplier currency code
                        // views/invoice/setting/views/partial_settings_peppol
                        'supp_cc' => $this->s->getSetting('currency_code_from'),
                    ],
                    'taxCategory' => [
                        'taxScheme' => [
                            // Mandatory default 'VAT'
                            'value' => self::TAX_CATEGORY_VAT,
                        ],
                    ],
                ];
            }
        }
        return $array;
    }

    /**
     * @param Inv $invoice
     * @param paR $paR
     * @param cpR $cpR
     * @throws BuyerPostAddNf
     * @throws ClientNf
     * @return array
     */
    private function buildPeppolAccountingCustomerPartyArray(Inv $invoice,
        paR $paR, cpR $cpR): array
    {
        $client = $invoice->getClient();
        if ($client) {
            $postaladdress_id = $client->getPostaladdressId();
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if (null == $postaladdress_id) {
                throw new BuyerPostAddNf();
            }
            if ($postaladdress_id) {
                $postaladdress = $paR->repoClient($postaladdress_id);
                $accounting_customer_party = [];
                $country_helper = new CountryHelper();
                if ($postaladdress && $client_peppol) {
                    $accounting_customer_party = [
                        'Party' => [
                            'EndPointID' => [
                                'value' => $client_peppol->getEndpointid(),
                                'schemeID' =>
                                        $client_peppol->getEndpointidSchemeid(),
                            ],
//https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
//              cac-AccountingSupplierParty/cac-Party/cac-PartyIdentification/
                            'PartyIdentification' => [
                                'ID' => [
                                    'value' =>
                                            $client_peppol->getIdentificationid(),
                                    // optional
                                    'schemeID' =>
                                    $client_peppol->getIdentificationidSchemeid(),
                                ],
                            ],
                            'PostalAddress' => [
                                'StreetName' => $postaladdress->getStreetName(),
                                'AdditionalStreetName' =>
                                    $postaladdress->getAdditionalStreetName(),
                                'AddressLine' => [
                                    'Line' => $postaladdress->getBuildingNumber(),
                                ],
                                'CityName' => $postaladdress->getCityName(),
                                'PostalZone' => $postaladdress->getPostalZone(),
                                'CountrySubentity' =>
                                            $postaladdress->getCountrysubentity(),
                                'Country' => [
                                    'IdentificationCode' =>
$country_helper->getCountryIdentificationCodeWithLeague(
                                                    $postaladdress->getCountry()),
                    //https://docs.peppol.eu/poacc/billing/3.0/codelist/ISO3166/
                                    'ListId' => 'ISO3166-1:Alpha2',
                                ],
                            ],
                            'PhysicalLocation' => [
                                'StreetName' =>
                                        (string) $client->getClientAddress1(),
                                'AdditionalStreetName' =>
                                        (string) $client->getClientAddress2(),
                                'AddressLine' => [
                                    'Line' =>
                                    (string) $client->getClientBuildingNumber(),
                                ],
                                'CityName' => (string) $client->getClientCity(),
                                'PostalZone' => (string) $client->getClientZip(),
                                'CountrySubentity' =>
                                            (string) $client->getClientState(),
                                'Country' => [
                                    'IdentificationCode' =>
$country_helper->getCountryIdentificationCodeWithLeague(
                                        (string) $client->getClientCountry()),
                   //https://docs.peppol.eu/poacc/billing/3.0/codelist/ISO3166/
                                    'ListId' => 'ISO3166-1:Alpha2',
                                ],
                            ],
                            'Contact' => [
                                'Name' => $client->getClientName(),
                                'Telephone' =>
                                            (string) $client->getClientPhone(),
                                'ElectronicMail' => $client->getClientEmail(),
                            ],
                            'PartyTaxScheme' => [
                                'CompanyID' =>
                                        $client_peppol->getTaxschemecompanyid(),
                                'CompanyID_attributes' => [
                                    'schemeID' => '',
                                    'schemeAgencyID' => '',
                                ],
                                'TaxScheme' => [
//https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
//cac-AccountingSupplierParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/cbc-ID/
//VAT / !VAT
                                    'ID' => $client_peppol->getTaxSchemeid(),
                                    'Attributes' => [
                                        'schemeID' => '',
                                        'schemeAgencyID' => '',
                                    ],
                                ],
                            ],
                            'PartyLegalEntity' => [
                                'RegistrationName' =>
                            $client_peppol->getLegalEntityRegistrationName(),
                                'CompanyID' =>
                                    $client_peppol->getLegalEntityCompanyid(),
                                'Attributes' => [
                                    'schemeID' =>
                           $client_peppol->getLegalEntityCompanyidSchemeid(),
                                ],
                                'CompanyLegalform' =>
                           $client_peppol->getLegalEntityCompanyLegalForm(),
                            ],
                        ],
                    ];
                }
                return $accounting_customer_party;
            }
            return [];
        }
        throw new ClientNf($this->t);
    }

    /**
     * @param Inv $invoice
     * @param cpR $cpR
     * @throws ClientNf
     * @throws ClientsAccCostNf
     * @return string
     */
    private function accountingCost(Inv $invoice, cpR $cpR): string
    {
        $client = $invoice->getClient();
        if (null !== $client) {
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if (null === $client_peppol) {
                throw new ClientNf($this->t);
            }
            if ($client_peppol->getAccountingCost()) {
                return $client_peppol->getAccountingCost();
            }
            if (empty($client_peppol->getAccountingCost())) {
                throw new ClientsAccCostNf($this->t);
            }
            return '';
        }
        throw new ClientNf($this->t);
    }

    /**
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @return DateTime|null
     */
    public function actualDeliveryDate(Inv $invoice, DelRepo $delRepo): ?DateTime
    {
        $dateSupplied = DateTime::createFromImmutable($invoice->getDateSupplied());
        $invoice_id = $invoice->reqId();
        $delivery = $delRepo->repoInvoicequery($invoice_id);
        if (null !== $delivery) {
            $actual_delivery_date = $delivery->getActualDeliveryDate();
            if (null !== $actual_delivery_date) {
                return DateTime::createFromImmutable($actual_delivery_date);
            }
            return $dateSupplied;
        }
        return $dateSupplied;
    }

    /**
     * @param Inv $invoice
     * @param InvoicePeriod $invoice_period
     * @param iiaR $iiaR
     * @param cpR $cpR
     * @param SOIR $soiR
     * @param ACIIR $aciiR
     * @param unpR $unpR
     * @throws ProductUnitCodeNf
     * @throws SalesOrderPONumNe
     * @throws SOIPOINNe
     * @throws ClientNf
     * @return array
     */
    private function buildInvoiceLinesArray(Inv $invoice,
        InvoicePeriod $invoice_period, IIAR $iiaR, cpR $cpR, SOIR $soiR,
                                                ACIIR $aciiR, unpR $unpR): array
    {
        /**
         * Note: To compare amounts that have been converted via the
         * currency_converter, set the following boolean to true,
         * and a comparison string instead of the actual amount will be
         * displayed.
         */
        $client = $invoice->getClient();
        if ($client) {
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if ($client_peppol) {
                $invoiceLines = [];
                $b = Schema::CBC;
                $a = Schema::CAC;
                /**
                 * @var InvItem $item
                 */
                foreach ($invoice->getItems() as $item) {
                    $product = $item->getProduct();
                    if (null !== $product && $product->getUnitPeppolId() <= 0) {
                        throw new ProductUnitCodeNf($this->t, $product);
                    }
                    // Item Identification number eg. TRQWERQERQ9879
                    $peppol_po_itemid = $this->PeppolPoItemid($item, $soiR);
                    if (null == $peppol_po_itemid && $item->getSoItemId() > 0) {
                        throw new SOIPOINNe($this->t);
                    }

                    // Item Line Number eg. Line 1 of 4
                    $peppol_po_lineid = $this->PeppolPoLineid($item, $soiR);
                    if (null == $peppol_po_lineid && $item->getSoItemId() > 0) {
                        throw new SOIPOLNNe($this->t);
                    }
/**
 * Error
 * Location: invoice_6p-24oxnINV115_peppol
 * Element/context: /:Invoice[1]/cac:InvoiceLine[1]/cac:Item[1]/
 *                  cac:CommodityClassification[1]/cbc:ItemClassificationCode[1]
 * XPath test: ((not(contains(normalize-space(@listID), ' ')) and
 *  contains(' AA AB AC AD AE AF AG AH AI AJ AK AL AM AN AO AP .. ZZZ ',
 *   concat(' ', normalize-space(@listID), ' '))))
 * Error message: [BR-CL-13]-Item classification identifier identification
 *           scheme identifier MUST be coded using one of the UNTDID 7143 list.
 */
                    $listid = $product?->getProductIccListid();
                    if (null == $listid && null !== $product) {
                        throw new PICCSINf($this->t, $product);
                    }
                    $price = ($item->getPrice() ?? 0.00);
                    $discount = ($item->getDiscountAmount() ?? 0.00);

                    $item_id = $item->reqId();
                    $inv_item_amount = $this->getInvItemAmount($item_id, $iiaR);
                    if (isset($inv_item_amount)) {
                        $sub_total = $inv_item_amount->getSubtotal() ?? 0;
                        $convert_sub_total =
                            $this->s->currencyConverter($sub_total);
                        $unit_peppol_id = $item->getProduct()?->getUnitPeppolId();
                        if (null !== $unit_peppol_id) {
                            $unit_peppol = $unpR->repoUnitPeppolLoadedquery(
                                                                $unit_peppol_id);
                            if (null !== $unit_peppol) {
// using Array Format 2
// ..\vendor\sabre\xml\lib\Writer.php
// https://kinsta.com/blog/php-8-2/#deprecate--string-interpolation
// Note: The following string interpolation,
// ie. curly brackets within double quotes, conforms with php 8.2 requirements

                        $optionals = $this->buildOptionalInvoiceLineElements(
                            $item, $peppol_po_lineid, $peppol_po_itemid
                        );
                        $lineNote = $optionals['lineNote'];
                        $itemDesc = $optionals['itemDesc'];
                        $originCountry = $optionals['originCountry'];
                        $orderLineRef = $optionals['orderLineRef'];
                        $buyersItemId = $optionals['buyersItemId'];

            $invoiceLines[$item_id] =
                [
                    'name' => "{$a}InvoiceLine",
                    'value' => [
                        [
                            'name' => "{$b}ID",
                            'value' => (string) $item_id
                        ],
                        ...$lineNote,
                        [
                            'name' => "{$b}InvoicedQuantity",
                            'value' => (string) $item->getQuantity(),
                            'attributes' => [
                                'unitCode' => $unit_peppol->getCode()
                            ]
                        ],
                        [
                            'name' => "{$b}LineExtensionAmount",
                            'value' => $convert_sub_total,
                            'attributes' => [
                                'currencyID' =>
                                        $this->documentCurrency
                            ]
                        ],
                        [
                            'name' => "{$b}AccountingCost",
                            'value' => $client_peppol->getAccountingCost()
                        ],
                        [
                            'name' => "{$a}InvoicePeriod",
                            'value' => [
                                [
                                    'name' => "{$b}StartDate",
                                    'value' => $invoice_period->getStartDate()
                                ],
                                [
                                    'name' => "{$b}EndDate",
                                    'value' => $invoice_period->getEndDate()
                                ],
                            ]
                        ],
                        ...$orderLineRef,
                        [
                            'name' => "{$a}DocumentReference",
                            'value' => [
                                [
                                    'name' => "{$b}ID",
                                    'value' => $invoice->getNumber()
                                ],
                                [
                                    'name' => "{$b}DocumentTypeCode",
                                    'value' => '130'
                                ],
                            ],
                        ],
// Inv Item Allowance Charges: Implemented 01/2026
                        $this->itemLineACs($aciiR, $item_id),
                        $this->buildInvoiceLineItemElement($item, $itemDesc, $buyersItemId, $originCountry),
                        $this->buildInvoiceLinePriceElement($item, $unit_peppol, $price, $discount),
                    ],
                ];
                            } // null!== $unit_peppol
                        } // null!== $unit_peppol_id
                    } // isset $inv_item_amount
                } // foreach foreach ($invoice->getItems() as $item) {
                return $invoiceLines;
            }
            throw new ClientNf($this->t);
        } else {
            throw new ClientNf($this->t);
        }
    }

    /**
     * @psalm-return array{lineNote: array, itemDesc: array, originCountry: array, orderLineRef: array, buyersItemId: array}
     */
    private function buildOptionalInvoiceLineElements(
        InvItem $item,
        ?string $peppol_po_lineid,
        ?string $peppol_po_itemid,
    ): array {
        $a = Schema::CAC;
        $b = Schema::CBC;
                        // Optional elements — only emit when the source value is non-empty
                        // to avoid R008 "Document MUST not contain empty elements."
                        $lineDesc = $item->getDescription() ?? '';
                        $lineNote = $lineDesc !== ''
                            ? [['name' => "{$b}Note", 'value' => $lineDesc]]
                            : [];
                        $itemDesc = $lineDesc !== ''
                            ? [['name' => "{$b}Description", 'value' => $lineDesc]]
                            : [];
                        $originCode = $item->getProduct()?->getProductCountryOfOriginCode() ?? '';
                        $originCountry = $originCode !== ''
                            ? [['name' => "{$a}OriginCountry", 'value' => [['name' => "{$b}IdentificationCode", 'value' => $originCode]]]]
                            : [];
                        $orderLineRef = ($peppol_po_lineid !== null && $peppol_po_lineid !== '')
                            ? [['name' => "{$a}OrderLineReference", 'value' => [['name' => "{$b}LineID", 'value' => $peppol_po_lineid]]]]
                            : [];
                        $buyersItemId = ($peppol_po_itemid !== null && $peppol_po_itemid !== '')
                            ? [['name' => "{$a}BuyersItemIdentification", 'value' => [['name' => "{$b}ID", 'value' => $peppol_po_itemid]]]]
                            : [];
        return [
            'lineNote' => $lineNote,
            'itemDesc' => $itemDesc,
            'originCountry' => $originCountry,
            'orderLineRef' => $orderLineRef,
            'buyersItemId' => $buyersItemId,
        ];
    }

    private function buildInvoiceLineItemElement(
        InvItem $item,
        array $itemDesc,
        array $buyersItemId,
        array $originCountry,
    ): array {
        $a = Schema::CAC;
        $b = Schema::CBC;
        return [
                            'name' => "{$a}Item",
                            'value' => [
                                ...$itemDesc,
                                [
                                    'name' => "{$b}Name",
                                    'value' => $item->getName()
                                ],
                                ...$buyersItemId,
                                [
                                    'name' => "{$a}SellersItemIdentification",
                                    'value' => [
                                        [
                                            'name' => "{$b}ID",
                                            'value' =>
                                        $item->getProduct()?->getProductSku()
                                        ],
                                    ],
                                ],
                                [
                                    'name' => "{$a}StandardItemIdentification",
                                    'value' => [
                                        [
                                            'name' => "{$b}ID",
                                            'value' =>
                                       $item->getProduct()?->getProductSiiId(),
                                            'attributes' => [
                                                'schemeID' =>
                                $item->getProduct()?->getProductSiiSchemeid(),
                                            ],
                                        ],
                                    ],
                                ],
                                ...$originCountry,
                                [
                                    'name' => "{$a}CommodityClassification",
                                    'value' => [
                                        [
                                            'name' => "{$b}ItemClassificationCode",
                                            'value' =>
                                    $item->getProduct()?->getProductIccId(),
                                            'attributes' => [
                                                'listID' =>
                                    $item->getProduct()?->getProductIccListid(),
                                                'listVersionID' =>
                            $item->getProduct()?->getProductIccListversionid(),
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'name' => "{$a}ClassifiedTaxCategory",
                                    'value' => [
                                        [
                                            'name' => "{$b}ID",
                                            'value' =>
            $item->getTaxRate()?->getPeppolTaxRateCode()
                                        ],
                                        [
                                            'name' => "{$b}Percent",
                                            'value' =>
            $item->getTaxRate()?->getTaxRatePercent()
                                        ],
                                        [
                                            'name' => "{$a}TaxScheme",
                                            'value' => [
                                                [
                                                    'name' => "{$b}ID",
                                                    'value' => self::TAX_CATEGORY_VAT
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'name' => "{$a}AdditionalItemProperty",
                                'value' => [
                                    [
                                        'name' => "{$b}Name",
                                        'value' =>
            $item->getProduct()?->getProductAdditionalItemPropertyName()
                                    ],
                                    [
                                        'name' => "{$b}Value",
                                        'value' =>
            $item->getProduct()?->getProductAdditionalItemPropertyValue()
                                    ],
                                ],
                            ],
        ];
    }

    private function buildInvoiceLinePriceElement(
        InvItem $item,
        UnitPeppol $unit_peppol,
        float $price,
        float $discount,
    ): array {
        $a = Schema::CAC;
        $b = Schema::CBC;
        return [
                            'name' => "{$a}Price",
                            'value' => [
                                [
                                    'name' => "{$b}PriceAmount",
                                    'value' =>
                                            $this->s->currencyConverter($price),
                                    'attributes' => [
                                        'currencyID' =>
                                        $this->documentCurrency
                                    ]
                                ],
                                [
                                    'name' => "{$b}BaseQuantity",
                                    'value' => $item->getQuantity(),
                                    'attributes' => [
                                        'unitCode' => $unit_peppol->getCode()
                                    ]
                                ],
                    // This is an allowance/discount that is specific to price
                                [
                                    'name' => "{$a}AllowanceCharge",
                                    'value' => [
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/
//                            cac-Price/cac-AllowanceCharge/cbc-ChargeIndicator/
// Mandatory false:  discount on the price => An allowance or discount =>
//                                                       ChargeIndicator = false
// If there is a reduction of the price, the discount must be shown here
                                        [
                                            'name' => "{$b}ChargeIndicator",
                                            'value' => 'false'
                                        ],
                                        [
                                            'name' => "{$b}Amount",
                                            'value' =>
                                        $this->s->currencyConverter($discount),
                                            'attributes' => [
                                                'currencyID' =>
            $this->documentCurrency
                                            ]
                                        ],
// Item gross price
// Base Amount: The unit price, exclusive of VAT, before subtracting
// Item price discount, can not be negative
                                        [
                                            'name' => "{$b}BaseAmount",
                                            'value' =>
            $this->s->currencyConverter($price), 'attributes' => [
                                            'currencyID' =>
            $this->documentCurrency]
                                        ],
                                    ],
                                ],
                            ],
        ];
    }
    
    private function itemLineACs(ACIIR $aciiR, int $itemId): array {
        $aciis = $aciiR->repoInvItemquery($itemId);
        $a = Schema::CAC;
        $b = Schema::CBC;
        $itemLine = [];
        /**
         * @var InvItemAllowanceCharge $acii
         */
        foreach ($aciis as $acii) {
            $itemLine[] =
            [
                'name' =>
                "{$a}AllowanceCharge",
                'value' => [
                    ['name' =>
                        "{$b}ChargeIndicator",
                        'value' => $acii->getAllowanceCharge()?->getIdentifier()
                                                        == 0 ? 'false' : 'true'
                    ],
                    ['name' =>
                        "{$b}AllowanceChargeReasonCode",
                        'value' => $acii->getAllowanceCharge()?->getReasonCode()],
                    ['name' =>
                        "{$b}AllowanceChargeReason",
                        'value' => $acii->getAllowanceCharge()?->getReason()],
                    ['name' =>
                        "{$b}MultiplierFactorNumeric",
                        'value' =>
                     $acii->getAllowanceCharge()?->getMultiplierFactorNumeric()],
                    [
                        'name' => "{$b}Amount",
                        'value' => $this->s->currencyConverter($acii->getAmount()),
                        'attributes' => [
                            'currencyID' =>
                                $this->documentCurrency
                        ]
                    ],
                    [
                        'name' => "{$b}BaseAmount",
                        'value' =>
    $this->s->currencyConverter($acii->getAllowanceCharge()?->getBaseAmount()
            ?? 0.00),
                        'attributes' => [
                            'currencyID' =>
                                $this->documentCurrency
                        ]
                    ],
                ],
            ];
        }
        return $itemLine;
    }

    /**
     * Build a payment means array from the config/common/params file
     * @return array
     */
    private function buildPeppolPaymentMeansArray(): array
    {
        $config_peppol = $this->s->getConfigPeppol();
        /**
         * @var array $config_peppol['PaymentMeans']
         */
        $config = $config_peppol['PaymentMeans'] ?? [];
    /**
     * @var array $config['PayeeFinancialAccount']
     * @var array $config['PayeeFinancialAccount']['FinancialInstitutionBranch']
     * @var string $config['PayeeFinancialAccount']['ID']
     * @var string $config['PayeeFinancialAccount']['Name']
     */
        return [
            'PayeeFinancialAccount' => [
                // eg. IBAN number
                'ID' => $config['PayeeFinancialAccount']['ID'] ?? '',
                'Name' => $config['PayeeFinancialAccount']['Name'] ?? '',
                'FinancialInstitutionBranch' => [
                    'ID' => $config
                                ['PayeeFinancialAccount']
                                ['FinancialInstitutionBranch']
                                ['ID'] ?? '',
                ],
            ],
        ];
    }

    /**
     * Related logic:
     * https://docs.peppol.eu/poacc/billing/3.0/syntax/
                                               ubl-invoice/cbc-BuyerReference/
     * Related logic:
     * https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
     * @param Inv $invoice
     * @param cpR $cpR
     * @return string
     */
    private function buyerReference(Inv $invoice, cpR $cpR): string
    {
        $client = $invoice->getClient();
        if (null !== $client) {
            $client_id = $client->reqId();
            $client_peppol = $cpR->repoClientPeppolLoadedquery(
                                                        $client_id);
            if (null !== $client_peppol) {
                return $client_peppol->getBuyerReference();
            }
        }
        throw new ClientNf($this->t);
    }

    /**
     * @param Inv $invoice
     * @param ContractRepo $contractRepo
     * @return string|null
     */
    public function contractDocumentReference(Inv $invoice,
                                            ContractRepo $contractRepo): ?string
    {
        $contract_id = $invoice->getContractId();
        if (null!==$contract_id) {
            $contract = $contractRepo->repoContractquery($contract_id);
            if ($contract) {
                return $contract->getReference();
            }
        }
        return null;
    }

    /**
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @return Party|null
     */
    public function deliveryParty(Inv $invoice, DelRepo $delRepo,
                                             DelPartyRepo $delpartyRepo): ?Party
    {
        $invoice_id = $invoice->reqId();
        $inv = $delRepo->repoPartyquery($invoice_id);
        if ($inv) {
            $delivery_party_id = $inv->hasDeliveryPartyId() ? $inv->reqDeliveryPartyId() : null;
            $delparty = $delpartyRepo->repoDeliveryPartyquery((int) $delivery_party_id);
            $partyName = (null !== $delparty ? $delparty->getPartyName()
                                                                    : null);
            return null !== $partyName ? new Party($this->t, $partyName,
               null, null, null, null, null, null, null, null, null) : null;
        }
        return null;
    }

    /**
     * Default config document currency code
     * Subjective to $s->getSetting('peppol_document_currency')
     * @return string
     */
    public function documentCurrencyCode(): string
    {
        $config = $this->s->getConfigPeppol();
        /** @var string $config['DocumentCurrencyCode'] */
        return $config['DocumentCurrencyCode'] ?? '';
    }

    /**
     * @param int $item_id
     * @param IIAR $iiaR
     * @return InvItemAmount|null
     */
    public function getInvItemAmount(int $item_id, IIAR $iiaR): ?InvItemAmount
    {
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
     * @throws SOIPOINNe
     * @throws SOINe
     * @return string|null
     */
    private function peppolPoItemid(InvItem $item, SOIR $soiR): ?string
    {
        $sales_order_item_id = $item->getSoItemId();
        if ($sales_order_item_id > 0) {
            $sales_order_item = $soiR->repoSalesOrderItemquery($sales_order_item_id);
            if (null !== $sales_order_item) {
                $peppol_po_itemid = $sales_order_item->getPeppolPoItemid();
                if (null !== $peppol_po_itemid) {
                    return $peppol_po_itemid;
                }
                throw new SOIPOINNe($this->t);
            } else {
                throw new SOINe($this->t);
            }
        }
        // Standalone invoice: use InvItem's own buyer item identifier (may be empty)
        $itemid = $item->getPeppolPoItemid();
        return ($itemid !== '' && $itemid !== null) ? $itemid : null;
    }

    /**
     * Retrieve the Client/Customer's purchase order line id
     * @param InvItem $item
     * @param SOIR $soiR
     * @throws SOIPOLNNe
     * @throws SOINe
     * @return string|null
     */
    private function peppolPoLineid(InvItem $item, SOIR $soiR): ?string
    {
        $sales_order_item_id = $item->getSoItemId();
        if ($sales_order_item_id > 0) {
            $sales_order_item = $soiR->repoSalesOrderItemquery($sales_order_item_id);
            if (null !== $sales_order_item) {
                $peppol_po_lineid = $sales_order_item->getPeppolPoLineid();
                if (null !== $peppol_po_lineid) {
                    return $peppol_po_lineid;
                }
                throw new SOIPOLNNe($this->t);
            } else {
                throw new SOINe($this->t);
            }
        }
        // Standalone invoice: use InvItem's own PO line identifier (may be empty)
        $lineid = $item->getPeppolPoLineid();
        return ($lineid !== '' && $lineid !== null) ? $lineid : null;
    }

    /**
     * Retrieve Client's Account Id given by Supplier
     * @param Inv $invoice
     * @param cpR $cpR
     * @return string
     */
    private function supplierAssignedAccountId(Inv $invoice, cpR $cpR): string
    {
        $client = $invoice->getClient();
        if (null !== $client) {
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            $supplier_assigned_account_id = null !== $client_peppol ?
                    $client_peppol->getSupplierAssignedAccountId()
              : throw new ClientIdNf($this->t);
        } else {
            throw new ClientNf($this->t);
        }
        if (empty($supplier_assigned_account_id)) {
            throw new SAAINf($this->t);
        }
        return $supplier_assigned_account_id;
    }

    /**
     * @return Contact
     */
    public function supplierContact(): Contact
    {
        $config = $this->s->getConfigPeppol();
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
             * XPath test: not(cac:AccountingSupplierParty/cac:Party/
                                                      cac:Contact/cbc:Telefax)
             * Error message: [UBL-CR-190]-A UBL invoice should not include the
                                  AccountingSupplierParty Party Contact Telefax
             */
            null,
            (string) $config['Contact']['ElectronicMail'],
        );
    }

    /**
     * @return string
     */
    public function supplierEndpointID(): string
    {
        $config = $this->s->getConfigPeppol();
        /**
         * @var array $config
         * @var array $config['EndPointID']
         */
        return (string) $config['EndPointID']['value'];
    }

    /**
     * @return string
     */
    public function supplierEndPointIDSchemeID(): string
    {
        $config = $this->s->getConfigPeppol();
        /**
         * @var array $config
         * @var array $config['EndPointID']
         */
        return (string) $config['EndPointID']['schemeID'];
    }

    /**
     * @return PartyLegalEntity
     */
    public function supplierPartyLegalEntity(): PartyLegalEntity
    {
        $config = $this->s->getConfigPeppol();
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
     * If the DateTimeImmutable formatted tax point is 1901/01/01,
     *  it is NOT a tax point
     * @param Inv $invoice
     * @return bool
     */
    private function noTaxPointDate(Inv $invoice): bool
    {
        $date = $invoice->getDateTaxPoint()->format(self::DATE_FORMAT_YMD);
        return $date === '1901/01/01';
    }

    /**
     * @return PartyTaxScheme
     */
    public function supplierPartyTaxScheme(): PartyTaxScheme
    {
        $config = $this->s->getConfigPeppol();
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
            $taxScheme,
        );
    }

    /**
     * @return Address
     */
    public function supplierPostalAddress(): Address
    {
        $config = $this->s->getConfigPeppol();
        $address = 'SupplierPartyIdentificationPostalAddress';
        $configAddress = (array) $config[$address];
        $configAddressCountry = (array) $configAddress['Country'];
        $configAddressLine = (array) $configAddress['AddressLine'];
        return new Address(
            (string) $configAddress['StreetName'],
            (string) $configAddress['AdditionalStreetName'],
            (string) $configAddressLine['Line'],
            (string) $configAddress['CityName'],
            (string) $configAddress['PostalZone'],
            (string) $configAddress['CountrySubentity'],
            new Country(
                (string) $configAddressCountry['IdentificationCode'],
                (string) $configAddressCountry['ListId'],
            ),
/**
 * Warning
 * Location: invoice_IP4PC20OINV107_peppol
 * Element/context: /:Invoice[1]
 * XPath test: not(cac:AccountingSupplierParty/cac:Party/
                                            cac:PostalAddress/cbc:BuildingNumber)
 * Error message: [UBL-CR-155]-A UBL invoice should not include the
 *  AccountingSupplierParty Party PostalAddress BuildingNumber
 */
            true,
            false,
            false,
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
    private function taxAmounts(float $supp_tax_cc_tax_amount): array
    {
        // doc_cc_tax_amount will be compared with supp_tax_cc_amount
        // so make sure same type ie. float
        // currency_converter outputs a string
        $doc_cc_tax_amount =
                    (float) $this->s->currencyConverter($supp_tax_cc_tax_amount);
        return [
            // first tax total
            'supp_tax_cc_tax_amount' => $supp_tax_cc_tax_amount,
            'supp_tax_cc' => $this->from_currency,
            // second tax total
            'doc_cc_tax_amount' => $doc_cc_tax_amount,
            'doc_cc' => $this->to_currency,
        ];
    }

    /**
     * @param Inv $invoice
     * @param iiaR $iiaR
     * @param TRR $trR
     * @throws TCCNf
     * @throws TCPNf
     * @return array
     */
    private function buildTaxSubtotalArray(
                                     Inv $invoice, IIAR $iiaR, TRR $trR): array
    {
        $array = [];
        $item_tax_rates = [];
        $taxable_amount_total = 0;
        $tax_amount_total = 0;
        /**
         * What tax types do the items use? Build a list of tax type ids
         * @var InvItem $item
         */
        foreach ($invoice->getItems() as $item) {
            if (!in_array($item->reqTaxRateId(), $item_tax_rates)) {
                $item_tax_rates[] = $item->reqTaxRateId();
            }
        }
        foreach ($item_tax_rates as $id) {
            $taxRate = $trR->repoTaxRatequery($id);
            if (null !== $taxRate) {
                $tax_category = $taxRate->getPeppolTaxRateCode();
                $tax_percent = $taxRate->getTaxRatePercent();
                // Throw an exception if any Tax Category does not have a code
                if (null === $tax_category) {
                    throw new TCCNf($this->t);
                }
                if (null === $tax_percent) {
                    throw new TCPNf($this->t);
                }
                if (!empty($id)) {
                    $taxable_amount_total = 0.00;
                    $tax_amount_total = 0.00;
                    $items = $invoice->getItems();
                    /**
                     * @var InvItem $item
                     */
                    foreach ($items as $item) {
                        $item_id = $item->reqId();
                        if ($id == $item->getTaxRate()?->reqId()) {
                            $item_amount = $iiaR->repoInvItemAmountquery($item_id);
                            if (null !== $item_amount) {
                                $item_sub_total = $item_amount->getSubtotal();
                                if (null !== $item_sub_total) {
                                    $taxable_amount_total += $item_sub_total;
                                }
                                $item_tax_total = $item_amount->getTaxTotal();
                                if (null !== $item_tax_total) {
                                    $tax_amount_total += $item_tax_total;
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
                $sub_array['TaxableAmounts'] =$taxable_amount_total;
                /**
                 *  @var float $sub_array['TaxAmount']
                 */
                $sub_array['TaxAmount'] = $tax_amount_total;
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
                $sub_array['DocumentCurrency'] =
                            $this->documentCurrency;
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
    public function ublDeliveryLocation(?string $streetName,
            ?string $additionalStreetName, ?string $buildingNumber,
            ?string $cityName, ?string $postalZone, ?string $countrySubEntity,
            string $country_name): Address
    {
        //https://docs.peppol.eu/poacc/billing/3.0/rules/ubl-tc434/
        $country_helper = new CountryHelper();
        $cic = $country_helper->getCountryIdentificationCodeWithLeague(
                $country_name);
        $country = new Country($cic, 'ISO3166-1:Alpha2');
        return new Address(
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
             * XPath test: not(cac:Delivery/cac:DeliveryLocation/
             * cac:Address/cbc:BuildingNumber)
             * Error message: [UBL-CR-367]-A UBL invoice should not include the
             *  Delivery DeliveryLocation Address BuildingNumber
             */
            true,
        );
    }

    /**
     * This function creates the Invoice/Delivery period by outputting
     * the month's start and end date based on either the tax point
     * or the date_created (=> a.k.a date issued). If no tax point date has been
     * calculated due to goods not delivered yet, there will be no need for a
     * description code in the Invoice/Delivery Period
     *
     * The description code indicates what the tax point date calculation will be
     * based on in the future when the goods are delivered or paid.
     *
     * A tax point is only valid if different to the date_created a.k.a date
     * issued
     *
     * If a Peppol Invoice has a visible and calculated tax point it will not
     * need a description code in the Invoice Period since they are mutually
     * exclusive, as explained above.
     *
     * Delivered/paid already => tax/point can be calculated => no need for a
     * description code => 'Invoice Period'
     * Not delivered/paid yet => tax point cannot be calculated yet => need a
     * description code => 'Delivery Period'
     *
     * Related logic:
     *  https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
     *                                                          cbc-TaxPointDate/
     * Related logic:
     *  https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
     * @param Inv $invoice
     * @param SRepo $s
     * @return InvoicePeriod
     */
    public function ublInvoicePeriod(Inv $invoice, SRepo $s): InvoicePeriod
    {
        // Related logic: see InvService set_tax_point

        $datehelper = new DateHelper($s);
        $date_tax_point = $invoice->getDateTaxPoint();
        $date_created_or_issued = $invoice->getDateCreated();
        $date_supplied = $invoice->getDateSupplied();
        if ($date_tax_point === $date_created_or_issued) {
            // => there is NO need for a visible peppol tax point
            // therefore base the invoice period on the date_created
            // and include the description code Business Rule (BT-8)
            // Note: The description code describes what date the future
            // tax point will be based on ie. date supplied/delivery date
            // or date created or payment date
            $input_date = DateTime::createFromImmutable($date_created_or_issued);
            $description_code = $this->getDescriptionCodeForTaxPoint(
                            $invoice, $date_supplied, $date_created_or_issued);
        } else {
            // => there IS a need for a visible peppol tax point
            // therefore base the invoice period on the tax point
            // but exclude the description code Business Rule (BT-8)
            $input_date = DateTime::createFromImmutable($date_tax_point);
            $description_code = '';
        }
        // if the invoice has a delivery period use the delivery period's begin
        // and end date
        $start_end_array = $datehelper->invoicePeriodStartEnd(
                                        $invoice, $input_date, $this->delRepo);
        $startDate = (string) $start_end_array['StartDate'];
        $endDate = (string) $start_end_array['EndDate'];
        return new InvoicePeriod($startDate, $endDate, $description_code);
    }

    /**
     * @param Inv $invoice
     * @return string
     */
    public function uploadsTempPeppolXmlFileNamePathWithExt(Inv $invoice): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Uploads'
          . DIRECTORY_SEPARATOR . 'Temp'
          . DIRECTORY_SEPARATOR . 'Peppol'
          . DIRECTORY_SEPARATOR . 'invoice_' . Random::string(8)
          . ($invoice->getNumber() ?? '_search_null_invoice_id_') . '_peppol.xml';
    }

    /**
     * Return a number represented as a string indicating how the tax point was
     *  determined: according to date supplied or date created/issued
     * Related logic: see src\Invoice\Inv\InvService set_tax_point function
     * @param Inv $inv
     * @param DateTimeImmutable $date_supplied
     * @param DateTimeImmutable $date_created
     * @return string
     */
    public function getDescriptionCodeForTaxPoint(Inv $inv,
    DateTimeImmutable $date_supplied, DateTimeImmutable $date_created): string
    {
// For yii3-i,'Date created' is used interchangeably with 'Date issued'
// https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
// Subset from resources/peppol/uncl2005.php
        $uncl2005_subset_array = [
            self::ICD_ISSUE_INVOICE_DATE => '3',
            'Actual Delivery Date/Time ie. Date Supplied' => '35',
            'Paid to Date' => '432',
        ];
        if (null !== $inv->getClient()?->getClientVatId()) {
            if ($date_created > $date_supplied) {
                $diff = $date_supplied->diff($date_created)->format('%R%a');
                if ((int) $diff > 14) {
// date supplied more than 14 days before invoice date => use date supplied
                    return $uncl2005_subset_array[
                                'Actual Delivery Date/Time ie. Date Supplied'];
                }
// if the issue date (created) is within 14 days after the supply (basic) date
// then use the issue/created date.
                return $uncl2005_subset_array[
                    self::ICD_ISSUE_INVOICE_DATE];
            }
            if ($date_created < $date_supplied) {
                // normally set the tax point to the date_created
                return $uncl2005_subset_array[
                    self::ICD_ISSUE_INVOICE_DATE];
            }
            if ($date_created === $date_supplied) {
                // normally set the tax point to the date_created
                return $uncl2005_subset_array[
                    self::ICD_ISSUE_INVOICE_DATE];
            }
        }
        // If the client is not VAT registered, the tax point is the date
        //  supplied
        if (null == $inv->getClient()?->getClientVatId()) {
            return $uncl2005_subset_array[
                'Actual Delivery Date/Time ie. Date Supplied'];
        }
        // Default to date created
        return $uncl2005_subset_array[
            self::ICD_ISSUE_INVOICE_DATE];
    }

    /**
     * Related logic: https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5189/
     * @return array
     */
    public function getPeppolChargesSubsetArray(): array
    {
        return [
            '41' => 'Bonus for works ahead of schedule',
            '42' => 'Other Bonus',
            '60' => 'Manufacturerâ€™s consumer discount',
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
            '105' => 'Yearly turnover',
        ];
    }

    /**
     * Related logic:
     * https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/
      structure/codelist/UNCL7143.xml
     * @return array
     */
    public function getUnc7143(): array
    {
        return array_merge(
            $this->unc7143Chunk0(),
            $this->unc7143Chunk1(),
            $this->unc7143Chunk2(),
            $this->unc7143Chunk3(),
            $this->unc7143Chunk4a(),
            $this->unc7143Chunk4b(),
            $this->unc7143Chunk5(),
            $this->unc7143Chunk6(),
            $this->unc7143Chunk7(),
            $this->unc7143Chunk8(),
        );
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk0(): array
    {
        return [
            0 => [
                'Id' => 'AA',
                'Name' => 'Product version number',
                'Description' => 'Number assigned by manufacturer or seller '
                . 'to identify the release of a product.',
            ],
            1 => [
                'Id' => 'AB',
                'Name' => 'Assembly',
                'Description' => 'The item number is that of an assembly.',
            ],
            2 => [
                'Id' => 'AC',
                'Name' => 'HIBC (Health Industry Bar Code)',
                'Description' => 'Article identifier used within health sector '
                . 'to indicate data used conforms to HIBC.',
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
                'Description' => 'Number assigned to a slab, which is '
                . 'produced in a particular production step.',
            ],
            6 => [
                'Id' => 'AG',
                'Name' => 'Software revision number',
                'Description' => 'A number assigned to indicate'
                . ' a revision of software.',
            ],
            7 => [
                'Id' => 'AH',
                'Name' => 'UPC (Universal Product Code) Consumer package '
                . 'code (1-5-5)',
                'Description' => 'An 11-digit code that uniquely identifies '
                . 'consumer does not have a check digit.',
            ],
            8 => [
                'Id' => 'AI',
                'Name' => 'UPC (Universal Product Code) '
                . 'Consumer package code (1-5-5-1)',
                'Description' => 'A 12-digit code that uniquely identifies '
                . 'the consumer packaging of a product, including a check digit.',
            ],
            9 => [
                'Id' => 'AJ',
                'Name' => 'Sample number',
                'Description' => 'Number assigned to a sample.',
            ],
            10 => [
                'Id' => 'AK',
                'Name' => 'Pack number',
                'Description' => 'Number assigned to a pack containing '
                . 'a stack of items put together (e.g. cold roll sheets '
                . '(steel product)).',
            ],
            11 => [
                'Id' => 'AL',
                'Name' => 'UPC (Universal Product Code) Shipping container code '
                . '(1-2-5-5)',
                'Description' => 'A 13-digit code that uniquely identifies '
                . 'the manufacturer\'s shipping unit, including the '
                . 'packaging indicator.',
            ],
            12 => [
                'Id' => 'AM',
                'Name' => 'UPC (Universal Product Code)/EAN '
                . '(European article number) Shipping container code (1-2-5-5-1)',
                'Description' => 'Shipping container code '
                . '(1-2-5-5-1)manufacturer\'s shipping unit, including the
                  packagingindicator and the check digit.',
            ],
            13 => [
                'Id' => 'AN',
                'Name' => 'UPC (Universal Product Code) suffix',
                'Description' => 'A suffix used in conjunction with a '
                . 'higher level UPC (Universal product code) to '
                . 'define packing variations for a product.',
            ],
            14 => [
                'Id' => 'AO',
                'Name' => 'State label code',
                'Description' => 'A code which specifies the '
                . 'codification of the state\'s labelling requirements.',
            ],
            15 => [
                'Id' => 'AP',
                'Name' => 'Heat number',
                'Description' => 'Number assigned to the heat '
                . '(also known as the iron charge) for the '
                . 'production of steel products.',
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
                'Description' => 'Identification number on a product allowing '
                . 'a quick electronic retrieval of price information '
                . 'for that product.',
            ],
            20 => [
                'Id' => 'AU',
                'Name' => 'NSN (North Atlantic Treaty Organization Stock Number)',
                'Description' => 'Number assigned under the NATO '
                . '(North Atlantic Treaty Organization) codification system to '
                . 'provide the identification of an approved item of supply.',
            ],
            21 => [
                'Id' => 'AV',
                'Name' => 'Refined product code',
                'Description' => 'A code specifying the product refinement '
                . 'designation.',
            ],
            22 => [
                'Id' => 'AW',
                'Name' => 'Exhibit',
                'Description' => 'A code indicating that the product is '
                . 'identified by an',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk1(): array
    {
        return [
            0 => [
                'Id' => 'AX',
                'Name' => 'End item',
                'Description' => 'A number specifying an end item.',
            ],
            1 => [
                'Id' => 'AY',
                'Name' => 'Federal supply classification',
                'Description' => 'A code to specify a product\'s Federal '
                . 'supply classification.',
            ],
            2 => [
                'Id' => 'AZ',
                'Name' => 'Engineering data list',
                'Description' => 'A code specifying the product\'s engineering '
                . 'data list.',
            ],
            3 => [
                'Id' => 'BA',
                'Name' => 'Milestone event number',
                'Description' => 'A number to identify a milestone event.',
            ],
            4 => [
                'Id' => 'BB',
                'Name' => 'Lot number',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'product.',
            ],
            5 => [
                'Id' => 'BC',
                'Name' => 'National drug code 4-4-2 format',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'drug format 4-4-2.',
            ],
            6 => [
                'Id' => 'BD',
                'Name' => 'National drug code 5-3-2 format',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'drug format 5-3-2.',
            ],
            7 => [
                'Id' => 'BE',
                'Name' => 'National drug code 5-4-1 format',
                'Description' => self::ICD_A_CODE_IDENTIFYING_THE_PRODUCT_IN_NATIONAL
                . 'drug format 5-4-1.',
            ],
            8 => [
                'Id' => 'BF',
                'Name' => 'National drug code 5-4-2 format',
                'Description' => 'A code identifying the product in national '
                . 'drug format 5-4-2.',
            ],
            9 => [
                'Id' => 'BG',
                'Name' => 'National drug code',
                'Description' => 'A code specifying the national drug '
                . 'classification.',
            ],
            10 => [
                'Id' => 'BH',
                'Name' => 'Part number',
                'Description' => 'A number indicating the part.',
            ],
            11 => [
                'Id' => 'BI',
                'Name' => 'Local Stock Number (LSN)',
                'Description' => 'A local number assigned to an item of stock.',
            ],
            12 => [
                'Id' => 'BJ',
                'Name' => 'Next higher assembly number',
                'Description' => 'A number specifying the next higher '
                . 'assembly or component into which the product is being '
                . 'incorporated.',
            ],
            13 => [
                'Id' => 'BK',
                'Name' => 'Data category',
                'Description' => 'A code specifying a category of data.',
            ],
            14 => [
                'Id' => 'BL',
                'Name' => 'Control number',
                'Description' => 'To specify the control number.',
            ],
            15 => [
                'Id' => 'BM',
                'Name' => 'Special material identification code',
                'Description' => 'A number to identify the special material code.',
            ],
            16 => [
                'Id' => 'BN',
                'Name' => 'Locally assigned control number',
                'Description' => 'A number assigned locally for control purposes.',
            ],
            17 => [
                'Id' => 'BO',
                'Name' => 'Buyer\'s colour',
                'Description' => 'Colour assigned by buyer.',
            ],
            18 => [
                'Id' => 'BP',
                'Name' => 'Buyer\'s part number',
                'Description' => 'Reference number assigned by the buyer to '
                . 'identify an article.',
            ],
            19 => [
                'Id' => 'BQ',
                'Name' => 'Variable measure product code',
                'Description' => 'A code assigned to identify a variable '
                . 'measure item.',
            ],
            20 => [
                'Id' => 'BR',
                'Name' => 'Financial phase',
                'Description' => 'To specify as an item, the financial phase.',
            ],
            21 => [
                'Id' => 'BS',
                'Name' => 'Contract breakdown',
                'Description' => 'To specify as an item, the contract breakdown.',
            ],
            22 => [
                'Id' => 'BT',
                'Name' => 'Technical phase',
                'Description' => 'To specify as an item, the technical phase.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk2(): array
    {
        return [
            0 => [
                'Id' => 'BU',
                'Name' => 'Dye lot number',
                'Description' => 'Number identifying a dye lot.',
            ],
            1 => [
                'Id' => 'BV',
                'Name' => 'Daily statement of activities',
                'Description' => 'A statement listing activities of one day.',
            ],
            2 => [
                'Id' => 'BW',
                'Name' => 'Periodical statement of activities within a '
                . 'bilaterally agreed time period',
                'Description' => 'Periodical statement listing activities '
                . 'within a bilaterally agreed time period.',
            ],
            3 => [
                'Id' => 'BX',
                'Name' => 'Calendar week statement of activities',
                'Description' => 'A statement listing activities of a '
                . 'calendar week.',
            ],
            4 => [
                'Id' => 'BY',
                'Name' => 'Calendar month statement of activities',
                'Description' => 'A statement listing activities of a '
                . 'calendar month.',
            ],
            5 => [
                'Id' => 'BZ',
                'Name' => 'Original equipment number',
                'Description' => 'Original equipment number allocated to '
                . 'spare parts by the manufacturer.',
            ],
            6 => [
                'Id' => 'CC',
                'Name' => 'Industry commodity code',
                'Description' => 'The codes given to certain commodities '
                . 'by an industry.',
            ],
            7 => [
                'Id' => 'CG',
                'Name' => 'Commodity grouping',
                'Description' => 'Code for a group of articles with common '
                . 'characteristics (e.g. used for statistical purposes).',
            ],
            8 => [
                'Id' => 'CL',
                'Name' => 'Colour number',
                'Description' => 'Code for the colour of an article.',
            ],
            9 => [
                'Id' => 'CR',
                'Name' => 'Contract number',
                'Description' => 'Reference number identifying a contract.',
            ],
            10 => [
                'Id' => 'CV',
                'Name' => 'Customs article number',
                'Description' => 'Code defined by Customs authorities to an '
                . 'article or a group of articles for Customs purposes.',
            ],
            11 => [
                'Id' => 'DR',
                'Name' => 'Drawing revision number',
                'Description' => 'Reference number indicating that a change '
                . 'or revision has been applied to a drawing.',
            ],
            12 => [
                'Id' => 'DW',
                'Name' => 'Drawing',
                'Description' => 'Reference number identifying a drawing '
                . 'of an article.',
            ],
            13 => [
                'Id' => 'EC',
                'Name' => 'Engineering change level',
                'Description' => 'Reference number indicating that a change '
                . 'or revision has been applied to an article\'s specification.',
            ],
            14 => [
                'Id' => 'EF',
                'Name' => 'Material code',
                'Description' => 'Code defining the material\'s type, surface, '
                . 'geometric form plus various classifying characteristics.',
            ],
            15 => [
                'Id' => 'EMD',
                'Name' => 'EMDN (European Medical Device Nomenclature)',
                'Description' => 'Nomenclature system for identification of '
                . 'medical devices based on European Medical Device '
                . 'Nomenclature classification system.',
            ],
            16 => [
                'Id' => 'EN',
                'Name' => 'International Article Numbering Association (EAN)',
                'Description' => 'Number assigned to a manufacturer\'s product '
                . 'according to the International Article Numbering Association.',
            ],
            17 => [
                'Id' => 'FS',
                'Name' => 'Fish species',
                'Description' => 'Identification of fish species.',
            ],
            18 => [
                'Id' => 'GB',
                'Name' => 'Buyer\'s internal product group code',
                'Description' => 'Product group code used within a buyer\'s '
                . 'internal systems.',
            ],
            19 => [
                'Id' => 'GN',
                'Name' => 'National product group code',
                'Description' => 'National product group code. Administered by '
                . 'a national agency.',
            ],
            20 => [
                'Id' => 'GS',
                'Name' => 'General specification number',
                'Description' => 'The item number is a general specification '
                . 'number.',
            ],
            21 => [
                'Id' => 'HS',
                'Name' => 'Harmonised system',
                'Description' => 'The item number is part of, or is generated '
                . 'in the context of the Harmonised Commodity Description and '
                . 'Coding System (Harmonised System), as developed and '
                . 'maintained by the World Customs Organization (WCO).',
            ],
            22 => [
                'Id' => 'IB',
                'Name' => 'ISBN (International Standard Book Number)',
                'Description' => 'A unique number identifying a book.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk3(): array
    {
        return [
            0 => [
                'Id' => 'IN',
                'Name' => 'Buyer\'s item number',
                'Description' => 'The item number has been allocated '
                . 'by the buyer.',
            ],
            1 => [
                'Id' => 'IS',
                'Name' => 'ISSN (International Standard Serial Number)',
                'Description' => 'A unique number identifying a serial '
                . 'publication.',
            ],
            2 => [
                'Id' => 'IT',
                'Name' => 'Buyer\'s style number',
                'Description' => 'Number given by the buyer to a specific '
                . 'style or form of an article, especially used for garments.',
            ],
            3 => [
                'Id' => 'IZ',
                'Name' => 'Buyer\'s size code',
                'Description' => 'Code given by the buyer to designate the '
                . 'size of an article in textile and shoe industry.',
            ],
            4 => [
                'Id' => 'MA',
                'Name' => 'Machine number',
                'Description' => 'The item number is a machine number.',
            ],
            5 => [
                'Id' => 'MF',
                'Name' => 'Manufacturer\'s (producer\'s) article number',
                'Description' => 'The number given to an article by its '
                . 'manufacturer.',
            ],
            6 => [
                'Id' => 'MN',
                'Name' => 'Model number',
                'Description' => 'Reference number assigned by the '
                . 'manufacturer to differentiate variations in similar '
                . 'products in a class or group.',
            ],
            7 => [
                'Id' => 'MP',
                'Name' => 'Product/service identification number',
                'Description' => 'Reference number identifying a product '
                . 'or service.',
            ],
            8 => [
                'Id' => 'NB',
                'Name' => 'Batch number',
                'Description' => 'The item number is a batch number',
            ],
            9 => [
                'Id' => 'ON',
                'Name' => 'Customer order number',
                'Description' => 'Reference number of a customer\'s order.',
            ],
            10 => [
                'Id' => 'PD',
                'Name' => 'Part number description',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'description associated with a number ultimately used to '
                . 'identify an article.',
            ],
            11 => [
                'Id' => 'PL',
                'Name' => 'Purchaser\'s order line number',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'line entry '
                . 'in a customer\'s order for goods or services.',
            ],
            12 => [
                'Id' => 'PO',
                'Name' => 'Purchase order number',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'customer\'s order.',
            ],
            13 => [
                'Id' => 'PV',
                'Name' => 'Promotional variant number',
                'Description' => 'The item number is a promotional '
                . 'variant number.',
            ],
            14 => [
                'Id' => 'QS',
                'Name' => 'Buyer\'s qualifier for size',
                'Description' => 'The item number qualifies the size of '
                . 'the buyer.',
            ],
            15 => [
                'Id' => 'RC',
                'Name' => 'Returnable container number',
                'Description' => self::ICD_REFERENCE_NUMBER_IDENTIFYING_A
                . 'returnable container.',
            ],
            16 => [
                'Id' => 'RN',
                'Name' => 'Release number',
                'Description' => 'Reference number identifying a release '
                . 'from a buyer\'s purchase order.',
            ],
            17 => [
                'Id' => 'RU',
                'Name' => 'Run number',
                'Description' => 'The item number identifies the '
                . 'production or manufacturing run or sequence in which the '
                . 'item was manufactured, processed or assembled.',
            ],
            18 => [
                'Id' => 'RY',
                'Name' => 'Record keeping of model year',
                'Description' => 'The item number relates to the year in '
                . 'which the particular model was kept.',
            ],
            19 => [
                'Id' => 'SA',
                'Name' => 'Supplier\'s article number',
                'Description' => 'Number assigned to an article by the '
                . 'supplier of that article.',
            ],
            20 => [
                'Id' => 'SG',
                'Name' => 'Standard group of products (mixed assortment)',
                'Description' => 'The item number relates to a standard '
                . 'group of other items (mixed) which are grouped together '
                . 'as a single item for identification purposes.',
            ],
            21 => [
                'Id' => 'SK',
                'Name' => 'SKU (Stock keeping unit)',
                'Description' => 'Reference number of a stock keeping unit.',
            ],
            22 => [
                'Id' => 'SN',
                'Name' => 'Serial number',
                'Description' => 'Identification number of an item which '
                . 'distinguishes this specific item out of a number '
                . 'of identical items.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk4a(): array
    {
        return [
            0 => [
                'Id' => 'SRS',
                'Name' => 'RSK number',
                'Description' => 'Plumbing and heating.',
            ],
            1 => [
                'Id' => 'SRT',
                'Name' => 'IFLS (Institut Francais du Libre Service) '
                . '5 digit product classification code',
                'Description' => '5 digit code for product classification '
                . 'managed by the Institut Francais du Libre Service.',
            ],
            2 => [
                'Id' => 'SRU',
                'Name' => 'IFLS (Institut Francais du Libre Service) '
                . '9 digit product classification code',
                'Description' => '9 digit code for product classification '
                . 'managed by the Institut Francais du Libre Service.',
            ],
            3 => [
                'Id' => 'SRV',
                'Name' => 'GS1 Global Trade Item Number',
                'Description' => 'A unique number, up to 14-digits, '
                . 'assigned according to the numbering structure '
                . 'of the GS1 system.',
            ],
            4 => [
                'Id' => 'SRW',
                'Name' => 'EDIS (Energy Data Identification System)',
                'Description' => 'European system for identification '
                . 'of meter data.',
            ],
            5 => [
                'Id' => 'SRX',
                'Name' => 'Slaughter number',
                'Description' => 'Unique number given by a slaughterhouse '
                . 'to an animal or a group of animals of the same breed.',
            ],
            6 => [
                'Id' => 'SRY',
                'Name' => 'Official animal number',
                'Description' => 'Unique number given by a national authority '
                . 'to identify an animal individually.',
            ],
            7 => [
                'Id' => 'SRZ',
                'Name' => 'Harmonized tariff schedule',
                'Description' => 'The international Harmonized Tariff Schedule '
                . '(HTS) to classify the article for customs, statistical '
                . 'and other purposes.',
            ],
            8 => [
                'Id' => 'SS',
                'Name' => 'Supplier\'s supplier article number',
                'Description' => 'Article number referring to a sales '
                . 'catalogue of supplier\'s supplier.',
            ],
            9 => [
                'Id' => 'SSA',
                'Name' => '46 Level DOT Code',
                'Description' => 'A US Department of Transportation (DOT) '
                . 'code to identify hazardous (dangerous) goods, managed '
                . 'by the Customs and Border Protection (CBP) agency.',
            ],
            10 => [
                'Id' => 'SSB',
                'Name' => 'Airline Tariff 6D',
                'Description' => 'A US code agreed to by the airline '
                . 'industry to identify hazardous (dangerous) goods, '
                . 'managed by the Customs and Border Protection (CBP) agency.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk4b(): array
    {
        return [
            0 => [
                'Id' => 'SSC',
                'Name' => 'Title 49 Code of Federal Regulations',
                'Description' => 'A US Customs and Border Protection '
                . '(CBP) code used to identify hazardous (dangerous) goods.',
            ],
            1 => [
                'Id' => 'SSD',
                'Name' => 'International Civil Aviation Administration code',
                'Description' => 'A US Department of '
                . 'Transportation/Federal Aviation Administration code '
                . 'used to identify hazardous (dangerous) goods, '
                . 'managed by the Customs and Border Protection (CBP) agency.',
            ],
            2 => [
                'Id' => 'SSE',
                'Name' => 'Hazardous Materials ID DOT',
                'Description' => 'A US Department of Transportation (DOT)
                    code used toCustoms and Border
                  Protection (CBP) agency.',
            ],
            3 => [
                'Id' => 'SSF',
                'Name' => 'Endorsement',
                'Description' => 'A US Customs and Border Protection (CBP) '
                . 'code used to identify hazardous (dangerous) goods.',
            ],
            4 => [
                'Id' => 'SSG',
                'Name' => 'Air Force Regulation 71-4',
                'Description' => 'A department of Defense/Air Force code used to'
                 . 'identifyBorder Protection (CBP) agency.',
            ],
            5 => [
                'Id' => 'SSH',
                'Name' => 'Breed',
                'Description' => 'The breed of the item (e.g. plant or animal).',
            ],
            6 => [
                'Id' => 'SSI',
                'Name' => 'Chemical Abstract Service (CAS) registry number',
                'Description' => 'A unique numerical identifier for chemical '
                . 'compounds, polymers, biological sequences, '
                . 'mixtures and alloys.',
            ],
            7 => [
                'Id' => 'SSJ',
                'Name' => 'Engine model designation',
                'Description' => 'A name or designation to identify '
                . 'an engine model.',
            ],
            8 => [
                'Id' => 'SSK',
                'Name' => 'Institutional Meat Purchase '
                . 'Specifications (IMPS) Number',
                'Description' => 'A number assigned by agricultural '
                . 'authorities to identify and track meat and meat products.',
            ],
            9 => [
                'Id' => 'SSL',
                'Name' => 'Price Look-Up code (PLU)',
                'Description' => 'A number assigned by agricultural '
                . 'authorities to identify and track meat and meat products.',
            ],
            10 => [
                'Id' => 'SSM',
                'Name' => 'International Maritime Organization (IMO) Code',
                'Description' => 'An International Maritime Organization (IMO) '
                . 'code used to identify hazardous (dangerous) goods.',
            ],
            11 => [
                'Id' => 'SSN',
                'Name' => 'Bureau of Explosives 600-A (rail)',
                'Description' => 'A Department of Transportation/Federal '
                . 'Railroad Administration code used to '
                . 'identify hazardous (dangerous) goods.',
            ],
        ];
    }

    private function unc7143Chunk5(): array
    {
        return [
            0 => [
                'Id' => 'SSO',
                'Name' => 'United Nations Dangerous Goods List',
                'Description' => 'A UN code used to classify and '
                . 'identify dangerous goods.',
            ],
            1 => [
                'Id' => 'SSP',
                'Name' => 'International Code of Botanical Nomenclature (ICBN)',
                'Description' => 'A code established by the '
                . 'International Code of Botanical Nomenclature (ICBN) used '
                . 'to classify and identify botanical articles and commodities.',
            ],
            2 => [
                'Id' => 'SSQ',
                'Name' => 'International Code of Zoological Nomenclature (ICZN)',
                'Description' => 'A code established by the '
                . 'International Code of Zoological Nomenclature (ICZN) used '
                . 'to classify and identify animals.',
            ],
            3 => [
                'Id' => 'SSR',
                'Name' => 'International Code of Nomenclature '
                . 'for Cultivated Plants (ICNCP)',
                'Description' => 'A code established by the International '
                . 'Code of Nomenclature for Cultivated Plants (ICNCP) '
                . 'used to classify and identify animals.',
            ],
            4 => [
                'Id' => 'SSS',
                'Name' => 'Distributorâ€™s article identifier',
                'Description' => 'Identifier assigned to an article by the '
                . 'distributor of that article.',
            ],
            5 => [
                'Id' => 'SST',
                'Name' => 'Norwegian Classification system ENVA',
                'Description' => 'Product classification system used in the '
                . 'Norwegian market.',
            ],
            6 => [
                'Id' => 'SSU',
                'Name' => 'Supplier assigned classification',
                'Description' => 'Product classification assigned '
                . 'by the supplier.',
            ],
            7 => [
                'Id' => 'SSV',
                'Name' => 'Mexican classification system AMECE',
                'Description' => 'Product classification system used in '
                . 'the Mexican market.',
            ],
            8 => [
                'Id' => 'SSW',
                'Name' => 'German classification system CCG',
                'Description' => 'Product classification system used in '
                . 'the German market.',
            ],
            9 => [
                'Id' => 'SSX',
                'Name' => 'Finnish classification system EANFIN',
                'Description' => 'Product classification system used in '
                . 'the Finnish market.',
            ],
            10 => [
                'Id' => 'SSY',
                'Name' => 'Canadian classification system ICC',
                'Description' => 'Product classification system used in '
                . 'the Canadian market.',
            ],
            11 => [
                'Id' => 'SSZ',
                'Name' => 'French classification system IFLS5',
                'Description' => 'Product classification system used in '
                . 'the French market.',
            ],
            12 => [
                'Id' => 'ST',
                'Name' => 'Style number',
                'Description' => 'Number given to a specific style or '
                . 'form of an article, especially used for garments.',
            ],
            13 => [
                'Id' => 'STA',
                'Name' => 'Dutch classification system CBL',
                'Description' => 'Product classification system used in '
                . 'the Dutch market.',
            ],
            14 => [
                'Id' => 'STB',
                'Name' => 'Japanese classification system JICFS',
                'Description' => 'Product classification system used in '
                . 'the Japanese market.',
            ],
            15 => [
                'Id' => 'STC',
                'Name' => 'European Union dairy subsidy '
                . 'eligibility classification',
                'Description' => 'Category of product eligible for '
                . 'EU subsidy (applies for certain dairy products with '
                . 'specific level of fat content).',
            ],
            16 => [
                'Id' => 'STD',
                'Name' => 'GS1 Spain classification system',
                'Description' => 'Product classification system used in the '
                . 'Spanish market.',
            ],
            17 => [
                'Id' => 'STE',
                'Name' => 'GS1 Poland classification system',
                'Description' => 'Product classification system used '
                . 'in the Polish market.',
            ],
            18 => [
                'Id' => 'STF',
                'Name' => 'Federal Agency on Technical Regulating and '
                . 'Metrology of the Russian Federation',
                'Description' => 'A Russian government agency that serves '
                . 'as a national standardization body of the Russian Federation.',
            ],
            19 => [
                'Id' => 'STG',
                'Name' => 'Efficient Consumer Response (ECR) Austria '
                . 'classification system',
                'Description' => 'Product classification system used '
                . 'in the Austrian market.',
            ],
            20 => [
                'Id' => 'STH',
                'Name' => 'GS1 Italy classification system',
                'Description' => 'Product classification system used '
                . 'in the Italian market.',
            ],
            21 => [
                'Id' => 'STI',
                'Name' => 'CPV (Common Procurement Vocabulary)',
                'Description' => 'Official classification system for '
                . 'public procurement in the European Union.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk6(): array
    {
        return [
            0 => [
                'Id' => 'STJ',
                'Name' => 'IFDA (International Foodservice '
                . 'Distributors Association)',
                'Description' => 'International Foodservice '
                . 'Distributors Association (IFDA).',
            ],
            1 => [
                'Id' => 'STK',
                'Name' => 'AHFS (American Hospital Formulary Service) '
                . 'pharmacologic -therapeutic classification',
                'Description' => 'Pharmacologic -therapeutic classification '
                . 'maintained by the American Hospital Formulary Service (AHFS).',
            ],
            2 => [
                'Id' => 'STL',
                'Name' => 'ATC (Anatomical Therapeutic Chemical) '
                . 'classification system',
                'Description' => 'Anatomical Therapeutic Chemical '
                . 'classification system maintained by the '
                . 'World Health Organisation (WHO).',
            ],
            3 => [
                'Id' => 'STM',
                'Name' => 'CLADIMED (Classification des Dispositifs MÃ©dicaux)',
                'Description' => 'A five level classification '
                . 'system for medical decvices maintained by the CLADIMED '
                . 'organisation used in the French market.',
            ],
            4 => [
                'Id' => 'STN',
                'Name' => 'CMDR (Canadian Medical Device Regulations) '
                . 'classification system',
                'Description' => 'Classification system related to '
                . 'the Canadian Medical Device Regulations maintained '
                . 'by Health Canada.',
            ],
            5 => [
                'Id' => 'STO',
                'Name' => 'CNDM (Classificazione Nazionale dei Dispositivi Medici)',
                'Description' => 'A classification system for '
                . 'medical devices used in the Italian market.',
            ],
            6 => [
                'Id' => 'STP',
                'Name' => 'UK DM&D (Dictionary of Medicines & Devices) '
                . 'standard coding scheme',
                'Description' => 'A classification system '
                . 'for medicines and devices used in the UK market.',
            ],
            7 => [
                'Id' => 'STQ',
                'Name' => 'eCl@ss',
                'Description' => 'Standardized material and service '
                . 'classification and dictionary maintained by eClass e.V.',
            ],
            8 => [
                'Id' => 'STR',
                'Name' => 'EDMA (European Diagnostic Manufacturers Association) '
                . 'Product Classification',
                'Description' => 'Classification for in vitro diagnostics '
                . 'medical devices maintained by the European Diagnostic '
                . 'Manufacturers Association.',
            ],
            9 => [
                'Id' => 'STS',
                'Name' => 'EGAR (European Generic Article Register)',
                'Description' => 'A classification system for medical devices.',
            ],
            10 => [
                'Id' => 'STT',
                'Name' => 'GMDN (Global Medical Devices Nomenclature)',
                'Description' => 'Nomenclature system for identification '
                . 'of medical devices officially '
                . 'apprroved by the European Union.',
            ],
            11 => [
                'Id' => 'STU',
                'Name' => 'GPI (Generic Product Identifier)',
                'Description' => 'A drug classification system '
                . 'managed by Medi-Span.',
            ],
            12 => [
                'Id' => 'STV',
                'Name' => 'HCPCS (Healthcare Common Procedure Coding System)',
                'Description' => 'A classification system used with '
                . 'US healthcare insurance programs.',
            ],
            13 => [
                'Id' => 'STW',
                'Name' => 'ICPS (International Classification for Patient Safety)',
                'Description' => 'A patient safety taxonomy maintained '
                . 'by the World Health Organisation.',
            ],
            14 => [
                'Id' => 'STX',
                'Name' => 'MedDRA (Medical Dictionary for Regulatory Activities)',
                'Description' => 'A medical dictionary maintained '
                . 'by the International Federation of Pharmaceutical '
                . 'Manufacturers and Associations (IFPMA).',
            ],
            15 => [
                'Id' => 'STY',
                'Name' => 'Medical Columbus',
                'Description' => 'Medical product classification '
                . 'system used in the German market.',
            ],
            16 => [
                'Id' => 'STZ',
                'Name' => 'NAPCS (North American Product Classification System)',
                'Description' => 'Product classification system used '
                . 'in the North American market.',
            ],
            17 => [
                'Id' => 'SUA',
                'Name' => 'NHS (National Health Services) eClass',
                'Description' => 'Product and Service classification '
                . 'system used in United Kingdom market.',
            ],
            18 => [
                'Id' => 'SUB',
                'Name' => 'US FDA (Food and Drug Administration) Product Code '
                . 'Classification Database',
                'Description' => 'US FDA Product Code Classification '
                . 'Database contains medical device names and associated '
                . 'information developed by the Center for Devices and '
                . 'Radiological Health (CDRH).',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk7(): array
    {
        return [
            0 => [
                'Id' => 'SUC',
                'Name' => 'SNOMED CT (Systematized Nomenclature of '
                . 'Medicine-Clinical Terms)',
                'Description' => 'A medical nomenclature system developed '
                . 'between the NHS and the College of American Pathologists.',
            ],
            1 => [
                'Id' => 'SUD',
                'Name' => 'UMDNS (Universal Medical Device Nomenclature System)',
                'Description' => 'A standard international nomenclature '
                . 'and computer coding system for medical devices maintained '
                . 'by the Emergency Care Research Institute (ECRI).',
            ],
            2 => [
                'Id' => 'SUE',
                'Name' => 'GS1 Global Returnable Asset Identifier, '
                . 'non-serialised',
                'Description' => 'A unique, 13-digit number assigned '
                . 'according to the numbering structure of the GS1 system '
                . 'and used to identify a type of Reusable Transport Item (RTI).',
            ],
            3 => [
                'Id' => 'SUF',
                'Name' => 'IMEI',
                'Description' => 'The International Mobile Station '
                . 'Equipment Identity (IMEI) is a unique number to identify '
                . 'mobile phones. It includes the origin, model and serial '
                . 'number of the device. The structure is specified in '
                . '3GPP TS 23.003.',
            ],
            4 => [
                'Id' => 'SUG',
                'Name' => 'Waste Type (EMSA)',
                'Description' => 'Classification of waste as defined by '
                . 'the European Maritime Safety Agency (EMSA).',
            ],
            5 => [
                'Id' => 'SUH',
                'Name' => 'Ship\'s store classification type',
                'Description' => 'Classification of shipâ€™s stores.',
            ],
            6 => [
                'Id' => 'SUI',
                'Name' => 'Emergency fire code',
                'Description' => 'Classification for emergency response '
                . 'procedures related to fire.',
            ],
            7 => [
                'Id' => 'SUJ',
                'Name' => 'Emergency spillage code',
                'Description' => 'Classification for emergency response '
                . 'procedures related to spillage.',
            ],
            8 => [
                'Id' => 'SUK',
                'Name' => 'IMDG packing group',
                'Description' => 'Packing group as defined in the '
                . 'International Marititme Dangerous Goods (IMDG) specification.',
            ],
            9 => [
                'Id' => 'SUL',
                'Name' => 'MARPOL Code IBC',
                'Description' => 'International Bulk Chemical (IBC) '
                . 'code defined by the International Convention for the '
                . 'Prevention of Pollution from Ships (MARPOL).',
            ],
            10 => [
                'Id' => 'SUM',
                'Name' => 'IMDG subsidiary risk class',
                'Description' => 'Subsidiary risk class as defined in the '
                . 'International Maritime Dangerous Goods (IMDG) specification.',
            ],
            11 => [
                'Id' => 'TG',
                'Name' => 'Transport group number',
                'Description' => '(8012) Additional number to form article '
                . 'groups for packing and/or transportation purposes.',
            ],
            12 => [
                'Id' => 'TSN',
                'Name' => 'Taxonomic Serial Number',
                'Description' => 'A unique number assigned to a taxonomic '
                . 'entity, commonly to a species of plants or animals, '
                . 'providing information on their hierarchical classification, '
                . 'scientific name, taxonomic rank, associated synonyms and '
                . 'vernacular names where appropriate, data source information '
                . 'and data quality indicators.',
            ],
        ];
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function unc7143Chunk8(): array
    {
        return [
            0 => [
                'Id' => 'TSO',
                'Name' => 'IMDG main hazard class',
                'Description' => 'Main hazard class as defined in the '
                . 'International Maritime Dangerous Goods (IMDG) specification.',
            ],
            1 => [
                'Id' => 'TSP',
                'Name' => 'EU Combined Nomenclature',
                'Description' => 'The number is part of, or is generated '
                . 'in the context of the Combined Nomenclature classification, '
                . 'as developed and maintained by the European Union (EU).',
            ],
            2 => [
                'Id' => 'TSQ',
                'Name' => 'Therapeutic classification number',
                'Description' => 'A code to specify a product\'s therapeutic '
                . 'classification.',
            ],
            3 => [
                'Id' => 'TSR',
                'Name' => 'European Waste Catalogue',
                'Description' => 'Waste type number according to the European '
                . 'Waste Catalogue (EWC).',
            ],
            4 => [
                'Id' => 'TSS',
                'Name' => 'Price grouping code',
                'Description' => 'Number assigned to identify a grouping of '
                . 'products based on price.',
            ],
            5 => [
                'Id' => 'TST',
                'Name' => 'UNSPSC',
                'Description' => 'The UNSPSC commodity classification system.',
            ],
            6 => [
                'Id' => 'TSU',
                'Name' => 'EU RoHS Directive',
                'Description' => 'European Union Directive on the '
                . 'restriction of hazardous substances.',
            ],
            7 => [
                'Id' => 'UA',
                'Name' => 'Ultimate customer\'s article number',
                'Description' => 'Number assigned by ultimate customer to '
                . 'identify relevant article.',
            ],
            8 => [
                'Id' => 'UP',
                'Name' => 'UPC (Universal product code)',
                'Description' => 'Number assigned to a manufacturer\'s '
                . 'product by the Product Code Council.',
            ],
            9 => [
                'Id' => 'VN',
                'Name' => 'Vendor item number',
                'Description' => 'Reference number assigned by a '
                . 'vendor/seller identifying',
            ],
            10 => [
                'Id' => 'VP',
                'Name' => 'Vendor\'s (seller\'s) part number',
                'Description' => 'Reference number assigned by a '
                . 'vendor/seller identifying a product/service/article.',
            ],
            11 => [
                'Id' => 'VS',
                'Name' => 'Vendor\'s supplemental item number',
                'Description' => 'The item number is a specified by the '
                . 'vendor as a supplemental number for the vendor\'s purposes.',
            ],
            12 => [
                'Id' => 'VX',
                'Name' => 'Vendor specification number',
                'Description' => 'The item number has been allocated by the '
                . 'vendor as a specification number.',
            ],
            13 => [
                'Id' => 'ZZZ',
                'Name' => 'Mutually defined',
                'Description' => 'Item type identification mutually agreed '
                . ' between interchanging parties.',
            ],
        ];
    }
}
