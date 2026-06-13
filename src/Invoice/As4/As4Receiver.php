<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;
use Psr\Log\LoggerInterface;

/**
 * AS4 Inbound Message Receiver
 *
 * Handles incoming AS4 signal messages (receipts, errors)
 * and inbound business documents from trading partners.
 *
 * Per eDelivery AS4 2.0 section 3.3.2: Receipts and errors are sent
 * synchronously on the response channel (HTTP 200 with signal message body).
 *
 * @psalm-suppress UnusedClass
 */
class As4Receiver
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Parse incoming multipart AS4 message.
     */
    public function receive(string $contentType, string $body): As4InboundMessage
    {
        $boundary = $this->extractBoundary($contentType);
        if ($boundary === null) {
            throw new As4ParseException('No boundary in multipart message');
        }

        $parts = explode("--{$boundary}", $body);

        if (!isset($parts[1])) {
            throw new As4ParseException('SOAP envelope not found in multipart message');
        }

        $soapPart = $this->extractPartBody($parts[1]);
        if ($soapPart === '') {
            throw new As4ParseException('Invalid SOAP part');
        }

        $doc = new DOMDocument();
        $doc->loadXML($soapPart);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('eb', As4Constants::EBMS3_NS);
        $xpath->registerNamespace('ds', As4Constants::XMLDSIG_NS);

        $userMessages = $xpath->query('//eb:UserMessage');
        $signalMessages = $xpath->query('//eb:SignalMessage');

        if ($userMessages !== false && $userMessages->length > 0) {
            $this->logger->info('Received AS4 UserMessage');
            return $this->parseUserMessage($soapPart, $parts, $xpath);
        } elseif ($signalMessages !== false && $signalMessages->length > 0) {
            $this->logger->info('Received AS4 SignalMessage');
            return $this->parseSignalMessage($soapPart, $xpath);
        }

        throw new As4ParseException('Neither UserMessage nor SignalMessage found');
    }

    /**
     * Parse UserMessage (inbound business document).
     *
     * @param string[] $mimeparts
     */
    private function parseUserMessage(
        string $soapPart,
        array $mimeparts,
        \DOMXPath $xpath
    ): As4InboundMessage {
        $msgNode = $xpath->query('//eb:UserMessage')->item(0);
        if (!$msgNode instanceof DOMElement) {
            throw new As4ParseException('UserMessage element not found');
        }

        $messageId = $this->queryText($xpath, 'eb:MessageInfo/eb:MessageId', $msgNode);
        $conversationId = $this->queryText($xpath, 'eb:CollaborationInfo/eb:ConversationId', $msgNode);
        $service = $this->queryText($xpath, 'eb:CollaborationInfo/eb:Service', $msgNode);
        $action = $this->queryText($xpath, 'eb:CollaborationInfo/eb:Action', $msgNode);
        $fromParty = $this->queryText($xpath, 'eb:PartyInfo/eb:From/eb:PartyId', $msgNode);
        $toParty = $this->queryText($xpath, 'eb:PartyInfo/eb:To/eb:PartyId', $msgNode);

        $payloads = [];
        $partInfos = $xpath->query('eb:PayloadInfo/eb:PartInfo', $msgNode);
        if ($partInfos !== false) {
            foreach ($partInfos as $partInfo) {
                if (!$partInfo instanceof DOMElement) {
                    continue;
                }
                $href = $partInfo->getAttribute('href');
                if (preg_match('/cid:(.+)/', $href, $matches)) {
                    $contentId = $matches[1];
                    $payloads[$contentId] = $this->extractPayloadPart($contentId, $mimeparts);
                }
            }
        }

        return new As4InboundMessage(
            type: 'UserMessage',
            messageId: $messageId,
            conversationId: $conversationId,
            service: $service,
            action: $action,
            senderPartyId: $fromParty,
            receiverPartyId: $toParty,
            payloads: $payloads,
            xmlBody: $soapPart
        );
    }

    /**
     * Parse SignalMessage (receipt or error).
     */
    private function parseSignalMessage(string $soapPart, \DOMXPath $xpath): As4InboundMessage
    {
        $signal = $xpath->query('//eb:SignalMessage')->item(0);
        if (!$signal instanceof DOMElement) {
            throw new As4ParseException('SignalMessage element not found');
        }

        $messageId = $this->queryText($xpath, 'eb:MessageInfo/eb:MessageId', $signal);
        $refToMessageId = $this->queryText($xpath, 'eb:MessageInfo/eb:RefToMessageId', $signal);

        $receipts = $xpath->query('eb:Receipt', $signal);
        if ($receipts !== false && $receipts->length > 0) {
            $receipt = $receipts->item(0);
            $digestValue = '';
            if ($receipt instanceof DOMElement) {
                $digestValue = $this->queryText($xpath, 'ds:Reference/ds:DigestValue', $receipt);
            }

            return new As4InboundMessage(
                type: 'Receipt',
                messageId: $messageId,
                refToMessageId: $refToMessageId,
                digestValue: $digestValue,
                xmlBody: $soapPart
            );
        }

        $errors = $xpath->query('eb:Error', $signal);
        if ($errors !== false && $errors->length > 0) {
            $errorNode = $errors->item(0);
            if (!$errorNode instanceof DOMElement) {
                throw new As4ParseException('No Receipt or Error in SignalMessage');
            }

            $errorCode = $errorNode->getAttribute('errorCode');
            if ($errorCode === '') {
                $errorCode = $this->queryText($xpath, 'eb:ErrorCode', $errorNode);
            }
            $shortDesc = $this->queryText($xpath, 'eb:ShortDescription', $errorNode);
            $desc = $this->queryText($xpath, 'eb:Description', $errorNode);
            $category = $errorNode->getAttribute('category');

            return new As4InboundMessage(
                type: 'Error',
                messageId: $messageId,
                refToMessageId: $refToMessageId,
                errorCode: $errorCode,
                errorShortDescription: $shortDesc,
                errorDescription: $desc,
                errorCategory: $category,
                xmlBody: $soapPart
            );
        }

        throw new As4ParseException('No Receipt or Error in SignalMessage');
    }

    /**
     * Extract binary payload from MIME parts by Content-ID.
     *
     * @param string[] $parts
     */
    private function extractPayloadPart(string $contentId, array $parts): string
    {
        foreach ($parts as $part) {
            if (
                strpos($part, "Content-ID: <{$contentId}>") !== false ||
                strpos($part, "Content-ID: <{$contentId}") !== false
            ) {
                return $this->extractPartBody($part);
            }
        }
        return '';
    }

    private function extractPartBody(string $part): string
    {
        $bodyStart = strpos($part, "\r\n\r\n");
        if ($bodyStart !== false) {
            return substr($part, $bodyStart + 4);
        }
        $bodyStart = strpos($part, "\n\n");
        if ($bodyStart !== false) {
            return substr($part, $bodyStart + 2);
        }
        return '';
    }

    private function extractBoundary(string $contentType): ?string
    {
        if (preg_match('/boundary=(["\']?)([^"\';\s]+)\1/', $contentType, $matches)) {
            return $matches[2];
        }
        return null;
    }

    /**
     * Query a single text node relative to a context element.
     */
    private function queryText(\DOMXPath $xpath, string $query, DOMElement $context): string
    {
        $nodes = $xpath->query($query, $context);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        return $nodes->item(0)?->nodeValue ?? '';
    }
}

