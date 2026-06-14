<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
//https://github.com/brick/money
use Brick\Money\Context\DefaultContext;
use Brick\Money\CurrencyConverter;
// Use settings/view/peppol to manually load the exchange rate for today via:
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
// Yiisoft
use Yiisoft\Translator\TranslatorInterface as Translator;
// Entities
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation as DL;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\PaymentPeppol\PaymentPeppol;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Upload\Upload;
// Helpers
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository as SRepo;
// Libraries
use App\Invoice\Libraries\Crypt;
// Repositories
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
//use App\Invoice\InvAmount\InvAmountRepository as IAR;
//use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\Contract\ContractRepository as ContractRepo;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\DeliveryParty\DeliveryPartyRepository as DelPartyRepo;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\ProductProperty\ProductPropertyRepository as ppR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\UnitPeppol\UnitPeppolRepository as unpR;
use App\Invoice\Upload\UploadRepository as upR;
// Ubl/
use App\Invoice\Ubl\Address;
use App\Invoice\Ubl\Contact;
use App\Invoice\Ubl\Country;
use App\Invoice\Ubl\InvoicePeriod;
use App\Invoice\Ubl\Party;
use App\Invoice\Ubl\PartyLegalEntity;
use App\Invoice\Ubl\PartyTaxScheme;
use App\Invoice\Ubl\TaxScheme;
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
    PeppolBuyerPostalAddressNotFoundException,
    PeppolClientNotFoundException,
    PeppolClientsAccountingCostNotFoundException,
    PeppolDeliveryLocationCountryNameNotFoundException,
    PeppolDeliveryLocationIDNotFoundException,
    PeppolSalesOrderPurchaseOrderNumberNotExistException,
    PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException,
    PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException,
    PeppolSalesOrderItemNotExistException,
    PeppolTaxCategoryCodeNotFoundException,
    PeppolTaxCategoryPercentNotFoundException,
};
use DateTime;
use DateTimeImmutable;

