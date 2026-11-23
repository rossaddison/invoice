<?php

declare(strict_types=1);

namespace Tests\Testo\Middleware;

use App\Invoice\Setting\SettingRepository;
use App\Middleware\SessionIdleTimeout;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Http\Status;
use Yiisoft\Session\SessionInterface;

/**
 * A session unused for longer than Settings -> General -> Session Idle Timeout (Minutes) is cleared, so the visitor is
 * signed out; the time of the last request is kept in the session and rewritten at most once a minute.
 */
#[Test]
final class SessionIdleTimeoutTest
{
    private const string COOKIE = 'PHPSESSID';
    private const int ALLOWED_MINUTES = 30;

    /** @var array<string, mixed> what the fake session holds */
    private array $stored = [];
    private int $clears = 0;
    private int $writes = 0;
    private bool $sessionTouched = false;

    /** @param array<string, mixed> $stored */
    private function run(array $stored, bool $withCookie): ResponseInterface
    {
        $this->stored = $stored;
        $this->clears = 0;
        $this->writes = 0;
        $this->sessionTouched = false;

        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('getName')->andReturn(self::COOKIE);
        $session->shouldReceive('get')->andReturnUsing(function (string $key, mixed $default = null): mixed {
            $this->sessionTouched = true;
            return $this->stored[$key] ?? $default;
        });
        $session->shouldReceive('set')->andReturnUsing(function (string $key, mixed $value): void {
            $this->sessionTouched = true;
            ++$this->writes;
            $this->stored[$key] = $value;
        });
        $session->shouldReceive('clear')->andReturnUsing(function (): void {
            $this->sessionTouched = true;
            ++$this->clears;
            $this->stored = [];
        });

        $settings = TypedMock::of(SettingRepository::class);
        $settings->shouldReceive('sessionIdleMinutes')->andReturn(self::ALLOWED_MINUTES);

        $request = new HttpFactory()->createServerRequest('GET', 'https://example.test/invoice/inv');
        if ($withCookie) {
            $request = $request->withCookieParams([self::COOKIE => 'abc']);
        }
        $handler = new class () implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HttpFactory()->createResponse(Status::OK);
            }
        };

        return new SessionIdleTimeout($session, $settings)->process($request, $handler);
    }

    private function lastActivity(): int
    {
        return (int) ($this->stored[SessionIdleTimeout::LAST_ACTIVITY_KEY] ?? 0);
    }

    public function theIdleRuleOnlyFiresPastTheAllowedGap(): void
    {
        Assert::false(SessionIdleTimeout::isIdle(null, 10_000, 1800));
        Assert::false(SessionIdleTimeout::isIdle(10_000 - 1800, 10_000, 1800));
        Assert::true(SessionIdleTimeout::isIdle(10_000 - 1801, 10_000, 1800));
    }

    public function aSessionIdleForLongerThanTheSettingIsCleared(): void
    {
        $response = $this->run(
            ['last_activity' => time() - (self::ALLOWED_MINUTES * 60 + 5), 'identity' => 'signed-in'],
            true,
        );

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->clears, 1);
        Assert::false(array_key_exists('identity', $this->stored));
        // the new, empty session starts counting from now
        Assert::true($this->lastActivity() >= time() - 2);
    }

    public function aSessionWithinTheSettingIsKept(): void
    {
        $this->run(['last_activity' => time() - 600, 'identity' => 'signed-in'], true);

        Assert::same($this->clears, 0);
        Assert::same($this->stored['identity'], 'signed-in');
    }

    public function theTimeIsRefreshedOnlyOnceAMinuteHasPassed(): void
    {
        $this->run(['last_activity' => time() - 10], true);
        Assert::same($this->writes, 0);

        $this->run(['last_activity' => time() - 120], true);
        Assert::same($this->writes, 1);
        Assert::true($this->lastActivity() >= time() - 2);
    }

    public function aSessionWithNoRecordedTimeStartsCounting(): void
    {
        $this->run(['identity' => 'signed-in'], true);

        Assert::same($this->clears, 0);
        Assert::same($this->writes, 1);
    }

    public function aRequestWithNoSessionCookieNeverTouchesTheSession(): void
    {
        $response = $this->run([], false);

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::false($this->sessionTouched);
    }
}
