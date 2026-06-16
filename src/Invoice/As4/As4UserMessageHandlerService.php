<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4MessageFactory;
use Psr\Log\LoggerInterface;

final class As4UserMessageHandlerService implements As4UserMessageHandlerInterface
{
    public function __construct(
        private readonly As4MessageRepositoryInterface $repository,
        private readonly As4DuplicateDetectorInterface $duplicateDetector,
        private readonly As4ReceiptGeneratorInterface $receiptGenerator,
        private readonly As4PayloadHandlerInterface $payloadHandler,
        private readonly LoggerInterface $logger,
    ) {}

    #[\Override]
    public function handle(As4InboundMessage $message): string
    {
        $messageId = $message->messageId ?? '';

        if ($this->duplicateDetector->isDuplicate($messageId)) {
            $this->logger->info('AS4 receive: duplicate UserMessage — returning Receipt', [
                'messageId' => $messageId,
            ]);
            return $this->receiptGenerator->generate($messageId, $message->xmlBody);
        }

        $this->repository->save(As4MessageFactory::fromInbound($message));

        foreach ($message->payloads as $payloadXml) {
            $this->payloadHandler->handle(
                $payloadXml,
                $message->senderPartyId ?? '',
                $message->action ?? '',
            );
        }

        $this->logger->info('AS4 receive: UserMessage accepted', [
            'messageId'     => $messageId,
            'senderPartyId' => $message->senderPartyId,
            'action'        => $message->action,
            'payloadCount'  => count($message->payloads),
        ]);

        return $this->receiptGenerator->generate($messageId, $message->xmlBody);
    }
}
