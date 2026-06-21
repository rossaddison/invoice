<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove;

use Yiisoft\Translator\TranslatorInterface as Translator;
// Entities
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation as DL;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\PaymentPeppol\PaymentPeppol;
use App\Infrastructure\Persistence\Upload\Upload;
// Helpers
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository as SRepo;
// Repositories
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\Contract\ContractRepository as ContractRepo;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\Upload\UploadRepository as upR;
// Ubl/
use App\Invoice\Ubl\Contact;
use App\Invoice\Ubl\InvoicePeriod;
// StoreCove sub-helpers
// Storecove/Exceptions
use App\Invoice\Helpers\StoreCove\Exceptions\{
    LegalEntityCompanyIdNotFoundException,
    TaxSchemeCompanyIdNotFoundException,
    ContactEmailNotFoundException,
    ContactNameNotFoundException,
    ContactFirstNameNotFoundException,
    ContactLastNameNotFoundException,
    ContactTelephoneNotFoundException,
};
// Peppol/Exceptions
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolBuyerReferenceNotFoundException,
    PeppolClientNotFoundException,
    PeppolClientsAccountingCostNotFoundException,
};
use DateTime;
use DateTimeImmutable;

final readonly class StoreCoveHelper
{
    private const string SETTING_CURRENCY_CODE_FROM = 'currency_code_from';
    private const string KEY_PAYEE_FINANCIAL_ACCOUNT = 'PayeeFinancialAccount';
    private const string KEY_COMPANY_ID = 'CompanyID';
    private const string KEY_DOCUMENT_TYPE = 'documentType';
    private const string KEY_DOCUMENT_ID = 'documentId';

    private StoreCoveCustomerPartyParser $customerParser;
    private StoreCoveDeliveryHelper $deliveryHelper;
    private StoreCoveInvoiceLineBuilder $lineBuilder;

    public function __construct(
        private SRepo $s,
        private DelRepo $delRepo,
        private DL $deliveryLocation,
        private Translator $t,
    ) {
        $this->customerParser = new StoreCoveCustomerPartyParser($this->t);
        $this->deliveryHelper = new StoreCoveDeliveryHelper($this->deliveryLocation, $this->t);
        $this->lineBuilder = new StoreCoveInvoiceLineBuilder($this->s, $this->t);
    }

    /**
     * @param Inv $invoice
     * @param cpR $cpR
     * @throws PeppolClientNotFoundException
     * @throws PeppolClientsAccountingCostNotFoundException
     * @return string
     */
    private function accountingCost(Inv $invoice, cpR $cpR): string
    {
        $client = $invoice->getClient();
        if (null !== $client) {
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if (null === $client_peppol) {
                throw new PeppolClientNotFoundException($this->t);
            }
            if ($client_peppol->getAccountingCost()) {
                return $client_peppol->getAccountingCost();
            }
            if (empty($client_peppol->getAccountingCost())) {
                throw new PeppolClientsAccountingCostNotFoundException($this->t);
            }
            return '';
        }
        throw new PeppolClientNotFoundException($this->t);
    }

    /**
     * @param Inv $invoice
     * @param upR $upR
     * @return array
     */
    private function buildAttachmentsArray(Inv $invoice, upR $upR): array
    {
        $url_key = $invoice->getUrlKey();
        $invoice_id = $invoice->reqId();
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
            $path_parts = pathinfo($target_path_with_filename);
            $file_ext = $path_parts['extension'] ?? '';
            $incrementor = 0;
            if (file_exists($target_path_with_filename)) {
                if (null !== $invoice->getNumber()) {
                    $documentId = $invoice->getNumber();
                } else {
                    $documentId = 'id' . $invoice_id . 'uploadid'
                        . $inv_attachment->reqId();
                }
                $allowed_content_type_array = $upR->getContentTypes();
                $save_ctype = isset($allowed_content_type_array[$file_ext]);
                /** @var string $ctype */
                $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] :
                                        $upR->getContentTypeDefaultOctetStream();
                $attachments[$incrementor] = [
                    'filename' => $inv_attachment->getFileNameOriginal(),
                    'document'
                    => mb_convert_encoding($target_path_with_filename,
                        'HTML-ENTITIES', 'UTF-8'),
                    'mimeType' => $ctype,
                    'primaryImage' => false,
                    self::KEY_DOCUMENT_ID => $documentId,
                    'description' => $inv_attachment->getDescription(),
                ];
            }
        }
        return $attachments;
    }

    /**
     * @param string $provider
     * @param int $inv_id
     * @return int
     */
    private function buildPeppolPaymentForReference(
                                            string $provider, int $inv_id): int
    {
        $pp = new PaymentPeppol($inv_id, $provider);
        return $pp->getAutoReference();
    }

    /**
     * Return a number represented as a string indicating how the tax point
              was determined: according to date supplied or date created/issued
     * Related logic: see src\Invoice\Inv\InvService set_tax_point function
     * @param Inv $inv
     * @param DateTimeImmutable $date_supplied
     * @param DateTimeImmutable $date_created
     * @return string
     */
    public function getDescriptionCodeForTaxPoint(Inv $inv,
      DateTimeImmutable $date_supplied, DateTimeImmutable $date_created): string
    {
        $dcI = 'Invoice Issue Date/Time ie. Date Created/Issued';
        $adS = 'Actual Delivery Date/Time ie. Date Supplied';
        $uncl2005_subset_array = [
            $dcI => '3',
            $adS => '35',
            'Paid to Date' => '432',
        ];
        if (null !== $inv->getClient()?->getClientVatId()
            && $date_created > $date_supplied) {
            $diff = $date_supplied->diff($date_created)->format('%R%a');
            if ((int) $diff > 14) {
                return $uncl2005_subset_array[$adS];
            }
        }
        $key = null === $inv->getClient()?->getClientVatId() ? $adS : $dcI;
        return $uncl2005_subset_array[$key];
    }

    /**
     * @param Inv $invoice
     * @param SRepo $s
     * @return string
     */
    public function invoicePeriod(Inv $invoice, SRepo $s): string
    {
        $datehelper = new DateHelper($s);
        $date_tax_point = $invoice->getDateTaxPoint();
        $date_created_or_issued = $invoice->getDateCreated();
        if ($date_tax_point === $date_created_or_issued) {
            $input_date = DateTime::createFromImmutable($date_created_or_issued);
        } else {
            $input_date = DateTime::createFromImmutable($date_tax_point);
        }
        $start_end_array = $datehelper->invoicePeriodStartEnd(
                            $invoice, $input_date, $this->delRepo);
        $startDate = (string) $start_end_array['StartDate'];
        $endDate = (string) $start_end_array['EndDate'];
        return $startDate . ' - ' . $endDate;
    }

    /**
     * @param Inv $invoice
     * @param ContractRepo $contractRepo
     * @param cpR $cpR
     * @param SOIR $soiR
     * @param SOR $soR
     * @return array
     */
    public function buildReferencesArray(Inv $invoice,
            ContractRepo $contractRepo, cpR $cpR, SOIR $soiR, SOR $soR): array
    {
        $invoice_id = $invoice->reqId();
        $sales_order_id = $invoice->getSoId();
        if ($sales_order_id > 0) {
            $sales_order = $soR->repoSalesOrderUnLoadedquery($sales_order_id);
            if ($sales_order) {
                $sales_order_number = ($sales_order->getNumber() ??
                    $this->t->translate(
                        'storecove.salesorder.number.not.exist')) ;
                $inv_items = $invoice->getItems();
                $contract_id = $invoice->getContractId();
                $contract_reference = '';
                if (null!== $contract_id) {
                    $contract = $contractRepo->repoContractquery($contract_id);
                    $contract_reference = $contract?->getReference() ??
                        $this->t->translate(
                            'storecove.no.contract.exists');
                }
                $incrementor = 0;
                $line_number = 1;
                $references = [];
                /**
                 * @var InvItem $item
                 */
                foreach ($inv_items as $item) {
                    $so_item_id = $item->getSoItemId();
                    $so_item = $soiR->repoSalesOrderItemquery((int) $so_item_id);
                    if (null !== $so_item) {
                        $po_itemid = $so_item->getPeppolPoItemid() ??
                            $this->t->translate(
                                'storecove.purchase.order.item.id.null');
                        $references[$incrementor] = [
                            self::KEY_DOCUMENT_TYPE => 'purchase_order',
                            self::KEY_DOCUMENT_ID => 'So_item_id/Po_item_id - '
                                        . (string) $so_item_id . '/' . $po_itemid,
                            'lineId' => 'Seller Inv Line - '
                                        . (string) $line_number,
                            'issueDate' => $invoice->getDateCreated(),
                        ];
                        $incrementor += 1;
                        $line_number += 1;
                    }
                }
                $references[$incrementor] = [
                    self::KEY_DOCUMENT_TYPE => 'buyer_reference',
                    self::KEY_DOCUMENT_ID => $this->buyerReference($invoice, $cpR),
                ];
                $incrementor += 1;
                $references[$incrementor] = [
                    self::KEY_DOCUMENT_TYPE => 'sales_order',
                    self::KEY_DOCUMENT_ID => $sales_order_number,
                ];
                $incrementor += 1;
                $references[$incrementor] = [
                    self::KEY_DOCUMENT_TYPE => 'billing',
                    self::KEY_DOCUMENT_ID => 'refers to a previous invoice',
                ];
                $incrementor += 1;
                $references[$incrementor] = [
                    self::KEY_DOCUMENT_TYPE => 'contract',
                    self::KEY_DOCUMENT_ID => $contract_reference,
                ];
                if (null !== $invoice->getNumber()) {
                    $ref = $invoice->getNumber();
                } else {
                    $ref = $this->t->translate(
                            'number.missing.therefore.use.invoice.id')
                            . $invoice_id;
                }
                $incrementor += 1;
                $references[$incrementor] = [
                    self::KEY_DOCUMENT_TYPE => 'originator',
                    self::KEY_DOCUMENT_ID => null !== $ref ? $this->t->translate(
                            'storecove.invoice') . $ref : '',
                ];
                return $references;
            }
        }
        return [];
    }

    /**
     * @param Contact $contact
     * @throws ContactEmailNotFoundException
     * @throws ContactNameNotFoundException
     * @throws ContactFirstNameNotFoundException
     * @throws ContactLastNameNotFoundException
     * @throws ContactTelephoneNotFoundException
     */
    public function validateSupplierContact(Contact $contact): void
    {
        if (null == $contact->getElectronicMail()) {
            throw new ContactEmailNotFoundException($this->t);
        }
        if (null == $contact->getName()) {
            throw new ContactNameNotFoundException($this->t);
        }
        if (null == $contact->getFirstName()) {
            throw new ContactFirstNameNotFoundException($this->t);
        }
        if (null == $contact->getLastName()) {
            throw new ContactLastNameNotFoundException($this->t);
        }
        if (null == $contact->getTelephone()) {
            throw new ContactTelephoneNotFoundException($this->t);
        }
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
        /** @var array<string, mixed> $pfa */
        $pfa = $config[self::KEY_PAYEE_FINANCIAL_ACCOUNT] ?? [];
        /** @var array<string, mixed> $fib */
        $fib = $pfa['FinancialInstitutionBranch'] ?? [];
        return [
            self::KEY_PAYEE_FINANCIAL_ACCOUNT => [
                'ID' => $pfa['ID'] ?? '',
                'Name' => $pfa['Name'] ?? '',
                'FinancialInstitutionBranch' => [
                    'ID' => $fib['ID'] ?? '',
                ],
            ],
        ];
    }

    /**
     * @param Inv $invoice
     * @param cpR $cpR
     * @return string
     */
    private function buyerReference(Inv $invoice, cpR $cpR): string
    {
        $client = $invoice->getClient();
        if (null !== $client) {
            $client_id = $client->reqId();
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client_id);
            if (null !== $client_peppol) {
                return $client_peppol->getBuyerReference();
            }
            throw new PeppolBuyerReferenceNotFoundException();
        }
        throw new PeppolClientNotFoundException($this->t);
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
            (string) $config['Contact']['Telefax'],
            (string) $config['Contact']['ElectronicMail'],
        );
    }

    /**
     * @param Inv $invoice
     * @param SRepo $s
     * @return InvoicePeriod
     */
    public function ublInvoicePeriod(Inv $invoice, SRepo $s): InvoicePeriod
    {
        $datehelper = new DateHelper($s);
        $date_tax_point = $invoice->getDateTaxPoint();
        $date_created_or_issued = $invoice->getDateCreated();
        $date_supplied = $invoice->getDateSupplied();
        if ($date_tax_point === $date_created_or_issued) {
            $input_date = DateTime::createFromImmutable($date_created_or_issued);
            $description_code = $this->getDescriptionCodeForTaxPoint(
                            $invoice, $date_supplied, $date_created_or_issued);
        } else {
            $input_date = DateTime::createFromImmutable($date_tax_point);
            $description_code = '';
        }
        $start_end_array = $datehelper->invoicePeriodStartEnd(
                                        $invoice, $input_date, $this->delRepo);
        $startDate = (string) $start_end_array['StartDate'];
        $endDate = (string) $start_end_array['EndDate'];
        return new InvoicePeriod($startDate, $endDate, $description_code);
    }

    /**
     * Note: The integer values in the array must be kept to ensure json array
     *  encoding later
     * Related logic: see https://www.storecove.com/docs/#_json_object 3.3.3.
     */
    public function maximumPreJsonPhpObjectForAnInvoice(
        Inv $invoice,
        StoreCoveHelperInvDeps $inv,
        StoreCoveHelperNetDeps $net,
        StoreCoveHelperChargeDeps $charge,
    ): array {
        $invoice_id = $invoice->reqId();
        $preamble = $this->resolveStoreCoveIdentifiers($invoice, $inv, $net, $charge);
        $references = $preamble['references'];
        $legal_entity_id = $preamble['legal_entity_id'];
        $tax_scheme_id = $preamble['tax_scheme_id'];
        $routing_scheme_identifier = $preamble['routing_scheme_identifier'];
        $contact = $this->supplierContact();
        $this->validateSupplierContact($contact);
        $payment_means_array = $this->buildPeppolPaymentMeansArray();
        /**
         * @var array $payment_means_array['PayeeFinancialAccount']
         */
        $payeeFinancialAccount = $payment_means_array[self::KEY_PAYEE_FINANCIAL_ACCOUNT];
        /**
         * @var string $payeeFinancialAccount['ID']
         */
        $pm_id = $payeeFinancialAccount['ID'];
        $payment_id = $this->buildPeppolPaymentForReference('storecove',
                                                          $invoice_id);
        $invoice_period = $this->ublInvoicePeriod($invoice, $this->s);
        $invoice_lines = $this->lineBuilder->buildInvoiceLinesArray($invoice,
                                            $invoice_period, $inv, $net, $charge);
        $allowance_charges = $this->lineBuilder->documentLevelAllowanceCharges(
                                                        $invoice, $charge->aciR);
        $taxSubtotal = $this->lineBuilder->buildTaxSubtotalArray(
                                                $invoice, $inv->iiaR, $charge->trR);
        /**
         * @var float $taxSubtotal['TaxableAmounts']
         */
        $taxable_amount = $taxSubtotal['TaxableAmounts'] ?? 0.00;
        /**
         * @var float $taxSubtotal['TaxAmount']
         */
        $tax_amount = $taxSubtotal['TaxAmount'] ?? 0.00;
        /**
         * @var float $taxSubtotal['TaxCategoryPercent']
         */
        $percentage = $taxSubtotal['TaxCategoryPercent'] ?? 0.00;
        $amount_including_vat = $taxable_amount + $tax_amount;
        return [
            'legalEntityId' =>
                          $this->s->getSetting('storecove_legal_entity_id'),
            'idempotencyGuid' => '61b37456-5f9e-4d56-b63b-3b1a23fa5c73',
            'routing' => [
                'eIdentifiers' => [
                    0 => [
                        'scheme' => $routing_scheme_identifier,
                        'id' => $routing_scheme_identifier ===
                                $this->t->translate('storecove.legal') ?
                                $legal_entity_id :
                                $tax_scheme_id,
                    ],
                ],
                'emails' => [
                    0 => $invoice->getClient()?->getClientEmail(),
                ],
                'workflow' => 'full',
            ],
            'attachments' => $this->buildAttachmentsArray($invoice, $net->upR),
            'document' => [
                self::KEY_DOCUMENT_TYPE => 'invoice',
                'invoice' => [
                    'taxSystem' => 'tax_line_percentages',
                    'documentCurrency' =>
                                $this->s->getSetting('currency_code_to'),
                    'invoiceNumber' => $invoice->getNumber(),
                    'issueDate' => $invoice->getDateCreated(),
                    'taxPointDate' => $invoice->getDateTaxPoint(),
                    'dueDate' => $invoice->getDateDue(),
                    'invoicePeriod' =>
                                $this->invoicePeriod($invoice, $this->s),
                    'references' => $references,
                    'accountingCost' =>
                                $this->accountingCost($invoice, $inv->cpR),
                    'note' => $invoice->getNote() ??
       $this->t->translate('storecove.advisory.to.developer.easily.missed'),
                    'accountingSupplierParty' => [
                        'party' => [
                            'contact' => [
                                'email' => $contact->getElectronicMail(),
                                'firstName' => $contact->getFirstName(),
                                'lastName' => $contact->getLastName(),
                                'phone' => $contact->getTelephone(),
                            ],
                        ],
                    ],
                    'accountingCustomerParty' => $this->buildStoreCoveAccountingCustomerParty($invoice, $inv),
                    'delivery' => $this->buildStoreCoveDeliverySection($invoice, $net),
                    'paymentTerms' => [
                        'note' => $invoice->getTerms(),
                    ],
                    'paymentMeansArray' => [
                        0 => [
                            'code' => 'credit_transfer',
                            'account' => $pm_id,
                            'paymentId' => $payment_id,
                        ],
                    ],
                    'invoiceLines' => $invoice_lines,
                    'allowanceCharges' => $allowance_charges,
                    'taxSubtotals' => [
                        0 => [
                            'taxableAmount' => $taxable_amount,
                            'taxAmount' => $tax_amount,
                            'percentage' => $percentage,
                            'country' =>
                                $this->s->getSetting(self::SETTING_CURRENCY_CODE_FROM),
                        ],
                    ],
                    'amountIncludingVat' => $amount_including_vat,
                    'prepaidAmount' => 1,
                ],
            ],
        ];
    }

    /**
     * @psalm-return array{references: array, legal_entity_id: string, tax_scheme_id: string, routing_scheme_identifier: string}
     */
    private function resolveStoreCoveIdentifiers(
        Inv $invoice,
        StoreCoveHelperInvDeps $inv,
        StoreCoveHelperNetDeps $net,
        StoreCoveHelperChargeDeps $charge,
    ): array {
        $references = $this->buildReferencesArray($invoice, $net->contractRepo,
                                                        $inv->cpR, $charge->soiR, $inv->soR);
        $config_peppol = $this->s->getConfigPeppol();
        /**
         * @var array $config_peppol['PartyLegalEntity']
         */
        $legal_entity = $config_peppol['PartyLegalEntity'] ?? '';
        /**
         * @var string $legal_entity['CompanyID']
         */
        $legal_entity_id = $legal_entity[self::KEY_COMPANY_ID] ?? '';
        if (empty($legal_entity_id)) {
            throw new LegalEntityCompanyIdNotFoundException($this->t);
        }
        /**
         * @var array $config_peppol['PartyTaxScheme']
         */
        $tax_scheme = $config_peppol['PartyTaxScheme'] ?? [];
        /**
         * @var string $tax_scheme['CompanyID']
         */
        $tax_scheme_id = $tax_scheme[self::KEY_COMPANY_ID] ?? '';
        if (empty($tax_scheme_id)) {
            throw new TaxSchemeCompanyIdNotFoundException($this->t);
        }
        $identifier = (int) $this->s->getSetting(
                                             'storecove_sender_identifier');
        $store_cove_sender_array =
                      StoreCoveArrays::storeCoveSenderIdentifierArray();
        $identifier_basis = $this->s->getSetting(
                                        'storecove_sender_identifier_basis');
        $routing_scheme_identifier = '';
        /**
         * @var int $key
         * @var string $value
         */
        foreach ($store_cove_sender_array as $key => $value) {
            if ($key == $identifier) {
                if ($identifier_basis === $this->t->translate(
                                                        'storecove.tax')) {
                    /**
                     * @var string $value[$identifier_basis]
                     */
                    $routing_scheme_identifier = $value[$identifier_basis];
                    continue;
                }
                if ($identifier_basis === $this->t->translate(
                                                        'storecove.legal')) {
                    /**
                     * @var string $value[$identifier_basis]
                     */
                    $routing_scheme_identifier = $value[$identifier_basis];
                }
            }
        }
        return [
            'references' => $references,
            'legal_entity_id' => $legal_entity_id,
            'tax_scheme_id' => $tax_scheme_id,
            'routing_scheme_identifier' => $routing_scheme_identifier,
        ];
    }

    private function buildStoreCoveAccountingCustomerParty(
        Inv $invoice,
        StoreCoveHelperInvDeps $inv,
    ): array {
        $acp = $this->customerParser->buildPeppolAccountingCustomerPartyArray(
                                                        $invoice, $inv->paR, $inv->cpR);
        $customer_partyTaxScheme = $this->customerParser->buildCustomerPartyTaxScheme($acp);
        $customer_partyLegalEntity = $this->customerParser->buildCustomerLegalEntity($acp);
        $customer_tax_scheme = $customer_partyTaxScheme->getTaxScheme();
        $customer_tax_id = $customer_partyTaxScheme->getCompanyId();
        $customer_legal_scheme =
                $customer_partyLegalEntity->getCompanyIdAttributeSchemeId();
        $customer_legal_id = $customer_partyLegalEntity->getCompanyId();
        $customer_physical = $this->customerParser->buildCustomerPhysicalLocation($acp);
        $c_contact = $this->customerParser->buildCustomerContact($acp);
        return [
                        'publicIdentifiers' => [
                            0 => [
                                'scheme' => $customer_legal_scheme,
                                'id' => $customer_legal_id,
                            ],
                            1 => [
                                'scheme' => $customer_tax_scheme,
                                'id' => $customer_tax_id,
                            ],
                        ],
                        'party' => [
                            'companyName' => 'Receiver Company',
                            'address' => [
                                'street1' =>
                                        $customer_physical->getStreetName(),
                                'street2' =>
                                $customer_physical->getAdditionalStreetName(),
                                'city' => $customer_physical->getCityName(),
                                'zip' => $customer_physical->getPostalZone(),
                                'county' =>
                                   $customer_physical->getCountrySubEntity(),
                                'country' =>
                  $customer_physical->getCountry()?->getIdentificationCode(),
                            ],
                            'contact' => [
                                'email' => $c_contact->getElectronicMail(),
                                'firstName' => $c_contact->getFirstName(),
                                'lastName' => $c_contact->getLastName(),
                                'phone' => $c_contact->getTelephone(),
                            ],
                        ],
        ];
    }

    private function buildStoreCoveDeliverySection(
        Inv $invoice,
        StoreCoveHelperNetDeps $net,
    ): array {
        $c_del_loc_address = $this->deliveryHelper->buildDeliveryLocationAddress();
        $c_actual_del_datetime = $this->deliveryHelper->actualDeliveryDate($invoice, $net->delRepo);
        $c_del_party = $this->deliveryHelper->deliveryParty($invoice, $net->delRepo, $net->delPartyRepo);
        return [
                        'deliveryPartyName' =>
                            null !== $c_del_party ?
                            $c_del_party->getPartyName() :
                            $this->t->translate('storecove.not.available'),
                        'actualDeliveryDate' =>
                                    $c_actual_del_datetime?->format('Y-m-d'),
                        'deliveryLocation' => [
                            'id' =>
                      $this->deliveryLocation->getGlobalLocationNumber(),
                            'schemeId' =>
                      $this->deliveryLocation->getElectronicAddressScheme(),
                            'address' => [
                                'street1' =>
                                $c_del_loc_address->getStreetName(),
                                'street2' =>
                               $c_del_loc_address->getAdditionalStreetName(),
                                'city' => $c_del_loc_address->getCityName(),
                                'zip' => $c_del_loc_address->getPostalZone(),
                                'county' =>
                                   $c_del_loc_address->getCountrySubEntity(),
                                'country' =>
                  $c_del_loc_address->getCountry()?->getIdentificationCode(),
                            ],
                        ],
        ];
    }

    public function storeCoveCallApiGetLegalEntityId(): bool|string
    {
        /**
         * @var mixed $api_key_here
         */
        $api_key_here =
            $this->s->decode($this->s->getSetting('gateway_storecove_apiKey'));
        $country_code_identifier = $this->s->getSetting('storecove_country');
        $site = curl_init();
        if ($site) {
            curl_setopt($site,
                    CURLOPT_URL, 'https://api.storecove.com/api/v2/legal_entities');
            curl_setopt($site, CURLOPT_POST, true);
            curl_setopt($site, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($site, CURLOPT_HTTPHEADER,
                    [
                        'Accept: application/json',
                        "Authorization: Bearer $api_key_here",
                        'Content-Type: application/json'
                    ]);
            curl_setopt($site, CURLOPT_HEADER, true);
            $data = '{"party_name": "Test Party", "line1": "Test Street 1",'
                    . ' "city": "Test City", "zip": "Zippy",'
                    . ' "country": "' . $country_code_identifier . '"}';
            curl_setopt($site, CURLOPT_POSTFIELDS, $data);
            return curl_exec($site);
        }
        return false;
    }
}