final readonly class StoreCoveHelper
{
    private const ISO3166_1_ALPHA2 = 'ISO3166-1:Alpha2';
    private const SETTING_CURRENCY_CODE_FROM = 'currency_code_from';
    private const KEY_PARTY = 'Party';
    private const KEY_PAYEE_FINANCIAL_ACCOUNT = 'PayeeFinancialAccount';
    private const KEY_COMPANY_ID = 'CompanyID';
    private const KEY_DOCUMENT_TYPE = 'documentType';
    private const KEY_DOCUMENT_ID = 'documentId';
    private const KEY_STREET_NAME = 'StreetName';
    private const KEY_ADDITIONAL_STREET_NAME = 'AdditionalStreetName';
    private const KEY_ADDRESS_LINE = 'AddressLine';
    private const KEY_CITY_NAME = 'CityName';
    private const KEY_POSTAL_ZONE = 'PostalZone';
    private const KEY_COUNTRY_SUBENTITY = 'CountrySubentity';
    private const KEY_IDENTIFICATION_CODE = 'IdentificationCode';
    private const KEY_LIST_ID = 'ListId';

    private DateHelper $datehelper;
    private string $fromCurrency;
    private string $toCurrency;
    private string $fromToManualInput;
    private string $toFromManualInput;

    public function __construct(
        private SRepo $s,
        private DelRepo $delRepo,
        private DL $deliveryLocation,
        private Translator $t,
    ) {
        $this->datehelper = new DateHelper($this->s);
        $this->fromCurrency = $this->s->getSetting(self::SETTING_CURRENCY_CODE_FROM);
        $this->toCurrency = $this->s->getSetting('currency_code_to');
        $this->fromToManualInput = $this->s->getSetting('currency_from_to');
        $this->toFromManualInput = $this->s->getSetting('currency_to_from');
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
     * Related logic: see InvController and download_file function
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
                // Check extension against allowed content file types
                // Related logic: see UploadRepository getContentTypes
                $save_ctype = isset($allowed_content_type_array[$file_ext]);
                /** @var string $ctype */
                $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] :
                                        $upR->getContentTypeDefaultOctetStream();
                // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
                //  cac-AdditionalDocumentReference/
                // $inv_attachment->reqId() => upload repository id
                $attachments[$incrementor] = [
                    'filename' => $inv_attachment->getFileNameOriginal(),
                    'document'
                    // https://stackoverflow.com/questions/2236668/
                    //  file-get-contents-breaks-up-utf-8-characters
                    => mb_convert_encoding($target_path_with_filename,
                        'HTML-ENTITIES', 'UTF-8'),
                    // JsonException Malformed UTF-8 characters,
                    //  possibly incorrectly encoded
                    //file_get_contents($target_path_with_filename, true),
                    'mimeType' => $ctype,
                    'primaryImage' => false,
                    self::KEY_DOCUMENT_ID => $documentId,
                    'description' => $inv_attachment->getDescription(),
                ];
            } //if
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
        // For yii3-i,'Date created' is used interchangeably with 'Date issued'
        // Canonical source and quarterly update guidance: resources/peppol/uncl2005.php
        // Upstream: https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
        $dcI = 'Invoice Issue Date/Time ie. Date Created/Issued';
        $adS = 'Actual Delivery Date/Time ie. Date Supplied';
        $ptd = 'Paid to Date';
        $uncl2005_subset_array = [
            $dcI => '3',
            $adS => '35',
            $ptd => '432',
        ];
        if (null !== $inv->getClient()?->getClientVatId()) {
            if ($date_created > $date_supplied) {
                $diff = $date_supplied->diff($date_created)->format('%R%a');
                if ((int) $diff > 14) {
                    // date supplied more than 14 days before invoice date =>
                    //  use date supplied
                    return $uncl2005_subset_array[$adS];
                }
                // if the issue date (created) is within 14 days after the
                //  supply (basic) date then use the issue/created date.
                return $uncl2005_subset_array[$dcI];
            }
            if ($date_created < $date_supplied) {
                // normally set the tax point to the date_created
                return $uncl2005_subset_array[$dcI];
            }
            if ($date_created === $date_supplied) {
                // normally set the tax point to the date_created
                return $uncl2005_subset_array[$dcI];
            }
        }
        // If the client is not VAT registered, the tax point is the date supplied
        if (null == $inv->getClient()?->getClientVatId()) {
            return $uncl2005_subset_array[$adS];
        }
        // Default to date created
        return $uncl2005_subset_array[$dcI];
    }

    /**
     * Use the invoice's delivery period in preference to the month that
     * Related logic:
        https://docs.peppol.eu/poacc/billing/3.0/syntax/
                                                 ubl-invoice/cac-InvoicePeriod/
     * Related logic:
        https://docs.peppol.eu/poacc/billing/3.0/
                                                      rules/ubl-tc434/BR-CO-03/
     * @param Inv $invoice
     * @param SRepo $s
     * @return string
     */
    public function invoicePeriod(Inv $invoice, SRepo $s): string
    {
        // Related logic: see InvService set_tax_point
        $datehelper = new DateHelper($s);
        $date_tax_point = $invoice->getDateTaxPoint();
        $date_created_or_issued = $invoice->getDateCreated();
        if ($date_tax_point === $date_created_or_issued) {
            // => there is NO need for a visible peppol tax point
            // because the date issued and tax point are the same
            // therefore base the invoice period on the date_created
            // and include the description code Business Rule (BT-8)
            // Note: The description code describes what date the future
            // tax point will be based on ie. date supplied/delivery date
            // or payment date
            $input_date = DateTime::createFromImmutable($date_created_or_issued);
        } else {
            // => there IS a need for a visible peppol tax point
            // therefore base the invoice period on the tax point
            // but exclude the description code Business Rule (BT-8)
            $input_date = DateTime::createFromImmutable($date_tax_point);
        }
        // if the invoice has a delivery period use it's dates in preference
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
                 * @var \App\Infrastructure\Persistence\InvItem\InvItem $item
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
                    } // null!== $so_item
                }
                // build the client buyer reference
                $references[$incrementor] = [
                    self::KEY_DOCUMENT_TYPE => 'buyer_reference',
                    self::KEY_DOCUMENT_ID => $this->buyerReference($invoice, $cpR),
                ];
                $incrementor += 1;
                // build the sales order reference
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
            } // null!== $sales_order
        } // null !== $sales_order_id
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
   * Retrieve the Client/Customer's purchase order item id
   * @param InvItem $item
   * @param SOIR $soiR
   * @throws PeppolSalesOrderPurchaseOrderNumberNotExistException
   * @throws PeppolSalesOrderItemNotExistException
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
throw new PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException($this->t);
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
     * @throws PeppolSalesOrderPurchaseOrderNumberNotExistException
     * @throws PeppolSalesOrderItemNotExistException
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
                throw new PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException(
                        $this->t);
            } else {
                throw new PeppolSalesOrderItemNotExistException($this->t);
            }
        }
        return null;
    }

    /**
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @return DateTime
     */
    public function actualDeliveryDate(Inv $invoice, DelRepo $delRepo): ?DateTime
    {
        $invoice_id = $invoice->reqId();
        $dateSupplied = DateTime::createFromImmutable($invoice->getDateSupplied());
        $delivery = $delRepo->repoInvoicequery($invoice_id);
        if (null !== $delivery) {
            $actual_delivery_date = $delivery->getActualDeliveryDate();
            if (null !== $actual_delivery_date) {
                return DateTime::createFromImmutable($actual_delivery_date);
            }
        }
        return $dateSupplied;
    }

    /**
     * @param int $product_id
     * @param ppR $ppR
     * @return array
     */
    private function buildProductPropertyArray(int $product_id, ppR $ppR): array
    {
        $product_propertys = $ppR->findAllProduct($product_id);
        $product_property_array = [];
        $i = 1;
        /**
         * @var \\App\Infrastructure\Persistence\ProductProperty\ProductProperty $product_property
         */
        foreach ($product_propertys as $product_property) {
            $product_property_array[$i] = [
                'name' => $product_property->getName(),
                'value' => $product_property->getValue(),
            ];
            $i += 1;
        }
        return $product_property_array;
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
        $allowanceCharges = [];
        if ($aciR->repoACICount($invoice_id)) {
            /**
             * @var InvAllowanceCharge $ac
             */
            foreach ($allowances_or_charges as $ac) {
                //https://www.storecove.com/docs/#_openapi_allowancecharge
                $allowanceCharges[] = [
// The dropdown reason in free text determines if it is an allowance or charge
//  eg. "Agreed settlement" =>
                    'reason' => $ac->getAllowanceCharge()?->getReason(),
                    'amountExcludingTax' => $ac->getAmount(),
                    // optional 'amountIncludingTax' => 3,
                    'baseAmountExcludingTax' =>
                        $ac->getAllowanceCharge()?->getBaseAmount(),
                    // optional 'baseAmountIncludingTax' => 4,
                    //5.2.83 Tax https://www.storecove.com/docs/#_openapi_tax
                    'tax' => [
                        // The percentage Tax.
                        // This should be a valid
                        // Tax percentage in the
                        // country at the time of
                        // the issueDate of this
                        // invoice. Mandatory if
                        // taxSystem == 'tax_line_percentages'
                        'percentage' =>
            $ac->getAllowanceCharge()?->getTaxRate()?->getTaxRatePercent(),
                        // sender country code
                        'country' =>
                                $this->s->getSetting(self::SETTING_CURRENCY_CODE_FROM),
                        // stored in snake_case format eg. zero_rated
                        'category' =>
            $ac->getAllowanceCharge()?->getTaxRate()?->getStorecoveTaxType(),
                    ], // tax
                ]; // allowancecharges[]
            } // foreach
        }
        return $allowanceCharges;
    }

    /**
     * @param Inv $invoice
     * @param InvoicePeriod $invoice_period
     * @param StoreCoveHelperInvDeps $inv
     * @param StoreCoveHelperNetDeps $net
     * @param StoreCoveHelperChargeDeps $charge
     * @throws PeppolClientNotFoundException
     * @return array
     */
    private function buildInvoiceLinesArray(
        Inv $invoice,
        InvoicePeriod $invoice_period,
        StoreCoveHelperInvDeps $inv,
        StoreCoveHelperNetDeps $net,
        StoreCoveHelperChargeDeps $charge,
    ): array
    {
        $client = $invoice->getClient();
        if ($client) {
            $client_peppol = $inv->cpR->repoClientPeppolLoadedquery($client->reqId());
            if ($client_peppol) {
                $invoiceLines = [];
                /**
                 * @var InvItem $item
                 */
                foreach ($invoice->getItems() as $item) {
                    $price = ($item->getPrice() ?? 0.00);
                    $peppol_po_itemid = $this->peppolPoItemid($item, $charge->soiR);
                    $peppol_po_lineid = $this->peppolPoLineid($item, $charge->soiR);
                    $item_id = $item->reqId();
                    // if the additionalitemproperty field has been used,
                    //  use the product property name value pairs to build
                    //   an array
                    $product_properties_array =
                            $this->buildProductPropertyArray($item_id, $net->ppR);
                    $inv_item_amount = $this->getInvItemAmount($item_id, $inv->iiaR);
                    if (isset($inv_item_amount)) {

        // using Array Format 2
        // ..\vendor\sabre\xml\lib\Writer.php
        // https://kinsta.com/blog/php-8-2/#deprecate--string-interpolation
        // Note: The following string interpolation confirms with php 8.2
                        $invoiceLines[$item_id] = [
                            'lineId' => $item_id,
// storecove.com/docs 5.2.50. PaymentMeans Netherlands
                            'amountExcludingVat' => '',
                            'itemPrice' => $this->s->currencyConverter($price),
// baseQuantity: number of sub-items included in the price of the item
                            'baseQuantity' =>
                    $item->getProduct()?->getProductPriceBaseQuantity(),
                            'quantity' => $item->getQuantity(),
                            'quantityUnitCode' => $this->unitCode(
                                $item->getProduct()?->getUnit()?->reqId(),
                                $net->unpR),
                            'tax' => [
                                'percentage' =>
                   $item->getProduct()?->getTaxRate()?->getTaxRatePercent(),
                                'country' =>
                  $item->getProduct()?->getProductCountryOfOriginCode(),
                                'category' =>
                 $item->getProduct()?->getTaxRate()?->getStoreCoveTaxType(),
                            ],
//https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/
// cac-OrderLineReference/cbc-LineID/
                            'orderLineReferenceLineId' =>
                        $peppol_po_lineid ?? $this->t->translate('client.'),
                            'accountingCost' =>
                        $client_peppol->getAccountingCost(),
                            'name' => $item->getName(),
                            'description' => $item->getDescription(),
                            'invoicePeriod' =>
                        $invoice_period->getStartDate()
                                . ' - ' . $invoice_period->getEndDate(),
                            'note' => $item->getNote(),
                            'references' => [
                            ],
                            //https://www.storecove.com/docs
                            // buyersItemIdentification
                            'buyersItemIdentification' => $peppol_po_itemid,
                            'sellersItemIdentification' =>
                                $item->getProduct()?->getProductSku(),
                            'standardItemIdentification' =>
                                $item->getProduct()?->getProductSiiId(),
                            'standardItemIdentificationSchemeId' =>
                             $item->getProduct()?->getProductSiiSchemeid(),
                            'additionalItemProperties' => [
                                0 => [
                                    'name' =>
           $item->getProduct()?->getProductAdditionalItemPropertyName(),
                                    'value' =>
          $item->getProduct()?->getProductAdditionalItemPropertyValue(),
                                ],
                                $product_properties_array,
                            ],
                        ];
                        $inv_item_allowance_charges =
                                $charge->aciiR->repoInvItemquery($item_id);
                        /**
                         * @var InvItemAllowanceCharge $acii
                         */
                        foreach ($inv_item_allowance_charges as $acii) {
                            $invoiceLines[$item_id]['allowanceCharges'][] = [
                                'reason' =>
                                   $acii->getAllowanceCharge()?->getReason(),
                                'amountExcludingTax' =>
                                $acii->getAllowanceCharge()?->getBaseAmount(),
                            ];
                        }
                    } // isset inv_item_amount
                } // foreach
                return $invoiceLines;
            }
            throw new PeppolClientNotFoundException($this->t);
        } else {
            throw new PeppolClientNotFoundException($this->t);
        }
    }

    /**
       * @param Inv $invoice
       * @param paR $paR
       * @param cpR $cpR
       * @throws PeppolBuyerPostalAddressNotFoundException
       * @throws PeppolClientNotFoundException
       * @return array
       */
    private function buildPeppolAccountingCustomerPartyArray(
                                        Inv $invoice, paR $paR, cpR $cpR): array
    {
        $client = $invoice->getClient();
        if ($client) {
            $postaladdress_id = $client->getPostaladdressId();
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if (null == $postaladdress_id) {
                throw new PeppolBuyerPostalAddressNotFoundException();
            }
            if ($postaladdress_id) {
                $postaladdress = $paR->repoClient($postaladdress_id);
                $accounting_customer_party = [];
                $country_helper = new CountryHelper();
                if ($postaladdress && $client_peppol) {
                    $accounting_customer_party = [
                        self::KEY_PARTY => [
                            'EndPointID' => [
                                'value' => $client_peppol->getEndpointid(),
                                'schemeID' =>
                                        $client_peppol->getEndpointidSchemeid(),
                            ],
//https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
//  cac-AccountingSupplierParty/cac-Party/cac-PartyIdentification/
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
                                self::KEY_STREET_NAME => $postaladdress->getStreetName(),
                                self::KEY_ADDITIONAL_STREET_NAME =>
                                     $postaladdress->getAdditionalStreetName(),
                                self::KEY_ADDRESS_LINE => [
                                    'Line' =>
                                            $postaladdress->getBuildingNumber(),
                                ],
                                self::KEY_CITY_NAME => $postaladdress->getCityName(),
                                self::KEY_POSTAL_ZONE => $postaladdress->getPostalZone(),
                                self::KEY_COUNTRY_SUBENTITY =>
                                           $postaladdress->getCountrysubentity(),
                                'Country' => [
                                    self::KEY_IDENTIFICATION_CODE =>
                                                    $postaladdress->getCountry(),
                    //https://docs.peppol.eu/poacc/billing/3.0/codelist/ISO3166/
                                    self::KEY_LIST_ID => self::ISO3166_1_ALPHA2,
                                ],
                            ],
                            'PhysicalLocation' => [
                                self::KEY_STREET_NAME => (string)
                                                 $client->getClientAddress1(),
                                self::KEY_ADDITIONAL_STREET_NAME =>
                                        (string) $client->getClientAddress2(),
                                self::KEY_ADDRESS_LINE => [
                                    'Line' => (string)
                                            $client->getClientBuildingNumber(),
                                ],
                                self::KEY_CITY_NAME => (string)
                                                       $client->getClientCity(),
                                self::KEY_POSTAL_ZONE => (string)
                                                        $client->getClientZip(),
                                self::KEY_COUNTRY_SUBENTITY => (string)
                                                      $client->getClientState(),
                                'Country' => [
                                    self::KEY_IDENTIFICATION_CODE =>
    $country_helper->getCountryIdentificationCodeWithLeague((string)
                                                   $client->getClientCountry()),
                                    //https://docs.peppol.eu/poacc/billing/3.0/codelist/ISO3166/
                                    self::KEY_LIST_ID => self::ISO3166_1_ALPHA2,
                                ],
                            ],
                            'Contact' => [
                                'Name' => $client->getClientName(),
                                'Telephone' =>
                                            (string) $client->getClientPhone(),
                                'ElectronicMail' => $client->getClientEmail(),
                            ],
                            'PartyTaxScheme' => [
                                self::KEY_COMPANY_ID =>
                                        $client_peppol->getTaxschemecompanyid(),
                                'TaxScheme' => [
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
// cac-AccountingSupplierParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/cbc-ID/
// VAT / !VAT
                                    'ID' => $client_peppol->getTaxSchemeid(),
                                ],
                            ],
                            'PartyLegalEntity' => [
                                'RegistrationName' =>
                             $client_peppol->getLegalEntityRegistrationName(),
                                'CompanyIdAttributes' => [
                                    'value' =>
                                     $client_peppol->getLegalEntityCompanyid(),
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
        throw new PeppolClientNotFoundException($this->t);
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
        $party_legal_entity = $party[self::KEY_PARTY]['PartyLegalEntity'] ?? [];
        /**
         * @var string $party_legal_entity['RegistrationName']
         */
        $registration_name = $party_legal_entity['RegistrationName'] ?? '';
        /**
         * @var string $party_legal_entity['CompanyID']
         */
        $company_id = $party_legal_entity[self::KEY_COMPANY_ID] ?? '';
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
// cac-AccountingCustomerParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/

        /**
         * @var array $party['Party']
         * @var array $party['Party']['PartyTaxScheme']
         */
        $party_tax_scheme = $party[self::KEY_PARTY]['PartyTaxScheme'] ?? [];
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
        $party_tax_scheme_companyID = $party_tax_scheme[self::KEY_COMPANY_ID];

        return new PartyTaxScheme(
            $party_tax_scheme_companyID,
            new TaxScheme($party_tax_scheme_ID),
        );
    }

    /**
     * @param array $party
     * @return Address
     */
    public function buildCustomerPhysicalLocation(array $party): Address
    {
        /**
         * @var array $party['Party']
         * @var array $party['Party']['PhysicalLocation']
         */
        $party_physical_location = $party[self::KEY_PARTY]['PhysicalLocation'] ?? [];
        /**
         * @var array $party_physical_location['Country']
         */
        $party_physical_location_country =
            $party_physical_location['Country'] ?? [];
        /**
         * @var string $party_physical_location['StreetName']
         */
        $street_name = $party_physical_location[self::KEY_STREET_NAME] ?? '';
        /**
         * @var string $party_physical_location['AdditionalStreetName']
         */
        $additional_street_name =
            $party_physical_location[self::KEY_ADDITIONAL_STREET_NAME] ?? '';
        /**
         * @var array $party_physical_location['AddressLine']
         */
        $address_line = $party_physical_location[self::KEY_ADDRESS_LINE] ?? [];
        /**
         * @var string $address_line['Line']
         */
        $line = $address_line['Line'] ?? '';
        /**
         * @var string $party_physical_location['CityName']
         */
        $city_name = $party_physical_location[self::KEY_CITY_NAME] ?? '';
        /**
         * @var string $party_physical_location['PostalZone']
         */
        $postal_zone = $party_physical_location[self::KEY_POSTAL_ZONE] ?? '';
        /**
         * @var string $party_physical_location['CountrySubentity']
         */
        $country_sub_entity =
            $party_physical_location[self::KEY_COUNTRY_SUBENTITY] ?? '';
        /**
         * @var string $party_physical_location_country['IdentificationCode']
         */
        $identification_code =
            $party_physical_location_country[self::KEY_IDENTIFICATION_CODE] ?? '';
        /**
         * @var string $party_physical_location_country['ListId']
         */
        $listId = $party_physical_location_country[self::KEY_LIST_ID] ?? '';
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
        $postal_address = $party[self::KEY_PARTY]['PostalAddress'] ?? [];
        /**
         * @var string $postal_address['StreetName']
         */
        $street_name = $postal_address[self::KEY_STREET_NAME] ?? '';
        /**
         * @var string $postal_address['AdditionalStreetName']
         */
        $additional_street_name = $postal_address[self::KEY_ADDITIONAL_STREET_NAME] ?? '';
        /**
         * @var array $postal_address['AddressLine']
         */
        $address_line = $postal_address[self::KEY_ADDRESS_LINE] ?? [];
        /**
         * @var string $address_line['Line']
         */
        $line = $address_line['Line'] ?? '';
        /**
         * @var string $postal_address['CityName']
         */
        $city_name = $postal_address[self::KEY_CITY_NAME] ?? '';
        /**
         * @var string $postal_address['PostalZone']
         */
        $postal_zone = $postal_address[self::KEY_POSTAL_ZONE] ?? '';
        /**
         * @var string $postal_address['CountrySubentity']
         */
        $country_sub_entity = $postal_address[self::KEY_COUNTRY_SUBENTITY] ?? '';
        /**
         * @var array $postal_address['Country']
         */
        $country = $postal_address['Country'] ?? [];
        /**
         * @var string $country['IdentificationCode']
         */
        $identification_code = $country[self::KEY_IDENTIFICATION_CODE] ?? '';
        /**
         * @var string $country['ListId']
         */
        $listId = $country[self::KEY_LIST_ID] ?? '';
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
        );
    }

    /**
     * Introduce Storecove's firstname and lastname field
     * @param array $party
     * @return Contact
     */
    public function buildCustomerContact(array $party): Contact
    {
        /** @var array<string, mixed> $party_data */
        $party_data = $party[self::KEY_PARTY] ?? [];
        /** @var array $contact */
        $contact = $party_data['Contact'] ?? [];

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
            // Telefax
            '',
            $electronicMail,
        );
    }

    /**
     * @return Address
     */
    public function buildDeliveryLocationAddress(): Address
    {
        // The customer/client must choose their delivery location from their
        // dashboard
        // Alternatively the administrator can edit the invoice under
        //  view...options.
        // Peppol 3.0: Building number can be included in address_1
        $street_name = $this->deliveryLocation->getAddress1();
        $additional_street_name = $this->deliveryLocation->getAddress2();
        $building_number = $this->deliveryLocation->getBuildingNumber();
        $cityName = $this->deliveryLocation->getCity();
        $postalZone = $this->deliveryLocation->getZip();
        $countrySubEntity = $this->deliveryLocation->getState();
        $country_name = $this->deliveryLocation->getCountry();
        /**
         * Related logic: see DeliveryLocation
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
        throw new PeppolDeliveryLocationCountryNameNotFoundException($this->t);
    }

    /**
     * @throws PeppolDeliveryLocationIDNotFoundException
     * @return array
     */
    public function buildDeliveryLocationIDScheme(): array
    {
        $id = $this->deliveryLocation->getGlobalLocationNumber();
        if (null == $id) {
            throw new PeppolDeliveryLocationIDNotFoundException($this->t);
        }
        return [
            'ID' => $id,
            'attributes' => [
                'schemeID' =>
                        $this->deliveryLocation->getElectronicAddressScheme(),
            ],
        ];
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
            self::KEY_PAYEE_FINANCIAL_ACCOUNT => [
                // eg. IBAN number
                'ID' => $config[self::KEY_PAYEE_FINANCIAL_ACCOUNT]['ID'] ?? '',
                'Name' => $config[self::KEY_PAYEE_FINANCIAL_ACCOUNT]['Name'] ?? '',
                'FinancialInstitutionBranch' => [
                    'ID' =>
     $config[self::KEY_PAYEE_FINANCIAL_ACCOUNT]['FinancialInstitutionBranch']['ID'] ?? '',
                ],
            ],
        ];
    }

    /**
     * @param Inv $invoice
     * @param iiaR $iiaR
     * @param TRR $trR
     * @throws PeppolTaxCategoryCodeNotFoundException
     * @throws PeppolTaxCategoryPercentNotFoundException
     * @return array
     */
    private function buildTaxSubtotalArray(Inv $invoice, IIAR $iiaR, TRR $trR): array
    {
        $array = [];
        // For each tax rate, build the taxable amount array
        $taxRates = $trR->findAllPreloaded();
        /**
         * @var TaxRate $taxRate
         */
        foreach ($taxRates as $taxRate) {
            $id = $taxRate->reqId();
            $tax_category = $taxRate->getPeppolTaxRateCode();
            $tax_percent = $taxRate->getTaxRatePercent();
            // Throw an exception if any Tax Category does not have a code
            if (null == $tax_category) {
                throw new PeppolTaxCategoryCodeNotFoundException($this->t);
            }
            if (null === $tax_percent) {
                throw new PeppolTaxCategoryPercentNotFoundException($this->t);
            }
            
            $taxable_amount_total = 0.00;
            $tax_amount_total = 0.00;
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                $item_id = $item->reqId();
                if ($id === $item->getTaxRate()?->reqId()) {
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

            /**
             * @var array $array[$id]
             */
            $sub_array = $array[$id] ?? [];
            /**
             *  @var float $sub_array['TaxableAmounts']
             */
            $sub_array['TaxableAmounts'] = (float) $this->s->currencyConverter(
                                                     $taxable_amount_total);
            /**
             *  @var float $sub_array['TaxAmount']
             */
            $sub_array['TaxAmount'] = (float) $this->s->currencyConverter(
                                                        $tax_amount_total);
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
            $sub_array['DocumentCurrency'] = $this->toCurrency;
            $array[$id] = $sub_array;
        }
        return $array;
    }

    /**
     * Related logic:
        https://docs.peppol.eu/poacc/billing/3.0/syntax/
                                                ubl-invoice/cbc-BuyerReference/
     * Related logic: https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
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
     * @param BigNumber|float|int|string $from
     * @return string
     */
    private function currencyConverter(BigNumber|int|float|string $from): string
    {
        $a = $this->fromCurrency;
        $b = $this->toCurrency;
        $one_of_a_converts_to_this_of_b = $this->fromToManualInput;
        $one_of_b_converts_to_this_of_a = $this->toFromManualInput;
        $provider = ConfigurableProvider::builder()
            ->addExchangeRate($a, $b, $one_of_a_converts_to_this_of_b)
            ->addExchangeRate($b, $a, $one_of_b_converts_to_this_of_a)
            ->build();
        $converter = new CurrencyConverter($provider);
        $money = Money::of((string) $from, $a);
        // see https://github.com/brick/money#Using an ORM
        $float = (float) $converter->convert($money, $b, [], new DefaultContext(), RoundingMode::Down)
                        // convert to cents in order to use the int
                        ->getMinorAmount()
                        ->toInt();
        return number_format($float / 100.00, 2);
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
            $partyName = (null !== $delparty ? $delparty->getPartyName() :
                null);
            return null !== $partyName ? new Party($this->t, $partyName,
                null, null, null, null, null, null, null, null, null) : null;
        }
        return null;
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
     * @param int|null $unit_id
     * @param unpR $unpR
     * @return string|null
     */
    private function unitCode(?int $unit_id, unpR $unpR): ?string
    {
        // If the unit has an extension in unitpeppol
        if (null!==$unit_id && ($unpR->repoUnitCount($unit_id) == 1)) {
            $unit_peppol = $unpR->repoUnit($unit_id);
            return $unit_peppol?->getCode();
        }
        return '';
    }

    /**
     * @param int $item_id
     * @param IIAR $iiaR
     * @return InvItemAmount|null
     */
    public function getInvItemAmount(int $item_id, IIAR $iiaR): ?InvItemAmount
    {
        return $iiaR->repoInvItemAmountquery($item_id);
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
        $country = new Country($cic, self::ISO3166_1_ALPHA2);
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
     * The above dependent functions are identical/modified PeppolHelper
     * functions.
     * Note: The integer values in the array must be kept to ensure json array
     *  encoding later
     * Related logic: see https://wtools.io/convert-json-to-php-array
     * Related logic: see https://www.storecove.com/docs/#_json_object 3.3.3.
     *  JSON Object
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
        $invoice_lines = $this->buildInvoiceLinesArray($invoice,
                                            $invoice_period, $inv, $net, $charge);
        $allowance_charges = $this->documentLevelAllowanceCharges($invoice,
                                                                  $charge->aciR);
        $taxSubtotal = $this->buildTaxSubtotalArray($invoice, $inv->iiaR, $charge->trR);
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
                /**
                 * Related logic:
                 * https://www.storecove.com/docs/#_sender_identifiers_list
                 */
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
//https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-PaymentMeans/
//                                                          cbc-PaymentMeansCode/
//https://www.storecove.com/docs#_openapi_paymentmeans
                            'code' => 'credit_transfer',
        /**
         * @var array $payment_means_array['PayeeFinancialAccount']
         * @var string $payment_means_array['PayeeFinancialAccount']['ID']
         */
                            'account' => $pm_id,
// Use Entity PaymentPeppol to generate a DateTimeImmutable integer expressed
//  as a string
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
        // Currently a key number as an integer
        /**
         * Related logic:
            http://yii3-i-4.myhost/invoice/setting/tab_index
                                                        6.2 Sender identifier
         */
        $identifier = (int) $this->s->getSetting(
                                             'storecove_sender_identifier');
        // Get the complete array
        $store_cove_sender_array =
                      StoreCoveArrays::storeCoveSenderIdentifierArray();
        /**
         * Related logic: http://yii3-i/invoice/setting/tab_index
         *                                       6.2 sender identifier basis
         */
        $identifier_basis = $this->s->getSetting(
                                        'storecove_sender_identifier_basis');
        $routing_scheme_identifier = '';
        /**
         * Search the array for the identifier to retrieve the sub array
         * @var int $key
         * @var string $value
         */
        foreach ($store_cove_sender_array as $key => $value) {
            if ($key == $identifier) {
// Use the identifier basis to retrieve either the legal or tax identifier
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
        $acp = $this->buildPeppolAccountingCustomerPartyArray(
                                                        $invoice, $inv->paR, $inv->cpR);
        $customer_partyTaxScheme = $this->buildCustomerPartyTaxScheme($acp);
        $customer_partyLegalEntity = $this->buildCustomerLegalEntity($acp);
        $customer_tax_scheme = $customer_partyTaxScheme->getTaxScheme();
        $customer_tax_id = $customer_partyTaxScheme->getCompanyId();
        $customer_legal_scheme =
                $customer_partyLegalEntity->getCompanyIdAttributeSchemeId();
        $customer_legal_id = $customer_partyLegalEntity->getCompanyId();
        $customer_physical = $this->buildCustomerPhysicalLocation($acp);
        $c_contact = $this->buildCustomerContact($acp);
        return [
                        'publicIdentifiers' => [
                            // Legal Identifier
                            0 => [
                                'scheme' => $customer_legal_scheme,
                                'id' => $customer_legal_id,
                            ],
                            // Tax Identifier
                            1 => [
                                'scheme' => $customer_tax_scheme,
                                // vat id
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
        $c_del_loc_address = $this->buildDeliveryLocationAddress();
        $c_actual_del_datetime = $this->actualDeliveryDate($invoice, $net->delRepo);
        $c_del_party = $this->deliveryParty($invoice, $net->delRepo, $net->delPartyRepo);
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
