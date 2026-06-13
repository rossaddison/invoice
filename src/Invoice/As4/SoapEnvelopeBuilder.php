<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;

/**
 * Builds a SOAP 1.2 / ebMS3 envelope ready for WS-Security signing.
 *
 * Output structure:
 *   soapenv:Envelope
 *     soapenv:Header
 *       eb:Messaging (mustUnderstand)
 *         eb:UserMessage
 *           eb:MessageInfo (Timestamp + MessageId)
 *           eb:PartyInfo   (From + To with ISO 6523 party IDs)
 *           eb:CollaborationInfo (Service + Action + ConversationId)
 *           eb:PayloadInfo (PartInfo with MimeType property)
 *     soapenv:Body
 *       [payload XML imported as child element]
 *
 * @psalm-suppress UnusedClass
 */
final class SoapEnvelopeBuilder implements As4EnvelopeBuilderInterface
{
    private const string NS_SOAP = As4Constants::SOAP_NS;
    private const string NS_EB   = As4Constants::EBMS3_NS;

    private const string ROLE_INITIATOR =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/initiator'; // NOSONAR
    private const string ROLE_RESPONDER =
        'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/responder'; // NOSONAR

    // ISO 6523 scheme prefix for eb:PartyId type attributes
    private const string PARTY_TYPE_PREFIX = 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:';

    #[\Override]
    public function build(SoapEnvelopeParams $params): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;

        $envelope = $doc->createElementNS(self::NS_SOAP, 'soapenv:Envelope');
        $doc->appendChild($envelope);

        $header = $doc->createElementNS(self::NS_SOAP, 'soapenv:Header');
        $envelope->appendChild($header);

        $header->appendChild($this->buildMessaging($doc, $params));

        $body = $doc->createElementNS(self::NS_SOAP, 'soapenv:Body');
        $envelope->appendChild($body);
        $body->appendChild($this->importPayload($doc, $params->payloadXml));

        return $doc;
    }

    private function buildMessaging(DOMDocument $doc, SoapEnvelopeParams $params): DOMElement
    {
        $ts = $params->timestamp ?? gmdate('Y-m-d\TH:i:s\Z');

        $messaging = $doc->createElementNS(self::NS_EB, 'eb:Messaging');
        $messaging->setAttributeNS(self::NS_SOAP, 'soapenv:mustUnderstand', 'true');

        $userMessage = $doc->createElementNS(self::NS_EB, 'eb:UserMessage');
        $messaging->appendChild($userMessage);

        $userMessage->appendChild($this->buildMessageInfo($doc, $params->messageId, $ts));
        $userMessage->appendChild($this->buildPartyInfo($doc, $params->senderPartyId,
            $params->receiverPartyId));
        $userMessage->appendChild($this->buildCollaborationInfo($doc, $params->service,
            $params->action, $params->conversationId));
        $userMessage->appendChild($this->buildPayloadInfo($doc, $params->payloadContentId));

        return $messaging;
    }

    private function buildMessageInfo(DOMDocument $doc, string $messageId, string $timestamp): DOMElement
    {
        $info = $doc->createElementNS(self::NS_EB, 'eb:MessageInfo');
        $info->appendChild($this->el($doc, 'eb:Timestamp', $timestamp));
        $info->appendChild($this->el($doc, 'eb:MessageId', $messageId));
        return $info;
    }

    private function buildPartyInfo(DOMDocument $doc, string $senderPartyId, string $receiverPartyId): DOMElement
    {
        $partyInfo = $doc->createElementNS(self::NS_EB, 'eb:PartyInfo');
        $partyInfo->appendChild($this->buildParty($doc, 'eb:From', $senderPartyId, self::ROLE_INITIATOR));
        $partyInfo->appendChild($this->buildParty($doc, 'eb:To', $receiverPartyId, self::ROLE_RESPONDER));
        return $partyInfo;
    }

    private function buildParty(DOMDocument $doc, string $tag, string $partyId, string $role): DOMElement
    {
        ['scheme' => $scheme, 'value' => $value] = $this->parsePartyId($partyId);

        $party   = $doc->createElementNS(self::NS_EB, $tag);
        $partyEl = $doc->createElementNS(self::NS_EB, 'eb:PartyId');
        $partyEl->setAttribute('type', self::PARTY_TYPE_PREFIX . $scheme);
        $partyEl->appendChild($doc->createTextNode($value));
        $party->appendChild($partyEl);
        $party->appendChild($this->el($doc, 'eb:Role', $role));
        return $party;
    }

    private function buildCollaborationInfo(
        DOMDocument $doc,
        string $service,
        string $action,
        string $conversationId,
    ): DOMElement {
        $info      = $doc->createElementNS(self::NS_EB, 'eb:CollaborationInfo');
        $serviceEl = $doc->createElementNS(self::NS_EB, 'eb:Service');
        $serviceEl->setAttribute('type', $service);
        $serviceEl->appendChild($doc->createTextNode($service));
        $info->appendChild($serviceEl);
        $info->appendChild($this->el($doc, 'eb:Action', $action));
        $info->appendChild($this->el($doc, 'eb:ConversationId', $conversationId));
        return $info;
    }

    private function buildPayloadInfo(DOMDocument $doc, string $contentId): DOMElement
    {
        $payloadInfo = $doc->createElementNS(self::NS_EB, 'eb:PayloadInfo');
        $partInfo    = $doc->createElementNS(self::NS_EB, 'eb:PartInfo');
        $partInfo->setAttribute('href', 'cid:' . $contentId);

        $partProps = $doc->createElementNS(self::NS_EB, 'eb:PartProperties');
        $mimeType  = $doc->createElementNS(self::NS_EB, 'eb:Property');
        $mimeType->setAttribute('name', 'MimeType');
        $mimeType->appendChild($doc->createTextNode(As4Constants::MIME_XML));
        $partProps->appendChild($mimeType);
        $partInfo->appendChild($partProps);

        $payloadInfo->appendChild($partInfo);
        return $payloadInfo;
    }

    private function importPayload(DOMDocument $doc, string $payloadXml): DOMElement
    {
        $payloadDoc = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $payloadDoc->loadXML($payloadXml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);

        if ($loaded === false) {
            throw new \InvalidArgumentException('payloadXml is not well-formed XML');
        }
        if (!$payloadDoc->documentElement instanceof DOMElement) {
            throw new \InvalidArgumentException('payloadXml has no document element');
        }
        $imported = $doc->importNode($payloadDoc->documentElement, true);
        if (!$imported instanceof DOMElement) {
            throw new \UnexpectedValueException('importNode did not return a DOMElement');
        }
        return $imported;
    }

    private function el(DOMDocument $doc, string $tag, string $textContent): DOMElement
    {
        $el = $doc->createElementNS(self::NS_EB, $tag);
        $el->appendChild($doc->createTextNode($textContent));
        return $el;
    }

    /**
     * @return array{scheme: string, value: string}
     */
    private function parsePartyId(string $partyId): array
    {
        $parts = explode(':', $partyId, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException("Party ID must be 'scheme:value', got: {$partyId}");
        }
        return ['scheme' => $parts[0], 'value' => $parts[1]];
    }
}
