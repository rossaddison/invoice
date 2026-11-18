<?php

declare(strict_types=1);

namespace Tests\Testo\Support;

use LogicException;
use Phptg\BotApi\Transport\ApiResponse;
use Phptg\BotApi\Transport\TransportInterface;

/**
 * A Telegram transport for tests: nothing leaves the machine, and every POST is kept so a test can read
 * what the bot would really have sent. Each is answered with a plain Telegram error, since only the
 * request matters.
 */
final class RecordingTelegramTransport implements TransportInterface
{
    private const string ANSWER = '{"ok":false,"error_code":400,"description":"Bad Request: test"}';

    /** @var list<array{url: string, body: array<string, mixed>}> */
    private array $requests = [];

    /**
     * @return list<array{url: string, body: array<string, mixed>}>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    /**
     * @return list<string> the text of every sendMessage, in order
     */
    public function sentTexts(): array
    {
        $texts = [];
        foreach ($this->requests as $request) {
            if (str_ends_with($request['url'], '/sendMessage')) {
                $texts[] = (string) ($request['body']['text'] ?? '');
            }
        }
        return $texts;
    }

    #[\Override]
    public function get(string $url): ApiResponse
    {
        return new ApiResponse(400, self::ANSWER);
    }

    #[\Override]
    public function post(string $url, string $body, array $headers): ApiResponse
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $this->requests[] = ['url' => $url, 'body' => $decoded];
        return new ApiResponse(400, self::ANSWER);
    }

    #[\Override]
    public function postWithFiles(string $url, array $data, array $files): ApiResponse
    {
        return new ApiResponse(400, self::ANSWER);
    }

    #[\Override]
    public function downloadFile(string $url): mixed
    {
        throw new LogicException('not used');
    }
}
