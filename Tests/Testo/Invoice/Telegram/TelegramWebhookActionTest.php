<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Telegram\TelegramController;
use App\Invoice\Telegram\TelegramUpdateHandler;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\DataResponse\DataStream\DataStream;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactory;

/**
 * The webhook action itself: a request that cannot prove it came from Telegram is answered 403 and reaches
 * nothing (no payment, no linking), whether the secret is unset, missing or wrong. The 403, and not a 200,
 * is deliberate: Telegram keeps a real update queued and shows the error in getWebhookInfo, and delivers it
 * once the secret is set and the webhook registered again.
 *
 * @see TelegramController::webhook()
 */
#[Test]
final class TelegramWebhookActionTest
{
    private const string BOT = '123456:ABC-DEF';
    private const string SECRET = 'Xk3-9_pQ7vLm2Wz8RtYh5NcB4aGd1sFe';

    private function controller(string $botToken, string $secret): TelegramController
    {
        $sR = TypedMock::of(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('telegram_token')->andReturn($botToken);
        $sR->shouldReceive('getSetting')->with('telegram_webhook_secret_token')->andReturn($secret);

        $reflection = new ReflectionClass(TelegramController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $properties = [
            'sR' => $sR,
            'logger' => new NullLogger(),
            'factory' => new DataResponseFactory(new HttpFactory()),
        ];
        foreach ($properties as $name => $value) {
            $reflection->getProperty($name)->setValue($controller, $value);
        }
        return $controller;
    }

    /**
     * @param string|null $presentedSecret the secret header to send, null for none
     */
    private function post(TelegramController $controller, ?string $presentedSecret, string $body = ''): ResponseInterface
    {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'https://example.test/telegram/webhook')
            ->withBody($factory->createStream($body));
        if (null !== $presentedSecret) {
            $request = $request->withHeader('X-Telegram-Bot-Api-Secret-Token', $presentedSecret);
        }
        // a handler that must never be reached: a refused request does nothing
        $handler = TypedMock::of(TelegramUpdateHandler::class);
        $handler->shouldReceive('handle')->never();

        return $controller->webhook($request, $handler);
    }

    /**
     * The data the action put in the response. The body is formatted later, by the application's
     * DataResponseMiddleware, so it is read here rather than as a string.
     */
    private function dataOf(ResponseInterface $response): mixed
    {
        $body = $response->getBody();
        return $body instanceof DataStream ? $body->getData() : (string) $body;
    }

    public function withNoSecretConfiguredARequestIsRefusedWith403(): void
    {
        $response = $this->post($this->controller(self::BOT, ''), null);

        Assert::same($response->getStatusCode(), 403);
        Assert::same($this->dataOf($response), '{"fail":"unauthorized"}');
    }

    public function withNoSecretConfiguredEvenAHeaderIsRefused(): void
    {
        // a forged update cannot get in by guessing a header either: with no secret there is nothing to match
        Assert::same($this->post($this->controller(self::BOT, ''), 'anything')->getStatusCode(), 403);
    }

    public function aMissingHeaderIsRefusedWith403(): void
    {
        Assert::same($this->post($this->controller(self::BOT, self::SECRET), null)->getStatusCode(), 403);
    }

    public function aWrongSecretIsRefusedWith403(): void
    {
        Assert::same($this->post($this->controller(self::BOT, self::SECRET), 'wrong')->getStatusCode(), 403);
    }

    public function withoutABotTokenARequestIsRefusedWith403(): void
    {
        Assert::same($this->post($this->controller('', self::SECRET), self::SECRET)->getStatusCode(), 403);
    }

    public function everyRefusalGivesTheSameAnswerSoACallerLearnsNothingAboutTheSetUp(): void
    {
        $bodies = [
            $this->dataOf($this->post($this->controller(self::BOT, ''), null)),
            $this->dataOf($this->post($this->controller(self::BOT, self::SECRET), 'wrong')),
            $this->dataOf($this->post($this->controller('', self::SECRET), self::SECRET)),
        ];

        Assert::same(array_unique($bodies), ['{"fail":"unauthorized"}']);
    }

    public function aRequestWithTheRightSecretIsNotRefused(): void
    {
        // an empty body does not parse as an update, which is answered 200 with the reason: the point is
        // that it got past the gate and was not refused
        $response = $this->post($this->controller(self::BOT, self::SECRET), self::SECRET);

        Assert::same($response->getStatusCode(), 200);
    }
}
