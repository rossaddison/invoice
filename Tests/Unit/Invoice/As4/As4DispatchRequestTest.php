<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4DispatchRequest;
use PHPUnit\Framework\TestCase;

class As4DispatchRequestTest extends TestCase
{
    private const string RECIPIENT  = '0088:9999999999999';
    private const string DOCTYPE    = 'busdox-docid-qns::urn:test:doc:1.0';
    private const string PROCESS    = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    private const string PAYLOAD    = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"/>';

    private function minimal(): As4DispatchRequest
    {
        return new As4DispatchRequest(
            recipientPartyId: self::RECIPIENT,
            documentTypeId:   self::DOCTYPE,
            processId:        self::PROCESS,
            payloadXml:       self::PAYLOAD,
        );
    }

    public function testConstructorStoresRecipientPartyId(): void
    {
        $this->assertSame(self::RECIPIENT, $this->minimal()->recipientPartyId);
    }

    public function testConstructorStoresDocumentTypeId(): void
    {
        $this->assertSame(self::DOCTYPE, $this->minimal()->documentTypeId);
    }

    public function testConstructorStoresProcessId(): void
    {
        $this->assertSame(self::PROCESS, $this->minimal()->processId);
    }

    public function testConstructorStoresPayloadXml(): void
    {
        $this->assertSame(self::PAYLOAD, $this->minimal()->payloadXml);
    }

    public function testOptionalMessageIdDefaultsToNull(): void
    {
        $this->assertNull($this->minimal()->messageId);
    }

    public function testOptionalConversationIdDefaultsToNull(): void
    {
        $this->assertNull($this->minimal()->conversationId);
    }

    public function testOptionalPayloadContentIdDefaultsToNull(): void
    {
        $this->assertNull($this->minimal()->payloadContentId);
    }

    public function testExplicitMessageIdStored(): void
    {
        $req = new As4DispatchRequest(
            recipientPartyId: self::RECIPIENT,
            documentTypeId:   self::DOCTYPE,
            processId:        self::PROCESS,
            payloadXml:       self::PAYLOAD,
            messageId:        'explicit-id@test.local',
        );
        $this->assertSame('explicit-id@test.local', $req->messageId);
    }

    public function testExplicitConversationIdStored(): void
    {
        $req = new As4DispatchRequest(
            recipientPartyId: self::RECIPIENT,
            documentTypeId:   self::DOCTYPE,
            processId:        self::PROCESS,
            payloadXml:       self::PAYLOAD,
            conversationId:   'conv-001',
        );
        $this->assertSame('conv-001', $req->conversationId);
    }

    public function testExplicitPayloadContentIdStored(): void
    {
        $req = new As4DispatchRequest(
            recipientPartyId:  self::RECIPIENT,
            documentTypeId:    self::DOCTYPE,
            processId:         self::PROCESS,
            payloadXml:        self::PAYLOAD,
            payloadContentId:  'payload@as4.local',
        );
        $this->assertSame('payload@as4.local', $req->payloadContentId);
    }
}
