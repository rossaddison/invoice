<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4DispatchRequest;
use App\Invoice\As4\As4DispatchRequestFactory;
use App\Invoice\As4\PeppolPModeFactory;
use App\Invoice\As4\PMode;
use PHPUnit\Framework\TestCase;

class PModeTest extends TestCase
{
    private const string SENDER_ID    = '0088:1111111111111';
    private const string RECIPIENT_ID = '0088:9999999999999';
    private const string ENDPOINT_URL = 'https://as4.example.com/as4';
    private const string PAYLOAD_XML  = '<Invoice/>';
    private const string PROCESS      = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    private const string DOCTYPE      = 'busdox-docid-qns::urn:test:doc:1.0';

    // ── PMode constructor / defaults ──────────────────────────────────────────

    public function testConstructorSetsInitiatorParty(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(self::SENDER_ID, $mode->getInitiatorParty());
    }

    public function testConstructorSetsResponderParty(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(self::RECIPIENT_ID, $mode->getResponderParty());
    }

    public function testConstructorSetsResponderProtocolAddress(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(self::ENDPOINT_URL, $mode->getResponderProtocolAddress());
    }

    public function testConstructorSetsService(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(self::PROCESS, $mode->getService());
    }

    public function testConstructorSetsAction(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(self::DOCTYPE, $mode->getAction());
    }

    public function testDefaultMepIsOneWay(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(As4Constants::MEP_ONE_WAY, $mode->getMep());
    }

    public function testDefaultMepBindingIsPush(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(As4Constants::MEPBINDING_PUSH, $mode->getMepBinding());
    }

    public function testDefaultMpcIsCorrect(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(As4Constants::DEFAULT_MPC, $mode->getMpc());
    }

    public function testSigningEnabledByDefault(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertTrue($mode->isSigningEnabled());
    }

    public function testEncryptionEnabledByDefault(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertTrue($mode->isEncryptionEnabled());
    }

    public function testReceptionAwarenessEnabledByDefault(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertTrue($mode->isReceptionAwarenessEnabled());
    }

    public function testDefaultMaxRetriesIsThree(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertSame(3, $mode->getMaxRetries());
    }

    public function testSendReceiptEnabledByDefault(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $this->assertTrue($mode->shouldSendReceipt());
    }

    // ── PMode toArray ─────────────────────────────────────────────────────────

    public function testToArrayContainsInitiatorParty(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        /** @var array{party: string} $section */
        $section = $mode->toArray()['initiator'];
        $this->assertSame(self::SENDER_ID, $section['party']);
    }

    public function testToArrayContainsResponderParty(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        /** @var array{party: string} $section */
        $section = $mode->toArray()['responder'];
        $this->assertSame(self::RECIPIENT_ID, $section['party']);
    }

    public function testToArrayContainsService(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        /** @var array{service: string} $section */
        $section = $mode->toArray()['business_info'];
        $this->assertSame(self::PROCESS, $section['service']);
    }

    public function testToArrayContainsAction(): void
    {
        $mode = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        /** @var array{action: string} $section */
        $section = $mode->toArray()['business_info'];
        $this->assertSame(self::DOCTYPE, $section['action']);
    }

    // ── PeppolPModeFactory ────────────────────────────────────────────────────

    public function testBillingInvoiceHasPeppolProcess(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::PEPPOL_PROCESS_BIS3, $mode->getService());
    }

    public function testBillingInvoiceHasInvoiceDocType(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::PEPPOL_DOCTYPE_INVOICE_BIS3, $mode->getAction());
    }

    public function testBillingInvoiceHasInitiatorParty(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(self::SENDER_ID, $mode->getInitiatorParty());
    }

    public function testBillingInvoiceHasResponderParty(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(self::RECIPIENT_ID, $mode->getResponderParty());
    }

    public function testBillingInvoiceHasResponderAddress(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(self::ENDPOINT_URL, $mode->getResponderProtocolAddress());
    }

    public function testBillingInvoiceHasOneWayMep(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::MEP_ONE_WAY, $mode->getMep());
    }

    public function testBillingInvoiceHasPushBinding(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::MEPBINDING_PUSH, $mode->getMepBinding());
    }

    public function testBillingInvoiceHasInitiatorRole(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::ROLE_INITIATOR, $mode->getInitiatorRole());
    }

    public function testBillingInvoiceHasResponderRole(): void
    {
        $mode = PeppolPModeFactory::billingInvoice(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::ROLE_RESPONDER, $mode->getResponderRole());
    }

    public function testBillingCreditNoteHasCreditNoteDocType(): void
    {
        $mode = PeppolPModeFactory::billingCreditNote(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::PEPPOL_DOCTYPE_CREDITNOTE_BIS3, $mode->getAction());
    }

    public function testBillingCreditNoteHasPeppolProcess(): void
    {
        $mode = PeppolPModeFactory::billingCreditNote(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL);
        $this->assertSame(As4Constants::PEPPOL_PROCESS_BIS3, $mode->getService());
    }

    // ── As4DispatchRequestFactory ─────────────────────────────────────────────

    public function testFromPModeUsesResponderAsRecipient(): void
    {
        $mode    = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $request = As4DispatchRequestFactory::fromPMode($mode, self::PAYLOAD_XML);
        $this->assertSame(self::RECIPIENT_ID, $request->recipientPartyId);
    }

    public function testFromPModeUsesActionAsDocumentTypeId(): void
    {
        $mode    = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $request = As4DispatchRequestFactory::fromPMode($mode, self::PAYLOAD_XML);
        $this->assertSame(self::DOCTYPE, $request->documentTypeId);
    }

    public function testFromPModeUsesServiceAsProcessId(): void
    {
        $mode    = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $request = As4DispatchRequestFactory::fromPMode($mode, self::PAYLOAD_XML);
        $this->assertSame(self::PROCESS, $request->processId);
    }

    public function testFromPModeIncludesPayloadXml(): void
    {
        $mode    = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $request = As4DispatchRequestFactory::fromPMode($mode, self::PAYLOAD_XML);
        $this->assertSame(self::PAYLOAD_XML, $request->payloadXml);
    }

    public function testFromPModeReturnsDispatchRequest(): void
    {
        $mode    = new PMode(self::SENDER_ID, self::RECIPIENT_ID, self::ENDPOINT_URL, self::PROCESS, self::DOCTYPE);
        $request = As4DispatchRequestFactory::fromPMode($mode, self::PAYLOAD_XML);
        $this->assertInstanceOf(As4DispatchRequest::class, $request);
    }
}
