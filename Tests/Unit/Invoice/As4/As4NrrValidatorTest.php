<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4NrrResult;
use App\Invoice\As4\As4NrrValidator;
use PHPUnit\Framework\TestCase;

class As4NrrValidatorTest extends TestCase
{
    private const string URI_BODY      = '#Body-abc123';
    private const string URI_MESSAGING = '#Messaging-def456';
    private const string DIGEST_BODY   = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
    private const string DIGEST_MSG    = 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB=';
    private const string DIGEST_OTHER  = 'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC=';

    private function validator(): As4NrrValidator
    {
        return new As4NrrValidator();
    }

    // ── Fixture builders ──────────────────────────────────────────────────────

    private function signedEnvelope(
        string $uri1 = self::URI_BODY,
        string $digest1 = self::DIGEST_BODY,
        string $uri2 = self::URI_MESSAGING,
        string $digest2 = self::DIGEST_MSG,
    ): string {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                           xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
              <soap:Header>
                <wsse:Security>
                  <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
                    <ds:SignedInfo>
                      <ds:Reference URI="{$uri1}">
                        <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                        <ds:DigestValue>{$digest1}</ds:DigestValue>
                      </ds:Reference>
                      <ds:Reference URI="{$uri2}">
                        <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                        <ds:DigestValue>{$digest2}</ds:DigestValue>
                      </ds:Reference>
                    </ds:SignedInfo>
                    <ds:SignatureValue>SIGNATUREVALUE==</ds:SignatureValue>
                  </ds:Signature>
                </wsse:Security>
              </soap:Header>
              <soap:Body/>
            </soap:Envelope>
            XML;
    }

    private function receiptEnvelope(
        string $uri1 = self::URI_BODY,
        string $digest1 = self::DIGEST_BODY,
        string $uri2 = self::URI_MESSAGING,
        string $digest2 = self::DIGEST_MSG,
    ): string {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                           xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soap:Header>
                <eb:Messaging>
                  <eb:SignalMessage>
                    <eb:MessageInfo>
                      <eb:MessageId>receipt-001@test.local</eb:MessageId>
                    </eb:MessageInfo>
                    <eb:Receipt>
                      <ebbp:NonRepudiationInformation
                          xmlns:ebbp="http://docs.oasis-open.org/ebcore/ns/NonRepudiation/v1.0">
                        <ebbp:MessagePartNRInformation>
                          <ds:Reference xmlns:ds="http://www.w3.org/2000/09/xmldsig#" URI="{$uri1}">
                            <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                            <ds:DigestValue>{$digest1}</ds:DigestValue>
                          </ds:Reference>
                        </ebbp:MessagePartNRInformation>
                        <ebbp:MessagePartNRInformation>
                          <ds:Reference xmlns:ds="http://www.w3.org/2000/09/xmldsig#" URI="{$uri2}">
                            <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                            <ds:DigestValue>{$digest2}</ds:DigestValue>
                          </ds:Reference>
                        </ebbp:MessagePartNRInformation>
                      </ebbp:NonRepudiationInformation>
                    </eb:Receipt>
                  </eb:SignalMessage>
                </eb:Messaging>
              </soap:Header>
              <soap:Body/>
            </soap:Envelope>
            XML;
    }

    // ── As4NrrResult value object ─────────────────────────────────────────────

    public function testSuccessResultIsValid(): void
    {
        $result = As4NrrResult::success();
        $this->assertTrue($result->valid);
    }

    public function testSuccessResultHasEmptyReason(): void
    {
        $result = As4NrrResult::success();
        $this->assertSame('', $result->reason);
    }

    public function testFailureResultIsNotValid(): void
    {
        $result = As4NrrResult::failure('something went wrong');
        $this->assertFalse($result->valid);
    }

