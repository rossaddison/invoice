<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Invoice\Peppol\PeppolInboundController;
use App\Invoice\Peppol\PeppolMessageRepositoryInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Status;

/**
 * Spy for DataResponseFactory — captures every $data arg passed to createResponse().
 * Named class required so Psalm can see the $capturedData property.
 */
final class SpyDataResponseFactory implements DataResponseFactoryInterface
{
    /** @var list<mixed> */
    public array $capturedData = [];

    /** @var list<int> */
    public array $capturedCodes = [];

    #[\Override]
    public function createResponse(
        mixed $data = null,
        int $code = Status::OK,
        string $reasonPhrase = '',
    ): ResponseInterface {
        $this->capturedData[] = $data;
        $this->capturedCodes[] = $code;
        return new Response($code);
    }
}

/**
 * Configurable spy for PeppolMessageRepositoryInterface.
 * Call withMessage() to prime repoByMessageId(); withSaveThrows() to exercise the error branch.
 */
final class InboundSpyRepository implements PeppolMessageRepositoryInterface
{
    private bool $throwOnSave = false;
    private ?PeppolMessage $message = null;

    /** @var list<string> */
    public array $savedStatuses = [];

    public function withMessage(PeppolMessage $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function withSaveThrows(): static
    {
        $this->throwOnSave = true;
        return $this;
    }

    #[\Override]
    public function save(PeppolMessage $message): void
    {
        if ($this->throwOnSave) {
            throw new \RuntimeException('DB unavailable'); // NOSONAR: php:S112 — test spy simulating transient infrastructure failure
        }
        $this->savedStatuses[] = $message->getStatus();
    }

    #[\Override]
    public function repoByMessageId(string $message_id): ?PeppolMessage
    {
        if ($this->message !== null && $this->message->getMessageId() === $message_id) {
            return $this->message;
        }
        return null;
    }

    #[\Override]
    public function repoByStatus(string $status): iterable
    {
        return [];
    }
}

class PeppolInboundControllerTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private SpyDataResponseFactory $factory;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private InboundSpyRepository $repo;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private LoggerInterface&\PHPUnit\Framework\MockObject\Stub $logger;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private PeppolInboundController $controller;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new SpyDataResponseFactory();
        $this->repo    = new InboundSpyRepository();
        $this->logger  = $this->createStub(LoggerInterface::class);

