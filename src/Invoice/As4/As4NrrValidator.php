<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Non-Repudiation of Receipt (NRR) validator.
 *
 * Verifies that an ebMS3 receipt's ebbp:NonRepudiationInformation contains
 * at least the same ds:Reference/@URI + ds:DigestValue pairs that appear in
 * the signed outbound message.  This satisfies the NRR requirement of
 * eDelivery AS4 2.0 §5.1.8 and Peppol AS4 Profile §4.3.
 *
 * Returned value is an As4NrrResult describing the outcome so the caller
 * can log or throw without coupling validation to exception handling.
 *
 * @psalm-suppress UnusedClass
 */
final class As4NrrValidator
{
    private const string NS_EB   = As4Constants::EBMS3_NS;
    private const string NS_DS   = As4Constants::XMLDSIG_NS;
    private const string NS_EBBP = 'http://docs.oasis-open.org/ebcore/ns/NonRepudiation/v1.0'; // NOSONAR

    /**
     * @param string $signedEnvelopeXml  Serialized outbound signed SOAP envelope
     * @param string $receiptEnvelopeXml Serialized AS4 receipt SOAP envelope
     */
    public function validate(string $signedEnvelopeXml, string $receiptEnvelopeXml): As4NrrResult
    {
        $sentRefs    = $this->extractSentReferences($signedEnvelopeXml);
        $receiptRefs = $this->extractReceiptReferences($receiptEnvelopeXml);

        if ($sentRefs === null) {
            return As4NrrResult::failure('Signed envelope contains no ds:Reference elements');
        }
        if ($receiptRefs === null) {
            return As4NrrResult::failure('Receipt contains no NonRepudiationInformation');
        }

        $missing = $this->findMissingReferences($sentRefs, $receiptRefs);
        return $missing !== []
            ? As4NrrResult::failure('Receipt NRR is missing references: ' . implode(', ', $missing))
            : As4NrrResult::success();
    }

    // ── Reference extraction ──────────────────────────────────────────────────

    /**
     * @return array<string,string>|null  Map of URI → DigestValue from the signed envelope,
     *                                    or null if no ds:Reference elements are found
     */
    private function extractSentReferences(string $xml): ?array
    {
        $doc = $this->loadDocument($xml);
        if ($doc === null) {
            return null;
        }

        $xpath = $this->makeXPath($doc);
        $nodes = $xpath->query('//ds:Signature//ds:Reference');
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $refs = [];
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $uri    = $node->getAttribute('URI');
            $digest = $this->nodeText($xpath, 'ds:DigestValue', $node);
            if ($uri !== '' && $digest !== '') {
                $refs[$uri] = $digest;
            }
        }

        return $refs === [] ? null : $refs;
    }

    /**
     * @return array<string,string>|null  Map of URI → DigestValue from the receipt NRI,
     *                                    or null if no NRI is found
     */
    private function extractReceiptReferences(string $xml): ?array
    {
        $doc = $this->loadDocument($xml);
        if ($doc === null) {
            return null;
        }

        $xpath = $this->makeXPath($doc);
        $nodes = $xpath->query('//eb:Receipt//ds:Reference');
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $refs = [];
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $uri    = $node->getAttribute('URI');
            $digest = $this->nodeText($xpath, 'ds:DigestValue', $node);
            if ($uri !== '' && $digest !== '') {
                $refs[$uri] = $digest;
            }
        }

        return $refs === [] ? null : $refs;
    }

    // ── Comparison ────────────────────────────────────────────────────────────

    /**
     * Returns URIs present in $sent that are absent (or have mismatched digest) in $receipt.
     *
     * @param array<string,string> $sent
     * @param array<string,string> $receipt
     * @return list<string>
     */
    private function findMissingReferences(array $sent, array $receipt): array
    {
        $missing = [];
        foreach ($sent as $uri => $digest) {
            if (!isset($receipt[$uri]) || $receipt[$uri] !== $digest) {
                $missing[] = $uri;
            }
        }
        return $missing;
    }

    // ── DOM helpers ───────────────────────────────────────────────────────────

    private function loadDocument(string $xml): ?DOMDocument
    {
        if ($xml === '') {
            return null;
        }
        $doc        = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);
        return $loaded ? $doc : null;
    }

    private function makeXPath(DOMDocument $doc): DOMXPath
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('eb',   self::NS_EB);
        $xpath->registerNamespace('ds',   self::NS_DS);
        $xpath->registerNamespace('ebbp', self::NS_EBBP);
        return $xpath;
    }

    private function nodeText(DOMXPath $xpath, string $expr, DOMElement $context): string
    {
        $nodes = $xpath->query($expr, $context);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        $node = $nodes->item(0);
        return $node instanceof DOMElement ? trim($node->textContent) : '';
    }
}
