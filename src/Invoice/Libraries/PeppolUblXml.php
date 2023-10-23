<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\Ubl\AdditionalDocumentReference;
use App\Invoice\Ubl\Address;
use App\Invoice\Ubl\Contact;
use App\Invoice\Ubl\ContractDocumentReference;
use App\Invoice\Ubl\Delivery;
use App\Invoice\Ubl\Generator;
use App\Invoice\Ubl\Invoice;
use App\Invoice\Ubl\InvoicePeriod;
use App\Invoice\Ubl\LegalMonetaryTotal;
use App\Invoice\Ubl\OrderReference;
use App\Invoice\Ubl\Party;
use App\Invoice\Ubl\PartyLegalEntity;
use App\Invoice\Ubl\PartyTaxScheme;
use App\Invoice\Ubl\PayeeFinancialAccount;
use App\Invoice\Ubl\PaymentTerms;
use App\Invoice\Ubl\PaymentMeans;
use Doctrine\Common\Collections\ArrayCollection;
use Sabre\Xml\Writer;
use Yiisoft\Translator\TranslatorInterface as Translator;
use \DateTime;

class PeppolUblXml {

  private Inv $invoice;
  private ArrayCollection $items;
  private sR $sR;
  private string $currencyCode_to;
  private array $company;
  // Each InvItem entity has an extension record InvItemAmount
  // which holds the totals of the individual InvItem
  // Note: InvAmount => totals of Inv, and ...
  //                    totals of items ie. item_subtotal, item_tax_total
  // Use $iiaR in function itemsSubtotalGroupedByTaxPercent() to get the
  // individual item's subtotal amount using the item's id.
  private iiaR $iiaR;
  private InvAmount $inv_amount;
  private Translator $t;

  /**
   * @param sR $sR
   * @param Inv $inv
   * @param iiaR $iiaR
   * @param InvAmount $inv_amount
   */
  public function __construct(sR $sR, Translator $translator, Inv $inv, iiaR $iiaR, InvAmount $inv_amount) {
    $this->invoice = $inv;
    $this->items = $inv->getItems();
    $this->sR = $sR;
    $this->t = $translator;
    $this->currencyCode_to = $sR->get_setting('currency_to');
    $this->company = $sR->get_config_company_details();
    // Use in function itemsSubtotalGroupedByTaxPercent()
    $this->iiaR = $iiaR;
    // Use in function xmlSpecifiedTradeSettlementMonetarySummation()
    $this->inv_amount = $inv_amount;
  }

