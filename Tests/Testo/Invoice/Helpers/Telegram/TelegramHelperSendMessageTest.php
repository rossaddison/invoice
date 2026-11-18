<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers\Telegram;

use App\Invoice\Helpers\Telegram\TelegramHelper;
use Closure;
use LogicException;
use Phptg\BotApi\Transport\ApiResponse;
use Phptg\BotApi\Transport\TransportInterface;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Test;

/**
 * phptg/bot-api 0.23 moved `chatId` after the required parameters of sendMessage(), so a call that passed
 * the chat id first sent the chat id as the TEXT and the text as the chat id ("chat not found") without any
 * error at the call site: both are strings. This reads the request body that would really have been sent.
 *
 * @see TelegramHelper::sendMessage()
 */
#[Test]
final class TelegramHelperSendMessageTest
{
    private const string FAILED = '{"ok":false,"error_code":400,"description":"Bad Request: test"}';

    /**
     * @param Closure(string, array<string, mixed>): void $record called with the URL and the decoded body of every POST
     */
    private function helper(Closure $record): TelegramHelper
    {
        $transport = new class ($record, self::FAILED) implements TransportInterface {
            /** @param Closure(string, array<string, mixed>): void $record */
            public function __construct(private readonly Closure $record, private readonly string $answer)
            {
            }

            #[\Override]
            public function get(string $url): ApiResponse
            {
                return new ApiResponse(400, $this->answer);
            }

            #[\Override]
            public function post(string $url, string $body, array $headers): ApiResponse
            {
                /** @var array<string, mixed> $decoded */
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                ($this->record)($url, $decoded);
                // only the request matters here, so answer with a plain Telegram error
                return new ApiResponse(400, $this->answer);
            }

            #[\Override]
            public function postWithFiles(string $url, array $data, array $files): ApiResponse
            {
                return new ApiResponse(400, $this->answer);
            }

            #[\Override]
            public function downloadFile(string $url): mixed
            {
                throw new LogicException('not used by sendMessage');
            }
        };

        return new TelegramHelper('123:TEST', new NullLogger(), $transport);
    }

    /**
     * @return array{url: string, body: array<string, mixed>} the one request sendMessage() made
     */
    private function sentRequest(string $chatId, string $text): array
    {
        $posted = [];
        $this->helper(static function (string $url, array $body) use (&$posted): void {
            $posted[] = ['url' => $url, 'body' => $body];
        })->sendMessage($chatId, $text);

        Assert::same(count($posted), 1);
        return $posted[0] ?? throw new LogicException('no request was sent');
    }

    public function theChatIdIsSentAsTheChatAndTheTextAsTheText(): void
    {
        $request = $this->sentRequest('987654321', 'Hello World');

        Assert::true(str_ends_with($request['url'], '/sendMessage'));
        Assert::same((string) $request['body']['chat_id'], '987654321');
        Assert::same($request['body']['text'], 'Hello World');
    }

    public function aNumericLookingTextIsNotMistakenForTheChat(): void
    {
        // the case that fails silently: with the arguments swapped, a numeric text becomes a valid chat id
        $request = $this->sentRequest('111', '222');

        Assert::same((string) $request['body']['chat_id'], '111');
        Assert::same($request['body']['text'], '222');
    }
}
