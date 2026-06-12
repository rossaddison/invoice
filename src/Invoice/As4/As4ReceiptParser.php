<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses an AS4 HTTP response body for an ebMS3 signal message.
 *
 * The response body can be:
 *  - Empty (HTTP 202 async receipt) → null
 *  - Plain SOAP 1.2 XML            → parsed directly
 *  - MIME multipart/related        → SOAP part extracted, then parsed
 *
 * Returns an As4ReceiptSignal on receipt, an As4ErrorSignal on error, or null
 * when the body contains no recognisable signal message.
 *
 * @psalm-suppress UnusedClass
 */
final class As4ReceiptParser implements As4ReceiptParserInterface
{
    private const string NS_EB = As4Constants::EBMS3_NS;

    /**
     * @param string $body        Raw HTTP response body (may be empty, XML, or MIME multipart)
     * @param string $contentType Value of the HTTP Content-Type response header
     */
    #[\Override]
    public function parse(string $body, string $contentType = ''): As4ReceiptSignal|As4ErrorSignal|null
    {
        $xml = $this->extractXml(trim($body), $contentType);
        if ($xml === null) {
            return null;
        }

        $doc = $this->loadDocument($xml);
        if ($doc === null) {
            return null;
        }

        return $this->extractSignal($doc);
    }

    // ── XML extraction ────────────────────────────────────────────────────────

    private function extractXml(string $body, string $contentType): ?string
    {
        if ($body === '') {
            return null;
        }

        $boundary = $this->detectBoundary($body, $contentType);
        if ($boundary !== null) {
            return $this->extractFirstMimePart($body, $boundary);
        }

        return $body;
    }

    /** Finds the MIME boundary from the Content-Type header or heuristically from the body. */
    private function detectBoundary(string $body, string $contentType): ?string
    {
        if (str_contains($contentType, 'multipart/')) {
            return $this->parseBoundary($contentType);
        }

        // Heuristic: body starts with "--<boundary>\r\n" or "--<boundary>\n"
        if (str_starts_with($body, '--')) {
            $eol = min(
                ($p = strpos($body, "\r\n")) !== false ? $p : PHP_INT_MAX,
                ($p = strpos($body, "\n"))   !== false ? $p : PHP_INT_MAX,
            );
            $candidate = trim(substr($body, 2, $eol - 2));
            if ($candidate !== '' && !str_starts_with($candidate, '-')) {
                return $candidate;
            }
        }

        return null;
    }

    private function parseBoundary(string $contentType): ?string
    {
        if (preg_match('/boundary="?([^";\s]+)"?/', $contentType, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractFirstMimePart(string $body, string $boundary): ?string
    {
        $parts = explode('--' . $boundary, $body);

        foreach ($parts as $part) {
            $part = ltrim($part, "\r\n");

            // Skip empty preamble and the closing "--" terminator
            if ($part === '' || str_starts_with($part, '--')) {
                continue;
            }

            // Split MIME headers from body at the first blank line
            $pos  = strpos($part, "\r\n\r\n");
            $skip = 4;
            if ($pos === false) {
                $pos  = strpos($part, "\n\n");
                $skip = 2;
            }
            if ($pos === false) {
                continue;
            }

            $content = trim(substr($part, $pos + $skip));
            if ($content !== '') {
                return $content;
            }
        }

        return null;
    }

    // ── Document loading ──────────────────────────────────────────────────────

    private function loadDocument(string $xml): ?DOMDocument
    {
        $doc        = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);
        return $loaded ? $doc : null;
    }

    // ── Signal extraction ─────────────────────────────────────────────────────

    private function extractSignal(DOMDocument $doc): As4ReceiptSignal|As4ErrorSignal|null
    {
        $xpath  = $this->makeXPath($doc);
        $signal = $this->findSignalElement($xpath);
        if ($signal === null) {
            return null;
        }
        return $this->parseSignalElement($xpath, $signal);
    }

    private function makeXPath(DOMDocument $doc): DOMXPath
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('eb', self::NS_EB);
        return $xpath;
    }

    private function findSignalElement(DOMXPath $xpath): ?DOMElement
    {
        $nodes = $xpath->query('//eb:Messaging/eb:SignalMessage');
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $node = $nodes->item(0);
        return $node instanceof DOMElement ? $node : null;
    }

    private function parseSignalElement(
        DOMXPath $xpath,
        DOMElement $signal,
    ): As4ReceiptSignal|As4ErrorSignal|null {
        $messageId = $this->text($xpath, 'eb:MessageInfo/eb:MessageId', $signal);
        $refId     = $this->text($xpath, 'eb:MessageInfo/eb:RefToMessageId', $signal);
        $timestamp = $this->text($xpath, 'eb:MessageInfo/eb:Timestamp', $signal);

        $receipts = $xpath->query('eb:Receipt', $signal);
        if ($receipts !== false && $receipts->length > 0) {
            return new As4ReceiptSignal(
                messageId:      $messageId,
                refToMessageId: $refId,
                timestamp:      $timestamp,
            );
        }

        $errors  = $xpath->query('eb:Error', $signal);
        $errorEl = ($errors !== false && $errors->length > 0) ? $errors->item(0) : null;
        if ($errorEl instanceof DOMElement) {
            return new As4ErrorSignal(
                messageId:        $messageId,
                refToMessageId:   $refId,
                timestamp:        $timestamp,
                category:         $errorEl->getAttribute('category'),
                errorCode:        $errorEl->getAttribute('errorCode'),
                severity:         $errorEl->getAttribute('severity'),
                shortDescription: $errorEl->getAttribute('shortDescription'),
                description:      $this->text($xpath, 'eb:Error/eb:Description', $signal),
            );
        }

        return null;
    }

    private function text(DOMXPath $xpath, string $expr, DOMElement $context): string
    {
        $nodes = $xpath->query($expr, $context);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            return '';
        }
        return trim($node->textContent);
    }
}
