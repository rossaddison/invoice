<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Ubl;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Ubl\{Address, Contact, Country, Generator, OrderResponse, OrderResponseCode, OrderResponseLine, Party, PartyLegalEntity, PartyTaxScheme, Schema, TaxScheme};
use DateTime;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Yiisoft\Translator\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class OrderResponseTest extends TestCase
{
    private function translator(): TranslatorInterface
    {
        $t = $this->createMock(TranslatorInterface::class);
        $t->method('translate')->willReturnArgument(0);
        return $t;
    }

    private function settingRepository(): SettingRepository
    {
        $s = $this->createMock(SettingRepository::class);
        $s->method('getSetting')->willReturn('EUR');
        return $s;
    }

    private function party(): Party
    {
        return new Party(
            $this->translator(),
            'Acme Ltd',
            'GB123456789',
            '0088',
            new Address('1 High St', '', '', 'London', 'SW1A 1AA', '', new Country('GB', 'ISO3166-1:Alpha2')),
            null,
            new Contact('Jane Doe', '', '', '+441234567890', null, 'jane@example.test'),
            new PartyTaxScheme('GB123456789', new TaxScheme('VAT')),
            new PartyLegalEntity('Acme Ltd', 'GB123456789', [], ''),
            '9876543210987',
            '0088',
        );
    }

    /** @return OrderResponseLine[] */
    private function lines(string $lineStatusCode1 = '5', string $lineStatusCode2 = '5'): array
    {
        return [
            new OrderResponseLine('1', $lineStatusCode1, '1', 'Widget'),
            new OrderResponseLine('2', $lineStatusCode2, '2', 'Gadget'),
        ];
    }

    private function generate(OrderResponseCode $code, ?array $lines = null): DOMXPath
    {
        $orderResponse = new OrderResponse(
            sR:                $this->settingRepository(),
            id:                'OR-0001',
            issueDate:         new DateTime('2026-06-02'),
            orderResponseCode: $code,
            orderReferenceId:  'PO-IMPORT-001',
            sellerSupplierParty: $this->party(),
            buyerCustomerParty:  $this->party(),
            lines: $lines ?? $this->lines(),
        );

        $xml = Generator::orderResponse($orderResponse);

        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ubl', Schema::ORDER_RESPONSE_NS);
        $xpath->registerNamespace('cbc', Schema::CBC_NS);
        $xpath->registerNamespace('cac', Schema::CAC_NS);
        return $xpath;
    }

    private function text(DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        return $nodes->item(0)?->textContent ?? '';
    }

    public function testOrderResponseCodeIsWritten(): void
    {
        $xpath = $this->generate(OrderResponseCode::Accepted);
        $this->assertSame('AP', $this->text($xpath, '/ubl:OrderResponse/cbc:OrderResponseCode'));
    }

    public function testOrderReferenceIdMatchesTheBuyersOrderNumber(): void
    {
        $xpath = $this->generate(OrderResponseCode::Accepted);
        $this->assertSame('PO-IMPORT-001', $this->text($xpath, '/ubl:OrderResponse/cac:OrderReference/cbc:ID'));
    }

    public function testCustomizationAndProfileIdAreTheAdvancedOrderingOnes(): void
    {
        $xpath = $this->generate(OrderResponseCode::Accepted);
        $this->assertSame(
            Schema::ORDER_RESPONSE_ADVANCED_CUSTOMIZATION_ID,
            $this->text($xpath, '/ubl:OrderResponse/cbc:CustomizationID'),
        );
        $this->assertSame(
            Schema::ORDER_RESPONSE_ADVANCED_PROFILE_ID,
            $this->text($xpath, '/ubl:OrderResponse/cbc:ProfileID'),
        );
    }

    public function testEveryLineCarriesTheMatchingLineStatusCode(): void
    {
        $xpath = $this->generate(OrderResponseCode::Rejected, $this->lines('7', '7'));
        $nodes = $xpath->query('/ubl:OrderResponse/cac:OrderLine/cac:LineItem/cbc:LineStatusCode');
        $this->assertNotFalse($nodes);
        $this->assertSame(2, $nodes->length);
        foreach ($nodes as $node) {
            $this->assertSame('7', $node->textContent);
        }
    }

    public function testLinesCanCarryDifferentStatusCodesIndependently(): void
    {
        // Real per-line staff decisions: one line accepted, one rejected --
        // the two OrderResponseLines are fully independent of each other
        // and of the header code (mirrors OrderResponseAdvancedService's
        // per-line path, which derives the header from these, not the
        // other way round).
        $xpath = $this->generate(OrderResponseCode::AcceptedWithChanges, $this->lines('5', '7'));
        $nodes = $xpath->query('/ubl:OrderResponse/cac:OrderLine/cac:LineItem/cbc:LineStatusCode');
        $this->assertNotFalse($nodes);
        $this->assertSame(2, $nodes->length);
        $this->assertSame('5', $nodes->item(0)?->textContent);
        $this->assertSame('7', $nodes->item(1)?->textContent);
    }

    public function testLineCarriesTheBuyersOwnLineId(): void
    {
        $xpath = $this->generate(OrderResponseCode::Accepted);
        $this->assertSame(
            '1',
            $this->text($xpath, '/ubl:OrderResponse/cac:OrderLine[1]/cac:LineItem/cac:OrderLineReference/cbc:LineID'),
        );
    }

    public function testSellerAndBuyerPartiesArePresent(): void
    {
        $xpath = $this->generate(OrderResponseCode::Accepted);
        $this->assertSame(
            '9876543210987',
            $this->text($xpath, '/ubl:OrderResponse/cac:SellerSupplierParty/cac:Party/cbc:EndpointID'),
        );
        $this->assertSame(
            '9876543210987',
            $this->text($xpath, '/ubl:OrderResponse/cac:BuyerCustomerParty/cac:Party/cbc:EndpointID'),
        );
    }

    public function testMissingOrderReferenceIdFailsValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrderResponse(
            sR:                $this->settingRepository(),
            id:                'OR-0001',
            issueDate:         new DateTime(),
            orderResponseCode: OrderResponseCode::Accepted,
            orderReferenceId:  null,
            sellerSupplierParty: $this->party(),
            buyerCustomerParty:  $this->party(),
            lines: $this->lines('5'),
        )->xmlSerialize((new \Sabre\Xml\Service())->getWriter());
    }
}