        $this->controller = new PeppolInboundController(
            factory:                 $this->factory,
            logger:                  $this->logger,
            peppolMessageRepository: $this->repo,
            callbackSecret:          self::SECRET,
        );
    }

    private const string SECRET = 'a-shared-secret-of-32-characters!!';

    /** @return ServerRequestInterface&\PHPUnit\Framework\MockObject\Stub */
    private function buildRequest(
        string $body,
        string $headerSecret = self::SECRET,
        array $query = [],
    ): ServerRequestInterface {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);
        $request->method('getHeaderLine')->willReturn($headerSecret);
        $request->method('getQueryParams')->willReturn($query);
        return $request;
    }

    public function testDeliveryWithAWrongSecretIsRefusedAndChangesNothing(): void
    {
        $message = new PeppolMessage(inv_id: 7, recipient_id: '0088:1234567890123', status: 'SENT');
        $message->setMessageId('msg-guarded');
        $this->repo->withMessage($message);

        $this->controller->delivery($this->buildRequest('{"messageId":"msg-guarded"}', 'not-the-secret'));

        $this->assertSame([['status' => 'forbidden']], $this->factory->capturedData);
        $this->assertSame([403], $this->factory->capturedCodes);
        $this->assertSame('SENT', $message->getStatus());
        $this->assertSame([], $this->repo->savedStatuses);
    }

    public function testDeliveryWithNoSecretIsRefused(): void
    {
        $this->controller->delivery($this->buildRequest('{"messageId":"x"}', ''));

        $this->assertSame([['status' => 'forbidden']], $this->factory->capturedData);
    }

    public function testDeliveryRefusesEveryCallWhenNoSecretIsConfigured(): void
    {
        $controller = new PeppolInboundController(
            factory:                 $this->factory,
            logger:                  $this->logger,
            peppolMessageRepository: $this->repo,
        );
        $message = new PeppolMessage(inv_id: 7, recipient_id: '0088:1234567890123', status: 'SENT');
        $message->setMessageId('msg-open');
        $this->repo->withMessage($message);

        // even a request that presents a blank secret, as an attacker would
        $controller->delivery($this->buildRequest('{"messageId":"msg-open"}', ''));

        $this->assertSame([['status' => 'forbidden']], $this->factory->capturedData);
        $this->assertSame('SENT', $message->getStatus());
    }

    public function testDeliveryAcceptsTheSecretAsAQueryParameter(): void
    {
        $message = new PeppolMessage(inv_id: 7, recipient_id: '0088:1234567890123', status: 'SENT');
        $message->setMessageId('msg-query');
        $this->repo->withMessage($message);

        $this->controller->delivery($this->buildRequest('{"messageId":"msg-query"}', '', ['secret' => self::SECRET]));

        $this->assertSame([['status' => 'ok']], $this->factory->capturedData);
        $this->assertSame('DELIVERED', $message->getStatus());
    }

    public function testDeliveryReturnsBadRequestWhenMessageIdIsNotAString(): void
    {
        $this->controller->delivery($this->buildRequest('{"messageId":["a"]}'));

        $this->assertSame([['status' => 'bad_request']], $this->factory->capturedData);
    }

    public function testDeliveryReturnsBadRequestOnEmptyBody(): void
    {
        $this->controller->delivery($this->buildRequest(''));

        $this->assertSame([['status' => 'bad_request']], $this->factory->capturedData);
    }

    public function testDeliveryReturnsBadRequestOnInvalidJson(): void
    {
        $this->controller->delivery($this->buildRequest('{not-json}'));

        $this->assertSame([['status' => 'bad_request']], $this->factory->capturedData);
    }

    public function testDeliveryReturnsBadRequestOnMissingMessageId(): void
    {
        $this->controller->delivery($this->buildRequest('{"other":"value"}'));

        $this->assertSame([['status' => 'bad_request']], $this->factory->capturedData);
    }

    public function testDeliveryReturnsNotFoundForUnknownMessageId(): void
    {
        $this->controller->delivery($this->buildRequest('{"messageId":"unknown-xyz"}'));

        $this->assertSame([['status' => 'not_found']], $this->factory->capturedData);
    }

    public function testDeliveryMarksMessageDeliveredOnSuccess(): void
    {
        $message = new PeppolMessage(inv_id: 7, recipient_id: '0088:1234567890123', status: 'SENT');
        $message->setMessageId('msg-delivered-001');
        $this->repo->withMessage($message);

        $this->controller->delivery($this->buildRequest('{"messageId":"msg-delivered-001"}'));

        $this->assertSame([['status' => 'ok']], $this->factory->capturedData);
        $this->assertSame('DELIVERED', $message->getStatus());
        $this->assertNotNull($message->getDeliveredAt());
    }

    public function testDeliverySavesWithDeliveredStatus(): void
    {
        $message = new PeppolMessage(inv_id: 3, recipient_id: '0088:1234567890123', status: 'SENT');
        $message->setMessageId('msg-save-check');
        $this->repo->withMessage($message);

        $this->controller->delivery($this->buildRequest('{"messageId":"msg-save-check"}'));

        $this->assertContains('DELIVERED', $this->repo->savedStatuses);
    }

    public function testDeliveryReturnsErrorWhenSaveFails(): void
    {
        $message = new PeppolMessage(inv_id: 9, recipient_id: '0088:1234567890123', status: 'SENT');
        $message->setMessageId('msg-fail-001');
        $this->repo->withMessage($message)->withSaveThrows();

        $this->controller->delivery($this->buildRequest('{"messageId":"msg-fail-001"}'));

        $this->assertSame([['status' => 'error']], $this->factory->capturedData);
    }
}
