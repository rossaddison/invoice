<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4ParseException;
use App\Invoice\As4\UblOrderData;
use App\Invoice\As4\UblOrderXmlParser;
use App\Invoice\Ubl\Schema;
use PHPUnit\Framework\TestCase;

class UblOrderXmlParserTest extends TestCase
{
    private function parser(): UblOrderXmlParser
    {
        return new UblOrderXmlParser();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function orderXml(string $id = 'PO-001', string $extraElements = ''): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ubl:Order xmlns:ubl="{$this->ns(Schema::ORDER_NS)}"
           xmlns:cbc="{$this->ns(Schema::CBC_NS)}"
           xmlns:cac="{$this->ns(Schema::CAC_NS)}">
    <cbc:ID>{$id}</cbc:ID>
    <cbc:IssueDate>2026-06-01</cbc:IssueDate>
    <cbc:DocumentCurrencyCode>GBP</cbc:DocumentCurrencyCode>
    <cbc:Note>Deliver to loading bay 3</cbc:Note>
    <cac:BuyerCustomerParty>
        <cac:Party>
            <cbc:EndpointID schemeID="0088">9876543210987</cbc:EndpointID>
        </cac:Party>
    </cac:BuyerCustomerParty>
    {$extraElements}
</ubl:Order>
XML;
    }

    private function lineXml(): string
    {
        return <<<XML
<cac:OrderLine xmlns:cbc="{$this->ns(Schema::CBC_NS)}" xmlns:cac="{$this->ns(Schema::CAC_NS)}">
    <cac:LineItem>
        <cbc:ID>1</cbc:ID>
        <cbc:Quantity unitCode="EA">4.00</cbc:Quantity>
        <cbc:LineExtensionAmount currencyID="GBP">200.00</cbc:LineExtensionAmount>
        <cac:Price><cbc:PriceAmount currencyID="GBP">50.00</cbc:PriceAmount></cac:Price>
        <cac:Item>
            <cbc:Name>Widget A</cbc:Name>
            <cbc:Description>A blue widget</cbc:Description>
        </cac:Item>
    </cac:LineItem>
</cac:OrderLine>
XML;
    }

    private function ns(string $uri): string
    {
        return $uri;
    }

    // ── Header ────────────────────────────────────────────────────────────────

    public function testParseReturnsUblOrderData(): void
    {
        $data = $this->parser()->parse($this->orderXml());
        $this->assertInstanceOf(UblOrderData::class, $data);
    }

    public function testOrderNumberExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml('PO-2026-042'));
        $this->assertSame('PO-2026-042', $data->orderNumber);
    }

    public function testIssueDateExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml());
        $this->assertSame('2026-06-01', $data->issueDate->format('Y-m-d'));
    }

    public function testCurrencyCodeExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml());
        $this->assertSame('GBP', $data->currencyCode);
    }

    public function testNoteExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml());
        $this->assertSame('Deliver to loading bay 3', $data->note);
    }

    public function testMissingNoteYieldsNull(): void
    {
        $xml  = $this->orderXml();
        $xml  = preg_replace('|<cbc:Note>.*?</cbc:Note>|', '', $xml) ?? $xml;
        $data = $this->parser()->parse($xml);
        $this->assertNull($data->note);
    }

    public function testBuyerEndpointIdExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml());
        $this->assertSame('9876543210987', $data->buyerEndpointId);
        $this->assertSame('0088', $data->buyerEndpointSchemeId);
    }

    public function testOrderWithNoLinesReturnsEmptyArray(): void
    {
        $data = $this->parser()->parse($this->orderXml());
        $this->assertSame([], $data->lines);
    }

    // ── Lines ─────────────────────────────────────────────────────────────────

    public function testLineCountMatches(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertCount(1, $data->lines);
    }

    public function testLineIdExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertSame('1', $data->lines[0]->lineId);
    }

    public function testLineNameExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertSame('Widget A', $data->lines[0]->name);
    }

    public function testLineDescriptionExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertSame('A blue widget', $data->lines[0]->description);
    }

    public function testLineQuantityExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertSame(4.00, $data->lines[0]->quantity);
    }

    public function testLineUnitCodeExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertSame('EA', $data->lines[0]->unitCode);
    }

    public function testLineUnitPriceExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertSame(50.00, $data->lines[0]->unitPrice);
    }

    public function testLineExtensionAmountExtracted(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml()));
        $this->assertSame(200.00, $data->lines[0]->lineExtensionAmount);
    }

    public function testTwoLinesBothParsed(): void
    {
        $data = $this->parser()->parse($this->orderXml(extraElements: $this->lineXml() . $this->lineXml()));
        $this->assertCount(2, $data->lines);
    }

    // ── Error paths ───────────────────────────────────────────────────────────

    public function testThrowsOnInvalidXml(): void
    {
        $this->expectException(As4ParseException::class);
        $this->parser()->parse('<broken xml <<');
    }

    public function testThrowsWhenIdMissing(): void
    {
        $xml = $this->orderXml();
        $xml = preg_replace('|<cbc:ID>.*?</cbc:ID>|', '', $xml) ?? $xml;
        $this->expectException(As4ParseException::class);
        $this->parser()->parse($xml);
    }
}
