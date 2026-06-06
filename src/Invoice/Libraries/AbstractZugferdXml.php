<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use DOMDocument;
use DOMElement;
use DateTimeImmutable;
use DateTime;

/**
 * Pure DOM-building utilities shared by ZugferdXml and any future subclasses.
 *
 * All methods here are stateless with respect to the invoice data — they depend
 * only on their explicit arguments. Extracted from ZugferdXml to keep that
 * class under the S1448 method-count threshold.
 */
abstract class AbstractZugferdXml
{
    protected function dateElement(DOMDocument $doc, DateTimeImmutable $date): DOMElement
    {
        $el = $doc->createElement('udt:DateTimeString', $this->zugferdFormattedDate($date) ?? 'YYYYmmdd');
        $el->setAttribute('format', (string) 102);
        return $el;
    }

    protected function zugferdFormattedDate(DateTimeImmutable $date): ?string
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date->format('Y-m-d'));
        return $dt ? $dt->format('Ymd') : null;
    }

    protected function zugferdFormattedFloat(float $amount, int $nb_decimals = 2): string
    {
        return number_format($amount, $nb_decimals);
    }

    protected function xmlSpecifiedTaxRegistration(DOMDocument $doc, string $schemeID, string $content): DOMElement
    {
        $node = $doc->createElement('ram:SpecifiedTaxRegistration');
        $el   = $doc->createElement('ram:ID', $content);
        $el->setAttribute('schemeID', $schemeID);
        $node->appendChild($el);
        return $node;
    }

    protected function quantityElement(DOMDocument $doc, string $name, float $quantity): DOMElement
    {
        $el = $doc->createElement($name, $this->zugferdFormattedFloat($quantity, 4));
        $el->setAttribute('unitCode', 'C62');
        return $el;
    }
}
