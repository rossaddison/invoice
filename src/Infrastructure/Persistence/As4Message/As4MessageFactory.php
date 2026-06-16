<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

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
}
