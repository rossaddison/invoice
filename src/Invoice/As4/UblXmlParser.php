<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Invoice\Ubl\Schema;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses an inbound UBL 2.4 Invoice or CreditNote XML payload into a
 * UblInvoiceData value object, using the namespace constants from
 * App\Invoice\Ubl\Schema as the single source of truth for UBL namespaces.
 */
final class UblXmlParser
{
    /**
     * @throws As4ParseException when the payload is not valid XML or missing cbc:ID
     */
    public function parse(string $xml): UblInvoiceData
    {
        $doc   = $this->loadXml($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cbc', Schema::CBC_NS);
        $xpath->registerNamespace('cac', Schema::CAC_NS);

        $root         = $doc->documentElement;
        $documentType = ($root !== null && $root->localName === 'CreditNote') ? 'CreditNote' : 'Invoice';
        $lineTag      = $documentType === 'CreditNote' ? Schema::CAC . 'CreditNoteLine' : Schema::CAC . 'InvoiceLine';
        $qtyTag       = $documentType === 'CreditNote' ? Schema::CBC . 'CreditedQuantity' : Schema::CBC . 'InvoicedQuantity';

        $invoiceNumber = $this->text($xpath, '/*/'. Schema::CBC . 'ID');
        if ($invoiceNumber === '') {
            throw new As4ParseException('UBL document missing required ' . Schema::CBC . 'ID');
        }

        $issueDate  = $this->parseDate($this->text($xpath, '/*/' . Schema::CBC . 'IssueDate'));
        $dueDateStr = $this->text($xpath, '/*/' . Schema::CBC . 'DueDate');
        $dueDate    = $dueDateStr !== '' ? $this->parseDate($dueDateStr) : $issueDate->modify('+30 days');

        [$supplierEndpointId, $supplierSchemeId] = $this->extractSupplierEndpoint($xpath);

        $payableAmount = (float) $this->text(
            $xpath,
            '/*/' . Schema::CAC . 'LegalMonetaryTotal/' . Schema::CBC . 'PayableAmount',
        );
        $currencyCode  = $this->text($xpath, '/*/' . Schema::CBC . 'DocumentCurrencyCode');

        return new UblInvoiceData(
            invoiceNumber:            $invoiceNumber,
            issueDate:                $issueDate,
            dueDate:                  $dueDate,
            currencyCode:             $currencyCode,
            supplierEndpointId:       $supplierEndpointId,
            supplierEndpointSchemeId: $supplierSchemeId,
            payableAmount:            $payableAmount,
            note:                     $this->nullable($xpath, '/*/' . Schema::CBC . 'Note'),
            buyerReference:           $this->nullable($xpath, '/*/' . Schema::CBC . 'BuyerReference'),
            lines:                    $this->parseLines($xpath, $lineTag, $qtyTag),
            documentType:             $documentType,
        );
    }

    private function loadXml(string $xml): DOMDocument
    {
        $doc  = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok   = $doc->loadXML($xml);
        libxml_use_internal_errors($prev);
        libxml_clear_errors();

        if (!$ok) {
            throw new As4ParseException('UBL payload is not valid XML');
        }
        return $doc;
    }

    /** @return array{string, string} */
    private function extractSupplierEndpoint(DOMXPath $xpath): array
    {
        $query = '/*/' . Schema::CAC . 'AccountingSupplierParty/'
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

    /** @return UblInvoiceLineData[] */
    private function parseLines(DOMXPath $xpath, string $lineTag, string $qtyTag): array
    {
        $nodes = $xpath->query('/*/' . $lineTag);
        if ($nodes === false) {
            return [];
        }
        $lines = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $lines[] = $this->parseLine($xpath, $node, $qtyTag);
            }
        }
        return $lines;
    }

    private function parseLine(DOMXPath $xpath, DOMElement $lineNode, string $qtyTag): UblInvoiceLineData
    {
        $unitCode = '';
        $qtyNodes = $xpath->query($qtyTag, $lineNode);
        if ($qtyNodes !== false && $qtyNodes->length > 0) {
            $n = $qtyNodes->item(0);
            if ($n instanceof DOMElement) {
                $unitCode = $n->getAttribute('unitCode');
            }
        }

        return new UblInvoiceLineData(
            name:                $this->rel($xpath, Schema::CAC . 'Item/' . Schema::CBC . 'Name', $lineNode),
            description:         $this->rel($xpath, Schema::CAC . 'Item/' . Schema::CBC . 'Description', $lineNode),
            quantity:            (float) $this->rel($xpath, $qtyTag, $lineNode),
            unitCode:            $unitCode,
            unitPrice:           (float) $this->rel($xpath, Schema::CAC . 'Price/' . Schema::CBC . 'PriceAmount', $lineNode),
            lineExtensionAmount: (float) $this->rel($xpath, Schema::CBC . 'LineExtensionAmount', $lineNode),
            peppolPoItemId:      $this->rel($xpath, Schema::CAC . 'OrderLineReference/' . Schema::CBC . 'LineID', $lineNode),
            peppolPoLineId:      $this->rel($xpath, Schema::CAC . 'DocumentReference/' . Schema::CBC . 'ID', $lineNode),
        );
    }

    private function text(DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        return $nodes->item(0)?->textContent ?? '';
    }

    private function rel(DOMXPath $xpath, string $query, DOMElement $context): string
    {
        $nodes = $xpath->query($query, $context);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        return $nodes->item(0)?->textContent ?? '';
    }

    private function nullable(DOMXPath $xpath, string $query): ?string
    {
        $val = $this->text($xpath, $query);
        return $val !== '' ? $val : null;
    }

    private function parseDate(string $text): DateTimeImmutable
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $text);
        return $d !== false ? $d : new DateTimeImmutable('now');
    }
}
