<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Signs a SOAP envelope with WS-Security (X.509 token profile, RSA-SHA256, EXC-C14N).
 *
 * Produces the structure required by eDelivery AS4 / Peppol:
 *   wsse:Security
 *     wsse:BinarySecurityToken  (base64 DER certificate)
 *     ds:Signature
 *       ds:SignedInfo            (references soap:Body + eb:Messaging)
 *       ds:SignatureValue
 *       ds:KeyInfo > wsse:SecurityTokenReference
 *
 * @psalm-suppress UnusedClass
 */
final class WsSecuritySigner implements As4EnvelopeSignerInterface
{
    private const string NS_WSS  = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private const string NS_WSU  = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    private const string NS_DS   = 'http://www.w3.org/2000/09/xmldsig#';
    private const string NS_SOAP = 'http://www.w3.org/2003/05/soap-envelope';
    private const string NS_EB   = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/';

    private const string ENCODING_BASE64 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';
    private const string VALUE_TYPE_X509 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3';

    public function __construct(
        private readonly string $privateKeyPem,
        private readonly string $certificatePem,
    ) {}

    /**
     * Returns a new signed DOMDocument. The original is not modified.
     */
    public function sign(DOMDocument $envelope): DOMDocument
    {
        $doc = clone $envelope;

        $bodyId      = 'Body-'      . bin2hex(random_bytes(8));
        $messagingId = 'Messaging-' . bin2hex(random_bytes(8));
        $tokenId     = 'X509-'      . bin2hex(random_bytes(8));

        $this->assignWsuId($doc, '//soap:Body', $bodyId);
        $this->assignWsuId($doc, '//eb:Messaging', $messagingId);

        $security    = $this->requireSecurityHeader($doc);
        $this->addBinarySecurityToken($security, $doc, $tokenId);

        $bodyEl      = $this->requireElement($doc, '//soap:Body', 'soap:Body not found in envelope');
        $messagingEl = $this->requireElement($doc, '//eb:Messaging', 'eb:Messaging not found in envelope');

        // Append ds:Signature to Security before signing so SignedInfo is
        // canonicalized within the correct document namespace context.
        $signature  = $doc->createElementNS(self::NS_DS, 'ds:Signature');
        $signedInfo = $this->buildSignedInfo($doc, $bodyEl, $bodyId, $messagingEl, $messagingId);
        $signature->appendChild($signedInfo);
        $security->appendChild($signature);

        $c14n = $this->excC14n($signedInfo);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($this->privateKeyPem, false);
        /** @psalm-suppress MixedAssignment */
        $rawSig = $key->signData($c14n);

        $sigValueEl = $doc->createElementNS(self::NS_DS, 'ds:SignatureValue');
        $sigValueEl->appendChild($doc->createTextNode(base64_encode((string) $rawSig)));
        $signature->appendChild($sigValueEl);
        $signature->appendChild($this->buildKeyInfo($doc, $tokenId));

        return $doc;
    }

