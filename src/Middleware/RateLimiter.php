<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\ClientIpResolver;
use Closure;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;
use Yiisoft\Yii\RateLimiter\Policy\LimitAlways;
use Yiisoft\Yii\RateLimiter\Policy\LimitCallback;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

/**
 * Shared rate-limiter builders for config/common/routes/routes.php, avoiding
 * the repeated global/per-IP limiter closures duplicated across the auth
 * routes (login, forgotpassword, resetpassword, signup, change).
 *
 * Both builders wrap LimitRequestsMiddleware in RateLimitRedirectMiddleware,
 * so a request that actually trips the limit gets redirected back to the
 * page it was for instead of the bare, unstyled 429 LimitRequestsMiddleware
 * (and TooManyRequestsMiddleware, for the CAS-failure case) return on their
 * own — see RateLimitRedirectMiddleware's own docblock.
 */
final class RateLimiter
{
    /**
     * A global path-wide counter, regardless of requester IP.
     */
    public static function global(int $limit, int $window = 60): Closure
    {
        return static fn (ResponseFactoryInterface $responseFactory, StorageInterface $storage) => new RateLimitRedirectMiddleware(
            new LimitRequestsMiddleware(
                new Counter($storage, $limit, $window),
                $responseFactory,
                new LimitAlways(),
                new TooManyRequestsMiddleware($responseFactory),
            ),
            $responseFactory,
        );
    }

    /**
     * A per-real-IP counter, scoped by $salt so different routes don't share a bucket. The IP comes
     * from ClientIpResolver: forwarding headers (CF-Connecting-IP, X-Forwarded-For) are only
     * believed when the connection itself comes from a trusted proxy; otherwise REMOTE_ADDR is
     * used, so a visitor cannot pick their own bucket by sending a header.
     */
    public static function perIp(int $limit, string $salt, int $window = 60): Closure
    {
        return static fn (
            ResponseFactoryInterface $responseFactory,
            StorageInterface $storage,
            ClientIpResolver $clientIp,
        ) => new RateLimitRedirectMiddleware(
            self::perIpInner($limit, $salt, $window, $responseFactory, $storage, $clientIp),
            $responseFactory,
        );
    }

    /**
     * Like global(), but counts every method, GET included, and answers 429 itself rather than
     * redirecting. For links opened from an email (GET) that change state: the redirect wrapper
     * ignores GET, and redirecting a throttled GET back to itself would loop.
     */
    public static function globalAnyMethod(int $limit, int $window = 60): Closure
    {
        return static fn (ResponseFactoryInterface $responseFactory, StorageInterface $storage) => new LimitRequestsMiddleware(
            new Counter($storage, $limit, $window),
            $responseFactory,
            new LimitAlways(),
            new TooManyRequestsMiddleware($responseFactory),
        );
    }

    /** Like perIp(), but counts every method, GET included, and answers 429 itself. See globalAnyMethod(). */
    public static function perIpAnyMethod(int $limit, string $salt, int $window = 60): Closure
    {
        return static fn (
            ResponseFactoryInterface $responseFactory,
            StorageInterface $storage,
            ClientIpResolver $clientIp,
        ) => self::perIpInner($limit, $salt, $window, $responseFactory, $storage, $clientIp);
    }

    private static function perIpInner(
        int $limit,
        string $salt,
        int $window,
        ResponseFactoryInterface $responseFactory,
        StorageInterface $storage,
        ClientIpResolver $clientIp,
    ): LimitRequestsMiddleware {
        return new LimitRequestsMiddleware(
            new Counter($storage, $limit, $window),
            $responseFactory,
            new LimitCallback(static function (ServerRequestInterface $r) use ($salt, $clientIp): string {
                return hash('sha256', $salt . ($clientIp->resolve($r) ?? 'unknown'));
            }),
            new TooManyRequestsMiddleware($responseFactory),
        );
    }
}
