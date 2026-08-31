<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Ubl;

use App\Invoice\Ubl\AdditionalDocumentReference;
use Sabre\Xml\Service;
use Testo\Assert;
use Testo\Test;

/**
 * DocumentDescription (BT-123) is genuinely optional (UBL cardinality
 * 0..1). This class previously also had a validate() that unconditionally
 * threw when it was null, contradicting xmlSerialize()'s own
 * null-means-omit treatment of the same field -- removed 2026-08-31, found
 * via the real HTTP Peppol route's EN16931 validator (see
 * PeppolHelper::additionalDocumentReference()'s own comment on the same
 * date).
 */
#[Test]
final class AdditionalDocumentReferenceTest
{
    private function serialize(AdditionalDocumentReference $reference): string
    {
        $service = new Service();
        $service->namespaceMap = [
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2' => 'cbc',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2' => 'cac',
        ];
        return $service->write('AdditionalDocumentReference', $reference);
    }

    public function omitsDocumentDescriptionElementWhenNull(): void
    {
        $xml = $this->serialize(new AdditionalDocumentReference('INV123', null, null, []));

        Assert::false(str_contains($xml, 'DocumentDescription'));
    }

    public function doesNotThrowWhenDocumentDescriptionIsNull(): void
    {
        // The regression this guards against: a construct-then-serialize
        // with a null description used to throw InvalidArgumentException
        // from a since-removed validate() call, rejecting the vast
        // majority of ordinary invoices that simply never had a
        // description text.
        $this->serialize(new AdditionalDocumentReference('INV123', null, null, []));
        Assert::true(true);
    }

    public function includesDocumentDescriptionElementWhenPresent(): void
    {
        $xml = $this->serialize(new AdditionalDocumentReference('INV123', null, 'Invoice PDF', []));

        Assert::true(str_contains($xml, '<cbc:DocumentDescription>Invoice PDF</cbc:DocumentDescription>'));
    }

    public function alwaysIncludesTheIdElementEvenWhenEverythingElseIsBlank(): void
    {
        $xml = $this->serialize(new AdditionalDocumentReference('INV123', null, null, []));

        Assert::true(str_contains($xml, '<cbc:ID>INV123</cbc:ID>'));
    }
}
