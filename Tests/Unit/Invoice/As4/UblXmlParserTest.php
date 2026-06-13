<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4ParseException;
use App\Invoice\As4\UblInvoiceData;
use App\Invoice\As4\UblXmlParser;
use App\Invoice\Ubl\Schema;
use PHPUnit\Framework\TestCase;

class UblXmlParserTest extends TestCase
{
    private function parser(): UblXmlParser
    {
        return new UblXmlParser();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function invoiceXml(string $id = 'INV-001', string $extraElements = ''): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ubl:Invoice xmlns:ubl="{$this->ns(Schema::INVOICE_NS)}"
             xmlns:cbc="{$this->ns(Schema::CBC_NS)}"
             xmlns:cac="{$this->ns(Schema::CAC_NS)}">
    <cbc:ID>{$id}</cbc:ID>
    <cbc:IssueDate>2026-01-15</cbc:IssueDate>
    <cbc:DueDate>2026-02-14</cbc:DueDate>
    <cbc:DocumentCurrencyCode>GBP</cbc:DocumentCurrencyCode>
    <cbc:Note>Test note</cbc:Note>
    <cbc:BuyerReference>PO-789</cbc:BuyerReference>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cbc:EndpointID schemeID="0088">1234567890123</cbc:EndpointID>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:LegalMonetaryTotal>
        <cbc:PayableAmount currencyID="GBP">120.00</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    {$extraElements}
</ubl:Invoice>
XML;
    }

