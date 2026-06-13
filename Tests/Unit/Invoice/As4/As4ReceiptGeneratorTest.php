<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4ReceiptGenerator;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

class As4ReceiptGeneratorTest extends TestCase
{
    private const string INBOUND_ID = 'msg-001@sender.example.com';
    private const string XML_BODY   = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Body/></env:Envelope>';

    private function sut(): As4ReceiptGenerator
    {
        return new As4ReceiptGenerator();
    }

    private function loadXml(string $xml): DOMDocument
    {
        $doc = new DOMDocument();
        $loaded = $doc->loadXML($xml);
        $this->assertTrue($loaded, 'Generator output must be well-formed XML');
        return $doc;
    }

    /** Returns the first element with the given namespace+localName, asserting it exists. */
    private function firstElement(DOMDocument $doc, string $ns, string $localName): DOMElement
    {
        $node = $doc->getElementsByTagNameNS($ns, $localName)->item(0);
        $this->assertInstanceOf(DOMElement::class, $node, "Expected <{$localName}> element in output");
        return $node;
    }

    public function testGenerateReturnsWellFormedXml(): void
    {
        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($this->sut()->generate(self::INBOUND_ID, self::XML_BODY)));
    }

    public function testGenerateContainsRefToMessageId(): void
    {
        $doc = $this->loadXml($this->sut()->generate(self::INBOUND_ID, self::XML_BODY));
        $el  = $this->firstElement($doc, As4Constants::EBMS3_NS, 'RefToMessageId');

        $this->assertSame(self::INBOUND_ID, $el->textContent);
    }

    public function testGenerateContainsEbReceipt(): void
    {
        $doc = $this->loadXml($this->sut()->generate(self::INBOUND_ID, self::XML_BODY));

        $this->assertSame(1, $doc->getElementsByTagNameNS(As4Constants::EBMS3_NS, 'Receipt')->count());
    }

    public function testGenerateContainsCorrectSha256Digest(): void
    {
        $expectedDigest = base64_encode(hash('sha256', self::XML_BODY, true));
        $doc            = $this->loadXml($this->sut()->generate(self::INBOUND_ID, self::XML_BODY));
        $el             = $this->firstElement($doc, As4Constants::XMLDSIG_NS, 'DigestValue');

        $this->assertSame($expectedDigest, $el->textContent);
    }

    public function testGenerateContainsSha256DigestMethodAlgorithm(): void
    {
        $doc = $this->loadXml($this->sut()->generate(self::INBOUND_ID, self::XML_BODY));
        $el  = $this->firstElement($doc, As4Constants::XMLDSIG_NS, 'DigestMethod');

        $this->assertSame(As4Constants::HASH_ALGORITHM, $el->getAttribute('Algorithm'));
    }

    public function testTwoCallsProduceDifferentSignalIds(): void
    {
        $sut  = $this->sut();
        $doc1 = $this->loadXml($sut->generate(self::INBOUND_ID, self::XML_BODY));
        $doc2 = $this->loadXml($sut->generate(self::INBOUND_ID, self::XML_BODY));

        $id1 = $this->firstElement($doc1, As4Constants::EBMS3_NS, 'MessageId')->textContent;
        $id2 = $this->firstElement($doc2, As4Constants::EBMS3_NS, 'MessageId')->textContent;

        $this->assertNotSame($id1, $id2);
    }

    public function testDifferentXmlBodyProducesDifferentDigest(): void
    {
        $sut   = $this->sut();
        $body1 = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Body>one</env:Body></env:Envelope>';
        $body2 = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Body>two</env:Body></env:Envelope>';

        $doc1 = $this->loadXml($sut->generate(self::INBOUND_ID, $body1));
        $doc2 = $this->loadXml($sut->generate(self::INBOUND_ID, $body2));

        $digest1 = $this->firstElement($doc1, As4Constants::XMLDSIG_NS, 'DigestValue')->textContent;
        $digest2 = $this->firstElement($doc2, As4Constants::XMLDSIG_NS, 'DigestValue')->textContent;

        $this->assertNotSame($digest1, $digest2);
    }
}
