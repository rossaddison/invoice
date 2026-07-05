<?php

declare(strict_types=1);

namespace App\Invoice\As4\Console;

use App\Invoice\As4\As4DispatchRequest;
use App\Invoice\As4\As4DispatchResult;
use App\Invoice\As4\As4ErrorSignal;
use App\Invoice\As4\As4MessageDispatcher;
use App\Invoice\As4\As4ReceiptSignal;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Sends a test AS4 message to a bilateral peer without needing a real Peppol invoice.
 *
 * Useful for smoke-testing the full outbound pipeline end-to-end:
 *   WsSecuritySigner → SoapEnvelopeBuilder → As4HttpClient → peer /as4/receive
 *
 * Usage (localhost → yii3i.online):
 *   php yii as4/test-send \
 *     --receiver-id=bilateral:yii3i.online \
 *     --endpoint=https://yii3i.online/as4/receive
 *
 * Usage (yii3i.online → localhost via ngrok):
 *   php yii as4/test-send \
 *     --receiver-id=bilateral:localhost \
 *     --endpoint=https://abc123.ngrok-free.app/as4/receive
 *
 * The --endpoint option overrides AS4_PEER_ENDPOINT for this single run.
 * Omit --endpoint to use whatever is set in the environment.
 */
final class As4TestSendCommand extends Command
{
    protected static string $defaultName = 'as4/test-send';

    private const string PING_DOCUMENT_TYPE =
        'urn:yii3i:bilateral:ping:1.0';

    private const string PING_PROCESS_ID =
        'urn:yii3i:bilateral:process:test:1.0';

    public function __construct(
        private readonly As4MessageDispatcher $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription(
                'Send a bilateral AS4 test message to verify the full outbound pipeline.'
            )
            ->addOption(
                'receiver-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Receiver party ID (e.g. bilateral:yii3i.online or 0088:1234567890123).',
                $_ENV['AS4_PEER_PARTY_ID'] ?? 'bilateral:peer',
            )
            ->addOption(
                'endpoint',
                null,
                InputOption::VALUE_REQUIRED,
                'Override AS4 receive endpoint URL (e.g. https://yii3i.online/as4/receive).',
                $_ENV['AS4_PEER_ENDPOINT'] ?? '',
            )
            ->addOption(
                'document-type',
                null,
                InputOption::VALUE_REQUIRED,
                'ebMS3 document type identifier.',
                self::PING_DOCUMENT_TYPE,
            )
            ->addOption(
                'process-id',
                null,
                InputOption::VALUE_REQUIRED,
                'ebMS3 process identifier.',
                self::PING_PROCESS_ID,
            )
            ->addOption(
                'payload',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to a UBL XML file to send as payload (omit to send a minimal ping).',
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('AS4 Test Send');

        $receiverId   = (string) ($input->getOption('receiver-id')    ?? '');
        $endpoint     = (string) ($input->getOption('endpoint')       ?? '');
        $documentType = (string) ($input->getOption('document-type')  ?? self::PING_DOCUMENT_TYPE);
        $processId    = (string) ($input->getOption('process-id')     ?? self::PING_PROCESS_ID);
        $payloadPath  = (string) ($input->getOption('payload')        ?? '');

        if ($receiverId === '') {
            $io->error('--receiver-id is required (or set AS4_PEER_PARTY_ID in .env).');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $payloadXml = $this->buildPayload($payloadPath, $receiverId, $io);
        if ($payloadXml === null) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $io->section('Dispatch parameters');
        $io->table(
            ['Field', 'Value'],
            [
                ['Sender Party ID',  $_ENV['AS4_SENDER_PARTY_ID'] ?? '(from .env)'],
                ['Receiver Party ID', $receiverId],
                ['Endpoint',          $endpoint !== '' ? $endpoint : '(from AS4_PEER_ENDPOINT)'],
                ['Document Type',     $documentType],
                ['Process ID',        $processId],
                ['Payload',           $payloadPath !== '' ? $payloadPath : '(ping XML)'],
            ],
        );

        $request = new As4DispatchRequest(
            recipientPartyId: $receiverId,
            documentTypeId:   $documentType,
            processId:        $processId,
            payloadXml:       $payloadXml,
        );
        return $this->confirmAndSend($request, $io);
    }

    private function confirmAndSend(As4DispatchRequest $request, SymfonyStyle $io): int
    {
        if (!$io->confirm('Send now?', true)) {
            $io->info('Aborted.');
            return ExitCode::OK;
        }
        try {
            $result = $this->dispatcher->dispatch($request);
        } catch (\Throwable $e) {
            $this->logger->error('as4/test-send failed', ['error' => $e->getMessage()]);
            $io->error("Dispatch exception: {$e->getMessage()}");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return $this->reportResult($result, $io);
    }

    private function buildPayload(string $path, string $receiverId, SymfonyStyle $io): ?string
    {
        if ($path !== '') {
            if (!is_readable($path)) {
                $io->error("Cannot read payload file: {$path}");
                return null;
            }
            return (string) file_get_contents($path);
        }

        // Minimal bilateral ping document
        $ts         = gmdate('c');
        $senderNode = $_ENV['AS4_SENDER_PARTY_ID'] ?? 'unknown';
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <BilateralPing xmlns="urn:yii3i:bilateral:ping:1.0">
                <Timestamp>{$ts}</Timestamp>
                <SenderNode>{$senderNode}</SenderNode>
                <ReceiverNode>{$receiverId}</ReceiverNode>
                <Message>AS4 bilateral connectivity test from Yii3-i</Message>
            </BilateralPing>
            XML;
    }

    private function reportResult(As4DispatchResult $result, SymfonyStyle $io): int
    {
        $signal = $result->signal;
        if ($signal instanceof As4ReceiptSignal) {
            $signalStr = 'Receipt — ' . $signal->messageId;
        } elseif ($signal instanceof As4ErrorSignal) {
            $signalStr = 'Error — ' . $signal->errorCode;
        } else {
            $signalStr = 'none (async 202)';
        }
        $io->section('Result');
        $io->table(
            ['Field', 'Value'],
            [
                ['Message ID',  $result->messageId],
                ['HTTP Status', (string) $result->httpStatus],
                ['Success',     $result->success ? 'yes' : 'no'],
                ['Signal',      $signalStr],
            ],
        );
        if (!$result->success || $result->hasError()) {
            $io->error('Send failed — check the signal detail above.');
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if ($signal instanceof As4ReceiptSignal) {
            $io->success('Synchronous receipt received — delivery confirmed!');
        } else {
            $io->success('HTTP 202 accepted — async delivery. Check /as4/messages on the receiver.');
        }
        return ExitCode::OK;
    }
}