  public function xml(
    ?string $profileID,
    ?string $id,
    DateTime $issueDate,
    DateTime $dueDate,
    ?string $note,    
    DateTime $taxPointDate,
    ?string $accountingCostCode,
    ?string $buyerReference,
    
    // IP
    string $start_date,
    string $end_date,
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/
    // The code of the date when the VAT becomes accountable
    // for the Seller and for the Buyer.
    string $description_code,   
    
    // OR
    ?string $order_id,
    ?string $sales_order_id,
    ?string $cdr_id,
    AdditionalDocumentReference $additionalDocumentReference,
    
    // aSP
    ?string $supplier_name,
    ?string $supplier_partyIdentificationId,
    ?string $supplier_partyIdentificationSchemeId,
    ?Address $supplier_postalAddress,
    ?Contact $supplier_contact,
    ?PartyTaxScheme $supplier_partyTaxScheme,
    ?PartyLegalEntity $supplier_partyLegalEntity,
    ?string $supplier_endpointID,
    mixed $supplier_endpointID_schemeID,
    
    // cSP
    ?string $customer_name,
    ?string $customer_partyIdentificationId,
    ?string $customer_partyIdentificationSchemeId,
    ?Address $customer_postalAddress,
    ?Contact $customer_contact,
    ?PartyTaxScheme $customer_partyTaxScheme,
    ?PartyLegalEntity $customer_partyLegalEntity,
    ?string $customer_endpointID,
    mixed $customer_endpointID_schemeID,    
    
    // D
    ?DateTime $actualDeliveryDate,
    array $deliveryLocationID_scheme,
    ?Address $deliveryLocation,
    ?Party $deliveryParty,
    // PM
    ?PayeeFinancialAccount $payeeFinancialAccount,
    string $paymentId,
    // PT
    ?string $payment_terms,
    array $allowanceCharges,
    // TT
    array $taxAmounts,
    // TST
    array $taxSubtotal, 
    
    // LMT
    float $lineExtensionAmount,
    float $taxExclusiveAmount,
    float $taxInclusiveAmount,
    float $allowanceTotalAmount,
    float $payableAmount,
    
    array $invoiceLines,
    ?bool $isCopyIndicator,
    ?string $supplierAssignedAccountID,
   
  ) : Invoice {
    $ubl_invoice = new Invoice(
      $this->sR,
      $profileID,
      $id,
      $issueDate,
      $dueDate,
      $note,
      $taxPointDate,
      $accountingCostCode,
      $buyerReference,
      new InvoicePeriod(
        $start_date, 
        $end_date, 
        $description_code
      ),
      new OrderReference(
        $order_id, 
        $sales_order_id
      ),
      new ContractDocumentReference($cdr_id),
      $additionalDocumentReference,
      // Accounting Supplier Party
      new Party(
        $this->t,
        $supplier_name, 
        $supplier_partyIdentificationId, 
        $supplier_partyIdentificationSchemeId, 
        $supplier_postalAddress,
        /**
         * Supplier Physical Location must not be supplied => null
         * Location: invoice_sqKOvgahINV107_peppol
         * Element/context: /:Invoice[1]
         * XPath test: not(cac:AccountingSupplierParty/cac:Party/cac:PhysicalLocation)
         * Error message: [UBL-CR-168]-A UBL invoice should not include the AccountingSupplierParty Party PhysicalLocation
         */
        null,
        $supplier_contact, 
        $supplier_partyTaxScheme, 
        $supplier_partyLegalEntity, 
        $supplier_endpointID, 
        $supplier_endpointID_schemeID
      ), 
      // Accounting Customer Party
      new Party(
        $this->t,
        $customer_name, 
        $customer_partyIdentificationId, 
        $customer_partyIdentificationSchemeId, 
        $customer_postalAddress,
        /**
         * Customer Physical Location must not be included => null
         * Warning
         * Location: invoice_sqKOvgahINV107_peppol
         * Element/context: /:Invoice[1]
         * XPath test: not(cac:AccountingCustomerParty/cac:Party/cac:PhysicalLocation)
         * Error message: [UBL-CR-231]-A UBL invoice should not include the AccountingCustomerParty Party PhysicalLocation
         */
        null, 
        $customer_contact, 
        $customer_partyTaxScheme, 
        $customer_partyLegalEntity,
        /**
         * Error
         * Location: invoice_8x8vShcxINV111_peppol
         * Element/context: /:Invoice[1]/cac:AccountingCustomerParty[1]/cac:Party[1]
         * XPath test: cbc:EndpointID
         * Error message: Buyer electronic address MUST be provided
         */
        $customer_endpointID, 
        $customer_endpointID_schemeID
      ),
      new Delivery(
        $actualDeliveryDate,
        $deliveryLocationID_scheme, 
        $deliveryLocation, 
        $deliveryParty
      ), 
      new PaymentMeans(
        $payeeFinancialAccount, 
        $paymentId
      ), 
      new PaymentTerms($payment_terms),
      $allowanceCharges,
      $taxAmounts,
      $taxSubtotal,
      new LegalMonetaryTotal(
        $lineExtensionAmount, 
        $taxExclusiveAmount, 
        $taxInclusiveAmount,
        $allowanceTotalAmount,
        $payableAmount, 
        $this->sR->get_setting('currency_code_to') ?: 
        $this->sR->get_setting('currency_code_from')
      ), 
      $invoiceLines,
      $isCopyIndicator,
      $supplierAssignedAccountID,
    );
    return $ubl_invoice;
  }

  /**
   *
   * @param Invoice $ubl_invoice
   * @return string
   */
  public function output(Invoice $ubl_invoice): string {
    $writer = new Writer();
    $writer->openMemory();
    $writer->setIndent(true);
    //$writer->startDocument('1.0', 'UTF-8');
    $writer->text(Generator::invoice($ubl_invoice, $this->currencyCode_to));
    $writer->endDocument();
    return $writer->outputMemory();
  }

}
