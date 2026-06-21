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
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperCustomerTrait;
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperDeliveryTrait;
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperInvoiceLineTrait;
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperSupplierTrait;
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperTaxTrait;
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperUnc7143Trait;
use DateTimeImmutable;
use DateTime;

class PeppolHelper
{
    use PeppolHelperCustomerTrait;
    use PeppolHelperDeliveryTrait;
    use PeppolHelperInvoiceLineTrait;
    use PeppolHelperSupplierTrait;
    use PeppolHelperTaxTrait;
    use PeppolHelperUnc7143Trait;
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
        // For VAT-registered clients: use delivery date only when invoice date is
        // more than 14 days after supply date; otherwise use invoice/created date.
        if (null !== $inv->getClient()?->getClientVatId()
            && $date_created > $date_supplied) {
            $diff = $date_supplied->diff($date_created)->format('%R%a');
            if ((int) $diff > 14) {
                // date supplied more than 14 days before invoice date => use date supplied
                return $uncl2005_subset_array['Actual Delivery Date/Time ie. Date Supplied'];
            }
        }
        // Non-VAT-registered clients use date supplied; all other cases use date created
        $key = null === $inv->getClient()?->getClientVatId()
            ? 'Actual Delivery Date/Time ie. Date Supplied'
            : self::ICD_ISSUE_INVOICE_DATE;
        return $uncl2005_subset_array[$key];
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

}
