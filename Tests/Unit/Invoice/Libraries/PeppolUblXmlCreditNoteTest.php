<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Libraries;

use App\Invoice\Libraries\{
    PeppolFinancialData, PeppolInvoiceDates, PeppolInvoiceHeader,
    PeppolInvoiceReferences, PeppolPaymentData, PeppolUblXml
};
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Ubl\{
    Address, AdditionalDocumentReference, Delivery, InvoicePeriod,
    InvoiceTypeCode, LegalMonetaryTotal, OrderReference, Party, PaymentMeans,
    PaymentTerms, Schema
};
use DateTime;
use PHPUnit\Framework\TestCase;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * A credit note is generated on the same code path as a regular invoice
 * (PeppolHelper::generateInvoicePeppolUblXmlTempFile -> PeppolUblXml -> Ubl\Generator),
 * distinguished only by a boolean flag threaded through from
 * PeppolHelper::isCreditNote(). Before this fix, PeppolUblXml always built an
 * Ubl\Invoice and Ubl\Generator::invoice() hardcoded the 'Invoice' root tag
 * and Invoice-2 namespace regardless of which object was passed in — so a
 * credit note's outbound XML was indistinguishable from a normal invoice at
 * the document-type level (wrong root element, wrong namespace), even though
 * InvoiceTypeCode itself was already correct (381).
 *
 * These tests exercise the real xml()/output() pipeline end-to-end (real
 * Ubl\Invoice/CreditNote/Generator objects — only SettingRepository/Translator
 * are stubbed) and assert on the actual serialized XML string.
 */
final class PeppolUblXmlCreditNoteTest extends TestCase
{
    private function settingRepository(): SettingRepository
    {
        $s = $this->createMock(SettingRepository::class);
        $s->method('getSetting')->willReturnMap([
            ['peppol_document_currency', 'EUR'],
            ['peppol_debug_with_emojis', '0'],
        ]);
        $s->method('currencyConverter')->willReturnCallback(
            static fn (mixed $v): string => (string) $v
        );
        return $s;
    }

    private function translator(): Translator
    {
        $t = $this->createMock(Translator::class);
        $t->method('translate')->willReturnArgument(0);
        return $t;
    }

    private function minimalParty(): Party
    {
        return new Party(
            $this->translator(),
            'Acme Ltd',
            null,
            null,
            new Address(null, null, null, null, null, null, null),
            null,
            null,
            null,
            null,
            '9999:12345', // endpointID — Party::validate() throws if null
            'EM',
        );
    }

    /**
     * @return array{
     *     header: PeppolInvoiceHeader,
     *     additionalDocumentReference: AdditionalDocumentReference,
     *     delivery: Delivery,
     *     payment: PeppolPaymentData,
     *     financial: PeppolFinancialData,
     * }
     */
    private function minimalDocumentGraph(SettingRepository $s): array
    {
        $header = new PeppolInvoiceHeader(
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            123,
            new PeppolInvoiceDates(
                new DateTime('2026-01-01'),
                new DateTime('2026-01-31'),
                new DateTime('2026-01-01'),
                new InvoicePeriod('2026-01-01', '2026-01-31', ''),
            ),
            'Test note',
            null,
            'buyer-ref',
            new PeppolInvoiceReferences(
                new OrderReference(null, null),
                null,
                true,
                null,
            ),
        );

        $additionalDocumentReference = new AdditionalDocumentReference(
            $this->translator(), 'INV123', null, 'Invoice PDF', [],
        );

        $delivery = new Delivery(null, [], null, null);

        $payment = new PeppolPaymentData(
            new PaymentMeans(null, ''),
            new PaymentTerms(null),
        );

        $financial = new PeppolFinancialData(
            [],
            ['supp_tax_cc_tax_amount' => 0.00, 'supp_tax_cc' => 'EUR'],
            [],
            new LegalMonetaryTotal(100.00, 100.00, 120.00, 0.00, 120.00, 'EUR', $s),
            [
                ['name' => Schema::CAC . 'InvoiceLine', 'value' => [
                    ['name' => Schema::CBC . 'ID', 'value' => '1'],
                ]],
            ],
        );

        return compact('header', 'additionalDocumentReference', 'delivery', 'payment', 'financial');
    }

    public function testInvoiceOutputsInvoiceRootTagNamespaceAndTypeCode(): void
    {
        $s = $this->settingRepository();
        $graph = $this->minimalDocumentGraph($s);
        $party = $this->minimalParty();
        $peppolUblXml = new PeppolUblXml($s);

        $ubl = $peppolUblXml->xml(
            $graph['header'], $graph['additionalDocumentReference'],
            $party, $party, $graph['delivery'], $graph['payment'], $graph['financial'],
        );
        $xml = $peppolUblXml->output($ubl);

        $this->assertMatchesRegularExpression('/^<\?xml[^>]*\?>\s*<Invoice\b/', $xml);
        $this->assertStringContainsString(
            'xmlns="' . Schema::INVOICE_NS . '"', $xml,
        );
        $this->assertStringContainsString(
            '<cbc:InvoiceTypeCode>' . InvoiceTypeCode::INVOICE . '</cbc:InvoiceTypeCode>', $xml,
        );
        $this->assertStringNotContainsString('<CreditNote', $xml);
        $this->assertStringNotContainsString(Schema::CREDIT_NOTE_NS, $xml);
    }

    public function testCreditNoteOutputsCreditNoteRootTagNamespaceAndTypeCode(): void
    {
        $s = $this->settingRepository();
        $graph = $this->minimalDocumentGraph($s);
        $party = $this->minimalParty();
        $peppolUblXml = new PeppolUblXml($s);

        $ubl = $peppolUblXml->xml(
            $graph['header'], $graph['additionalDocumentReference'],
            $party, $party, $graph['delivery'], $graph['payment'], $graph['financial'],
            isCreditNote: true,
        );
        $xml = $peppolUblXml->output($ubl);

        $this->assertMatchesRegularExpression('/^<\?xml[^>]*\?>\s*<CreditNote\b/', $xml);
        $this->assertStringContainsString(
            'xmlns="' . Schema::CREDIT_NOTE_NS . '"', $xml,
        );
        $this->assertStringContainsString(
            '<cbc:InvoiceTypeCode>' . InvoiceTypeCode::CREDIT_NOTE . '</cbc:InvoiceTypeCode>', $xml,
        );
        $this->assertStringNotContainsString('<Invoice ', $xml);
        $this->assertStringNotContainsString(Schema::INVOICE_NS, $xml);
    }
}
