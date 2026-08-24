<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Invoice\Ubl\Schema;
use DOMElement;
use DOMXPath;

/**
 * Parses an inbound UBL Order (Peppol BIS Ordering / Advanced Ordering,
 * T01) payload into a UblOrderData value object — the Order-side
 * counterpart to UblXmlParser, kept as its own class rather than a
 * branch inside UblXmlParser since Order's shape genuinely differs
 * (Buyer party instead of Supplier, no due date / payable amount /
 * buyer reference, cac:LineItem wrapping each line instead of the line
 * element carrying its fields directly) — the two share only the small
 * XPath-reading mechanics, via UblXmlHelperTrait.
 */
final class UblOrderXmlParser
{
    use UblXmlHelperTrait;

    /**
     * @throws As4ParseException when the payload is not valid XML or missing cbc:ID
     */
    public function parse(string $xml): UblOrderData
    {
        $doc   = $this->loadXml($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cbc', Schema::CBC_NS);
        $xpath->registerNamespace('cac', Schema::CAC_NS);

        $orderNumber = $this->text($xpath, '/*/' . Schema::CBC . 'ID');
        if ($orderNumber === '') {
            throw new As4ParseException('UBL Order missing required ' . Schema::CBC . 'ID');
        }

        $issueDate = $this->parseDate($this->text($xpath, '/*/' . Schema::CBC . 'IssueDate'));
        [$buyerEndpointId, $buyerSchemeId] = $this->extractBuyerEndpoint($xpath);

        return new UblOrderData(
            orderNumber:           $orderNumber,
            issueDate:             $issueDate,
            currencyCode:          $this->text($xpath, '/*/' . Schema::CBC . 'DocumentCurrencyCode'),
            buyerEndpointId:       $buyerEndpointId,
            buyerEndpointSchemeId: $buyerSchemeId,
            note:                  $this->nullable($xpath, '/*/' . Schema::CBC . 'Note'),
            lines:                 $this->parseLines($xpath),
        );
    }

    /** @return array{string, string} */
    private function extractBuyerEndpoint(DOMXPath $xpath): array
    {
        $query = '/*/' . Schema::CAC . 'BuyerCustomerParty/'
               . Schema::CAC . 'Party/' . Schema::CBC . 'EndpointID';

        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return ['', ''];
        }
        $node = $nodes->item(0);
        if (!($node instanceof DOMElement)) {
            return [$node?->textContent ?? '', ''];
        }
        return [$node->textContent, $node->getAttribute('schemeID')];
    }

    /** @return UblOrderLineData[] */
    private function parseLines(DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/*/' . Schema::CAC . 'OrderLine/' . Schema::CAC . 'LineItem');
        if ($nodes === false) {
            return [];
        }
        $lines = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $lines[] = $this->parseLine($xpath, $node);
            }
        }
        return $lines;
    }

    private function parseLine(DOMXPath $xpath, DOMElement $lineItemNode): UblOrderLineData
    {
        $unitCode = '';
        $qtyNodes = $xpath->query(Schema::CBC . 'Quantity', $lineItemNode);
        if ($qtyNodes !== false && $qtyNodes->length > 0) {
            $n = $qtyNodes->item(0);
            if ($n instanceof DOMElement) {
                $unitCode = $n->getAttribute('unitCode');
            }
        }

        return new UblOrderLineData(
            lineId:              $this->rel($xpath, Schema::CBC . 'ID', $lineItemNode),
            name:                $this->rel($xpath, Schema::CAC . 'Item/' . Schema::CBC . 'Name', $lineItemNode),
            description:         $this->rel($xpath, Schema::CAC . 'Item/' . Schema::CBC . 'Description', $lineItemNode),
            quantity:            (float) $this->rel($xpath, Schema::CBC . 'Quantity', $lineItemNode),
            unitCode:            $unitCode,
            unitPrice:           (float) $this->rel(
                $xpath,
                Schema::CAC . 'Price/' . Schema::CBC . 'PriceAmount',
                $lineItemNode,
            ),
            lineExtensionAmount: (float) $this->rel($xpath, Schema::CBC . 'LineExtensionAmount', $lineItemNode),
        );
    }
}
