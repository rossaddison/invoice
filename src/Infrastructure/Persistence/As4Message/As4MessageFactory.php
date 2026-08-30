<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4InboundMessage;

final class As4MessageFactory
{
    public static function fromInbound(As4InboundMessage $msg): As4Message
    {
        $entity = new As4Message(new As4MessageParams(
            messageId:        $msg->messageId ?? '',
            conversationId:   $msg->conversationId ?? '',
            senderPartyId:    $msg->senderPartyId ?? '',
            senderRole:       '',
            receiverPartyId:  $msg->receiverPartyId ?? '',
            receiverRole:     '',
            service:          $msg->service ?? '',
            action:           $msg->action ?? '',
            receiverEndpoint: '',
            soapMessage:      $msg->xmlBody,
        ));
        return $entity->markReceived();
    }

    /**
     * Builds the pending record for a message this app is about to send.
     * Callers (As4MessageDispatcher) still advance it through
     * recordAttempt()/markSent()/markReceiptReceived()/markFailed()
     * themselves — this only assembles the initial routing/payload snapshot.
     */
    public static function fromOutbound(As4OutboundMessageParams $params): As4Message
    {
        return new As4Message(new As4MessageParams(
            messageId:        $params->messageId,
            conversationId:   $params->conversationId,
            senderPartyId:    $params->senderPartyId,
            senderRole:       As4Constants::ROLE_INITIATOR,
            receiverPartyId:  $params->receiverPartyId,
            receiverRole:     As4Constants::ROLE_RESPONDER,
            service:          $params->service,
            action:           $params->action,
            receiverEndpoint: $params->receiverEndpoint,
            soapMessage:      $params->soapMessage,
        ));
    }
}
