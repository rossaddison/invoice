<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Invoice\Peppol\PeppolMessageRepositoryInterface;
use App\Invoice\Peppol\PeppolSendService;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Hand-written spy — records the status of every PeppolMessage passed to save().
 * Named class required so Psalm can see the $savedStatuses property.
 */
final class SpyPeppolMessageRepository implements PeppolMessageRepositoryInterface
{
    /** @var list<string> */
    public array $savedStatuses = [];

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

    #[\Override]
    public function repoByStatus(string $status): iterable
    {
        return [];
    }
}

class PeppolSendServiceTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private ClientInterface&\PHPUnit\Framework\MockObject\Stub $httpClient;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private SpyPeppolMessageRepository $pmR;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private PeppolSendService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->httpClient = $this->createStub(ClientInterface::class);
        $this->pmR        = new SpyPeppolMessageRepository();

        $this->service = new PeppolSendService(
            httpClient:          $this->httpClient,
            requestFactory:      new HttpFactory(),
            pmR:                 $this->pmR,
            oxalisBaseUrl:       'http://localhost:8181',
            senderParticipantId: '0088:9999999999999',
        );
    }

    public function testSendSavesQueuedThenSent(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"messageId":"msg-abc-001"}'));

        $message = $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertSame(['QUEUED', 'SENT'], $this->pmR->savedStatuses);
        $this->assertSame('SENT', $message->getStatus());
        $this->assertSame('msg-abc-001', $message->getMessageId());
        $this->assertNotNull($message->getSentAt());
    }

    public function testSendAcceptsInstanceIdentifierInResponse(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"instanceIdentifier":"inst-xyz-002"}'));

        $message = $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertSame('SENT', $message->getStatus());
        $this->assertSame('inst-xyz-002', $message->getMessageId());
    }

    public function testSendSetsEmptyMessageIdWhenResponseHasNoKnownField(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"other":"value"}'));

        $message = $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertSame('SENT', $message->getStatus());
        $this->assertSame('', $message->getMessageId());
    }

    public function testSendSetsFailedOnHttp400(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(400, [], 'Bad request: invalid recipient'));

        $message = $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertSame('FAILED', $message->getStatus());
        $this->assertStringContainsString('400', (string) $message->getErrorMessage());
        $this->assertStringContainsString('Bad request: invalid recipient', (string) $message->getErrorMessage());
    }

    public function testSendSetsFailedOnHttp500(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(500, [], 'Internal Server Error'));

        $message = $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertSame('FAILED', $message->getStatus());
        $this->assertStringContainsString('500', (string) $message->getErrorMessage());
    }

    public function testSendSetsFailedOnNetworkException(): void
    {
        $networkError = new class ('Connection refused') extends \RuntimeException
            implements ClientExceptionInterface {};

        $this->httpClient
            ->method('sendRequest')
            ->willThrowException($networkError);

        $message = $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertSame('FAILED', $message->getStatus());
        $this->assertSame('Connection refused', $message->getErrorMessage());
    }

    public function testSendAddsSchemePrefix(): void
    {
        $capturedRequest = null;
        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $req) use (&$capturedRequest): Response {
                $capturedRequest = $req;
                return new Response(200, [], '{"messageId":"x"}');
            });

        $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertInstanceOf(RequestInterface::class, $capturedRequest);
        $body = (string) $capturedRequest->getBody();
        $this->assertStringContainsString('iso6523-actorid-upis::0088:1234567890123', $body);
        $this->assertStringContainsString('cenbii-procid-ubl::urn:fdc:peppol.eu:2017:poacc:billing:01:1.0', $body);
    }

    public function testSendDoesNotDoublePrefixAlreadyPrefixedIds(): void
    {
        $capturedRequest = null;
        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $req) use (&$capturedRequest): Response {
                $capturedRequest = $req;
                return new Response(200, [], '{"messageId":"x"}');
            });

        $this->service->send(
            1,
            '<Invoice/>',
            'iso6523-actorid-upis::0088:1234567890123',
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            'cenbii-procid-ubl::urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
        );

        $this->assertInstanceOf(RequestInterface::class, $capturedRequest);
        $body = (string) $capturedRequest->getBody();
        $this->assertStringNotContainsString('iso6523-actorid-upis::iso6523-actorid-upis::', $body);
        $this->assertStringNotContainsString('cenbii-procid-ubl::cenbii-procid-ubl::', $body);
    }

    public function testSendIncludesSenderIdWhenConfigured(): void
    {
        $capturedRequest = null;
        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $req) use (&$capturedRequest): Response {
                $capturedRequest = $req;
                return new Response(200, [], '{"messageId":"x"}');
            });

        $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertInstanceOf(RequestInterface::class, $capturedRequest);
        $body = (string) $capturedRequest->getBody();
        $this->assertStringContainsString('iso6523-actorid-upis::0088:9999999999999', $body);
    }

    public function testSendOmitsSenderIdWhenNotConfigured(): void
    {
        $service = new PeppolSendService(
            httpClient:          $this->httpClient,
            requestFactory:      new HttpFactory(),
            pmR:                 $this->pmR,
            oxalisBaseUrl:       'http://localhost:8181',
            senderParticipantId: '',
        );

        $capturedRequest = null;
        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $req) use (&$capturedRequest): Response {
                $capturedRequest = $req;
                return new Response(200, [], '{"messageId":"x"}');
            });

        $service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertInstanceOf(RequestInterface::class, $capturedRequest);
        $body = (string) $capturedRequest->getBody();
        $this->assertStringNotContainsString('SenderId', $body);
    }

    public function testSendUsesMultipartContentType(): void
    {
        $capturedRequest = null;
        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $req) use (&$capturedRequest): Response {
                $capturedRequest = $req;
                return new Response(200, [], '{"messageId":"x"}');
            });

        $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertInstanceOf(RequestInterface::class, $capturedRequest);
        $this->assertStringStartsWith(
            'multipart/form-data; boundary=',
            $capturedRequest->getHeaderLine('Content-Type')
        );
    }

    public function testSendPostsToCorrectUrl(): void
    {
        $capturedRequest = null;
        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $req) use (&$capturedRequest): Response {
                $capturedRequest = $req;
                return new Response(200, [], '{"messageId":"x"}');
            });

        $this->service->send(1, '<Invoice/>', '0088:1234567890123');

        $this->assertInstanceOf(RequestInterface::class, $capturedRequest);
        $this->assertSame('POST', $capturedRequest->getMethod());
        $this->assertSame('http://localhost:8181/outbound/send', (string) $capturedRequest->getUri());
    }

    public function testSendStoresInvIdOnMessage(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"messageId":"x"}'));

        $message = $this->service->send(42, '<Invoice/>', '0088:1234567890123');

        $this->assertSame(42, $message->getInvId());
    }

    public function testRetryIncrementsCountAndResendsAsSent(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"messageId":"retry-msg-001"}'));

        $existing = new PeppolMessage(inv_id: 7, recipient_id: '0088:1234567890123', status: 'FAILED');
        $existing->setErrorMessage('Timeout');

        $result = $this->service->retry($existing, '<Invoice/>');

        $this->assertSame('SENT', $result->getStatus());
        $this->assertSame('retry-msg-001', $result->getMessageId());
    }
}