    public function testFailureResultPreservesReason(): void
    {
        $result = As4NrrResult::failure('missing reference');
        $this->assertSame('missing reference', $result->reason);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function testValidateReturnsAs4NrrResult(): void
    {
        $result = $this->validator()->validate($this->signedEnvelope(), $this->receiptEnvelope());
        $this->assertInstanceOf(As4NrrResult::class, $result);
    }

    public function testValidateSucceedsWhenAllReferencesMatch(): void
    {
        $result = $this->validator()->validate($this->signedEnvelope(), $this->receiptEnvelope());
        $this->assertTrue($result->valid);
    }

    public function testValidSuccessHasEmptyReason(): void
    {
        $result = $this->validator()->validate($this->signedEnvelope(), $this->receiptEnvelope());
        $this->assertSame('', $result->reason);
    }

    // ── Digest mismatch ───────────────────────────────────────────────────────

    public function testFailsWhenBodyDigestMismatches(): void
    {
        $receipt = $this->receiptEnvelope(digest1: self::DIGEST_OTHER);
        $result  = $this->validator()->validate($this->signedEnvelope(), $receipt);
        $this->assertFalse($result->valid);
        $this->assertStringContainsString(self::URI_BODY, $result->reason);
    }

    public function testFailsWhenMessagingDigestMismatches(): void
    {
        $receipt = $this->receiptEnvelope(digest2: self::DIGEST_OTHER);
        $result  = $this->validator()->validate($this->signedEnvelope(), $receipt);
        $this->assertFalse($result->valid);
        $this->assertStringContainsString(self::URI_MESSAGING, $result->reason);
    }

    // ── Missing references ────────────────────────────────────────────────────

    public function testFailsWhenReceiptMissingOneReference(): void
    {
        // Receipt only has the Body reference, not the Messaging one
        $receipt = $this->receiptEnvelope(
            uri2:    self::URI_BODY,
            digest2: self::DIGEST_BODY,
        );
        $result = $this->validator()->validate($this->signedEnvelope(), $receipt);
        $this->assertFalse($result->valid);
    }

    public function testFailsWhenSignedEnvelopeHasNoReferences(): void
    {
        $signed = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
              <soap:Header/>
              <soap:Body/>
            </soap:Envelope>
            XML;
        $result = $this->validator()->validate($signed, $this->receiptEnvelope());
        $this->assertFalse($result->valid);
        $this->assertStringContainsString('no ds:Reference', $result->reason);
    }

    public function testFailsWhenReceiptHasNoNonRepudiationInfo(): void
    {
        $receipt = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                           xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soap:Header>
                <eb:Messaging>
                  <eb:SignalMessage>
                    <eb:Receipt/>
                  </eb:SignalMessage>
                </eb:Messaging>
              </soap:Header>
              <soap:Body/>
            </soap:Envelope>
            XML;
        $result = $this->validator()->validate($this->signedEnvelope(), $receipt);
        $this->assertFalse($result->valid);
        $this->assertStringContainsString('NonRepudiationInformation', $result->reason);
    }

    // ── Malformed inputs ──────────────────────────────────────────────────────

    public function testFailsOnMalformedSignedEnvelope(): void
    {
        $result = $this->validator()->validate('not xml <<>>', $this->receiptEnvelope());
        $this->assertFalse($result->valid);
    }

    public function testFailsOnMalformedReceiptEnvelope(): void
    {
        $result = $this->validator()->validate($this->signedEnvelope(), 'not xml <<>>');
        $this->assertFalse($result->valid);
    }

    public function testFailsOnEmptySignedEnvelope(): void
    {
        $result = $this->validator()->validate('', $this->receiptEnvelope());
        $this->assertFalse($result->valid);
    }

    public function testFailsOnEmptyReceiptEnvelope(): void
    {
        $result = $this->validator()->validate($this->signedEnvelope(), '');
        $this->assertFalse($result->valid);
    }

    // ── Reason message content ────────────────────────────────────────────────

    public function testFailureReasonMentionsMissingUri(): void
    {
        $receipt = $this->receiptEnvelope(
            uri1:    '#wrong-ref',
            digest1: self::DIGEST_BODY,
            uri2:    '#also-wrong',
            digest2: self::DIGEST_MSG,
        );
        $result = $this->validator()->validate($this->signedEnvelope(), $receipt);
        $this->assertFalse($result->valid);
        $this->assertStringContainsString(self::URI_BODY, $result->reason);
    }
}