    private function makeXPath(DOMDocument $doc): DOMXPath
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('soap', self::NS_SOAP);
        $xpath->registerNamespace('eb',   self::NS_EB);
        $xpath->registerNamespace('wsse', self::NS_WSS);
        $xpath->registerNamespace('wsu',  self::NS_WSU);
        $xpath->registerNamespace('ds',   self::NS_DS);
        return $xpath;
    }

    private function assignWsuId(DOMDocument $doc, string $xpathQuery, string $id): void
    {
        $nodes = $this->makeXPath($doc)->query($xpathQuery);
        if ($nodes === false) {
            return;
        }
        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            return;
        }
        $node->setAttributeNS(self::NS_WSU, 'wsu:Id', $id);
    }

    private function requireSecurityHeader(DOMDocument $doc): DOMElement
    {
        $nodes  = $this->makeXPath($doc)->query('//soap:Header');
        $header = ($nodes !== false) ? $nodes->item(0) : null;
        if (!$header instanceof DOMElement) {
            throw new \RuntimeException('soap:Header not found in envelope');
        }
        $security = $doc->createElementNS(self::NS_WSS, 'wsse:Security');
        $security->setAttributeNS(self::NS_SOAP, 'soap:mustUnderstand', 'true');
        $header->insertBefore($security, $header->firstChild);
        return $security;
    }

    private function addBinarySecurityToken(DOMElement $security, DOMDocument $doc, string $tokenId): void
    {
        $stripped   = preg_replace('/-----[^-]+-----|\s/', '', $this->certificatePem);
        $certBase64 = $stripped ?? '';

        $token = $doc->createElementNS(self::NS_WSS, 'wsse:BinarySecurityToken');
        $token->setAttributeNS(self::NS_WSU, 'wsu:Id', $tokenId);
        $token->setAttribute('EncodingType', self::ENCODING_BASE64);
        $token->setAttribute('ValueType', self::VALUE_TYPE_X509);
        $token->appendChild($doc->createTextNode($certBase64));
        $security->appendChild($token);
    }

    private function requireElement(DOMDocument $doc, string $xpathQuery, string $errorMsg): DOMElement
    {
        $nodes = $this->makeXPath($doc)->query($xpathQuery);
        $node  = ($nodes !== false) ? $nodes->item(0) : null;
        if (!$node instanceof DOMElement) {
            throw new \RuntimeException($errorMsg);
        }
        return $node;
    }

    private function buildSignedInfo(
        DOMDocument $doc,
        DOMElement $body,
        string $bodyId,
        DOMElement $messaging,
        string $messagingId,
    ): DOMElement {
        $signedInfo = $doc->createElementNS(self::NS_DS, 'ds:SignedInfo');

        $c14nMethod = $doc->createElementNS(self::NS_DS, 'ds:CanonicalizationMethod');
        $c14nMethod->setAttribute('Algorithm', XMLSecurityDSig::EXC_C14N);
        $signedInfo->appendChild($c14nMethod);

        $sigMethod = $doc->createElementNS(self::NS_DS, 'ds:SignatureMethod');
        $sigMethod->setAttribute('Algorithm', XMLSecurityKey::RSA_SHA256);
        $signedInfo->appendChild($sigMethod);

        $signedInfo->appendChild($this->buildReference($doc, $bodyId, $this->elementDigest($body)));
        $signedInfo->appendChild($this->buildReference($doc, $messagingId, $this->elementDigest($messaging)));

        return $signedInfo;
    }

    /**
     * Exclusive C14N (EXC-C14N) of a DOM node — the canonical form required by WS-Security.
     */
    private function excC14n(DOMNode $node): string
    {
        $result = $node->C14N(true, false);
        if ($result === false) {
            throw new \RuntimeException('XML exclusive canonicalization failed');
        }
        return $result;
    }

    private function elementDigest(DOMElement $element): string
    {
        return base64_encode(hash('sha256', $this->excC14n($element), true));
    }

    private function buildReference(DOMDocument $doc, string $id, string $digestValue): DOMElement
    {
        $ref = $doc->createElementNS(self::NS_DS, 'ds:Reference');
        $ref->setAttribute('URI', '#' . $id);

        $transforms = $doc->createElementNS(self::NS_DS, 'ds:Transforms');
        $transform  = $doc->createElementNS(self::NS_DS, 'ds:Transform');
        $transform->setAttribute('Algorithm', XMLSecurityDSig::EXC_C14N);
        $transforms->appendChild($transform);
        $ref->appendChild($transforms);

        $digestMethod = $doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', XMLSecurityDSig::SHA256);
        $ref->appendChild($digestMethod);

        $digestEl = $doc->createElementNS(self::NS_DS, 'ds:DigestValue');
        $digestEl->appendChild($doc->createTextNode($digestValue));
        $ref->appendChild($digestEl);

        return $ref;
    }

    private function buildKeyInfo(DOMDocument $doc, string $tokenId): DOMElement
    {
        $keyInfo     = $doc->createElementNS(self::NS_DS, 'ds:KeyInfo');
        $secTokenRef = $doc->createElementNS(self::NS_WSS, 'wsse:SecurityTokenReference');
        $ref         = $doc->createElementNS(self::NS_WSS, 'wsse:Reference');
        $ref->setAttribute('URI', '#' . $tokenId);
        $ref->setAttribute('ValueType', self::VALUE_TYPE_X509);
        $secTokenRef->appendChild($ref);
        $keyInfo->appendChild($secTokenRef);
        return $keyInfo;
    }
}
