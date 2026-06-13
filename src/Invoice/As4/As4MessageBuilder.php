<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;

/**
 * AS4 Message Builder
 *
 * Constructs SOAP 1.2 messages with ebMS3 headers per eDelivery AS4 2.0
 * Common Profile specification.
 *
 * @psalm-suppress UnusedClass
 */
class As4MessageBuilder
{
    private DOMDocument $doc;
    private DOMElement $soapEnvelope;
    private DOMElement $soapBody;
    private DOMElement $wssHeader;
    private DOMElement $ebMessaging;

    public function __construct()
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;
        $this->doc->preserveWhiteSpace = false;

        $this->soapEnvelope = $this->doc->createElementNS(
            As4Constants::SOAP_NS,
            'env:Envelope'
        );
        $this->soapEnvelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:env', As4Constants::SOAP_NS);
        $this->soapEnvelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsse', As4Constants::WSS_NS);
        $this->soapEnvelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsu', As4Constants::WSS_UTIL_NS);
        $this->soapEnvelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:eb', As4Constants::EBMS3_NS);
        $this->soapEnvelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', As4Constants::XMLDSIG_NS);
        $this->soapEnvelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xenc', As4Constants::XMLENC_NS);
        $this->soapEnvelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dsig-more', As4Constants::XMLDSIG_MORE_NS);

        $this->doc->appendChild($this->soapEnvelope);

        $soapHeader = $this->doc->createElementNS(As4Constants::SOAP_NS, 'env:Header');
        $this->soapEnvelope->appendChild($soapHeader);

        $this->wssHeader = $this->doc->createElementNS(As4Constants::WSS_NS, 'wsse:Security');
        $this->wssHeader->setAttribute('env:mustUnderstand', 'true');
        $soapHeader->appendChild($this->wssHeader);

        $this->ebMessaging = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:Messaging');
        $this->ebMessaging->setAttribute('env:mustUnderstand', 'true');
        $soapHeader->appendChild($this->ebMessaging);

