<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Invoice\Peppol\Console\RetryFailedCommand;
use App\Invoice\Peppol\PeppolMessageRepositoryInterface;
use App\Invoice\Peppol\PeppolSendService;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Spy repository whose repoByStatus() returns a configurable list of messages.
 */
final class RetrySpyRepository implements PeppolMessageRepositoryInterface
{
    /** @var list<PeppolMessage> */
    private array $messages;

    /** @var list<string> */
    public array $savedStatuses = [];

    /** @param list<PeppolMessage> $messages */
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    #[\Override]
    public function save(PeppolMessage $message): void
    {
        $this->savedStatuses[] = $message->getStatus();
    }

    #[\Override]
    public function repoByMessageId(string $message_id): ?PeppolMessage
    {
        return null;
    }

    /** @return list<PeppolMessage> */
    #[\Override]
    public function repoByStatus(string $status): iterable
    {
        return $this->messages;
    }
}

class RetryFailedCommandTest extends TestCase
{
    private const string UBL_XML = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"/>';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private ClientInterface&\PHPUnit\Framework\MockObject\Stub $httpClient;

    #[\Override]
    protected function setUp(): void
    {
        $this->httpClient = $this->createStub(ClientInterface::class);
    }

    private function buildService(RetrySpyRepository $repo): PeppolSendService
    {
        return new PeppolSendService(
            httpClient:          $this->httpClient,
            requestFactory:      new HttpFactory(),
            pmR:                 $repo,
            oxalisBaseUrl:       'http://localhost:8181',
            senderParticipantId: '',
        );
    }

    private function buildCommand(
        RetrySpyRepository $repo,
        PeppolSendService $service,
    ): CommandTester {
        $command = new RetryFailedCommand($repo, $service, new NullLogger());
        return new CommandTester($command);
    }

    private function makeMessage(
        string $status = 'FAILED',
        ?string $ublXml = self::UBL_XML,
        int $retryCount = 0,
    ): PeppolMessage {
        $m = new PeppolMessage(inv_id: 1, recipient_id: '0088:1234567890123', status: $status);
        $m->setId(1);
        if ($ublXml !== null) {
            $m->setUblXml($ublXml);
        }
        for ($i = 0; $i < $retryCount; $i++) {
            $m->incrementRetryCount();
        }
        return $m;
    }

    public function testEmptyQueueExitsOk(): void
    {
        $repo    = new RetrySpyRepository([]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('0', $tester->getDisplay());
    }

    public function testSkipsMessageWithNoUblXml(): void
    {
        $message = $this->makeMessage(ublXml: null);
        $repo    = new RetrySpyRepository([$message]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('skipped', strtolower($display));
        // No HTTP call should have been made
        $this->assertSame([], $repo->savedStatuses);
    }

    public function testSkipsMessageThatExceedsMaxRetries(): void
    {
        $message = $this->makeMessage(retryCount: 3);
        $repo    = new RetrySpyRepository([$message]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $tester->execute([]);

        $this->assertSame([], $repo->savedStatuses);
    }

    public function testCustomMaxRetriesOptionIsRespected(): void
    {
        // retryCount = 2; with --max-retries=2 it should be skipped
        $message = $this->makeMessage(retryCount: 2);
        $repo    = new RetrySpyRepository([$message]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $tester->execute(['--max-retries' => '2']);

        $this->assertSame([], $repo->savedStatuses);
    }

    public function testSuccessfulRetryOutputsMessageId(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"messageId":"retry-ok-001"}'));

        $message = $this->makeMessage();
        $repo    = new RetrySpyRepository([$message]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('retry-ok-001', $tester->getDisplay());
    }

    public function testSuccessfulRetryIncrementsSentCount(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"messageId":"retry-ok-002"}'));

        $message = $this->makeMessage();
        $repo    = new RetrySpyRepository([$message]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $tester->execute([]);

        // retry() sets RETRYING then send() saves QUEUED and SENT
        $this->assertContains('SENT', $repo->savedStatuses);
    }

    public function testFailedRetryOutputsErrorStatus(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(500, [], 'Internal Server Error'));

        $message = $this->makeMessage();
        $repo    = new RetrySpyRepository([$message]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $tester->execute([]);

        $this->assertStringContainsString('FAILED', $tester->getDisplay());
    }

    public function testSummaryTableIsAlwaysPrinted(): void
    {
        $repo    = new RetrySpyRepository([]);
        $service = $this->buildService($repo);
        $tester  = $this->buildCommand($repo, $service);

        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('FAILED found', $display);
        $this->assertStringContainsString('Attempted', $display);
        $this->assertStringContainsString('Succeeded', $display);
        $this->assertStringContainsString('Skipped', $display);
    }
}
