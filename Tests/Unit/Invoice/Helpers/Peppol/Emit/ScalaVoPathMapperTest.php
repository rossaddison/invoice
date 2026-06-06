<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Helpers\Peppol\Emit;

use App\Invoice\Helpers\Peppol\Emit\ScalaVoPathMapper;
use PHPUnit\Framework\TestCase;

class ScalaVoPathMapperTest extends TestCase
{
    private ScalaVoPathMapper $mapper;

    #[\Override]
    protected function setUp(): void
    {
        $this->mapper = new ScalaVoPathMapper();
    }

    // ── Self / empty ───────────────────────────────────────────────────────────

    public function testSelfDotReturnsContextVar(): void
    {
        $this->assertSame('v', $this->mapper->map('.'));
    }

    public function testEmptyStringReturnsContextVar(): void
    {
        $this->assertSame('v', $this->mapper->map(''));
    }

    public function testCustomContextVarIsUsedForSelf(): void
    {
        $this->assertSame('e', $this->mapper->map('.', 'e'));
    }

    // ── Simple cbc: scalar fields ──────────────────────────────────────────────

    public function testCbcIdMapsToCamelId(): void
    {
        $this->assertSame('v.id', $this->mapper->map('cbc:ID'));
    }

    public function testCbcAmountMapsToCamelAmount(): void
    {
        $this->assertSame('v.amount', $this->mapper->map('cbc:Amount'));
    }

    public function testCbcCurrencyIdMapsWithIdSuffix(): void
    {
        // 'ID' suffix → 'Id' by toCamel
        $this->assertSame('v.currencyId', $this->mapper->map('cbc:CurrencyID'));
    }

    public function testCbcInvoiceTypeCodeMapsToCamelCase(): void
    {
        $this->assertSame('v.invoiceTypeCode', $this->mapper->map('cbc:InvoiceTypeCode'));
    }

    // ── Plural cac: elements (Seq properties) ─────────────────────────────────

    public function testCacInvoiceLineMapsToInvoiceLines(): void
    {
        $this->assertSame('v.invoiceLines', $this->mapper->map('cac:InvoiceLine'));
    }

    public function testCacTaxTotalMapsToTaxTotals(): void
    {
        $this->assertSame('v.taxTotals', $this->mapper->map('cac:TaxTotal'));
    }

    public function testCacAllowanceChargeMapsToAllowanceCharges(): void
    {
        $this->assertSame('v.allowanceCharges', $this->mapper->map('cac:AllowanceCharge'));
    }

    // ── Plural cac: with nested path ───────────────────────────────────────────

    public function testPluralWithChildPathGeneratesMapExpression(): void
    {
        $this->assertSame(
            'v.invoiceLines.map(e => e.id)',
            $this->mapper->map('cac:InvoiceLine/cbc:ID'),
        );
    }

    public function testPluralWithDeepChildPathGeneratesMapExpression(): void
    {
        $this->assertSame(
            'v.taxTotals.map(e => e.taxAmount)',
            $this->mapper->map('cac:TaxTotal/cbc:TaxAmount'),
        );
    }

    // ── Attribute paths ────────────────────────────────────────────────────────

    public function testAtAttributeMapsToProperty(): void
    {
        $this->assertSame('v.currencyId', $this->mapper->map('@currencyID'));
    }

    public function testAtAmountAttributeMapsToCamelCase(): void
    {
        $this->assertSame('v.amount', $this->mapper->map('@amount'));
    }

    // ── OVERRIDES ──────────────────────────────────────────────────────────────

    public function testAccountingSupplierPartyOverride(): void
    {
        $this->assertSame('v.supplier', $this->mapper->map('cac:AccountingSupplierParty'));
    }

    public function testAccountingCustomerPartyOverride(): void
    {
        $this->assertSame('v.customer', $this->mapper->map('cac:AccountingCustomerParty'));
    }

    public function testLegalMonetaryTotalOverride(): void
    {
        $this->assertSame('v.legalMonetaryTotal', $this->mapper->map('cac:LegalMonetaryTotal'));
    }

    public function testOrderReferenceIdOverride(): void
    {
        $this->assertSame('v.orderReference.id', $this->mapper->map('cac:OrderReference/cbc:ID'));
    }

    public function testTaxSchemeIdOverride(): void
    {
        $this->assertSame('v.taxSchemeId', $this->mapper->map('cac:TaxScheme/cbc:ID'));
    }

    // ── Variable path ($x/…) ──────────────────────────────────────────────────

    public function testBareVariableRefStripsLeadingDollar(): void
    {
        $this->assertSame('x', $this->mapper->map('$x'));
    }

    public function testVariableRefWithChildPath(): void
    {
        $this->assertSame('x.id', $this->mapper->map('$x/cbc:ID'));
    }

    public function testVariableRefWithCamelCaseChild(): void
    {
        $this->assertSame('x.invoiceTypeCode', $this->mapper->map('$x/cbc:InvoiceTypeCode'));
    }

    // ── isArrayPath ────────────────────────────────────────────────────────────

    public function testIsArrayPathTrueForInvoiceLine(): void
    {
        $this->assertTrue($this->mapper->isArrayPath('cac:InvoiceLine'));
    }

    public function testIsArrayPathTrueForTaxTotal(): void
    {
        $this->assertTrue($this->mapper->isArrayPath('cac:TaxTotal'));
    }

    public function testIsArrayPathFalseForScalarField(): void
    {
        $this->assertFalse($this->mapper->isArrayPath('cbc:ID'));
    }

    public function testIsArrayPathFalseForSingularCac(): void
    {
        $this->assertFalse($this->mapper->isArrayPath('cac:AccountingSupplierParty'));
    }

    public function testIsArrayPathFalseForSelf(): void
    {
        $this->assertFalse($this->mapper->isArrayPath('.'));
    }

    // ── Custom contextVar propagated through steps ─────────────────────────────

    public function testCustomContextVarPropagates(): void
    {
        $this->assertSame('line.id', $this->mapper->map('cbc:ID', 'line'));
    }
}
