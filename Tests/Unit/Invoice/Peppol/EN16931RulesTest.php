<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R001;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R002;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R003;
use App\Invoice\Helpers\Peppol\Rule\Severity;
use App\Invoice\Helpers\Peppol\Rule\ValidationContext;
use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use Yiisoft\Translator\TranslatorInterface;

final class EN16931RulesTest extends TestCase
{
    private const CBC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    private const CAC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function translator(): TranslatorInterface
    {
        $t = $this->createMock(TranslatorInterface::class);
        $t->method('translate')->willReturnArgument(0);
        return $t;
    }

    private function xpath(string $innerXml): DOMXPath
    {
        $xml = '<Invoice xmlns:cbc="' . self::CBC_NS . '" xmlns:cac="' . self::CAC_NS . '">'
            . $innerXml
            . '</Invoice>';
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('cbc', self::CBC_NS);
        $xp->registerNamespace('cac', self::CAC_NS);
        return $xp;
    }

    private function context(
        string $supplierCountry = 'GB',
        string $customerCountry = 'GB'
    ): ValidationContext {
        return new ValidationContext(
            documentType:         'Invoice',
            documentCurrencyCode: 'GBP',
            supplierCountry:      $supplierCountry,
            customerCountry:      $customerCountry,
            profile:              '01',
        );
    }

    // ── PEPPOL-EN16931-R001 ───────────────────────────────────────────────────

    public function testR001PassesWhenProfileIdPresent(): void
    {
        $rule = new PEPPOL_EN16931_R001($this->translator());
        $xp   = $this->xpath('<cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>');

        self::assertSame([], $rule->validate($xp, $this->context()));
    }

    public function testR001FailsWhenProfileIdAbsent(): void
    {
        $rule = new PEPPOL_EN16931_R001($this->translator());
        $xp   = $this->xpath('');

        $violations = $rule->validate($xp, $this->context());
        self::assertCount(1, $violations);
        /** @var ValidationViolation $v */
        $v = $violations[0];
        self::assertSame('PEPPOL-EN16931-R001', $v->ruleId);
        self::assertSame(Severity::Fatal, $v->severity);
    }

    public function testR001FailsWhenProfileIdIsEmptyElement(): void
    {
        // This is the regression test for the bug fixed in this branch.
        // An empty <cbc:ProfileID/> must still fire R001.
        $rule = new PEPPOL_EN16931_R001($this->translator());
        $xp   = $this->xpath('<cbc:ProfileID></cbc:ProfileID>');

        $violations = $rule->validate($xp, $this->context());
        self::assertCount(1, $violations, 'Empty ProfileID element must trigger R001');
    }

    public function testR001IdIsCorrect(): void
    {
        self::assertSame('PEPPOL-EN16931-R001', (new PEPPOL_EN16931_R001($this->translator()))->id());
    }

    // ── PEPPOL-EN16931-R002 ───────────────────────────────────────────────────

    public function testR002PassesWithOneNote(): void
    {
        $rule = new PEPPOL_EN16931_R002($this->translator());
        $xp   = $this->xpath('<cbc:Note>Single note</cbc:Note>');

        self::assertSame([], $rule->validate($xp, $this->context()));
    }

    public function testR002PassesWithNoNote(): void
    {
        $rule = new PEPPOL_EN16931_R002($this->translator());
        $xp   = $this->xpath('');

        self::assertSame([], $rule->validate($xp, $this->context()));
    }

    public function testR002FailsWithTwoNotesNonDE(): void
    {
        $rule = new PEPPOL_EN16931_R002($this->translator());
        $xp   = $this->xpath('<cbc:Note>Note 1</cbc:Note><cbc:Note>Note 2</cbc:Note>');

        $violations = $rule->validate($xp, $this->context('GB', 'GB'));
        self::assertCount(1, $violations);
        self::assertSame(Severity::Fatal, $violations[0]->severity);
    }

    public function testR002PassesWithTwoNotesDeToDE(): void
    {
        $rule = new PEPPOL_EN16931_R002($this->translator());
        $xp   = $this->xpath('<cbc:Note>Note 1</cbc:Note><cbc:Note>Note 2</cbc:Note>');

        self::assertSame([], $rule->validate($xp, $this->context('DE', 'DE')));
    }

    public function testR002FailsWhenOnlySupplierIsDE(): void
    {
        $rule = new PEPPOL_EN16931_R002($this->translator());
        $xp   = $this->xpath('<cbc:Note>Note 1</cbc:Note><cbc:Note>Note 2</cbc:Note>');

        $violations = $rule->validate($xp, $this->context('DE', 'GB'));
        self::assertCount(1, $violations);
    }

    public function testR002IdIsCorrect(): void
    {
        self::assertSame('PEPPOL-EN16931-R002', (new PEPPOL_EN16931_R002($this->translator()))->id());
    }

    // ── PEPPOL-EN16931-R003 ───────────────────────────────────────────────────

    public function testR003PassesWithBuyerReference(): void
    {
        $rule = new PEPPOL_EN16931_R003($this->translator());
        $xp   = $this->xpath('<cbc:BuyerReference>PO-123</cbc:BuyerReference>');

        self::assertSame([], $rule->validate($xp, $this->context()));
    }

    public function testR003PassesWithOrderReference(): void
    {
        $rule = new PEPPOL_EN16931_R003($this->translator());
        $xp   = $this->xpath(
            '<cac:OrderReference><cbc:ID>ORD-456</cbc:ID></cac:OrderReference>'
        );

        self::assertSame([], $rule->validate($xp, $this->context()));
    }

    public function testR003FailsWhenNeitherPresent(): void
    {
        $rule = new PEPPOL_EN16931_R003($this->translator());
        $xp   = $this->xpath('');

        $violations = $rule->validate($xp, $this->context());
        self::assertCount(1, $violations);
        self::assertSame('PEPPOL-EN16931-R003', $violations[0]->ruleId);
        self::assertSame(Severity::Fatal, $violations[0]->severity);
    }

    public function testR003IdIsCorrect(): void
    {
        self::assertSame('PEPPOL-EN16931-R003', (new PEPPOL_EN16931_R003($this->translator()))->id());
    }
}
