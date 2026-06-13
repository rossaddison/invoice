<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\WsSecuritySigner;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class WsSecuritySignerTest extends TestCase
{
    // Algorithm / namespace URIs used in assertions
    private const string NS_WSS         = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private const string NS_WSU         = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    private const string NS_DS          = 'http://www.w3.org/2000/09/xmldsig#';
    private const string NS_SOAP        = 'http://www.w3.org/2003/05/soap-envelope';
    private const string NS_EB          = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/';
    private const string EXC_C14N       = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    private const string SHA256_URI     = 'http://www.w3.org/2001/04/xmlenc#sha256';
    private const string RSA_SHA256_URI = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    private const string ENCODING_BASE64 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';
    private const string VALUE_TYPE_X509 = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3';

    // Pre-generated 2048-bit RSA fixtures — regenerate with gen_fixtures.php if needed
    private static string $testPrivateKey  = '';
    private static string $testCertificate = '';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private WsSecuritySigner $signer;

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        $dir = __DIR__;
        self::$testPrivateKey  = (string) file_get_contents("{$dir}/test_key.pem");
        self::$testCertificate = (string) file_get_contents("{$dir}/test_cert.pem");
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->signer = new WsSecuritySigner(self::$testPrivateKey, self::$testCertificate);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function makeEnvelope(): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope"
                              xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soapenv:Header>
                <eb:Messaging>
                  <eb:UserMessage>
                    <eb:MessageInfo>
                      <eb:MessageId>test-msg-001@as4.test</eb:MessageId>
                    </eb:MessageInfo>
                  </eb:UserMessage>
                </eb:Messaging>
              </soapenv:Header>
              <soapenv:Body>
                <Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
                  <ID>TEST-001</ID>
                </Invoice>
              </soapenv:Body>
            </soapenv:Envelope>
            XML);
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

    private function requireNode(DOMDocument $doc, string $xpathQuery): DOMElement
    {
        $nodes = $this->makeXPath($doc)->query($xpathQuery);
        $node  = ($nodes !== false) ? $nodes->item(0) : null;
        self::assertInstanceOf(DOMElement::class, $node, "Expected element at: {$xpathQuery}");
        return $node;
    }

    // ── wsu:Id assignment ─────────────────────────────────────────────────────

    public function testBodyHasWsuId(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $id     = $this->requireNode($signed, '//soap:Body')->getAttributeNS(self::NS_WSU, 'Id');
        $this->assertNotEmpty($id);
        $this->assertStringStartsWith('Body-', $id);
    }

    public function testMessagingHasWsuId(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $id     = $this->requireNode($signed, '//eb:Messaging')->getAttributeNS(self::NS_WSU, 'Id');
        $this->assertNotEmpty($id);
        $this->assertStringStartsWith('Messaging-', $id);
    }

    // ── WS-Security header structure ──────────────────────────────────────────

    public function testSecurityHeaderCreated(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $nodes  = $this->makeXPath($signed)->query('//soap:Header/wsse:Security');
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length);
    }

    public function testBinarySecurityTokenPresent(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $this->requireNode($signed, '//wsse:Security/wsse:BinarySecurityToken');
    }

    public function testBinarySecurityTokenEncodingType(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $token  = $this->requireNode($signed, '//wsse:BinarySecurityToken');
        $this->assertSame(self::ENCODING_BASE64, $token->getAttribute('EncodingType'));
    }

    public function testBinarySecurityTokenValueType(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $token  = $this->requireNode($signed, '//wsse:BinarySecurityToken');
        $this->assertSame(self::VALUE_TYPE_X509, $token->getAttribute('ValueType'));
    }

    public function testBinarySecurityTokenHasWsuId(): void
    {
        $signed  = $this->signer->sign($this->makeEnvelope());
        $tokenId = $this->requireNode($signed, '//wsse:BinarySecurityToken')
            ->getAttributeNS(self::NS_WSU, 'Id');
        $this->assertNotEmpty($tokenId);
        $this->assertStringStartsWith('X509-', $tokenId);
    }

    public function testBinarySecurityTokenContainsCertificate(): void
    {
        $signed  = $this->signer->sign($this->makeEnvelope());
        $token   = $this->requireNode($signed, '//wsse:BinarySecurityToken');
        $inToken = base64_decode($token->textContent);

        $stripped = (string) preg_replace('/-----[^-]+-----|\s/', '', self::$testCertificate);
        $expected = base64_decode($stripped);

        $this->assertSame($expected, $inToken);
    }

    // ── ds:Signature structure ────────────────────────────────────────────────

    public function testSignatureElementPresentInSecurity(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $this->requireNode($signed, '//wsse:Security/ds:Signature');
    }

    public function testSignedInfoPresent(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $this->requireNode($signed, '//ds:Signature/ds:SignedInfo');
    }

    public function testCanonicalizationMethodIsExcC14n(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $node   = $this->requireNode($signed, '//ds:CanonicalizationMethod');
        $this->assertSame(self::EXC_C14N, $node->getAttribute('Algorithm'));
    }

    public function testSignatureMethodIsRsaSha256(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $node   = $this->requireNode($signed, '//ds:SignatureMethod');
        $this->assertSame(self::RSA_SHA256_URI, $node->getAttribute('Algorithm'));
    }

    public function testTwoReferencesPresent(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $refs   = $this->makeXPath($signed)->query('//ds:SignedInfo/ds:Reference');
        $this->assertNotFalse($refs);
        $this->assertSame(2, $refs->length);
    }

    public function testBodyReferenceExists(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $bodyId = $this->requireNode($signed, '//soap:Body')->getAttributeNS(self::NS_WSU, 'Id');
        $refs   = $this->makeXPath($signed)->query("//ds:Reference[@URI='#{$bodyId}']");
        $this->assertNotFalse($refs);
        $this->assertSame(1, $refs->length);
    }

    public function testMessagingReferenceExists(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $msgId  = $this->requireNode($signed, '//eb:Messaging')->getAttributeNS(self::NS_WSU, 'Id');
        $refs   = $this->makeXPath($signed)->query("//ds:Reference[@URI='#{$msgId}']");
        $this->assertNotFalse($refs);
        $this->assertSame(1, $refs->length);
    }

    public function testEachReferenceHasNonEmptyDigestValue(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $refs   = $this->makeXPath($signed)->query('//ds:SignedInfo/ds:Reference');
        $this->assertNotFalse($refs);

        foreach ($refs as $ref) {
            $this->assertInstanceOf(DOMElement::class, $ref);
            $digestNodes = $this->makeXPath($signed)->query('ds:DigestValue', $ref);
            $this->assertNotFalse($digestNodes);
            $digestNode = $digestNodes->item(0);
            $this->assertInstanceOf(DOMElement::class, $digestNode);
            $this->assertNotEmpty(trim($digestNode->textContent));
        }
    }

    public function testDigestMethodIsSha256(): void
    {
        $signed  = $this->signer->sign($this->makeEnvelope());
        $methods = $this->makeXPath($signed)->query('//ds:DigestMethod');
        $this->assertNotFalse($methods);

        foreach ($methods as $method) {
            $this->assertInstanceOf(DOMElement::class, $method);
            $this->assertSame(self::SHA256_URI, $method->getAttribute('Algorithm'));
        }
    }

    public function testSignatureValueIsNonEmpty(): void
    {
        $signed   = $this->signer->sign($this->makeEnvelope());
        $sigValue = $this->requireNode($signed, '//ds:SignatureValue');
        $this->assertNotEmpty(trim($sigValue->textContent));
    }

    public function testKeyInfoUsesSecurityTokenReference(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $this->requireNode($signed, '//ds:KeyInfo/wsse:SecurityTokenReference');
    }

    public function testKeyInfoReferencePointsToToken(): void
    {
        $signed  = $this->signer->sign($this->makeEnvelope());
        $tokenId = $this->requireNode($signed, '//wsse:BinarySecurityToken')
            ->getAttributeNS(self::NS_WSU, 'Id');
        $ref     = $this->requireNode($signed, '//wsse:SecurityTokenReference/wsse:Reference');

        $this->assertSame('#' . $tokenId, $ref->getAttribute('URI'));
        $this->assertSame(self::VALUE_TYPE_X509, $ref->getAttribute('ValueType'));
    }

    // ── Cryptographic correctness ─────────────────────────────────────────────

    public function testBodyDigestMatchesActualBodyDigest(): void
    {
        $signed = $this->signer->sign($this->makeEnvelope());
        $body   = $this->requireNode($signed, '//soap:Body');
        $bodyId = $body->getAttributeNS(self::NS_WSU, 'Id');

        $c14n = $body->C14N(true, false);
        $this->assertNotFalse($c14n);
        $expected = base64_encode(hash('sha256', $c14n, true));

        $refs = $this->makeXPath($signed)->query("//ds:Reference[@URI='#{$bodyId}']/ds:DigestValue");
        $this->assertNotFalse($refs);
        $digestNode = $refs->item(0);
        $this->assertInstanceOf(DOMElement::class, $digestNode);
        $this->assertSame($expected, trim($digestNode->textContent));
    }

    public function testMessagingDigestMatchesActualMessagingDigest(): void
    {
        $signed    = $this->signer->sign($this->makeEnvelope());
        $messaging = $this->requireNode($signed, '//eb:Messaging');
        $msgId     = $messaging->getAttributeNS(self::NS_WSU, 'Id');

        $c14n = $messaging->C14N(true, false);
        $this->assertNotFalse($c14n);
        $expected = base64_encode(hash('sha256', $c14n, true));

        $refs = $this->makeXPath($signed)->query("//ds:Reference[@URI='#{$msgId}']/ds:DigestValue");
        $this->assertNotFalse($refs);
        $digestNode = $refs->item(0);
        $this->assertInstanceOf(DOMElement::class, $digestNode);
        $this->assertSame($expected, trim($digestNode->textContent));
    }

    public function testSignatureVerifiesWithCertificate(): void
    {
        $signed     = $this->signer->sign($this->makeEnvelope());
        $signedInfo = $this->requireNode($signed, '//ds:SignedInfo');

        $c14n = $signedInfo->C14N(true, false);
        $this->assertNotFalse($c14n);

        $sigValueNode = $this->requireNode($signed, '//ds:SignatureValue');
        $sigValue     = base64_decode(trim($sigValueNode->textContent));

        $pubKey = openssl_pkey_get_public(self::$testCertificate);
        $this->assertNotFalse($pubKey);

        $result = openssl_verify($c14n, $sigValue, $pubKey, OPENSSL_ALGO_SHA256);
        $this->assertSame(1, $result, 'RSA-SHA256 signature did not verify with the certificate public key');
    }

    // ── Error handling ────────────────────────────────────────────────────────

    public function testThrowsWhenBodyMissing(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope"
                              xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soapenv:Header><eb:Messaging/></soapenv:Header>
            </soapenv:Envelope>
            XML);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('soap:Body not found in envelope');
        $this->signer->sign($doc);
    }

    public function testThrowsWhenMessagingMissing(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope">
              <soapenv:Header/>
              <soapenv:Body><payload/></soapenv:Body>
            </soapenv:Envelope>
            XML);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('eb:Messaging not found in envelope');
        $this->signer->sign($doc);
    }

    // ── Non-mutation ──────────────────────────────────────────────────────────

    public function testDoesNotMutateOriginalDocument(): void
    {
        $envelope = $this->makeEnvelope();
        $original = $envelope->saveXML();

        $this->signer->sign($envelope);

        $this->assertSame($original, $envelope->saveXML());
    }
}
