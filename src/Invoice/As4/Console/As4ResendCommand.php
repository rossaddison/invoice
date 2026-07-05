<?php

declare(strict_types=1);

namespace App\Invoice\As4\Console;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4MessageState;
use App\Invoice\As4\As4ReceiptParserInterface;
use App\Invoice\As4\As4ReceiptSignal;
use App\Invoice\As4\As4SenderInterface;
use DOMDocument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Forces an immediate resend of a specific AS4 message, bypassing retry
 * interval and state checks. Useful for operator recovery of failed messages.
 *
 * Usage:
 *   php yii as4/resend --message-id=<RFC-2822-message-id>
 *
 * The message must exist in the database. Any state is accepted so that
 * operators can force-resend messages that are stuck or incorrectly failed.
 */
final class As4ResendCommand extends Command
{
    protected static string $defaultName = 'as4/resend';

    public function __construct(
        private readonly As4MessageRepositoryInterface $repository,
        private readonly As4SenderInterface $sender,
        private readonly As4ReceiptParserInterface $receiptParser,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription(
                'Force an immediate resend of a specific AS4 message, '
                . 'bypassing retry interval and state checks.'
            )
            ->addOption(
                'message-id',
                null,
                InputOption::VALUE_REQUIRED,
                'The RFC 2822 MessageId of the AS4 message to resend.',
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $messageId = (string) ($input->getOption('message-id') ?? '');

        if ($messageId === '') {
            $io->error('--message-id is required.');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $message = $this->repository->findByMessageId($messageId);
        if ($message === null) {
            $io->error("No message found with MessageId: {$messageId}");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $io->section('Message details');
        $io->table(
            ['Field', 'Value'],
            [
                ['DB ID',     (string) $message->reqId()],
                ['MessageId', $message->getMessageId()],
                ['State',     $message->getState()->value],
                ['Receiver',  $message->getRouting()->getReceiverPartyId()],
                ['Endpoint',  $message->getRouting()->getReceiverEndpoint()],
                ['Attempts',  $message->getRetryState()->getAttemptCount()
                              . ' / ' . $message->getRetryState()->getMaxAttempts()],
            ],
        );

        return $this->confirmAndSend($message, $messageId, $io);
    }

    private function confirmAndSend(As4Message $message, string $messageId, SymfonyStyle $io): int
    {
        if (!$io->confirm('Send this message now?', false)) {
            $io->info('Aborted.');
            return ExitCode::OK;
        }
        $envelope = $this->parseXml($message->getPayload()->getSoapMessage());
        if ($envelope === null) {
            $io->error('Stored SOAP envelope is not well-formed XML — cannot resend.');
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return $this->trySend($message, $messageId, $envelope, $io);
    }

    private function trySend(
        As4Message $message,
        string $messageId,
        DOMDocument $envelope,
        SymfonyStyle $io,
    ): int {
        $message->recordAttempt();
        $this->repository->save($message);
        try {
            $response = $this->sender->send(
                endpoint: $message->getRouting()->getReceiverEndpoint(),
                envelope: $envelope,
                parts:    [],
            );
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            $this->logger->error('as4/resend network failure', [
                'messageId' => $messageId,
                'error'     => $e->getMessage(),
            ]);
            $io->error("Network failure: {$e->getMessage()}");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if (!$response->isSuccess()) {
            $message->markFailed('HTTP_' . $response->statusCode, 'Non-retriable HTTP error on forced resend');
            $this->repository->save($message);
            $io->error("Endpoint returned HTTP {$response->statusCode}. Message marked FAILED.");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $signal = $this->receiptParser->parse($response->body, $response->contentType);
        if ($signal instanceof As4ReceiptSignal) {
            $message->markReceiptReceived($signal->messageId);
            $this->repository->save($message);
            $io->success("Receipt confirmed — message delivered. Receipt ID: {$signal->messageId}");
        } else {
            $message->markSent();
            $this->repository->save($message);
            $io->success(
                'Transport accepted (async). State → ' . As4MessageState::sent->value
                . '. Receipt will arrive via the inbound channel.'
            );
        }
        return ExitCode::OK;
    }

    private function parseXml(string $xml): ?DOMDocument
    {
        $doc        = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);
        return $loaded ? $doc : null;
    }
}