        $this->soapBody = $this->doc->createElementNS(As4Constants::SOAP_NS, 'env:Body');
        $this->soapBody->setAttributeNS(As4Constants::WSS_UTIL_NS, 'wsu:Id', '_' . $this->generateUuid());
        $this->soapEnvelope->appendChild($this->soapBody);
    }

    /**
     * Add a UserMessage to the ebMS3 Messaging header.
     *
     * @param array<string, string> $properties
     */
    public function addUserMessage(
        string $messageId,
        string $conversationId,
        string $service,
        string $action,
        string $senderPartyId,
        string $senderRole,
        string $receiverPartyId,
        string $receiverRole,
        ?string $refToMessageId = null,
        array $properties = []
    ): DOMElement {
        $userMessage = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:UserMessage');

        $messageInfo = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:MessageInfo');
        $this->appendElement($messageInfo, 'eb:Timestamp', date('c'));
        $this->appendElement($messageInfo, 'eb:MessageId', $messageId);
        if ($refToMessageId !== null) {
            $this->appendElement($messageInfo, 'eb:RefToMessageId', $refToMessageId);
        }
        $userMessage->appendChild($messageInfo);

        $partyInfo = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:PartyInfo');

        $from = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:From');
        $fromPartyId = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:PartyId');
        $fromPartyId->setAttribute('type', 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088');
        $fromPartyId->nodeValue = $senderPartyId;
        $from->appendChild($fromPartyId);
        $this->appendElement($from, 'eb:Role', $senderRole);
        $partyInfo->appendChild($from);

        $to = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:To');
        $toPartyId = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:PartyId');
        $toPartyId->setAttribute('type', 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088');
        $toPartyId->nodeValue = $receiverPartyId;
        $to->appendChild($toPartyId);
        $this->appendElement($to, 'eb:Role', $receiverRole);
        $partyInfo->appendChild($to);

        $userMessage->appendChild($partyInfo);

        $collabInfo = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:CollaborationInfo');
        $this->appendElement($collabInfo, 'eb:ConversationId', $conversationId);
        $this->appendElement($collabInfo, 'eb:Service', $service);
        $this->appendElement($collabInfo, 'eb:Action', $action);
        $userMessage->appendChild($collabInfo);

        if ($properties !== []) {
            $messageProps = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:MessageProperties');
            foreach ($properties as $name => $value) {
                $prop = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:Property');
                $prop->setAttribute('name', $name);
                $prop->nodeValue = $value;
                $messageProps->appendChild($prop);
            }
            $userMessage->appendChild($messageProps);
        }

        $this->ebMessaging->appendChild($userMessage);
        return $userMessage;
    }

    /**
     * Add PayloadInfo to UserMessage with support for multiple MIME parts.
     *
     * @param array<int, array{contentId?: string, mimeType?: string, charset?: string, compressed?: bool}> $payloads
     */
    public function addPayloadInfo(DOMElement $userMessage, array $payloads): void
    {
        $payloadInfo = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:PayloadInfo');

        foreach ($payloads as $idx => $payload) {
            $partInfo = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:PartInfo');
            $contentId = $payload['contentId'] ?? "part-{$idx}@invoice.example.com";
            $partInfo->setAttribute('href', "cid:{$contentId}");

            $partProps = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:PartProperties');

            $mimeProp = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:Property');
            $mimeProp->setAttribute('name', 'MimeType');
            $mimeProp->nodeValue = $payload['mimeType'] ?? As4Constants::MIME_XML;
            $partProps->appendChild($mimeProp);

            if (isset($payload['charset'])) {
                $charsetProp = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:Property');
                $charsetProp->setAttribute('name', 'CharacterSet');
                $charsetProp->nodeValue = $payload['charset'];
                $partProps->appendChild($charsetProp);
            }

            if (isset($payload['compressed']) && $payload['compressed']) {
                $compProp = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:Property');
                $compProp->setAttribute('name', 'CompressionType');
                $compProp->nodeValue = As4Constants::COMPRESSION_TYPE;
                $partProps->appendChild($compProp);
            }

            $partInfo->appendChild($partProps);
            $payloadInfo->appendChild($partInfo);
        }

        $userMessage->appendChild($payloadInfo);
    }

    /**
     * Add a SignalMessage (Receipt or Error).
     *
     * @param array{refId?: string, digestValue?: string}|null $receiptData
     * @param array{category?: string, refToMessageId?: string, code?: string, shortDescription?: string, description?: string}|null $errorData
     */
    public function addSignalMessage(
        string $signalId,
        string $refToMessageId,
        ?array $receiptData = null,
        ?array $errorData = null
    ): void {
        $signalMessage = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:SignalMessage');

        $messageInfo = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:MessageInfo');
        $this->appendElement($messageInfo, 'eb:Timestamp', date('c'));
        $this->appendElement($messageInfo, 'eb:MessageId', $signalId);
        $this->appendElement($messageInfo, 'eb:RefToMessageId', $refToMessageId);
        $signalMessage->appendChild($messageInfo);

        if ($receiptData !== null) {
            $this->addReceipt($signalMessage, $receiptData);
        } elseif ($errorData !== null) {
            $this->addError($signalMessage, $errorData);
        }

        $this->ebMessaging->appendChild($signalMessage);
    }

    /**
     * @param array{refId?: string, digestValue?: string} $receiptData
     */
    private function addReceipt(DOMElement $signalMessage, array $receiptData): void
    {
        $receipt = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:Receipt');

        $ref = $this->doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:Reference');
        $ref->setAttribute('URI', '#_' . ($receiptData['refId'] ?? 'original-message'));

        $digestMethod = $this->doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', As4Constants::HASH_ALGORITHM);
        $ref->appendChild($digestMethod);

        $digestValue = $this->doc->createElementNS(As4Constants::XMLDSIG_NS, 'ds:DigestValue');
        $digestValue->nodeValue = $receiptData['digestValue'] ?? '';
        $ref->appendChild($digestValue);

        $receipt->appendChild($ref);
        $signalMessage->appendChild($receipt);
    }

    /**
     * @param array{category?: string, refToMessageId?: string, code?: string, shortDescription?: string, description?: string} $errorData
     */
    private function addError(DOMElement $signalMessage, array $errorData): void
    {
        $error = $this->doc->createElementNS(As4Constants::EBMS3_NS, 'eb:Error');
        $error->setAttribute('category', $errorData['category'] ?? 'Processing');
        $error->setAttribute('refToMessageInError', $errorData['refToMessageId'] ?? '');

        if (isset($errorData['code'])) {
            $error->setAttribute('errorCode', $errorData['code']);
        }

        $this->appendElement($error, 'eb:ErrorCode', $errorData['code'] ?? 'EBMS:0001');
        if (isset($errorData['shortDescription'])) {
            $this->appendElement($error, 'eb:ShortDescription', $errorData['shortDescription']);
        }
        if (isset($errorData['description'])) {
            $this->appendElement($error, 'eb:Description', $errorData['description']);
        }

        $signalMessage->appendChild($error);
    }

    public function addTimestamp(int $expirationSeconds = 3600): void
    {
        $timestamp = $this->doc->createElementNS(As4Constants::WSS_UTIL_NS, 'wsu:Timestamp');
        $timestamp->setAttributeNS(As4Constants::WSS_UTIL_NS, 'wsu:Id', 'TS-' . $this->generateUuid());

        $created = $this->doc->createElementNS(As4Constants::WSS_UTIL_NS, 'wsu:Created');
        $created->nodeValue = date('c');
        $timestamp->appendChild($created);

        $expires = $this->doc->createElementNS(As4Constants::WSS_UTIL_NS, 'wsu:Expires');
        $expires->nodeValue = date('c', time() + $expirationSeconds);
        $timestamp->appendChild($expires);

        $this->wssHeader->appendChild($timestamp);
    }

    public function addBinarySecurityToken(string $certData, ?string $tokenId = null): void
    {
        $tokenId = $tokenId ?? 'X509-' . $this->generateUuid();

        $binaryToken = $this->doc->createElementNS(As4Constants::WSS_NS, 'wsse:BinarySecurityToken');
        $binaryToken->setAttribute('EncodingType', As4Constants::WSS_ENCODING_BASE64);
        $binaryToken->setAttribute('ValueType', As4Constants::WSS_TOKEN_X509V3);
        $binaryToken->setAttributeNS(As4Constants::WSS_UTIL_NS, 'wsu:Id', $tokenId);
        $binaryToken->nodeValue = $certData;

        $this->wssHeader->appendChild($binaryToken);
    }

    public function getXml(): string
    {
        $xml = $this->doc->saveXML();
        if ($xml === false) {
            throw new As4ParseException('DOMDocument serialization failed');
        }
        return $xml;
    }

    public function getDocument(): DOMDocument
    {
        return $this->doc;
    }

    private function appendElement(DOMElement $parent, string $tagName, string $value = ''): DOMElement
    {
        $elem = $this->doc->createElementNS(As4Constants::EBMS3_NS, $tagName);
        if ($value !== '') {
            $elem->nodeValue = $value;
        }
        $parent->appendChild($elem);
        return $elem;
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
