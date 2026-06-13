<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4SecurityHandler;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Tests for As4SecurityHandler sign+verify round-trip.
 *
 * Fixtures: as4_test_sign_key.pem  — Ed25519 secret key in sodium 64-byte format (PEM-wrapped)
 *           as4_test_sign_cert.pem — matching self-signed X.509 certificate
 *
 * Both were generated once with:
 *   openssl genpkey -algorithm ed25519 -out as4_test_sign_key_pkcs8.pem
 *   openssl req -new -x509 -key as4_test_sign_key_pkcs8.pem -out as4_test_sign_cert.pem -days 3650 -subj "//CN=as4-test"
 * The PKCS#8 key was then converted to the sodium 64-byte format expected by
 * As4SecurityHandler::loadEd25519PrivateKey() using sodium_crypto_sign_seed_keypair().
 */
class As4SecurityHandlerTest extends TestCase
{
    private static string $certFile;
    private static string $keyFile;

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        self::$certFile = __DIR__ . '/as4_test_sign_cert.pem';
        self::$keyFile  = __DIR__ . '/as4_test_sign_key.pem';
    }

    private function createHandler(): As4SecurityHandler
    {
        return new As4SecurityHandler(
            signingCertPath: self::$certFile,
            signingKeyPath:  self::$keyFile,
            encryptCertPath: self::$certFile,
        );
    }

    private function buildMinimalSoapDoc(): DOMDocument
    {
        $doc      = new DOMDocument('1.0', 'UTF-8');
        $env      = $doc->createElementNS(As4Constants::SOAP_NS, 'env:Envelope');
        $doc->appendChild($env);

        $header   = $doc->createElementNS(As4Constants::SOAP_NS, 'env:Header');
        $env->appendChild($header);

        $security = $doc->createElementNS(As4Constants::WSS_NS, 'wsse:Security');
        $security->setAttributeNS(As4Constants::SOAP_NS, 'env:mustUnderstand', 'true');
        $header->appendChild($security);

        $body     = $doc->createElementNS(As4Constants::SOAP_NS, 'env:Body');
        $env->appendChild($body);

        return $doc;
    }

    // ── Construction ──────────────────────────────────────────────────────────

    public function testConstructorSucceedsWithValidCertAndKey(): void
    {
        $this->assertInstanceOf(As4SecurityHandler::class, $this->createHandler());
    }

    // ── BinarySecurityToken ───────────────────────────────────────────────────

    public function testAddBinarySecurityTokenInsertsElement(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $handler->addBinarySecurityToken($doc);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('wsse', As4Constants::WSS_NS);
        $nodes = $xpath->query('//wsse:BinarySecurityToken');

        $this->assertInstanceOf(DOMNodeList::class, $nodes);
        $this->assertSame(1, $nodes->length);
    }

    public function testBinarySecurityTokenHasNonEmptyContent(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $token = $handler->addBinarySecurityToken($doc);

        $this->assertNotEmpty($token->textContent);
    }

    // ── Sign ──────────────────────────────────────────────────────────────────

    public function testSignMessageInsertsSignatureElement(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $handler->addBinarySecurityToken($doc);
        $handler->signMessage($doc, []);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $sigs = $xpath->query('//ds:Signature');

        $this->assertInstanceOf(DOMNodeList::class, $sigs);
        $this->assertSame(1, $sigs->length);
    }

    public function testSignMessageInsertsSignatureValue(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $handler->addBinarySecurityToken($doc);
        $handler->signMessage($doc, []);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $nodes = $xpath->query('//ds:SignatureValue');

        $this->assertInstanceOf(DOMNodeList::class, $nodes);
        $node = $nodes->item(0);
        $this->assertInstanceOf(DOMElement::class, $node);
        $this->assertNotEmpty($node->textContent);
    }

    // ── Verify ───────────────────────────────────────────────────────────────

    public function testVerifySignatureReturnsTrueForValidSignature(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $handler->addBinarySecurityToken($doc);
        $handler->signMessage($doc, []);

        $this->assertTrue($handler->verifySignature($doc));
    }

    public function testVerifySignatureReturnsFalseWhenSignatureValueTampered(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $handler->addBinarySecurityToken($doc);
        $handler->signMessage($doc, []);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $sigValues = $xpath->query('//ds:SignatureValue');
        $this->assertInstanceOf(DOMNodeList::class, $sigValues);
        $node = $sigValues->item(0);
        $this->assertInstanceOf(DOMElement::class, $node);
        $node->textContent = str_repeat('A', 88);

        $this->assertFalse($handler->verifySignature($doc));
    }

    public function testVerifySignatureReturnsFalseWhenSignatureAbsent(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $this->assertFalse($handler->verifySignature($doc));
    }

    public function testVerifySignatureReturnsFalseWhenSignedInfoTampered(): void
    {
        $doc     = $this->buildMinimalSoapDoc();
        $handler = $this->createHandler();

        $handler->addBinarySecurityToken($doc);
        $handler->signMessage($doc, []);

        // Alter the declared signature algorithm — changes the canonicalized SignedInfo bytes
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);
        $methods = $xpath->query('//ds:SignatureMethod');
        $this->assertInstanceOf(DOMNodeList::class, $methods);
        $method = $methods->item(0);
        $this->assertInstanceOf(DOMElement::class, $method);
        $method->setAttribute('Algorithm', 'http://tampered.example/algorithm');

        $this->assertFalse($handler->verifySignature($doc));
    }

    public function testTwoSignCallsProduceDifferentSignatureIds(): void
    {
        $handler = $this->createHandler();

        $doc1 = $this->buildMinimalSoapDoc();
        $handler->addBinarySecurityToken($doc1);
        $sig1 = $handler->signMessage($doc1, []);

        $doc2 = $this->buildMinimalSoapDoc();
        $handler->addBinarySecurityToken($doc2);
        $sig2 = $handler->signMessage($doc2, []);

        $this->assertNotSame($sig1->getAttribute('Id'), $sig2->getAttribute('Id'));
    }
}