    private function creditNoteXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ubl:CreditNote xmlns:ubl="{$this->ns(Schema::CREDIT_NOTE_NS)}"
                xmlns:cbc="{$this->ns(Schema::CBC_NS)}"
                xmlns:cac="{$this->ns(Schema::CAC_NS)}">
    <cbc:ID>CN-001</cbc:ID>
    <cbc:IssueDate>2026-01-20</cbc:IssueDate>
    <cbc:DocumentCurrencyCode>GBP</cbc:DocumentCurrencyCode>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cbc:EndpointID schemeID="0088">1234567890123</cbc:EndpointID>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:LegalMonetaryTotal>
        <cbc:PayableAmount currencyID="GBP">-50.00</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    <cac:CreditNoteLine>
        <cbc:ID>1</cbc:ID>
        <cbc:CreditedQuantity unitCode="EA">1.00</cbc:CreditedQuantity>
        <cbc:LineExtensionAmount currencyID="GBP">-50.00</cbc:LineExtensionAmount>
        <cac:Item>
            <cbc:Name>Return item</cbc:Name>
        </cac:Item>
        <cac:Price><cbc:PriceAmount currencyID="GBP">50.00</cbc:PriceAmount></cac:Price>
    </cac:CreditNoteLine>
</ubl:CreditNote>
XML;
    }

    private function lineXml(): string
    {
        return <<<XML
<cac:InvoiceLine xmlns:cbc="{$this->ns(Schema::CBC_NS)}" xmlns:cac="{$this->ns(Schema::CAC_NS)}">
    <cbc:ID>1</cbc:ID>
    <cbc:InvoicedQuantity unitCode="EA">2.00</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID="GBP">100.00</cbc:LineExtensionAmount>
    <cac:Item>
        <cbc:Name>Widget A</cbc:Name>
        <cbc:Description>A blue widget</cbc:Description>
    </cac:Item>
    <cac:Price><cbc:PriceAmount currencyID="GBP">50.00</cbc:PriceAmount></cac:Price>
</cac:InvoiceLine>
XML;
    }

    private function ns(string $uri): string
    {
        return $uri;
    }

    // ── Invoice parsing ───────────────────────────────────────────────────────

    public function testParseReturnsUblInvoiceData(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertInstanceOf(UblInvoiceData::class, $data);
    }

    public function testInvoiceNumberExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml('INV-2026-042'));
        $this->assertSame('INV-2026-042', $data->invoiceNumber);
    }

    public function testIssueDateExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame('2026-01-15', $data->issueDate->format('Y-m-d'));
    }

    public function testDueDateExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame('2026-02-14', $data->dueDate->format('Y-m-d'));
    }

    public function testMissingDueDateDefaultsToThirtyDaysAfterIssue(): void
    {
        $xml  = $this->invoiceXml();
        $xml  = preg_replace('|<cbc:DueDate>.*?</cbc:DueDate>|', '', $xml) ?? $xml;
        $data = $this->parser()->parse($xml);
        $this->assertSame('2026-02-14', $data->dueDate->format('Y-m-d'));
    }

    public function testCurrencyCodeExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame('GBP', $data->currencyCode);
    }

    public function testNoteExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame('Test note', $data->note);
    }

    public function testBuyerReferenceExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame('PO-789', $data->buyerReference);
    }

    public function testMissingNoteYieldsNull(): void
    {
        $xml  = $this->invoiceXml();
        $xml  = preg_replace('|<cbc:Note>.*?</cbc:Note>|', '', $xml) ?? $xml;
        $data = $this->parser()->parse($xml);
        $this->assertNull($data->note);
    }

    public function testSupplierEndpointIdExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame('1234567890123', $data->supplierEndpointId);
        $this->assertSame('0088', $data->supplierEndpointSchemeId);
    }

    public function testPayableAmountExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame(120.00, $data->payableAmount);
    }

    public function testDocumentTypeIsInvoice(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame('Invoice', $data->documentType);
    }

    // ── Line items ────────────────────────────────────────────────────────────

    public function testInvoiceWithNoLinesReturnsEmptyArray(): void
    {
        $data = $this->parser()->parse($this->invoiceXml());
        $this->assertSame([], $data->lines);
    }

    public function testInvoiceLineCountMatches(): void
    {
        $data = $this->parser()->parse($this->invoiceXml(extraElements: $this->lineXml()));
        $this->assertCount(1, $data->lines);
    }

    public function testLineNameExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml(extraElements: $this->lineXml()));
        $this->assertSame('Widget A', $data->lines[0]->name);
    }

    public function testLineDescriptionExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml(extraElements: $this->lineXml()));
        $this->assertSame('A blue widget', $data->lines[0]->description);
    }

    public function testLineQuantityExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml(extraElements: $this->lineXml()));
        $this->assertSame(2.00, $data->lines[0]->quantity);
    }

    public function testLineUnitCodeExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml(extraElements: $this->lineXml()));
        $this->assertSame('EA', $data->lines[0]->unitCode);
    }

    public function testLineUnitPriceExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml(extraElements: $this->lineXml()));
        $this->assertSame(50.00, $data->lines[0]->unitPrice);
    }

    public function testLineExtensionAmountExtracted(): void
    {
        $data = $this->parser()->parse($this->invoiceXml(extraElements: $this->lineXml()));
        $this->assertSame(100.00, $data->lines[0]->lineExtensionAmount);
    }

    // ── CreditNote ────────────────────────────────────────────────────────────

    public function testDocumentTypeIsCreditNote(): void
    {
        $data = $this->parser()->parse($this->creditNoteXml());
        $this->assertSame('CreditNote', $data->documentType);
    }

    public function testCreditNoteLineExtracted(): void
    {
        $data = $this->parser()->parse($this->creditNoteXml());
        $this->assertCount(1, $data->lines);
        $this->assertSame('Return item', $data->lines[0]->name);
        $this->assertSame(1.00, $data->lines[0]->quantity);
    }

    // ── Error paths ───────────────────────────────────────────────────────────

    public function testThrowsOnInvalidXml(): void
    {
        $this->expectException(As4ParseException::class);
        $this->parser()->parse('<broken xml <<');
    }

    public function testThrowsWhenIdMissing(): void
    {
        $xml = $this->invoiceXml();
        $xml = preg_replace('|<cbc:ID>.*?</cbc:ID>|', '', $xml) ?? $xml;
        $this->expectException(As4ParseException::class);
        $this->parser()->parse($xml);
    }
}
