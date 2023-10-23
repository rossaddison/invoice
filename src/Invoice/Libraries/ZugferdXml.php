<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use Yiisoft\Html\Html;
use Doctrine\Common\Collections\ArrayCollection;
use DOMDocument;
use DOMElement;

class ZugferdXml
{
    var Inv $invoice;
    var ArrayCollection $items;
    var sR $sR;
    var string $currencyCode;
    var array $company;
    // Each InvItem entity has an extension record InvItemAmount
    // which holds the totals of the individual InvItem 
    // Note: InvAmount => totals of Inv, and ...
    //                    totals of items ie. item_subtotal, item_tax_total 
    
    // Use $iiaR in function itemsSubtotalGroupedByTaxPercent() to get the 
    // individual item's subtotal amount using the item's id.
    var iiaR $iiaR;
    var InvAmount $inv_amount;
    
    /**
     * @param sR $sR
     * @param Inv $inv
     * @param iiaR $iiaR
     * @param InvAmount $inv_amount
     */
    public function __construct(sR $sR, Inv $inv, iiaR $iiaR, InvAmount $inv_amount)
    {
        $this->invoice = $inv;
        $this->items = $inv->getItems();
        $this->sR = $sR;
        $this->currencyCode = $sR->get_setting('currency_code');
        $this->company = $sR->get_config_company_details();
        // Use in function itemsSubtotalGroupedByTaxPercent()
        $this->iiaR = $iiaR;
        // Use in function xmlSpecifiedTradeSettlementMonetarySummation()
        $this->inv_amount = $inv_amount;
    }

