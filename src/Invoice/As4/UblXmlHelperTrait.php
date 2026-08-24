<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Small stateless DOMXPath-reading helpers shared by every inbound UBL
 * document parser (currently UblXmlParser for Invoice/CreditNote,
 * UblOrderXmlParser for Order) — extracted here purely to keep the
 * parsers from duplicating the exact same five tiny methods, not because
 * any of them carry parser-specific behaviour.
 */
trait UblXmlHelperTrait
{
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
