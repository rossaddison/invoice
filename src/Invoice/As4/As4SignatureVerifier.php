<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;

/**
 * Verifies WS-Security Ed25519 signatures in an AS4/SOAP document.
 *
 * Extracted from As4SecurityHandler to keep that class within SonarQube's
 * 20-method limit while preserving the verification algorithm intact.
 */
class As4SignatureVerifier
{
    public function verify(DOMDocument $doc): bool
    {
        try {
            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
            $signatures = $xpath->query('//ds:Signature');
            if ($signatures === false || $signatures->length === 0) {
                return false;
            }
            /** @psalm-suppress InvalidArgument */
            return $this->verifySignatures($signatures, $doc);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function verifySignatures(\DOMNodeList $signatures, DOMDocument $doc): bool
    {
        foreach ($signatures as $sig) {
            if (!$sig instanceof DOMElement || !$this->verifySignatureElement($sig, $doc)) {
                return false;
            }
        }
        return true;
    }

    private function verifySignatureElement(DOMElement $sigElem, DOMDocument $doc): bool
    {
        $sigBytes     = $this->extractSignatureBytes($sigElem, $doc);
        $canonSigInfo = $this->canonicalizeSignedInfo($sigElem, $doc);
        $publicKey    = $this->extractSignerPublicKey($doc);

        if ($sigBytes === null || $canonSigInfo === null || $publicKey === null) {
            return false;
        }

        if (!sodium_crypto_sign_verify_detached($sigBytes, $canonSigInfo, $publicKey)) {
            return false;
        }

        return $this->verifyAllReferences($sigElem, $doc);
    }

    private function extractSignatureBytes(DOMElement $sigElem, DOMDocument $doc): ?string
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $nodes = $xpath->query('ds:SignatureValue', $sigElem);
        $node  = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;

        if (!$node instanceof DOMElement) {
            return null;
        }

        $decoded = base64_decode(trim($node->textContent), true);
        return ($decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_BYTES) ? $decoded : null;
    }

    private function canonicalizeSignedInfo(DOMElement $sigElem, DOMDocument $doc): ?string
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $nodes = $xpath->query('ds:SignedInfo', $sigElem);
        $node  = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;

        if (!$node instanceof DOMElement) {
            return null;
        }

        $c14n = $node->C14N(true, false);
        return $c14n !== false ? $c14n : null;
    }

    private function extractSignerPublicKey(DOMDocument $doc): ?string
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('wsse', As4Constants::WSS_NS);
        $nodes = $xpath->query('//wsse:BinarySecurityToken');
        $node  = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;

        if (!$node instanceof DOMElement) {
            return null;
        }

        $certDer = base64_decode(trim($node->textContent), true);
        return $certDer !== false ? $this->extractEd25519PublicKeyFromCertDer($certDer) : null;
    }

    private function extractEd25519PublicKeyFromCertDer(string $certDer): ?string
    {
        $pem     = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($certDer), 64, "\n")
            . "-----END CERTIFICATE-----\n";
        $cert    = openssl_x509_read($pem);
        $pubKey  = $cert !== false ? openssl_pkey_get_public($cert) : false;
        $details = $pubKey !== false ? openssl_pkey_get_details($pubKey) : false;

        if ($details === false) {
            return null;
        }

        $spki = base64_decode($this->extractPemBody((string) ($details['key'] ?? '')), true);
        return ($spki !== false && strlen($spki) >= SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES)
            ? substr($spki, -SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES)
            : null;
    }

    private function extractPemBody(string $pem): string
    {
        $lines  = explode("\n", $pem);
        $body   = '';
        $inside = false;
        foreach ($lines as $line) {
            if (str_contains($line, 'BEGIN PUBLIC KEY')) { $inside = true; continue; }
            if (str_contains($line, 'END PUBLIC KEY')) { break; }
            if ($inside) { $body .= trim($line); }
        }
        return $body;
    }

    private function verifyAllReferences(DOMElement $sigElem, DOMDocument $doc): bool
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $refs = $xpath->query('ds:SignedInfo/ds:Reference', $sigElem);

        if ($refs === false) {
            return false;
        }

        foreach ($refs as $ref) {
            if ($ref instanceof DOMElement && !$this->verifyReference($ref, $doc)) {
                return false;
            }
        }

        return true;
    }

    private function verifyReference(DOMElement $refElem, DOMDocument $doc): bool
    {
        $uri = $refElem->getAttribute('URI');

        // MIME attachment references: raw bytes not available during XML-only verification
        if (str_starts_with($uri, 'cid:')) {
            return true;
        }

        return $this->verifyElementReference(ltrim($uri, '#'), $refElem, $doc);
    }

    private function verifyElementReference(string $elementId, DOMElement $refElem, DOMDocument $doc): bool
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $elementId)) {
            return false;
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('wsu', As4Constants::WSS_UTIL_NS);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);

        $nodes   = $xpath->query('//*[@wsu:Id="' . $elementId . '"]');
        $refNode = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;
        $c14n    = $refNode instanceof DOMElement ? $refNode->C14N(true, false) : false;

        if ($c14n === false) {
            return false;
        }

        $actualDigest = base64_encode(hash('sha256', $c14n, true));
        $digestNodes  = $xpath->query('ds:DigestValue', $refElem);
        $digestNode   = ($digestNodes !== false && $digestNodes->length > 0) ? $digestNodes->item(0) : null;

        return $digestNode instanceof DOMElement && $digestNode->textContent === $actualDigest;
    }
}