    /**
     * @return string
     */
    public function xml() : string
    {
        /** @var DOMDocument $doc */
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $doc->createElement('rsm:CrossIndustryDocument');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xmlns:rsm', 'urn:ferd:CrossIndustryDocument:invoice:1p0');
        $root->setAttribute('xmlns:ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:12');
        $root->setAttribute('xmlns:udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:15');
        $root->appendChild($this->xmlSpecifiedExchangedDocumentContext($doc));
        $root->appendChild($this->xmlHeaderExchangedDocument($doc));
        $root->appendChild($this->xmlSpecifiedSupplyChainTradeTransaction($doc));
        $doc->appendChild($root);
        return $doc->saveXML();
    }
    
    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlSpecifiedExchangedDocumentContext(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('rsm:SpecifiedExchangedDocumentContext');
        $guidelineNode = $doc->createElement('ram:GuidelineSpecifiedDocumentContextParameter');
        $guidelineNode->appendChild($doc->createElement('ram:ID', 'urn:ferd:CrossIndustryDocument:invoice:1p0:basic'));
        $node->appendChild($guidelineNode);
        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlHeaderExchangedDocument(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('rsm:HeaderExchangedDocument');
        $node->appendChild($doc->createElement('ram:ID', $this->invoice->getNumber() ?? ''));
        $node->appendChild($doc->createElement('ram:Name', $this->sR->trans('invoice')));
        $node->appendChild($doc->createElement('ram:TypeCode', (string)380));

        // IssueDateTime
        $dateNode = $doc->createElement('ram:IssueDateTime');
        $dateNode->appendChild($this->dateElement($doc, $this->invoice->getDate_created()));
        $node->appendChild($dateNode);

        // IncludedNote
        $noteNode = $doc->createElement('ram:IncludedNote');
        $noteNode->appendChild($doc->createElement('ram:Content', Html::encode($this->invoice->getTerms())));
        $node->appendChild($noteNode);

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param \DateTimeImmutable $date
     * @return DOMElement
     */
    protected function dateElement(DOMDocument $doc, \DateTimeImmutable $date) : DOMElement
    {
        $el = $doc->createElement('udt:DateTimeString', $this->zugferdFormattedDate($date) ?? 'YYYYmmdd');
        $el->setAttribute('format', (string)102);
        return $el;
    }

    /**
     * 
     * @param \DateTimeImmutable $date
     * @return string
     */
    protected function zugferdFormattedDate(\DateTimeImmutable $date) : string|null
    {
        $return_date = \DateTime::createFromFormat('Y-m-d', $date->format('Y-m-d'));
        return $return_date->format('Ymd');
    }

    /**
     * @return DOMElement
     */
    protected function xmlSpecifiedSupplyChainTradeTransaction(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('rsm:SpecifiedSupplyChainTradeTransaction');
        $node->appendChild($this->xmlApplicableSupplyChainTradeAgreement($doc));
        $node->appendChild($this->xmlApplicableSupplyChainTradeDelivery($doc));
        $node->appendChild($this->xmlApplicableSupplyChainTradeSettlement($doc));
        /** 
         * @var int $index
         * @var \App\Invoice\Entity\InvItem $item
         */
        foreach ($this->invoice->getItems() as $index => $item) {            
            $node->appendChild($this->xmlIncludedSupplyChainTradeLineItem($doc, $index + 1, $item));
        }
        return $node;
    }

    /**
     * @return DOMElement
     */
    protected function xmlApplicableSupplyChainTradeAgreement(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('ram:ApplicableSupplyChainTradeAgreement');
        $node->appendChild($this->xmlSellerTradeParty($doc));
        $node->appendChild($this->xmlBuyerTradeParty($doc));
        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlSellerTradeParty(DOMDocument $doc) : DOMElement
    {
        /** @var string $this->company['name'] */        
        $name = $this->company['name'];
        /** @var string $this->company['address_1'] */        
        $address_1 = $this->company['address_1'];
        /** @var string $this->company['address_2'] */        
        $address_2 = $this->company['address_2'];
        /** @var string $this->company['city'] */        
        $city = $this->company['city'];
        /** @var string $this->company['country'] */        
        $country = $this->company['country'];
        /** @var string $this->company['zip'] */        
        $zip = $this->company['zip'];
        
        $node = $doc->createElement('ram:SellerTradeParty');
        $node->appendChild($doc->createElement('ram:Name', Html::encode($name)));

        // PostalTradeAddress
        $addressNode = $doc->createElement('ram:PostalTradeAddress');
        $addressNode->appendChild($doc->createElement('ram:LineOne', Html::encode($address_1)));
        $addressNode->appendChild($doc->createElement('ram:LineTwo', Html::encode($address_2)));
        $addressNode->appendChild($doc->createElement('ram:CityName', Html::encode($city)));        
        $addressNode->appendChild($doc->createElement('ram:PostcodeCode', Html::encode($zip)));
        $addressNode->appendChild($doc->createElement('ram:CountryID', Html::encode($country)));

        $node->appendChild($addressNode);
        return $node;
    }

    /**
     * @return DOMElement
     */
    protected function xmlBuyerTradeParty(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('ram:BuyerTradeParty');
        $node->appendChild($doc->createElement('ram:Name', Html::encode($this->invoice->getClient()?->getClient_name())));

        // PostalTradeAddress
        $addressNode = $doc->createElement('ram:PostalTradeAddress');
        $addressNode->appendChild($doc->createElement('ram:PostcodeCode', Html::encode($this->invoice->getClient()?->getClient_zip())));
        $addressNode->appendChild($doc->createElement('ram:LineOne', Html::encode($this->invoice->getClient()?->getClient_address_1())));
        $addressNode->appendChild($doc->createElement('ram:LineTwo', Html::encode($this->invoice->getClient()?->getClient_address_2())));
        $addressNode->appendChild($doc->createElement('ram:CityName', Html::encode($this->invoice->getClient()?->getClient_city())));
        $addressNode->appendChild($doc->createElement('ram:CountryID', Html::encode($this->invoice->getClient()?->getClient_country())));
        $node->appendChild($addressNode);

        // SpecifiedTaxRegistration
        $node->appendChild($this->xmlSpecifiedTaxRegistration($doc, 'VA', $this->invoice->getClient()?->getClient_vat_id() ?? ''));
        $node->appendChild($this->xmlSpecifiedTaxRegistration($doc, 'FC', Html::encode($this->invoice->getClient()?->getClient_tax_code())));

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param string $schemeID
     * @param string $content
     * @return DOMElement
     */
    protected function xmlSpecifiedTaxRegistration(DOMDocument $doc, string $schemeID, string $content) : DOMElement
    {
        $node = $doc->createElement('ram:SpecifiedTaxRegistration');
        $el = $doc->createElement('ram:ID', $content);
        $el->setAttribute('schemeID', $schemeID);
        $node->appendChild($el);
        return $node;
    }

    /**
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlApplicableSupplyChainTradeDelivery(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('ram:ApplicableSupplyChainTradeDelivery');

        // ActualDeliverySupplyChainEvent
        $eventNode = $doc->createElement('ram:ActualDeliverySupplyChainEvent');
        $dateNode = $doc->createElement('ram:OccurrenceDateTime');
        $dateNode->appendChild($this->dateElement($doc, $this->invoice->getDate_created()));
        $eventNode->appendChild($dateNode);

        $node->appendChild($eventNode);
        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlApplicableSupplyChainTradeSettlement(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('ram:ApplicableSupplyChainTradeSettlement');

        $node->appendChild($doc->createElement('ram:PaymentReference', $this->invoice->getNumber() ?? ''));
        $node->appendChild($doc->createElement('ram:InvoiceCurrencyCode', $this->currencyCode));

        /**
         *  @var float $percent
         *  @var float $subtotal
         */
        foreach ($this->itemsSubtotalGroupedByTaxPercent() as $percent => $subtotal) {
            $node->appendChild($this->xmlApplicableTradeTax($doc, $percent, $subtotal));
        }

        // sums
        $node->appendChild($this->xmlSpecifiedTradeSettlementMonetarySummation($doc));

        return $node;
    }

    /**
     * @return array
     */
    protected function itemsSubtotalGroupedByTaxPercent() : array
    {
        $result = [];
        /**
         * @var \App\Invoice\Entity\InvItem $item
         */
        foreach ($this->invoice->getItems() as $item) {
            if ($item->getTaxRate()?->getTax_rate_percent() == 0) {
                continue;
            }
            $key = (int)$item->getTaxRate()?->getTax_rate_percent(); 
            if (!isset($result[$key])) {
                $result[$key] = 0;
            }
            $item_id = $item->getId();
            /** @var \App\Invoice\Entity\InvItemAmount $inv_item_amount */
            $inv_item_amount = $this->iiaR->repoInvItemAmountquery((string)$item_id);
            
            $result[$key] += $inv_item_amount->getSubtotal() ?? 0.00;
        }
        return $result;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param float $percent
     * @param float $subtotal
     * @return DOMElement
     */
    protected function xmlApplicableTradeTax(DOMDocument $doc, float $percent, float $subtotal) : DOMElement
    {
        $node = $doc->createElement('ram:ApplicableTradeTax');        $node->appendChild($this->currencyElement($doc, 'ram:CalculatedAmount', $subtotal * $percent / 100));
        $node->appendChild($doc->createElement('ram:TypeCode', 'VAT'));
        $node->appendChild($this->currencyElement($doc, 'ram:BasisAmount', $subtotal));
        $node->appendChild($doc->createElement('ram:CategoryCode', 'S'));
        $node->appendChild($doc->createElement('ram:ApplicablePercent', (string)$percent));
        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param string $name
     * @param float $amount
     * @param int $nb_decimals
     * @return DOMElement
     */
    protected function currencyElement(DOMDocument $doc, string $name, float $amount, int $nb_decimals = 2) : DOMElement
    {
        $el = $doc->createElement($name, $this->zugferdFormattedFloat($amount, $nb_decimals));
        $el->setAttribute('currencyID', $this->currencyCode);
        return $el;
    }

    /**
     * 
     * @param float $amount
     * @param int $nb_decimals
     * @return string
     */   
    protected function zugferdFormattedFloat(float $amount, int $nb_decimals = 2) : string 
    {
        return number_format($amount, $nb_decimals);
    }

    /**
     * 
     * @param DOMDocument $doc
     * @return DOMElement
     */
    protected function xmlSpecifiedTradeSettlementMonetarySummation(DOMDocument $doc) : DOMElement
    {
        $node = $doc->createElement('ram:SpecifiedTradeSettlementMonetarySummation');
        $node->appendChild($this->currencyElement($doc, 'ram:LineTotalAmount', $this->inv_amount->getItem_subtotal() ?: 0.00));
        $node->appendChild($this->currencyElement($doc, 'ram:ChargeTotalAmount', 0));
        $node->appendChild($this->currencyElement($doc, 'ram:AllowanceTotalAmount', 0));
        $node->appendChild($this->currencyElement($doc, 'ram:TaxBasisTotalAmount', $this->inv_amount->getItem_subtotal() ?: 0.00));
        $node->appendChild($this->currencyElement($doc, 'ram:TaxTotalAmount', $this->inv_amount->getItem_tax_total() ?: 0.00));
        $node->appendChild($this->currencyElement($doc, 'ram:GrandTotalAmount', $this->inv_amount->getTotal() ?? 0.00));
        $node->appendChild($this->currencyElement($doc, 'ram:TotalPrepaidAmount', $this->inv_amount->getPaid() ?? 0.00));
        $node->appendChild($this->currencyElement($doc, 'ram:DuePayableAmount', $this->inv_amount->getBalance() ?? 0.00));
        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param int $lineNumber
     * @param InvItem $item
     * @return DOMElement
     */
    protected function xmlIncludedSupplyChainTradeLineItem(DOMDocument $doc, int $lineNumber, InvItem $item) : DOMElement
    {
        $node = $doc->createElement('ram:IncludedSupplyChainTradeLineItem');

        // AssociatedDocumentLineDocument
        $lineNode = $doc->createElement('ram:AssociatedDocumentLineDocument');
        $lineNode->appendChild($doc->createElement('ram:LineID', (string)$lineNumber));
        $node->appendChild($lineNode);

        // SpecifiedSupplyChainTradeAgreement
        $node->appendChild($this->xmlSpecifiedSupplyChainTradeAgreement($doc,  $item));

        // SpecifiedSupplyChainTradeDelivery
        $deliveyNode = $doc->createElement('ram:SpecifiedSupplyChainTradeDelivery');
        $deliveyNode->appendChild($this->quantityElement($doc, 'ram:BilledQuantity', $item->getQuantity() ?? 0.00));
        $node->appendChild($deliveyNode);

        // SpecifiedSupplyChainTradeSettlement
        $node->appendChild($this->xmlSpecifiedSupplyChainTradeSettlement($doc, $item));

        // SpecifiedTradeProduct
        $tradeNode = $doc->createElement('ram:SpecifiedTradeProduct');
        $tradeNode->appendChild($doc->createElement('ram:Name', Html::encode($item->getName()) . "\n" . Html::encode($item->getDescription())));
        $node->appendChild($tradeNode);

        return $node;
    }
    
    /**
     * 
     * @param DOMDocument $doc
     * @param InvItem $item
     * @return DOMElement
     */
    protected function xmlSpecifiedSupplyChainTradeAgreement(DOMDocument $doc, InvItem $item) : DOMElement
    {
        $node = $doc->createElement('ram:SpecifiedSupplyChainTradeAgreement');

        // GrossPriceProductTradePrice
        $grossPriceNode = $doc->createElement('ram:GrossPriceProductTradePrice');
        $grossPriceNode->appendChild($this->currencyElement($doc, 'ram:ChargeAmount', $item->getPrice() ?? 0.00, 4));
        $node->appendChild($grossPriceNode);

        // NetPriceProductTradePrice
        $netPriceNode = $doc->createElement('ram:NetPriceProductTradePrice');
        $netPriceNode->appendChild($this->currencyElement($doc, 'ram:ChargeAmount', $item->getPrice() ?? 0.00, 4));
        $node->appendChild($netPriceNode);

        return $node;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param string $name
     * @param float $quantity
     * @return DOMElement
     */
    protected function quantityElement(DOMDocument $doc, string $name, float $quantity) : DOMElement
    {
        $el = $doc->createElement($name, $this->zugferdFormattedFloat($quantity, 4));
        $el->setAttribute('unitCode', 'C62');
        return $el;
    }

    /**
     * 
     * @param DOMDocument $doc
     * @param InvItem $item
     * @return DOMElement
     */
    protected function xmlSpecifiedSupplyChainTradeSettlement(DOMDocument $doc, InvItem $item) : DOMElement
    {
        $node = $doc->createElement('ram:SpecifiedSupplyChainTradeSettlement');

        // ApplicableTradeTax
        if ($item->getTaxRate()?->getTax_rate_percent() > 0) {
            $taxNode = $doc->createElement('ram:ApplicableTradeTax');
            $taxNode->appendChild($doc->createElement('ram:TypeCode', 'VAT'));
            $taxNode->appendChild($doc->createElement('ram:ApplicablePercent', (string)$item->getTaxRate()?->getTax_rate_percent()));
            $node->appendChild($taxNode);
        }

        // SpecifiedTradeSettlementMonetarySummation
        $sumNode = $doc->createElement('ram:SpecifiedTradeSettlementMonetarySummation');
        $sumNode->appendChild($this->currencyElement($doc, 'ram:LineTotalAmount', $this->inv_amount->getItem_subtotal() ?: 0.00));
        $node->appendChild($sumNode);

        return $node;
    }
}
